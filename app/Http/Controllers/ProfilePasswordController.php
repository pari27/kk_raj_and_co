<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfilePasswordController extends Controller
{
    public function edit(): View
    {
        $currentSessionId = session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'is_current_device' => $session->id === $currentSessionId,
                'device' => $this->describeUserAgent($session->user_agent),
                'ip_address' => $session->ip_address,
                'last_active' => Carbon::createFromTimestamp($session->last_activity),
            ]);

        return view('profile.change-password', ['sessions' => $sessions]);
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        Auth::user()->update([
            'password' => $request->validated()['password'],
        ]);

        return back()->with('status', 'Password updated successfully.');
    }

    private function describeUserAgent(?string $userAgent): string
    {
        $userAgent ??= '';

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'An unknown browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'an unknown device',
        };

        return "{$browser} on {$platform}";
    }
}
