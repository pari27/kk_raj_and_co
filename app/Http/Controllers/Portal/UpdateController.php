<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The client's Updates feed. Every query is limited to the signed-in client.
 */
class UpdateController extends Controller
{
    public function index(): View
    {
        $customer = Auth::guard('customer')->user();

        $updates = ClientNotification::query()
            ->where('customer_id', $customer->id)
            ->latest('created_at')->latest('id')
            ->limit(100)
            ->get()
            ->groupBy(function (ClientNotification $update) {
                return $update->created_at->isToday() ? 'Today' : ($update->created_at->isYesterday() ? 'Yesterday' : 'Earlier');
            });

        return view('customer.updates', [
            'groups' => $updates,
            'hasUnread' => $updates->flatten()->contains(fn (ClientNotification $update) => $update->isUnread()),
        ]);
    }

    public function open(ClientNotification $notification): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        // Someone else's update looks like it doesn't exist.
        abort_unless($notification->customer_id === $customer->id, 404);

        if ($notification->isUnread()) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->url ? redirect($notification->url) : redirect()->route('customer.updates');
    }

    public function readAll(): RedirectResponse
    {
        ClientNotification::where('customer_id', Auth::guard('customer')->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
