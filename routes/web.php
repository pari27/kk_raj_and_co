<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeDesignationController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmployeePasswordResetController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\SetEmployeePasswordController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Portal\AuthController as PortalAuthController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\DocumentController as PortalDocumentController;
use App\Http\Controllers\Portal\OtpController as PortalOtpController;
use App\Http\Controllers\Portal\PasswordController as PortalPasswordController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\UpdateController as PortalUpdateController;
use App\Http\Controllers\Portal\ServiceController as PortalServiceController;
use App\Http\Controllers\ProfilePasswordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketDocumentController;
use Illuminate\Support\Facades\Auth;
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

    Route::middleware('role:Admin,Employee')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('search', [SearchController::class, 'index'])->name('search');

        Route::get('clients', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('clients/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('clients', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('clients/search', [CustomerController::class, 'search'])->name('customers.search');
        Route::get('clients/lookup', [CustomerController::class, 'lookupByPhone'])->name('customers.lookup');
        Route::get('clients/check-email', [CustomerController::class, 'checkEmail'])->name('customers.check-email');
        Route::get('clients/check-phone', [CustomerController::class, 'checkPhone'])->name('customers.check-phone');
        Route::post('clients/quick-store', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
        Route::get('clients/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('clients/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('clients/{customer}', [CustomerController::class, 'update'])->name('customers.update');

        Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('enquiries/create', [EnquiryController::class, 'create'])->name('enquiries.create');
        Route::post('enquiries', [EnquiryController::class, 'store'])->name('enquiries.store');
        Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');

        Route::get('services', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('services/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('services/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.update-status');
        Route::patch('services/{ticket}/reassign', [TicketController::class, 'reassign'])->name('tickets.reassign');
        Route::post('services/{ticket}/documents', [TicketDocumentController::class, 'store'])->name('tickets.documents.store');
        Route::patch('services/{ticket}/documents/{document}/verify', [TicketDocumentController::class, 'verify'])->name('tickets.documents.verify');
        Route::patch('services/{ticket}/documents/{document}/reject', [TicketDocumentController::class, 'reject'])->name('tickets.documents.reject');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::put('notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences');
        Route::get('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    // Master Services list: read-only for employees (add/edit/activate stay admin-only below).
    Route::middleware('role:Admin,Employee')->name('admin.')->group(function () {
        Route::get('master-services', [ServiceController::class, 'index'])->name('services.index');

        // Employees see only their own entries (see AuditLogController).
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        Route::get('audit-log/export', [AuditLogController::class, 'export'])->name('audit-log.export');
    });

    // The software team only: who has Admin access to the system.
    Route::middleware('role:SuperAdmin')->name('admin.')->group(function () {
        Route::get('admin-accounts', [AdminAccountController::class, 'index'])->name('admin-accounts.index');
        Route::get('admin-accounts/create', [AdminAccountController::class, 'create'])->name('admin-accounts.create');
        Route::post('admin-accounts', [AdminAccountController::class, 'store'])->name('admin-accounts.store');
        Route::get('admin-accounts/{adminAccount}/edit', [AdminAccountController::class, 'edit'])->name('admin-accounts.edit');
        Route::put('admin-accounts/{adminAccount}', [AdminAccountController::class, 'update'])->name('admin-accounts.update');
        Route::post('admin-accounts/{adminAccount}/resend-invite', [AdminAccountController::class, 'resendInvitation'])->name('admin-accounts.resend-invite');
    });

    // The software team only: system health and recent errors.
    Route::middleware('role:SuperAdmin')->prefix('system')->name('super.')->group(function () {
        Route::get('/', [SystemController::class, 'index'])->name('system');
        Route::get('log', [SystemController::class, 'downloadLog'])->name('system.log');
        Route::post('errors/resolve', [SystemController::class, 'resolve'])->name('system.errors.resolve');
        Route::post('errors/resolve-all', [SystemController::class, 'resolveAll'])->name('system.errors.resolve-all');
        Route::post('errors/reopen', [SystemController::class, 'reopen'])->name('system.errors.reopen');
    });

    Route::middleware('role:Admin')->name('admin.')->group(function () {
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
        Route::get('master-employees/lookup', [EmployeeController::class, 'lookupByMobile'])->name('employees.lookup');
        Route::get('master-employees/check-email', [EmployeeController::class, 'checkEmail'])->name('employees.check-email');
        Route::post('master-employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('master-employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('master-employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('master-employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::patch('master-employees/{employee}/toggle-active', [EmployeeController::class, 'toggleActive'])->name('employees.toggle-active');
        Route::post('master-employees/{employee}/resend-invite', [EmployeeController::class, 'resendInvite'])->name('employees.resend-invite');


        Route::get('settings/firm-profile', [SettingController::class, 'edit'])->name('settings.firm-profile');
        Route::put('settings/firm-profile', [SettingController::class, 'updateFirmProfile'])->name('settings.firm-profile.update');
        Route::get('settings/payment-details', [SettingController::class, 'editPaymentDetails'])->name('settings.payment-details');
        Route::put('settings/payment-details', [SettingController::class, 'updatePaymentDetails'])->name('settings.payment-details.update');
        Route::put('settings/system', [SettingController::class, 'update'])->name('settings.system.update');
        Route::put('settings/numbering', [SettingController::class, 'updateNumbering'])->name('settings.numbering.update');
        Route::get('settings/email-templates', [EmailTemplateController::class, 'index'])->name('settings.email-templates.index');
        Route::get('settings/email-templates/{template}/edit', [EmailTemplateController::class, 'edit'])->name('settings.email-templates.edit');
        Route::put('settings/email-templates/{template}', [EmailTemplateController::class, 'update'])->name('settings.email-templates.update');
    });
});

// Client portal. Clients sign in through their own `customer` guard (separate from
// staff), and every screen below `auth:customer` only ever shows the signed-in
// client's own data.
Route::prefix('client')->name('customer.')->group(function () {
    // /client on its own: signed-in clients go to their dashboard, everyone else to the login.
    Route::get('/', fn () => redirect()->route(Auth::guard('customer')->check() ? 'customer.dashboard' : 'customer.login'))->name('home');

    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [PortalAuthController::class, 'create'])->name('login');
        Route::post('login', [PortalAuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');

        Route::get('verify-otp', [PortalOtpController::class, 'create'])->name('verify-otp');
        Route::post('verify-otp/send', [PortalOtpController::class, 'send'])->middleware('throttle:5,1')->name('otp.send');
        Route::post('verify-otp', [PortalOtpController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify');

        Route::get('set-password', [PortalPasswordController::class, 'create'])->name('set-password');
        Route::post('set-password', [PortalPasswordController::class, 'store'])->middleware('throttle:10,1')->name('set-password.store');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::post('logout', [PortalAuthController::class, 'destroy'])->name('logout');

        Route::get('dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');
        Route::get('services', [PortalServiceController::class, 'index'])->name('tickets.index');

        Route::get('updates', [PortalUpdateController::class, 'index'])->name('updates');
        Route::post('updates/read-all', [PortalUpdateController::class, 'readAll'])->name('updates.read-all');
        Route::get('updates/{notification}', [PortalUpdateController::class, 'open'])->name('updates.open');

        Route::get('profile', [PortalProfileController::class, 'show'])->name('profile');
        Route::put('profile/email-updates', [PortalProfileController::class, 'emailUpdates'])->name('profile.email-updates');
        Route::get('profile/password', [PortalProfileController::class, 'editPassword'])->name('password.edit');
        Route::put('profile/password', [PortalProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');
        Route::get('services/{ticket}', [PortalServiceController::class, 'show'])->name('tickets.show');
        Route::post('services/{ticket}/documents', [PortalDocumentController::class, 'store'])->name('documents.store');
        Route::get('services/{ticket}/documents/{document}/download', [PortalDocumentController::class, 'download'])->name('documents.download');
        Route::get('services/{ticket}/documents/{document}/view', [PortalDocumentController::class, 'view'])->name('documents.view');
        Route::get('payments', [PortalPaymentController::class, 'index'])->name('payments.index');
    });
});
