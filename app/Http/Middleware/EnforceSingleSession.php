<?php

namespace App\Http\Middleware;

use App\Services\SingleSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a session whose account has since been signed in on another device.
 */
class EnforceSingleSession
{
    /** Guard name => the sign-in page to send that kind of person back to. */
    private const GUARDS = [
        'web' => 'login',
        'customer' => 'customer.login',
    ];

    public function __construct(private readonly SingleSession $single) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        foreach (self::GUARDS as $guard => $loginRoute) {
            $auth = Auth::guard($guard);

            if (! $auth->check()) {
                continue;
            }

            $user = $auth->user();

            // Sessions that were open before this feature existed adopt the account the first time they are seen.
            if ($user->login_token === null) {
                $this->single->claim($user, $guard, $request);

                continue;
            }

            if (! $this->single->owns($user, $guard, $request)) {
                return $this->replaced($request, $guard, $loginRoute);
            }

            $this->single->touch($user, $guard, $request);
        }

        return $next($request);
    }

    private function replaced(Request $request, string $guard, string $loginRoute): Response
    {
        $auth = Auth::guard($guard);

        // Forget this one sign-in only. Logging out the normal way would also reset the
        // "remember me" token that the newer device may be using.
        $request->session()->remove($auth->getName());
        $request->session()->remove('login_token_'.$guard);
        $request->session()->remove('login_touched_'.$guard);
        $auth->forgetUser();
        Cookie::queue(Cookie::forget($auth->getRecallerName()));
        $request->session()->regenerate();
        $request->session()->flash('session_notice', SingleSession::REPLACED_MESSAGE);

        if ($request->expectsJson()) {
            return response()->json([
                'replaced' => true,
                'message' => SingleSession::REPLACED_MESSAGE,
                'login_url' => route($loginRoute),
            ], 401);
        }

        return redirect()->route($loginRoute);
    }
}
