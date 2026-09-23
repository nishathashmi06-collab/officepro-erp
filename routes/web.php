<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes (authentication)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated application routes
|--------------------------------------------------------------------------
| Coarse permission checks are applied with the `can:` middleware; record
| level checks (own / team / all) happen in policies inside controllers.
*/
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('search', SearchController::class)->name('search');

    // Own profile & preferences
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::put('profile/preferences', [ProfileController::class, 'preferences'])->name('profile.preferences');

    // Employees
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('employees.deactivate');

    Route::resource('departments', DepartmentController::class)->middleware('can:departments.manage');
    Route::resource('designations', DesignationController::class)->except('show')->middleware('can:designations.manage');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

    // Leave
    Route::resource('leave-types', LeaveTypeController::class)->except('show')->middleware('can:leave_types.manage');
    Route::get('leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('leaves/create', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::get('leaves/{leave}', [LeaveController::class, 'show'])->name('leaves.show');
    Route::post('leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
    Route::post('leaves/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');
    Route::get('leaves/{leave}/attachment', [LeaveController::class, 'attachment'])->name('leaves.attachment');

    // Tasks
    Route::get('tasks/board', [TaskController::class, 'board'])->name('tasks.board');
    Route::resource('tasks', TaskController::class);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('tasks/{task}/comments', [TaskController::class, 'comment'])->name('tasks.comments.store');

    // Payroll
    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/generate', [PayrollController::class, 'create'])->name('payroll.create')->middleware('can:payroll.manage');
    Route::post('payroll/generate', [PayrollController::class, 'store'])->name('payroll.store')->middleware('can:payroll.manage');
    Route::post('payroll/bulk-approve', [PayrollController::class, 'bulkApprove'])->name('payroll.bulk-approve')->middleware('can:payroll.manage');
    Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('payroll/{payroll}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    Route::post('payroll/{payroll}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
    Route::post('payroll/{payroll}/pay', [PayrollController::class, 'markPaid'])->name('payroll.pay');
    Route::get('payroll/{payroll}/slip', [PayrollController::class, 'slip'])->name('payroll.slip');

    // Expenses
    Route::resource('expense-categories', ExpenseCategoryController::class)->except('show')->middleware('can:expenses.manage');
    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
    Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

    // Assets
    Route::middleware('can:assets.view')->group(function () {
        Route::resource('assets', AssetController::class);
        Route::post('assets/{asset}/assign', [AssetController::class, 'assign'])->name('assets.assign');
        Route::post('assets/{asset}/return', [AssetController::class, 'return'])->name('assets.return');
        Route::post('assets/{asset}/maintenance', [AssetController::class, 'storeMaintenance'])->name('assets.maintenance.store');
        Route::post('assets/{asset}/maintenance/{maintenance}/complete', [AssetController::class, 'completeMaintenance'])->name('assets.maintenance.complete');
    });

    // Documents
    Route::resource('documents', DocumentController::class)->except('show');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Reports
    Route::middleware('can:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{type}/export/{format}', [ReportController::class, 'export'])->name('reports.export');
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('activity-logs', ActivityLogController::class)->name('activity-logs.index')->middleware('can:activity_logs.view');

    // Administration
    Route::resource('users', UserController::class)->middleware('can:users.manage');
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('can:users.manage');
    Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password')->middleware('can:users.manage');

    Route::middleware('can:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
