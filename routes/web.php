<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeDesignationController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmployeePasswordResetController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\SetEmployeePasswordController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfilePasswordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [EmployeePasswordResetController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [EmployeePasswordResetController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('employee.password.reset.store');
});

Route::middleware(['guest', 'signed'])->group(function () {
    Route::get('employees/{employee}/set-password', [SetEmployeePasswordController::class, 'show'])->name('employees.set-password');
    Route::post('employees/{employee}/set-password', [SetEmployeePasswordController::class, 'store'])->name('employees.set-password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('password/change', [ProfilePasswordController::class, 'edit'])->name('password.change');
    Route::put('password/change', [ProfilePasswordController::class, 'update'])->name('password.update');

    Route::middleware('role:SuperAdmin,Admin,Employee')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
        Route::get('customers/lookup', [CustomerController::class, 'lookupByPhone'])->name('customers.lookup');
        Route::get('customers/check-email', [CustomerController::class, 'checkEmail'])->name('customers.check-email');
        Route::post('customers/quick-store', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

        Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('enquiries/create', [EnquiryController::class, 'create'])->name('enquiries.create');
        Route::post('enquiries', [EnquiryController::class, 'store'])->name('enquiries.store');
        Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');

        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.update-status');
        Route::patch('tickets/{ticket}/reassign', [TicketController::class, 'reassign'])->name('tickets.reassign');
        Route::post('tickets/{ticket}/documents', [TicketDocumentController::class, 'store'])->name('tickets.documents.store');
        Route::patch('tickets/{ticket}/documents/{document}/verify', [TicketDocumentController::class, 'verify'])->name('tickets.documents.verify');
        Route::patch('tickets/{ticket}/documents/{document}/reject', [TicketDocumentController::class, 'reject'])->name('tickets.documents.reject');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('role:SuperAdmin,Admin')->name('admin.')->group(function () {
        Route::get('master-services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('master-services/create', [ServiceController::class, 'create'])->name('services.create');
        Route::post('master-services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('master-services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('master-services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::put('master-services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::patch('master-services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])->name('services.toggle-active');

        Route::get('master-designations', [EmployeeDesignationController::class, 'index'])->name('designations.index');
        Route::post('master-designations', [EmployeeDesignationController::class, 'store'])->name('designations.store');
        Route::put('master-designations/{designation}', [EmployeeDesignationController::class, 'update'])->name('designations.update');
        Route::patch('master-designations/{designation}/toggle-active', [EmployeeDesignationController::class, 'toggleActive'])->name('designations.toggle-active');
        Route::delete('master-designations/{designation}', [EmployeeDesignationController::class, 'destroy'])->name('designations.destroy');

        Route::get('master-employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('master-employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::get('master-employees/check-email', [EmployeeController::class, 'checkEmail'])->name('employees.check-email');
        Route::post('master-employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('master-employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('master-employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('master-employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('master-employees/{employee}/toggle-active', [EmployeeController::class, 'toggleActive'])->name('employees.toggle-active');
        Route::post('master-employees/{employee}/resend-invite', [EmployeeController::class, 'resendInvite'])->name('employees.resend-invite');

        Route::get('admin-accounts', fn () => view('admin.admin-accounts.index'))->name('admin-accounts.index');
        Route::get('admin-accounts/create', fn () => view('admin.admin-accounts.create'))->name('admin-accounts.create');
        Route::get('admin-accounts/{adminAccount}/edit', fn ($adminAccount) => view('admin.admin-accounts.edit', compact('adminAccount')))->name('admin-accounts.edit');

        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('audit-log/export', [AuditLogController::class, 'export'])->name('audit-log.export');

        Route::get('settings/firm-profile', fn () => view('admin.settings.firm-profile'))->name('settings.firm-profile');
        Route::get('settings/email-templates', fn () => view('admin.settings.email-templates.index'))->name('settings.email-templates.index');
        Route::get('settings/email-templates/{template}/edit', fn ($template) => view('admin.settings.email-templates.edit', compact('template')))->name('settings.email-templates.edit');
    });
});

// Customer portal â€” UI only for now. These will move behind the dedicated `customer`
// guard/middleware once the customer-portal backend module is approved and built.
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('login', fn () => view('customer.auth.login'))->name('login');
    Route::get('verify-otp', fn () => view('customer.auth.verify-otp'))->name('verify-otp');
    Route::get('set-password', fn () => view('customer.auth.set-password'))->name('set-password');
    Route::get('dashboard', fn () => view('customer.dashboard'))->name('dashboard');
    Route::get('tickets', fn () => view('customer.tickets.index'))->name('tickets.index');
    Route::get('tickets/{ticket}', fn ($ticket) => view('customer.tickets.show', compact('ticket')))->name('tickets.show');
});
