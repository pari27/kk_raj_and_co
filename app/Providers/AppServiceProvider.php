<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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
    }

    private function registerAuditLogListeners(): void
    {
        Event::listen(function (Login $event) {
            AuditLogger::log(
                action: 'Logged in',
                module: 'Login',
                recordLabel: $event->user->name ?? $event->user->email,
                userId: $event->user->getAuthIdentifier(),
            );
        });

        Event::listen(function (Failed $event) {
            AuditLogger::log(
                action: 'Failed login',
                module: 'Login',
                recordLabel: $event->credentials['email'] ?? null,
                details: 'Invalid credentials',
                userId: $event->user?->getAuthIdentifier(),
            );
        });

        Event::listen(function (Logout $event) {
            if (! $event->user) {
                return;
            }

            AuditLogger::log(
                action: 'Logged out',
                module: 'Login',
                recordLabel: $event->user->name ?? $event->user->email,
                userId: $event->user->getAuthIdentifier(),
            );
        });
    }
}
