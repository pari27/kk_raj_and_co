<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\Ticket;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()->withCount('documents')->orderBy('name')->get();

        return view('admin.services.index', compact('services'));
    }

    public function show(Service $service): View
    {
        $this->authorize('view', $service);

        $service->load(['documents', 'createdBy', 'activityLogs.user']);

        $fyStart = now()->month >= 4
            ? Carbon::create(now()->year, 4, 1)->startOfDay()
            : Carbon::create(now()->year - 1, 4, 1)->startOfDay();

        $ticketsThisFy = Ticket::query()
            ->where('service_id', $service->id)
            ->where('created_at', '>=', $fyStart)
            ->get(['status', 'total']);

        $closedTickets = $ticketsThisFy->filter(fn (Ticket $ticket) => $ticket->status->isClosed());

        $openTicketsCount = $ticketsThisFy->count() - $closedTickets->count();
        $completedTicketsCount = $closedTickets->count();
        $feesThisFy = (float) $closedTickets->sum('total');

        return view('admin.services.show', compact('service', 'openTicketsCount', 'completedTicketsCount', 'feesThisFy'));
    }

    public function create(): View
    {
        $this->authorize('create', Service::class);

        return view('admin.services.create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $service = Service::create([
            ...$request->safe()->only(['name', 'description', 'default_price', 'gst_percent']),
            'price_includes_gst' => $request->boolean('price_includes_gst'),
            'is_active' => $request->boolean('is_active'),
            'created_by' => Auth::id(),
        ]);

        $this->log($service, 'Created');
        $this->log($service, 'Updated', sprintf(
            'Price set to ₹%s (%s)',
            number_format($service->totalFee(), 0),
            $service->price_includes_gst ? 'GST included' : 'GST excluded'
        ));
        $this->syncDocuments($service, $request->input('documents', []), collect());

        return redirect()
            ->route('admin.services.index')
            ->with('status', "\"{$service->name}\" was created.");
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        $service->load('documents');

        return view('admin.services.edit', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $before = $service->only(['default_price', 'gst_percent']);
        $documentsBefore = $service->documents()->get()->keyBy('name');

        $service->update([
            ...$request->safe()->only(['name', 'description', 'default_price', 'gst_percent']),
            'price_includes_gst' => $request->boolean('price_includes_gst'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->logFieldChanges($service, $before);
        $this->syncDocuments($service, $request->input('documents', []), $documentsBefore);

        return redirect()
            ->route('admin.services.index')
            ->with('status', "\"{$service->name}\" was updated.");
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $service->update(['is_active' => ! $service->is_active]);

        $this->log($service, $service->is_active ? 'Activated' : 'Deactivated');

        return redirect()
            ->route('admin.services.show', $service)
            ->with('status', "\"{$service->name}\" is now ".($service->is_active ? 'active' : 'inactive').'.');
    }

    /**
     * Log price/GST changes made during an update.
     *
     * @param  array<string, mixed>  $before
     */
    private function logFieldChanges(Service $service, array $before): void
    {
        if ((string) $before['default_price'] !== (string) $service->default_price) {
            $this->log($service, 'Updated', sprintf(
                'Price ₹%s → ₹%s',
                number_format((float) $before['default_price'], 2),
                number_format((float) $service->default_price, 2)
            ));
        }

        if ((string) $before['gst_percent'] !== (string) $service->gst_percent) {
            $this->log($service, 'Updated', sprintf(
                'GST %s%% → %s%%',
                rtrim(rtrim((string) $before['gst_percent'], '0'), '.'),
                rtrim(rtrim((string) $service->gst_percent, '0'), '.')
            ));
        }
    }

    /**
     * Replace the service's document rows with whatever the form submitted,
     * discarding any blank rows, and log what changed.
     *
     * @param  array<int, array{name?: string, instructions?: string, allowed_formats?: string, max_file_size_mb?: string, mandatory?: string}>  $documents
     * @param  Collection<string, ServiceDocument>|null  $before
     */
    private function syncDocuments(Service $service, array $documents, ?Collection $before = null): void
    {
        $rows = [];
        $submittedNames = [];

        foreach ($documents as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $submittedNames[] = $name;

            $rows[] = [
                'name' => $name,
                'instructions' => $row['instructions'] ?? null,
                'allowed_formats' => $row['allowed_formats'] ?? null,
                'max_file_size_kb' => ! empty($row['max_file_size_mb']) ? (int) round(((float) $row['max_file_size_mb']) * 1024) : null,
                'is_mandatory' => ! empty($row['mandatory']),
            ];

            if ($before && $before->has($name)) {
                $existing = $before->get($name);
                $wasMandatory = $existing->is_mandatory;
                $isMandatory = ! empty($row['mandatory']);

                if ($wasMandatory !== $isMandatory) {
                    $this->log($service, 'Updated', "Document updated: {$name} (".($isMandatory ? 'mandatory' : 'optional').')');
                }
            } elseif ($before !== null) {
                $this->log($service, 'Updated', "Document added: {$name} (".(! empty($row['mandatory']) ? 'mandatory' : 'optional').')');
            }
        }

        if ($before !== null) {
            foreach ($before as $name => $document) {
                if (! in_array($name, $submittedNames, true)) {
                    $this->log($service, 'Updated', "Document removed: {$name}");
                }
            }
        }

        $service->documents()->delete();

        if ($rows !== []) {
            $service->documents()->createMany($rows);
        }
    }

    private function log(Service $service, string $action, ?string $details = null): void
    {
        AuditLogger::log(
            action: $action,
            module: 'Service',
            recordLabel: $service->name,
            recordUrl: route('admin.services.show', $service),
            details: $details,
            subject: $service,
        );
    }
}
