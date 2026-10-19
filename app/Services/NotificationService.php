<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Active Admin accounts.
     *
     * @return array<int, User>
     */
    public static function admins(): array
    {
        return User::query()
            ->whereIn('role', [UserRole::Admin->value])
            ->where('is_active', true)
            ->get()
            ->all();
    }

    /**
     * Create an in-app notification for each recipient who has this type
     * switched on. The person who caused the event is never notified.
     *
     * @param  iterable<int, User|null>  $recipients
     */
    public static function notify(iterable $recipients, string $type, string $title, string $body, ?string $url = null, ?string $linkText = null, ?string $linkLabel = null): void
    {
        $actorId = Auth::guard('web')->id();
        $seen = [];

        foreach ($recipients as $user) {
            if (! $user || $user->id === $actorId || isset($seen[$user->id]) || ! $user->is_active) {
                continue;
            }
            $seen[$user->id] = true;

            if (! NotificationPreference::forUser($user)->{$type}) {
                continue;
            }

            AppNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link_text' => $linkText,
                'link_label' => $linkLabel,
                'url' => $url,
            ]);
        }
    }
}
