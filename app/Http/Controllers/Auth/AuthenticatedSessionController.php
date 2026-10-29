<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\SingleSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** How long the same person has to sign in again, knowing the other device will be signed out. */
    private const CONFLICT_WINDOW_MINUTES = 5;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $warned = $request->session()->get('login_conflict_web');
        $alreadyWarned = is_array($warned) && ($warned['email'] ?? null) === $email && ($warned['until'] ?? 0) >= now()->timestamp;

        // Already signed in on another device: say so first. Signing in again takes over.
        if (! $alreadyWarned && $request->accountWithOtherSession()) {
            $request->session()->put('login_conflict_web', [
                'email' => $email,
                'until' => now()->addMinutes(self::CONFLICT_WINDOW_MINUTES)->timestamp,
            ]);

            return redirect()->route('login')
                ->withInput($request->only('email', 'remember'))
                ->with('session_conflict', true);
        }

        $request->authenticate();

        $request->session()->forget('login_conflict_web');
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Lets the login page warn, as soon as an email is typed, that the account is signed in elsewhere.
     */
    public function sessionCheck(Request $request): JsonResponse
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        $user = $email === '' ? null : User::query()->where('email', $email)->where('is_active', true)->first();

        $busy = $user !== null && app(SingleSession::class)->hasActiveSession($user);

        // The page is about to show the warning, so signing in next counts as having been warned.
        if ($busy) {
            $request->session()->put('login_conflict_web', [
                'email' => $email,
                'until' => now()->addMinutes(self::CONFLICT_WINDOW_MINUTES)->timestamp,
            ]);
        }

        return response()->json(['busy' => $busy]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}