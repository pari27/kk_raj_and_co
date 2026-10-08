<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->with([
                'tickets:id,customer_id,enquiry_id,status,total',
                'tickets.enquiry:id,total',
                'tickets.enquiry.payments:id,enquiry_id,amount',
            ])
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        $this->log($customer, 'Created');

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "\"{$customer->name}\" was added.");
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $email = trim((string) $request->query('email'));
        $ignoreCustomerId = filter_var($request->query('ignore'), FILTER_VALIDATE_INT);
        $customerQuery = Customer::query()->where('email', $email);

        if ($ignoreCustomerId !== false) {
            $customerQuery->whereKeyNot($ignoreCustomerId);
        }

        return response()->json([
            'available' => $email === '' || ! $customerQuery->exists(),
        ]);
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load(['tickets.service', 'tickets.enquiry.payments', 'tickets.assignedTo', 'createdBy']);

        if (! $customer->createdBy) {
            $createdBy = AuditLog::query()
                ->where('module', 'Customer')
                ->where('action', 'Created')
                ->where('subject_type', Customer::class)
                ->where('subject_id', $customer->getKey())
                ->with('user')
                ->first()?->user;

            $customer->setRelation('createdBy', $createdBy);
        }

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->log($customer, 'Updated');

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "\"{$customer->name}\" was updated.");
    }

    /**
     * Live "search by phone, name or email" used from the Add Enquiry form.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $term = trim((string) $request->query('q'));

        if ($term === '') {
            return response()->json(['customers' => []]);
        }

        $customers = Customer::query()
            ->withCount('tickets')
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email']);

        return response()->json(['customers' => $customers]);
    }

    /**
     * Exact phone-number lookup for the Enquiry create page's client step —
     * tells the form whether this is an existing client (pre-fill, read-only)
     * or a new one (editable fields).
     */
    public function lookupByPhone(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $phone = trim((string) $request->query('phone'));

        if (! preg_match('/^\d{10}$/', $phone)) {
            return response()->json(['customer' => null]);
        }

        $customer = Customer::query()
            ->withCount('tickets')
            ->where('phone', $phone)
            ->first(['id', 'name', 'phone', 'email', 'email_notifications_enabled', 'created_at']);

        if (! $customer) {
            return response()->json(['customer' => null]);
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'email_notifications_enabled' => $customer->email_notifications_enabled,
                'since' => $customer->created_at->format('M Y'),
                'tickets_count' => $customer->tickets_count,
            ],
        ]);
    }

    /**
     * Inline "+ New Client" creation from the Add Enquiry form (AJAX, no navigation).
     */
    public function quickStore(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create([
            ...$request->validated(),
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        $this->log($customer, 'Created');

        return response()->json(['customer' => $customer]);
    }

    private function log(Customer $customer, string $action): void
    {
        AuditLogger::log(
            action: $action,
            module: 'Customer',
            recordLabel: $customer->name,
            recordUrl: route('customers.show', $customer),
            subject: $customer,
        );
    }
}
