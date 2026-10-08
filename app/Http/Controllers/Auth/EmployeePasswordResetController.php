<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmployeePasswordResetRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeePasswordResetController extends Controller
{
    public function create(EmployeePasswordResetRequest $request, string $token): View
    {
        return view('auth.employee-reset-password', [
            'email' => (string) $request->query('email', ''),
            'token' => $token,
        ]);
    }

    public function store(EmployeePasswordResetRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $employee = User::query()
            ->where('email', $credentials['email'])
            ->where('role', UserRole::Employee)
            ->where('is_active', true)
            ->exists();

        if (! $employee) {
            return back()
                ->withInput($request->safe()->only('email'))
                ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        $status = Password::broker()->reset(
            $credentials,
            function (CanResetPassword $user, string $password): void {
                if (! $user instanceof User || ! $user->isEmployee() || ! $user->is_active) {
                    return;
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Your password has been reset. Please log in.');
        }

        return back()
            ->withInput($request->safe()->only('email'))
            ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
    }
}
