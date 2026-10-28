<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Setting::applyFirmProfile();

        // Replies to the app's emails go to this address (set MAIL_REPLY_TO_ADDRESS in .env).
        if (config('mail.reply_to.address')) {
            Mail::alwaysReplyTo(config('mail.reply_to.address'), config('mail.reply_to.name'));
        }

        $this->registerAuditLogListeners();

        Event::listen(function (NotificationSending $event) {
            // No email at all for the software team's Super Admin, and none when notifications are off.
            if ($event->notifiable instanceof \App\Models\User && $event->notifiable->isSuperAdmin()) {
                return false;
            }

            return Setting::notificationsEnabled();
        });
    }

    private function registerAuditLogListeners(): void
    {
        Event::listen(function (Login $event) {
            $isClient = $event->guard === 'customer';

            AuditLogger::log(
                action: 'Logged in',
                module: $isClient ? 'Client Portal' : 'Login',
                recordLabel: $event->user->name ?? $event->user->email,
                details: $isClient ? 'Client signed in to the portal' : null,
                userId: $isClient ? null : $event->user->getAuthIdentifier(),
            );
        });

        Event::listen(function (Failed $event) {
            $isClient = $event->guard === 'customer';

            AuditLogger::log(
                action: 'Failed login',
                module: $isClient ? 'Client Portal' : 'Login',
                recordLabel: $event->credentials['email'] ?? null,
                details: $isClient ? 'Invalid credentials (client portal)' : 'Invalid credentials',
                userId: $isClient ? null : $event->user?->getAuthIdentifier(),
            );
        });

        Event::listen(function (Logout $event) {
            if (! $event->user) {
                return;
            }

            $isClient = $event->guard === 'customer';

            AuditLogger::log(
                action: 'Logged out',
                module: $isClient ? 'Client Portal' : 'Login',
                recordLabel: $event->user->name ?? $event->user->email,
                userId: $isClient ? null : $event->user->getAuthIdentifier(),
            );
        });
    }
}
