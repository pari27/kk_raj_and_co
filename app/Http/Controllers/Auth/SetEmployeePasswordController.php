<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetEmployeePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SetEmployeePasswordController extends Controller
{
    public function show(Request $request, User $employee): View|RedirectResponse
    {
        if ($employee->hasSetPassword()) {
            return redirect()->route('login')->with('status', 'This account already has a password. Please sign in.');
        }

        return view('auth.set-password', [
            'employee' => $employee,
            'formAction' => $request->fullUrl(),
        ]);
    }

    public function store(SetEmployeePasswordRequest $request, User $employee): RedirectResponse
    {
        if ($employee->hasSetPassword()) {
            return redirect()->route('login')->with('status', 'This account already has a password. Please sign in.');
        }

        $employee->password = $request->validated()['password'];
        $employee->email_verified_at = now();
        $employee->save();

        Auth::login($employee);

        return redirect()->route('dashboard')->with('status', 'Your password has been set.');
    }
}
