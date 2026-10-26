<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\EmployeeInvitationMail;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The people who have Admin access. Any Admin can add or change the others.
 */
class AdminAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-accounts.index', [
            'accounts' => User::query()->where('role', UserRole::Admin)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $account = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => UserRole::Admin,
            'is_active' => $request->boolean('is_active'),
            'password' => $validated['password'],
        ]);

        // The password is chosen here, so there is no set-password step: they can sign in straight away.
        $account->forceFill(['email_verified_at' => now()])->save();

        $this->log($account, 'Created', "Admin account created for {$account->email} with a password set by an Admin");

        return redirect()->route('admin.admin-accounts.index')->with('status', "\"{$account->name}\" was added. Give them the password you set and ask them to change it after signing in.");
    }

    public function edit(User $adminAccount): View
    {
        $this->ensureAdmin($adminAccount);

        return view('admin.admin-accounts.edit', ['account' => $adminAccount]);
    }

    public function update(Request $request, User $adminAccount): RedirectResponse
    {
        $this->ensureAdmin($adminAccount);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($adminAccount->id)],
        ]);

        $active = $request->boolean('is_active');

        if (! $active && $adminAccount->is_active) {
            $otherActive = User::query()->where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($adminAccount->id)->exists();

            if (! $otherActive) {
                throw ValidationException::withMessages(['is_active' => 'There must always be at least one active Admin.']);
            }
        }

        $adminAccount->fill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'is_active' => $active,
        ]);

        $changes = collect(['name' => 'name', 'email' => 'email', 'is_active' => 'status'])
            ->filter(fn ($label, $field) => $adminAccount->isDirty($field))
            ->values();

        $adminAccount->save();

        if ($changes->isNotEmpty()) {
            $this->log($adminAccount, 'Updated', 'Admin account changed: '.$changes->implode(', '));
        }

        return redirect()->route('admin.admin-accounts.index');
    }

    public function resendInvitation(User $adminAccount): RedirectResponse
    {
        $this->ensureAdmin($adminAccount);

        if ($adminAccount->hasSetPassword()) {
            return redirect()->route('admin.admin-accounts.index')->with('error', 'This person has already set a password.');
        }

        $sent = $this->sendInvitation($adminAccount);

        if ($sent) {
            $this->log($adminAccount, 'Updated', 'Invitation email sent again');
        }

        return redirect()->route('admin.admin-accounts.index')->with($sent ? 'status' : 'error', $sent
            ? 'The invitation email was sent again.'
            : 'Email notifications are turned off in Settings, so no email was sent.');
    }

    private function ensureAdmin(User $account): void
    {
        abort_unless($account->role === UserRole::Admin, 404);
    }

    private function sendInvitation(User $account): bool
    {
        if (! Setting::notificationsEnabled()) {
            return false;
        }

        $url = URL::temporarySignedRoute('employees.set-password', now()->addDays(7), ['employee' => $account]);
        $recipient = Setting::testModeEnabled() ? config('mail.test_recipient') : $account->email;

        Mail::to($recipient)->send(new EmployeeInvitationMail($account, $url));

        return true;
    }

    private function log(User $account, string $action, string $details): void
    {
        AuditLogger::log(
            action: $action,
            module: 'Admin Account',
            recordLabel: $account->name,
            recordUrl: route('admin.admin-accounts.edit', $account),
            details: $details,
            subject: $account,
        );
    }
}
