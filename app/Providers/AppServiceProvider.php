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

        $this->registerAuditLogListeners();

        Event::listen(function (NotificationSending $event) {
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
