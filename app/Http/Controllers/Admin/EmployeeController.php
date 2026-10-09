<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Mail\EmployeeInvitationMail;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeDesignation;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAnyEmployees', User::class);

        $employees = User::query()
            ->where('role', UserRole::Employee)
            ->with('profile.designation')
            ->latest('created_at')
            ->get();

        $designationsCount = EmployeeDesignation::query()->where('is_active', true)->count();

        return view('admin.employees.index', compact('employees', 'designationsCount'));
    }

    public function create(): View
    {
        $this->authorize('createEmployee', User::class);

        $designations = EmployeeDesignation::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.employees.create', compact('designations'));
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $this->authorize('createEmployee', User::class);

        $email = (string) $request->query('email');

        $taken = $email !== ''
            && User::query()
                ->where('email', $email)
                ->when($request->query('ignore'), fn ($query, $ignore) => $query->whereKeyNot($ignore))
                ->exists();

        return response()->json(['available' => ! $taken]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->authorize('createEmployee', User::class);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('employee-photos', 'public')
            : null;

        $employee = DB::transaction(function () use ($request, $photoPath) {
            $profile = Employee::create([
                ...$request->safe()->only(['mobile', 'gender', 'designation_id']),
                'photo_path' => $photoPath,
            ]);

            return User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'role' => UserRole::Employee,
                'is_active' => $request->boolean('is_active'),
                'password' => Str::random(40),
                'employee_id' => $profile->id,
            ]);
        });

        $this->sendInvitation($employee);

        $this->log($employee, 'Created');

        return redirect()
            ->route('admin.employees.index')
            ->with('status', "\"{$employee->name}\" was added and an invitation email was sent.");
    }

    public function show(User $employee): View
    {
        $this->authorize('viewEmployee', $employee);

        abort_unless($employee->role === UserRole::Employee, 404);

        $employee->load('profile.designation');

        $fyStart = now()->month >= 4
            ? Carbon::create(now()->year, 4, 1)->startOfDay()
            : Carbon::create(now()->year - 1, 4, 1)->startOfDay();

        $tickets = Ticket::query()
            ->where('assigned_to', $employee->id)
            ->with(['service', 'customer', 'enquiry'])
            ->get();

        $openTickets = $tickets->reject(fn (Ticket $ticket) => $ticket->status->isClosed());

        $closedThisFy = $tickets->filter(
            fn (Ticket $ticket) => $ticket->status->isClosed()
                && $ticket->completed_at
                && $ticket->completed_at->greaterThanOrEqualTo($fyStart)
        );

        $openTicketsCount = $openTickets->count();
        $completedTicketsCount = $closedThisFy->count();

        $waitingOnClientsCount = $openTickets->whereIn('status', [
            TicketStatus::DocumentsPending,
            TicketStatus::AdditionalDocumentsRequired,
        ])->count();

        $withEnquiry = $closedThisFy->filter(fn (Ticket $ticket) => $ticket->enquiry);
        $avgTurnaroundDays = $withEnquiry->count()
            ? (int) round($withEnquiry->average(
                fn (Ticket $ticket) => $ticket->enquiry->created_at->diffInDays($ticket->completed_at)
            ))
            : null;

        $openByStatus = $openTickets
            ->groupBy(fn (Ticket $ticket) => $ticket->status->value)
            ->map->count()
            ->sortDesc();

        $ticketsByService = $tickets
            ->filter(fn (Ticket $ticket) => $ticket->created_at->greaterThanOrEqualTo($fyStart))
            ->groupBy(fn (Ticket $ticket) => $ticket->service->name ?? '—')
            ->map->count()
            ->sortDesc();

        $assignedTicketsCount = $tickets->count();

        $recentTickets = $tickets->sortByDesc('created_at')->take(5);

        $activity = AuditLog::query()
            ->where('user_id', $employee->id)
            ->latest('created_at')
            ->take(5)
            ->get();

        $addedBy = AuditLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $employee->id)
            ->where('action', 'Created')
            ->with('user')
            ->oldest('created_at')
            ->first();

        return view('admin.employees.show', compact(
            'employee',
            'openTicketsCount',
            'completedTicketsCount',
            'waitingOnClientsCount',
            'avgTurnaroundDays',
            'openByStatus',
            'ticketsByService',
            'assignedTicketsCount',
            'recentTickets',
            'activity',
            'addedBy',
        ));
    }

    public function edit(User $employee): View
    {
        $this->authorize('updateEmployee', $employee);

        abort_unless($employee->role === UserRole::Employee, 404);

        $employee->load('profile');

        $designations = EmployeeDesignation::query()
            ->where('is_active', true)
            ->orWhere('id', $employee->profile?->designation_id)
            ->orderBy('name')
            ->get();

        return view('admin.employees.edit', compact('employee', 'designations'));
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $this->authorize('updateEmployee', $employee);

        abort_unless($employee->role === UserRole::Employee, 404);

        $profile = $employee->profile;
        $photoPath = $profile?->photo_path;

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('employee-photos', 'public');
        } elseif ($request->boolean('remove_photo') && $photoPath) {
            Storage::disk('public')->delete($photoPath);
            $photoPath = null;
        }

        DB::transaction(function () use ($request, $employee, $profile, $photoPath) {
            $employee->update([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'is_active' => $request->boolean('is_active'),
            ]);

            $profile->update([
                ...$request->safe()->only(['mobile', 'gender', 'designation_id']),
                'photo_path' => $photoPath,
            ]);
        });

        $this->log($employee, 'Updated');

        return redirect()
            ->route('admin.employees.index')
            ->with('status', "\"{$employee->name}\" was updated.");
    }

    public function toggleActive(User $employee): RedirectResponse
    {
        $this->authorize('updateEmployee', $employee);

        abort_unless($employee->role === UserRole::Employee, 404);

        $employee->update(['is_active' => ! $employee->is_active]);

        $this->log($employee, $employee->is_active ? 'Activated' : 'Deactivated');

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('status', "\"{$employee->name}\" is now ".($employee->is_active ? 'active' : 'inactive').'.');
    }

    public function resendInvite(User $employee): RedirectResponse
    {
        $this->authorize('updateEmployee', $employee);

        abort_unless($employee->role === UserRole::Employee, 404);

        if ($employee->hasSetPassword()) {
            return redirect()
                ->route('admin.employees.show', $employee)
                ->with('status', "\"{$employee->name}\" has already set their password.");
        }

        $this->sendInvitation($employee);

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('status', "Invitation email resent to {$employee->email}.");
    }

    private function sendInvitation(User $employee): void
    {
        $setPasswordUrl = URL::temporarySignedRoute(
            'employees.set-password',
            now()->addDays(7),
            ['employee' => $employee]
        );

        $recipient = Setting::testModeEnabled() ? config('mail.test_recipient') : $employee->email;

        Mail::to($recipient)->send(new EmployeeInvitationMail($employee, $setPasswordUrl));
    }

    private function log(User $employee, string $action): void
    {
        AuditLogger::log(
            action: $action,
            module: 'Employee',
            recordLabel: $employee->name,
            recordUrl: route('admin.employees.show', $employee),
            subject: $employee,
        );
    }
}
