<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use App\Services\EmployeeDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * A single dashboard entry point for every internal role — the view
     * rendered depends on whether the user is Admin-like or an Employee.
     */
    public function index(EmployeeDashboardService $employeeDashboard, AdminDashboardService $adminDashboard): View
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            // ADMIN_DASHBOARD_DESIGN=classic brings back the earlier chart-based design.
            if (config('app.admin_dashboard_design') === 'classic') {
                return view('admin.dashboard-classic');
            }

            return view('admin.dashboard', $adminDashboard->dashboardData());
        }

        return view('employee.dashboard', $employeeDashboard->dashboardData($user));
    }
}
