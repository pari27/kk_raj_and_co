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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()->with('documents')->withCount('documents')->latest('created_at')->get();

        $canManage = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();

        return view('admin.services.index', compact('services', 'canManage'));
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

        $this->guardRemovedDocuments($documentsBefore, $request->input('documents', []));

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

            $isText = ($row['input_type'] ?? 'file') === 'text';

            $rows[] = [
                'name' => $name,
                'instructions' => $row['instructions'] ?? null,
                'input_type' => $isText ? 'text' : 'file',
                'allowed_formats' => $isText ? null : 'PDF, JPG, PNG',
                'max_file_size_kb' => $isText ? null : 5120,
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

        // Update documents in place (matched by name) so uploads already made against
        // them keep pointing at the same row; only add new ones and drop removed ones.
        $existing = $service->documents()->get()->keyBy('name');

        foreach ($rows as $row) {
            if ($existing->has($row['name'])) {
                $existing->get($row['name'])->update($row);
            } else {
                $existing->put($row['name'], $service->documents()->create($row));
            }
        }

        $service->documents()->whereNotIn('name', $submittedNames)->delete();
    }

    /**
     * A document that already has uploads or entries on a ticket can't be
     * removed from the service; staff should mark it Optional instead.
     *
     * @param  Collection<string, ServiceDocument>  $before
     * @param  array<int, array<string, mixed>>  $submitted
     */
    private function guardRemovedDocuments(Collection $before, array $submitted): void
    {
        $submittedNames = collect($submitted)
            ->map(fn ($row) => trim((string) ($row['name'] ?? '')))
            ->filter()
            ->all();

        $blocked = $before
            ->filter(fn (ServiceDocument $document, string $name) => ! in_array($name, $submittedNames, true) && $document->ticketDocuments()->exists())
            ->keys();

        if ($blocked->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'Cannot remove '.$blocked->implode(', ').': already used on existing services. Mark it Optional instead.',
            ]);
        }
    }

    private function log(Service $service, string $action, ?string $details = null): void
    {
        AuditLogger::log(
            action: $action,
            module: 'Master Service',
            recordLabel: $service->name,
            recordUrl: route('admin.services.show', $service),
            details: $details,
            subject: $service,
        );
    }
}
