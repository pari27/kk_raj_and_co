<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * The "lock screen" shown after idle minutes: the signed-in person proves it is
 * still them by typing their password. Nothing about the session itself changes.
 */
class LockScreenController extends Controller
{
    /** Staff: Super Admin, Admin and Employee. */
    public function unlock(Request $request): JsonResponse
    {
        return $this->check($request, $request->user()?->password);
    }

    /** Clients on the portal. */
    public function unlockClient(Request $request): JsonResponse
    {
        return $this->check($request, $request->user('customer')?->password);
    }

    private function check(Request $request, ?string $hash): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if ($hash === null || ! Hash::check($request->input('password'), $hash)) {
            return response()->json(['message' => 'That password is not right. Please try again.'], 422);
        }

        return response()->json(['ok' => true]);
    }
}
