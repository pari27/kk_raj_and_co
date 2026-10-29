<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $type = array_key_exists((string) $request->query('type'), AppNotification::TYPES) ? $request->query('type') : null;

        $base = AppNotification::where('user_id', $user->id);

        // Only what has not been read is listed: once a notification is read it leaves the list.
        $notifications = (clone $base)
            ->whereNull('read_at')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()->latest('id')
            ->paginate(10)
            ->withQueryString();

        $unreadByType = (clone $base)->whereNull('read_at')
            ->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        // Not kept by the browser, so coming back to this page (Back button) shows the list as it is now.
        return response()->view('notifications.index', [
            'notifications' => $notifications,
            'type' => $type,
            'unreadCount' => $unreadByType->sum(),
            'unreadByType' => $unreadByType,
            'preferences' => NotificationPreference::forUser($user),
        ], 200, ['Cache-Control' => 'no-store, max-age=0']);
    }

    public function open(AppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === Auth::id(), 404);

        if ($notification->isUnread()) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->url ? redirect($notification->url) : redirect()->route('notifications.index');
    }

    public function markAllRead(): RedirectResponse
    {
        AppNotification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $preferences = NotificationPreference::forUser(Auth::user());

        $values = [];
        foreach (array_keys(NotificationPreference::LABELS) as $key) {
            $values[$key] = $request->boolean($key);
        }
        $preferences->update($values);

        return back();
    }
}
