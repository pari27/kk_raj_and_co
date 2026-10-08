<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        if (! $this->supportsPrivateEmailDelivery()) {
            return back()->withErrors([
                'email' => 'Email delivery is not configured. Ask an administrator to configure SMTP before requesting a reset link.',
            ]);
        }

        $email = $request->validated('email');

        if (User::query()
            ->where('email', $email)
            ->where('role', UserRole::Employee)
            ->where('is_active', true)
            ->exists()) {
            Password::broker()->sendResetLink(['email' => $email]);
        }

        return back()->with(
            'status',
            'If an active employee account exists for that email, check your inbox for further instructions.'
        );
    }

    private function supportsPrivateEmailDelivery(): bool
    {
        $mailer = (string) config('mail.default');
        $mailerConfig = config("mail.mailers.{$mailer}", []);
        $transport = $mailerConfig['transport'] ?? $mailer;

        return ! in_array($transport, ['log', 'array'], true)
            && ! in_array('log', $mailerConfig['mailers'] ?? [], true);
    }
}
