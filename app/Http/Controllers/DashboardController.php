<?php

namespace App\Http\Controllers;

use App\Services\EmployeeDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * A single dashboard entry point for every internal role — the view
     * rendered depends on whether the user is Admin-like or an Employee.
     */
    public function index(EmployeeDashboardService $employeeDashboard): View
    {
        $user = Auth::user();

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return view('admin.dashboard');
        }

        return view('employee.dashboard', $employeeDashboard->dashboardData($user));
    }
}
