<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\UtilitiesController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDashboardController;
use App\Http\Controllers\AttendanceRegularizationController;
use App\Http\Controllers\CompensatoryOffController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseReportController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveReportController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\OrganisationHierarchyController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/employee.php';
require __DIR__.'/self-service.php';

Route::get('/demo', [DemoController::class, 'index'])->name('demo.show');

Route::get('/login', function () {
    return redirect()->route('auth.login');
})->name('login');

// Root route handler - redirects based on user role
Route::get('/', function () {
    if (auth()->check()) {
        if (isSaaSMode() && auth()->user()->hasRole('tenant')) {
            return redirect()->route('multitenancycore.tenant.dashboard');
        }

        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('auth:web')->group(function () {

    Route::get('support', [SupportController::class, 'index'])->name('support.index');

    // Employee search for Select2 AJAX (used in Payroll and other modules)
    Route::get('employees/search', [\App\Http\Controllers\Api\UserController::class, 'searchEmployees'])->name('employees.search');

    // Do not use this route anywhere, this is just to keep the route for backward compatibility. Use employees/search instead.
    Route::get('userssss/select-search', [UserController::class, 'searchActiveUsersForSelect'])->name('users.selectSearch');

    // Search Routes
    Route::get('/getSearchDataAjax', [BaseController::class, 'getSearchDataAjax'])->name('search.Ajax');

    // Addon Routes
    if (config('custom.custom.displayAddon')) {
        Route::get('/addons', [AddonController::class, 'index'])->name('addons.index');
        Route::get('/addons/ajax', [AddonController::class, 'indexAjax'])->name('addons.ajax');
        Route::get('/addons/statistics', [AddonController::class, 'statistics'])->name('addons.statistics');
        Route::get('/addons/{module}', [AddonController::class, 'show'])->name('addons.show');
        Route::post('/addons/{module}/check', [AddonController::class, 'checkDependencies'])->name('addons.check');
        Route::post('/addons/activate', [AddonController::class, 'activate'])->name('module.activate');
        Route::post('/addons/deactivate', [AddonController::class, 'deactivate'])->name('module.deactivate');
        Route::post('/addons/upload', [AddonController::class, 'upload'])->name('module.upload');
        Route::post('/addons/update', [AddonController::class, 'update'])->name('module.update');
        Route::delete('/addons/uninstall', [AddonController::class, 'uninstall'])->name('module.uninstall');
    }

    Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

    Route::middleware('auth')->group(callback: function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // User Status Routes
        Route::prefix('user-statuses')->name('user-statuses.')->group(function () {
            Route::get('/', [UserStatusController::class, 'index'])->name('index');
            Route::get('/me', [UserStatusController::class, 'me'])->name('me');
            Route::get('/options', [UserStatusController::class, 'options'])->name('options');
            Route::get('/statistics', [UserStatusController::class, 'statistics'])->name('statistics');
            Route::post('/bulk', [UserStatusController::class, 'bulk'])->name('bulk');
            Route::post('/bulk-update', [UserStatusController::class, 'bulkUpdate'])->name('bulkUpdate');
            Route::post('/update', [UserStatusController::class, 'update'])->name('update');
            Route::post('/clear', [UserStatusController::class, 'clear'])->name('clear');
            Route::get('/by-status/{status}', [UserStatusController::class, 'usersByStatus'])->name('byStatus');
            Route::get('/{userId}', [UserStatusController::class, 'show'])->name('show');
        });

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::delete('roles/deleteAjax/{id}', [RoleController::class, 'deleteAjax'])->name('roles.deleteAjax');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        // Alias for backward compatibility
        Route::get('/super-admin/dashboard', [DashboardController::class, 'index'])->name('superAdmin.dashboard');
        Route::get('account', [AccountController::class, 'index'])->name('account.index');
        Route::get('account/activeInactiveUserAjax/{id}', [AccountController::class, 'activeInactiveUserAjax'])->name('account.activeInactiveUserAjax');
        Route::get('account/suspendUserAjax/{id}', [AccountController::class, 'suspendUserAjax'])->name('account.suspendUserAjax');
        Route::get('account/deleteUserAjax/{id}', [AccountController::class, 'deleteUserAjax'])->name('account.deleteUserAjax');
        Route::get('account/viewUser/{id}', [AccountController::class, 'viewUser'])->name('account.viewUser');
        Route::get('account/myProfile', [AccountController::class, 'myProfile'])->name('account.myProfile');
        Route::get('account/indexAjax', [AccountController::class, 'userListAjax'])->name('account.userListAjax');
        Route::delete('account/deleteUserAjax/{id}', [AccountController::class, 'deleteUserAjax'])->name('account.deleteUserAjax');
        Route::get('account/getRolesAjax', [AccountController::class, 'getRolesAjax'])->name('account.getRolesAjax');
        Route::get('account/getUsersAjax', [AccountController::class, 'getUsersAjax'])->name('account.getUsersAjax');
        Route::get('account/getUsersByRoleAjax/{role}', [AccountController::class, 'getUsersByRoleAjax'])->name('account.getUsersByRoleAjax');
        Route::post('account/addOrUpdateUserAjax', [AccountController::class, 'addOrUpdateUserAjax'])->name('account.addOrUpdateUserAjax');
        Route::get('account/editUserAjax/{id}', [AccountController::class, 'editUserAjax'])->name('account.editUserAjax');
        Route::post('account/updateUserAjax/{id}', [AccountController::class, 'updateUserAjax'])->name('account.updateUserAjax');
        Route::post('account/updateUserStatusAjax/{id}', [AccountController::class, 'updateUserStatusAjax'])->name('account.updateUserStatusAjax');
        Route::post('account/changeUserStatusAjax/{id}', [AccountController::class, 'changeUserStatusAjax'])->name('account.changeUserStatusAjax');
        Route::post('account/changePassword', [AccountController::class, 'changePassword'])->name('account.changePassword');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/myNotifications', [NotificationController::class, 'myNotifications'])->name('notifications.myNotifications');
        Route::get('notifications/marksAllAsRead', [NotificationController::class, 'markAsRead'])->name('notifications.marksAllAsRead');
        Route::post('notifications/markAsRead/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('notifications/createAjax', [NotificationController::class, 'createAjax'])->name('notifications.createAjax');
        Route::delete('notifications/deleteAjax/{id}', [NotificationController::class, 'deleteAjax'])->name('notifications.deleteAjax');
        Route::post('notifications/saveToken', [NotificationController::class, 'saveToken'])->name('notifications.saveToken');
        Route::post('notifications/markAsReadAjax/{id}', [NotificationController::class, 'markAsReadAjax'])->name('notifications.markAsReadAjax');

        // Audit Logs
        Route::get('auditLogs', [AuditLogController::class, 'index'])->name('auditLogs.index');
        Route::get('auditLogs/show/{id}', [AuditLogController::class, 'show'])->name('auditLogs.show');

        // utilities Route
        Route::get('utilities', [UtilitiesController::class, 'index'])->name('utilities.index');
        Route::post('utilities/createBackup', [UtilitiesController::class, 'createBackup'])->name('utilities.createBackup');
        Route::get('utilities/downloadBackup/{fileName}', [UtilitiesController::class, 'downloadBackup'])->name('utilities.downloadBackup');
        Route::get('utilities/getBackupList', [UtilitiesController::class, 'getBackupListAjax'])->name('utilities.getBackupList');
        Route::delete('utilities/deleteBackup/{file}', [UtilitiesController::class, 'deleteBackup'])->name('utilities.deleteBackup');
        Route::post('utilities/restoreBackup/{fileName}', [UtilitiesController::class, 'restoreBackup'])->name('utilities.restoreBackup');
        Route::post('utilities/clearCache', [UtilitiesController::class, 'clearCache'])->name('utilities.clearCache');
        Route::post('utilities/clearLog', [UtilitiesController::class, 'clearLog'])->name('utilities.clearLog');
    });
});



Route::middleware(['web'])->group(function () {
    Route::get('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/login', [AuthController::class, 'loginPost'])->name('auth.loginPost');
    Route::get('/accessDenied', [BaseController::class, 'accessDenied'])->name('accessDenied');
});

Route::middleware([
    'web',
    // Role middleware temporarily disabled
    // 'role:admin|hr',
])->group(function () {

    Route::middleware('auth')->group(callback: function () {

        // Holidays (HR/Admin Functions)
        Route::prefix('holidays')->name('hrcore.holidays.')->group(function () {
            Route::get('/', [HolidayController::class, 'index'])->name('index');

            // Legacy self-service redirect - redirect to new /my routes
            Route::get('/my-holidays', function () {
                return redirect()->route('hrcore.my.holidays');
            })->name('my-holidays');
            Route::get('/datatable', [HolidayController::class, 'datatable'])->name('datatable');
            Route::get('/create', [HolidayController::class, 'create'])->name('create');
            Route::post('/', [HolidayController::class, 'store'])->name('store');
            Route::get('/{id}', [HolidayController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [HolidayController::class, 'edit'])->name('edit');
            Route::put('/{id}', [HolidayController::class, 'update'])->name('update');
            Route::delete('/{id}', [HolidayController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [HolidayController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Attendance Management (HR/Admin Functions)
        Route::prefix('hrcore/attendance')->name('hrcore.attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::get('/datatable', [AttendanceController::class, 'indexAjax'])->name('datatable');

            // Legacy self-service redirects - redirect to new /my routes
            Route::get('/my-attendance', function () {
                return redirect()->route('hrcore.my.attendance');
            })->name('my-attendance');
            Route::get('/regularization', function () {
                return redirect()->route('hrcore.my.attendance.regularization');
            })->name('regularization');
            Route::get('/reports', function () {
                return redirect()->route('hrcore.my.attendance.reports');
            })->name('reports');
            Route::get('/global-status', [AttendanceController::class, 'getGlobalStatus'])->name('global-status');
            Route::get('/export', [AttendanceController::class, 'export'])->name('export');
            Route::get('/statistics', [AttendanceController::class, 'statistics'])->name('statistics');
            Route::get('/{id}/details', [AttendanceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AttendanceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AttendanceController::class, 'update'])->name('update');

            // Monthly Summary Report
            Route::get('/monthly-summary', [\App\Http\Controllers\AttendanceMonthlySummaryController::class, 'index'])->name('monthly-summary');
            Route::get('/monthly-summary/datatable', [\App\Http\Controllers\AttendanceMonthlySummaryController::class, 'indexAjax'])->name('monthly-summary.datatable');
            Route::get('/monthly-summary/statistics', [\App\Http\Controllers\AttendanceMonthlySummaryController::class, 'statistics'])->name('monthly-summary.statistics');

            // Monthly Calendar View
            Route::get('/monthly-calendar', [AttendanceController::class, 'monthlyCalendar'])->name('monthly-calendar');
            Route::get('/monthly-calendar/data', [AttendanceController::class, 'monthlyCalendarData'])->name('monthly-calendar.data');
            Route::post('/recalculate', [AttendanceController::class, 'recalculateAttendance'])->name('recalculate');

            // Daily Attendance Report
            Route::get('/daily-report', [AttendanceController::class, 'dailyReport'])->name('daily-report');
            Route::get('/daily-report/ajax', [AttendanceController::class, 'dailyReportAjax'])->name('daily-report.ajax');

            // Late Arrivals Report
            Route::get('/late-arrivals', [\App\Http\Controllers\LateArrivalsReportController::class, 'index'])->name('late-arrivals');
            Route::get('/late-arrivals/datatable', [\App\Http\Controllers\LateArrivalsReportController::class, 'indexAjax'])->name('late-arrivals.datatable');
            Route::get('/late-arrivals/statistics', [\App\Http\Controllers\LateArrivalsReportController::class, 'statistics'])->name('late-arrivals.statistics');

            // Department Comparison Report
            Route::get('/department-comparison', [AttendanceController::class, 'departmentComparison'])->name('department-comparison');
            Route::get('/department-comparison-datatable', [AttendanceController::class, 'departmentComparisonAjax'])->name('department-comparison-datatable');
            Route::get('/department-comparison-stats', [AttendanceController::class, 'departmentComparisonStats'])->name('department-comparison-stats');

            // Overtime Report
            Route::get('/overtime-report', [\App\Http\Controllers\AttendanceReportController::class, 'overtimeReport'])->name('overtime-report');
            Route::get('/overtime-report/datatable', [\App\Http\Controllers\AttendanceReportController::class, 'overtimeReportAjax'])->name('overtime-report.datatable');
            Route::get('/overtime-report/statistics', [\App\Http\Controllers\AttendanceReportController::class, 'overtimeStatistics'])->name('overtime-report.statistics');
            Route::post('/overtime-report/{id}/approve', [\App\Http\Controllers\AttendanceReportController::class, 'approveOvertime'])->name('overtime-report.approve');

            // Employee History Report
            Route::get('/employee-history/{userId?}', [AttendanceController::class, 'employeeHistory'])->name('employee-history');
            Route::get('/employee-history-data/{userId}', [AttendanceController::class, 'employeeHistoryData'])->name('employee-history-data');
        });

        // Attendance Regularization (HR/Admin Functions)
        Route::prefix('hrcore/attendance-regularization')->name('hrcore.attendance-regularization.')->group(function () {
            Route::get('/', [AttendanceRegularizationController::class, 'index'])->name('index');
            Route::get('/datatable', [AttendanceRegularizationController::class, 'indexAjax'])->name('datatable');
            Route::get('/statistics', [AttendanceRegularizationController::class, 'statistics'])->name('statistics');
            Route::get('/create', [AttendanceRegularizationController::class, 'create'])->name('create');
            Route::post('/', [AttendanceRegularizationController::class, 'store'])->name('store');
            Route::get('/{id}', [AttendanceRegularizationController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [AttendanceRegularizationController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AttendanceRegularizationController::class, 'update'])->name('update');
            Route::delete('/{id}', [AttendanceRegularizationController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/approve', [AttendanceRegularizationController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [AttendanceRegularizationController::class, 'reject'])->name('reject');
        });

        // Attendance Dashboard
        Route::prefix('hrcore/attendance-dashboard')->name('hrcore.attendance-dashboard.')->group(function () {
            Route::get('/', [AttendanceDashboardController::class, 'index'])->name('index');
            Route::get('/stats', [AttendanceDashboardController::class, 'getStats'])->name('stats');
            Route::get('/team-attendance', [AttendanceDashboardController::class, 'getTeamAttendance'])->name('team-attendance');
            Route::get('/pending-regularizations', [AttendanceDashboardController::class, 'getPendingRegularizations'])->name('pending-regularizations');
            Route::get('/attendance-summary', [AttendanceDashboardController::class, 'getAttendanceSummary'])->name('attendance-summary');
        });

        Route::post('markAsRead', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('notifications/markAsRead/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::get('notifications/marksAllAsRead', [NotificationController::class, 'markAsRead'])->name('notifications.marksAllAsRead');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/myNotifications', [NotificationController::class, 'myNotifications'])->name('notifications.myNotifications');
        Route::get('getNotificationsAjax', [NotificationController::class, 'getNotificationsAjax'])->name('notifications.getNotificationsAjax');

        Route::middleware(['role:admin'])->prefix('settings/')->name('settings.')->group(function () {
            Route::get('', [SettingsController::class, 'index'])->name('index');
            Route::post('updateGeneralSettings', [SettingsController::class, 'updateGeneralSettings'])->name('updateGeneralSettings');
            Route::post('updateBrandingSettings', [SettingsController::class, 'updateBrandingSettings'])->name('updateBrandingSettings');
            Route::post('updateCompanySettings', [SettingsController::class, 'updateCompanySettings'])->name('updateCompanySettings');
            Route::post('updateMapSettings', [SettingsController::class, 'updateMapSettings'])->name('updateMapSettings');
            Route::post('updateEmployeeSettings', [SettingsController::class, 'updateEmployeeSettings'])->name('updateEmployeeSettings');
            Route::post('updateMailSettings', [SettingsController::class, 'updateMailSettings'])->name('updateMailSettings');
            Route::post('sendTestEmail', [SettingsController::class, 'sendTestEmail'])->name('sendTestEmail');
        });

        Route::middleware(['role:admin'])->get('system-status', [SystemStatusController::class, 'index'])->name('system-status.index');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('report/getAttendanceReport', [ReportController::class, 'getAttendanceReport'])->name('report.getAttendanceReport');
        Route::post('report/getVisitReport', [ReportController::class, 'getVisitReport'])->name('report.getVisitReport');
        Route::post('report/getExpenseReport', [ReportController::class, 'getExpenseReport'])->name('report.getExpenseReport');
        Route::post('reports/getProductOrderReport', [ReportController::class, 'getProductOrderReport'])->name('report.getProductOrderReport');

        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');

        Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');

        Route::prefix('attendance/')->name('attendance.')->group(function () {
            Route::get('', [AttendanceController::class, 'index'])->name('index');
            Route::get('indexAjax', [AttendanceController::class, 'indexAjax'])->name('indexAjax');
        });

        Route::get('visits', [VisitController::class, 'index'])->name('visits.index');

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

        Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles/addOrUpdateAjax', [RoleController::class, 'addOrUpdateAjax'])->name('roles.addOrUpdateAjax');
        Route::delete('roles/deleteAjax/{id}', [RoleController::class, 'deleteAjax'])->name('roles.deleteAjax');

        // Search Routes
        Route::get('/getSearchDataAjax', [BaseController::class, 'getSearchDataAjax'])->name('search.Ajax');

        Route::prefix('hrcore/employees/')->name('hrcore.employees.')->group(function () {
            Route::get('search', [EmployeeController::class, 'search'])->name('search');
        });

        Route::prefix('employees/')->name('employees.')->group(function () {
            Route::get('', [EmployeeController::class, 'index'])->name('index');
            Route::get('search', [EmployeeController::class, 'search'])->name('search');
            Route::get('view/{id}', [EmployeeController::class, 'show'])->name('show');
            Route::post('indexAjax', [EmployeeController::class, 'userListAjax'])->name('indexAjax');
            Route::get('create', [EmployeeController::class, 'create'])->name('create');
            Route::post('store', [EmployeeController::class, 'store'])->name('store');

            // Validation endpoints for employee builder
            Route::post('validate/email', [EmployeeController::class, 'checkEmailValidationAjax'])->name('validate.email');
            Route::post('validate/phone', [EmployeeController::class, 'checkPhoneValidationAjax'])->name('validate.phone');
            Route::post('validate/code', [EmployeeController::class, 'checkEmployeeCodeValidationAjax'])->name('validate.code');

            Route::delete('deleteEmployeeAjax/{id}', [EmployeeController::class, 'deleteEmployeeAjax'])->name('deleteEmployeeAjax');

            Route::post('changeEmployeeProfilePicture', [EmployeeController::class, 'changeEmployeeProfilePicture'])->name('changeEmployeeProfilePicture');
            Route::post('addOrUpdateBankAccount', [EmployeeController::class, 'addOrUpdateBankAccount'])->name('addOrUpdateBankAccount');
            Route::post('addOrUpdateDocument', [EmployeeController::class, 'addOrUpdateDocument'])->name('addOrUpdateDocument');
            Route::get('getUserDocumentsAjax/{userId}', [EmployeeController::class, 'getUserDocumentsAjax'])->name('getUserDocumentsAjax');
            Route::get('downloadUserDocument/{userDocumentId}', [EmployeeController::class, 'downloadUserDocument'])->name('downloadUserDocument');
            Route::post('updateBasicInfo', [EmployeeController::class, 'updateBasicInfo'])->name('updateBasicInfo');
            Route::post('addOrUpdateBankAccount', [EmployeeController::class, 'addOrUpdateBankAccount'])->name('addOrUpdateBankAccount');
            Route::post('updateWorkInformation', [EmployeeController::class, 'updateWorkInformation'])->name('updateWorkInformation');
            Route::post('updateEmergencyContactInfo', [EmployeeController::class, 'updateEmergencyContactInfo'])->name('updateEmergencyContactInfo');

            Route::get('getReportingToUsersAjax', [EmployeeController::class, 'getReportingToUsersAjax'])->name('getReportingToUsersAjax');
            Route::get('getEmployeeTimelineAjax/{userId}', [EmployeeController::class, 'getEmployeeTimelineAjax'])->name('timeline');
            Route::post('removeDevice', [EmployeeController::class, 'removeDevice'])->name('removeDevice');

            Route::post('toggleStatus/{id}', [EmployeeController::class, 'toggleStatus'])->name('employees.toggleStatus');
            Route::post('relieve/{id}', [EmployeeController::class, 'relieveEmployee'])->name('employees.relieve');

            Route::post('/{user}/terminate', [EmployeeController::class, 'initiateTermination'])->name('terminate');
            Route::post('/{user}/startOnboarding', [EmployeeController::class, 'startOnboarding'])->name('startOnboarding');
            Route::post('/{user}/confirmProbation', [EmployeeController::class, 'confirmProbation'])->name('confirmProbation');
            Route::post('/{user}/extendProbation', [EmployeeController::class, 'extendProbation'])->name('extendProbation');
            Route::post('/{user}/failProbation', [EmployeeController::class, 'failProbation'])->name('failProbation');
            Route::post('/{user}/suspend', [EmployeeController::class, 'suspendEmployee'])->name('suspend');
            Route::post('/{user}/reactivate', [EmployeeController::class, 'reactivateEmployee'])->name('reactivate');
            Route::post('/{user}/mark-relieved', [EmployeeController::class, 'markAsRelieved'])->name('markRelieved');
            Route::post('/{user}/mark-inactive', [EmployeeController::class, 'markAsInactive'])->name('markInactive');
            Route::post('/{user}/retire', [EmployeeController::class, 'retireEmployee'])->name('retire');

            // Employee Tab AJAX Routes
            Route::get('/{user}/tab/overview', [EmployeeController::class, 'overviewTab'])->name('overview');
            Route::get('/{user}/tab/documents', [EmployeeController::class, 'documentsTab'])->name('documents');
            Route::get('/{user}/tab/attendance', [EmployeeController::class, 'attendanceTab'])->name('attendance');
            Route::get('/{user}/tab/leave', [EmployeeController::class, 'leaveTab'])->name('leave');
            Route::get('/{user}/tab/performance', [EmployeeController::class, 'performanceTab'])->name('performance');
            Route::get('/{user}/tab/assets', [EmployeeController::class, 'assetsTab'])->name('assets');
            Route::get('/{user}/tab/loans', [EmployeeController::class, 'loansTab'])->name('loans');
            Route::get('/{user}/tab/disciplinary', [EmployeeController::class, 'disciplinaryTab'])->name('disciplinary');
            Route::get('/{user}/tab/timeline', [EmployeeController::class, 'timelineTab'])->name('timeline');

            // Employee Reports
            Route::get('reports/headcount', [EmployeeReportController::class, 'headcount'])->name('reports.headcount');
            Route::get('reports/headcount/data', [EmployeeReportController::class, 'headcountData'])->name('reports.headcount.data');
            Route::get('reports/headcount/export', [EmployeeReportController::class, 'exportHeadcount'])->name('reports.headcount.export');

            Route::get('reports/demographics', [EmployeeReportController::class, 'demographics'])->name('reports.demographics');
            Route::get('reports/demographics/data', [EmployeeReportController::class, 'demographicsData'])->name('reports.demographics.data');

            Route::get('reports/turnover', [EmployeeReportController::class, 'turnover'])->name('reports.turnover');
            Route::get('reports/turnover/data', [EmployeeReportController::class, 'turnoverData'])->name('reports.turnover.data');
            Route::get('reports/turnover/records', [EmployeeReportController::class, 'turnoverRecordsAjax'])->name('reports.turnover.records');
            Route::get('reports/turnover/export', [EmployeeReportController::class, 'exportTurnover'])->name('reports.turnover.export');

            Route::get('reports/tenure', [EmployeeReportController::class, 'tenure'])->name('reports.tenure');
            Route::get('reports/tenure/data', [EmployeeReportController::class, 'tenureData'])->name('reports.tenure.data');
            Route::get('reports/tenure/export', [EmployeeReportController::class, 'exportTenure'])->name('reports.tenure.export');

            Route::get('reports/probation-analysis', [EmployeeReportController::class, 'probationAnalysis'])->name('reports.probation-analysis');
            Route::get('reports/probation-analysis/data', [EmployeeReportController::class, 'probationAnalysisData'])->name('reports.probation-analysis.data');
            Route::get('reports/probation-analysis/export', [EmployeeReportController::class, 'exportProbationAnalysis'])->name('reports.probation-analysis.export');
            Route::get('reports/current-probation/data', [EmployeeReportController::class, 'currentProbationData'])->name('reports.current-probation.data');
            Route::get('reports/upcoming-probation/data', [EmployeeReportController::class, 'upcomingProbationData'])->name('reports.upcoming-probation.data');

            Route::get('reports/lifecycle-events', [EmployeeReportController::class, 'lifecycleEvents'])->name('reports.lifecycle-events');
            Route::get('reports/lifecycle-events/data', [EmployeeReportController::class, 'lifecycleEventsData'])->name('reports.lifecycle-events.data');
            Route::get('reports/lifecycle-events/export', [EmployeeReportController::class, 'exportLifecycleEvents'])->name('reports.lifecycle-events.export');
            Route::get('reports/lifecycle-event-statistics', [EmployeeReportController::class, 'lifecycleEventStatistics'])->name('reports.lifecycle-event-statistics');
        });

        Route::prefix('account/')->name('account.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('activeInactiveUserAjax/{id}', [AccountController::class, 'activeInactiveUserAjax'])->name('activeInactiveUserAjax');
            Route::get('suspendUserAjax/{id}', [AccountController::class, 'suspendUserAjax'])->name('suspendUserAjax');
            Route::get('deleteUserAjax/{id}', [AccountController::class, 'deleteUserAjax'])->name('deleteUserAjax');
            Route::get('viewUser/{id}', [AccountController::class, 'viewUser'])->name('viewUser');
            Route::get('indexAjax', [AccountController::class, 'userListAjax'])->name('userListAjax');
            Route::delete('deleteUserAjax/{id}', [AccountController::class, 'deleteUserAjax'])->name('deleteUserAjax');
            Route::get('getRolesAjax', [AccountController::class, 'getRolesAjax'])->name('getRolesAjax');
            Route::get('getUsersAjax', [AccountController::class, 'getUsersAjax'])->name('getUsersAjax');
            Route::get('getUsersByRoleAjax/{role}', [AccountController::class, 'getUsersByRoleAjax'])->name('getUsersByRoleAjax');
            Route::post('addOrUpdateUserAjax', [AccountController::class, 'addOrUpdateUserAjax'])->name('addOrUpdateUserAjax');
            Route::get('editUserAjax/{id}', [AccountController::class, 'editUserAjax'])->name('editUserAjax');
            Route::post('updateUserAjax/{id}', [AccountController::class, 'updateUserAjax'])->name('updateUserAjax');
            Route::post('updateUserStatusAjax/{id}', [AccountController::class, 'updateUserStatusAjax'])->name('updateUserStatusAjax');
            Route::post('changeUserStatusAjax/{id}', [AccountController::class, 'changeUserStatusAjax'])->name('changeUserStatusAjax');
            Route::post('changePassword', [AccountController::class, 'changePassword'])->name('changePassword');
        });

        // Audit Logs
        Route::prefix('auditLogs/')->name('auditLogs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('show/{id}', [AuditLogController::class, 'show'])->name('show');
        });

        // Leave Types
        Route::prefix('hrcore/leave-types')->name('hrcore.leave-types.')->group(function () {
            Route::get('/', [LeaveTypeController::class, 'index'])->name('index');
            Route::get('/datatable', [LeaveTypeController::class, 'indexAjax'])->name('datatable');
            Route::get('/check-code', [LeaveTypeController::class, 'checkCodeValidationAjax'])->name('check-code');
            Route::get('/create', [LeaveTypeController::class, 'create'])->name('create');
            Route::post('/', [LeaveTypeController::class, 'store'])->name('store');
            Route::get('/{id}', [LeaveTypeController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [LeaveTypeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [LeaveTypeController::class, 'update'])->name('update');
            Route::delete('/{id}', [LeaveTypeController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [LeaveTypeController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Leave Management (HR/Admin Functions)
        Route::prefix('hrcore/leaves')->name('hrcore.leaves.')->group(function () {
            Route::get('/', [LeaveController::class, 'index'])->name('index');
            Route::get('/datatable', [LeaveController::class, 'indexAjax'])->name('datatable');
            Route::get('/create', [LeaveController::class, 'create'])->name('create');

            // Legacy self-service redirects - redirect to new /my routes
            Route::get('/apply', function () {
                return redirect()->route('hrcore.my.leaves.apply');
            })->name('apply');
            Route::get('/balance', function () {
                return redirect()->route('hrcore.my.leaves.balance');
            })->name('balance');
            Route::get('/balance/{leaveTypeId}', [LeaveController::class, 'getLeaveBalanceForType'])->name('balance.type');
            Route::get('/team', [LeaveController::class, 'teamCalendar'])->name('team');
            Route::post('/', [LeaveController::class, 'store'])->name('store');
            Route::get('/{id}', [LeaveController::class, 'showPage'])->name('show');
            Route::get('/{id}/edit', [LeaveController::class, 'edit'])->name('edit');
            Route::put('/{id}', [LeaveController::class, 'update'])->name('update');
            Route::delete('/{id}', [LeaveController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/action', [LeaveController::class, 'actionAjax'])->name('action');
            Route::post('/{id}/approve', [LeaveController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [LeaveController::class, 'reject'])->name('reject');
            Route::post('/{id}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
        });

        // Leave Balance Management
        Route::prefix('hrcore/leave-balance')->name('hrcore.leave-balance.')->group(function () {
            Route::get('/', [LeaveBalanceController::class, 'index'])->name('index');
            Route::get('/datatable', [LeaveBalanceController::class, 'indexAjax'])->name('datatable');
            Route::get('/summary', [LeaveBalanceController::class, 'getBalanceSummary'])->name('summary');
            Route::get('/{employeeId}', [LeaveBalanceController::class, 'show'])->name('show');
            Route::post('/set-initial', [LeaveBalanceController::class, 'setInitialBalance'])->name('set-initial');
            Route::post('/adjust', [LeaveBalanceController::class, 'adjustBalance'])->name('adjust');
            Route::post('/bulk-set', [LeaveBalanceController::class, 'bulkSetInitialBalance'])->name('bulk-set');
        });

        // Leave Reports
        Route::prefix('hrcore/leave-reports')->name('hrcore.leave-reports.')->group(function () {
            Route::get('/dashboard', [LeaveReportController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/data', [LeaveReportController::class, 'getDashboardData'])->name('dashboard.data');

            Route::get('/balance', [LeaveReportController::class, 'balanceReport'])->name('balance');
            Route::get('/balance/data', [LeaveReportController::class, 'balanceReportData'])->name('balance.data');
            Route::get('/balance/statistics', [LeaveReportController::class, 'balanceStatistics'])->name('balance.statistics');
            Route::get('/balance/details/{user}', [LeaveReportController::class, 'balanceDetails'])->name('balance.details');
            Route::get('/balance/export', [LeaveReportController::class, 'exportBalance'])->name('balance.export');

            Route::get('/history', [LeaveReportController::class, 'historyReport'])->name('history');
            Route::get('/history/data', [LeaveReportController::class, 'historyReportData'])->name('history.data');
            Route::get('/history/statistics', [LeaveReportController::class, 'historyStatistics'])->name('history.statistics');
            Route::get('/history/export', [LeaveReportController::class, 'exportHistory'])->name('history.export');

            Route::get('/department', [LeaveReportController::class, 'departmentReport'])->name('department');
            Route::get('/department/data', [LeaveReportController::class, 'departmentReportData'])->name('department.data');
            Route::get('/department/chart', [LeaveReportController::class, 'departmentChartData'])->name('department.chart');

            Route::get('/compliance', [LeaveReportController::class, 'complianceReport'])->name('compliance');
            Route::get('/compliance/statistics', [LeaveReportController::class, 'complianceStatistics'])->name('compliance.statistics');
            Route::get('/compliance/expiring', [LeaveReportController::class, 'complianceExpiringData'])->name('compliance.expiring');
            Route::get('/compliance/encashment', [LeaveReportController::class, 'complianceEncashmentData'])->name('compliance.encashment');
            Route::get('/compliance/alerts', [LeaveReportController::class, 'complianceAlertsData'])->name('compliance.alerts');
        });

        // Expense Types
        Route::prefix('expenseTypes/')->name('expenseTypes.')->group(function () {
            Route::get('/', [ExpenseTypeController::class, 'index'])->name('index');
            Route::get('getExpenseTypesListAjax', [ExpenseTypeController::class, 'getExpenseTypesListAjax'])->name('getExpenseTypesListAjax');
            Route::post('addOrUpdateExpenseTypeAjax', [ExpenseTypeController::class, 'addOrUpdateExpenseTypeAjax'])->name('addOrUpdateAjax');
            Route::get('getExpenseTypeAjax/{id}', [ExpenseTypeController::class, 'getExpenseTypeAjax'])->name('getExpenseTypeAjax');
            Route::delete('deleteExpenseTypeAjax/{id}', [ExpenseTypeController::class, 'deleteExpenseTypeAjax'])->name('deleteExpenseTypeAjax');
            Route::post('changeStatus/{id}', [ExpenseTypeController::class, 'changeStatus'])->name('changeStatus');
            Route::get('getCodeAjax', [ExpenseTypeController::class, 'getCodeAjax'])->name('getCodeAjax');
            Route::get('view/{id}', [ExpenseTypeController::class, 'view'])->name('view');
            Route::post('addOrUpdateRule', [ExpenseTypeController::class, 'addOrUpdateRule'])->name('addOrUpdateRule');
            Route::delete('deleteRule/{id}', [ExpenseTypeController::class, 'deleteRule'])->name('deleteRule');
            Route::get('checkCodeValidationAjax', [ExpenseTypeController::class, 'checkCodeValidationAjax'])->name('checkCodeValidationAjax');
        });

        // Teams
        Route::prefix('teams/')->name('teams.')->group(function () {
            Route::get('', [TeamController::class, 'index'])->name('index');
            Route::get('getTeamsListAjax', [TeamController::class, 'getTeamsListAjax'])->name('getTeamsListAjax');
            Route::post('addOrUpdateTeamAjax', [TeamController::class, 'addOrUpdateTeamAjax'])->name('addOrUpdateTeamAjax');
            Route::get('getTeamAjax/{id}', [TeamController::class, 'getTeamAjax'])->name('getTeamAjax');
            Route::delete('deleteTeamAjax/{id}', [TeamController::class, 'deleteTeamAjax'])->name('deleteTeamAjax');
            Route::post('changeStatus/{id}', [TeamController::class, 'changeStatus'])->name('changeStatus');
            Route::get('getCodeAjax', [TeamController::class, 'getCodeAjax'])->name('getCodeAjax');
            Route::get('getTeamListAjax', [TeamController::class, 'getTeamListAjax'])->name('getTeamListAjax');
            Route::get('checkCodeValidationAjax', [TeamController::class, 'checkCodeValidationAjax'])->name('checkCodeValidationAjax');
        });

        // Shifts
        Route::prefix('shifts')->name('shifts.')->group(function () {
            Route::get('/', [ShiftController::class, 'index'])->name('index');
            Route::get('/datatable', [ShiftController::class, 'indexAjax'])->name('indexAjax');
            Route::post('/', [ShiftController::class, 'store'])->name('store');
            Route::get('/{shift}/edit', [ShiftController::class, 'edit'])->name('edit');
            Route::put('/{shift}', [ShiftController::class, 'update'])->name('update');
            Route::delete('/{shift}', [ShiftController::class, 'destroy'])->name('destroy');
            Route::post('/{shift}/toggle-status', [ShiftController::class, 'toggleStatus'])->name('toggleStatus');
            Route::get('/getActiveShiftsForDropdown', [ShiftController::class, 'getActiveShiftsForDropdown'])->name('getActiveShiftsForDropdown');
        });

        // Visits
        Route::group(['prefix' => 'visits'], function () {
            Route::get('/', [VisitController::class, 'index'])->name('visits.index');
            Route::get('/getListAjax', [VisitController::class, 'getListAjax'])->name('visits.getListAjax');
            Route::delete('/deleteVisitAjax/{id}', [VisitController::class, 'deleteVisitAjax'])->name('visits.deleteVisitAjax');
            Route::get('/getByIdAjax/{id}', [VisitController::class, 'getByIdAjax'])->name('visits.getByIdAjax');
        });

        // Leave Requests
        Route::group(['prefix' => 'leaveRequests'], function () {
            Route::get('/', [LeaveController::class, 'index'])->name('leaveRequests.index');
            Route::get('/getListAjax', [LeaveController::class, 'getListAjax'])->name('leaveRequests.getListAjax');
            Route::post('/actionAjax', [LeaveController::class, 'actionAjax'])->name('leaveRequests.actionAjax');
            Route::get('/getByIdAjax/{id}', [LeaveController::class, 'getByIdAjax'])->name('leaveRequests.getByIdAjax');
        });

        // Employees
        Route::get('employee/getGeofenceGroups', [EmployeeController::class, 'getGeofenceGroups'])->name('employee.getGeofenceGroups');
        Route::get('employee/getIpGroups', [EmployeeController::class, 'getIpGroups'])->name('employee.getIpGroups');
        Route::get('employee/getQrGroups', [EmployeeController::class, 'getQrGroups'])->name('employee.getQrGroups');
        Route::get('employee/getSites', [EmployeeController::class, 'getSites'])->name('employee.getSites');
        Route::get('employee/getDynamicQrDevices', [EmployeeController::class, 'getDynamicQrDevices'])->name('employee.getDynamicQrDevices');

        Route::get('employee/myProfile', [EmployeeController::class, 'myProfile'])->name('employee.myProfile');
    });

    // Expense Requests
    Route::group(['prefix' => 'expenseRequests'], function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('expenseRequests.index');
        Route::get('/indexAjax', [ExpenseController::class, 'indexAjax'])->name('expenseRequests.indexAjax');
        Route::get('/getByIdAjax/{id}', [ExpenseController::class, 'getByIdAjax'])->name('expenseRequests.getByIdAjax');
        Route::post('/actionAjax', [ExpenseController::class, 'actionAjax'])->name('expenseRequests.actionAjax');
    });

    // Departments
    Route::group(['prefix' => 'departments'], function () {
        Route::get('/', [DepartmentsController::class, 'index'])->name('departments.index');
        Route::get('/indexAjax', [DepartmentsController::class, 'indexAjax'])->name('departments.indexAjax');
        Route::post('/addOrUpdateDepartmentAjax', [DepartmentsController::class, 'addOrUpdateDepartmentAjax'])->name('departments.addOrUpdateDepartmentAjax');
        Route::get('/getListAjax', [DepartmentsController::class, 'getListAjax'])->name('departments.getListAjax');
        Route::get('/getParentDepartments', [DepartmentsController::class, 'getParentDepartments'])->name('departments.getParentDepartments');
        Route::get('/getDepartmentAjax/{id}', [DepartmentsController::class, 'getDepartmentAjax'])->name('departments.getDepartmentAjax');
        Route::delete('/deleteAjax/{id}', [DepartmentsController::class, 'deleteAjax'])->name('departments.deleteAjax');
        Route::post('/changeStatus/{id}', [DepartmentsController::class, 'changeStatus'])->name('departments.changeStatus');
    });

    // Compensatory Offs
    Route::prefix('hrcore/compensatory-offs')->name('hrcore.compensatory-offs.')->group(function () {
        Route::get('/', [CompensatoryOffController::class, 'index'])->name('index');
        Route::get('/datatable', [CompensatoryOffController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [CompensatoryOffController::class, 'create'])->name('create');
        Route::post('/', [CompensatoryOffController::class, 'store'])->name('store');
        Route::get('/statistics', [CompensatoryOffController::class, 'statistics'])->name('statistics');
        Route::get('/{id}', [CompensatoryOffController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CompensatoryOffController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CompensatoryOffController::class, 'update'])->name('update');
        Route::delete('/{id}', [CompensatoryOffController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [CompensatoryOffController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [CompensatoryOffController::class, 'reject'])->name('reject');
    });

    // Expense Management (Standardized Routes)
    Route::prefix('hrcore/expenses')->name('hrcore.expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/datatable', [ExpenseController::class, 'indexAjax'])->name('datatable');
        Route::get('/{id}', [ExpenseController::class, 'getByIdAjax'])->name('show');
        Route::post('/{id}/action', [ExpenseController::class, 'actionAjax'])->name('action');
    });

    // Expense Types (Standardized Routes)
    Route::prefix('hrcore/expense-types')->name('hrcore.expense-types.')->group(function () {
        Route::get('/', [ExpenseTypeController::class, 'index'])->name('index');
        Route::get('/datatable', [ExpenseTypeController::class, 'datatable'])->name('datatable');
        Route::post('/', [ExpenseTypeController::class, 'store'])->name('store');
        Route::put('/{id}', [ExpenseTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExpenseTypeController::class, 'destroy'])->name('destroy');
    });

    // Expense Reports
    Route::prefix('expenses/reports')->name('expenses.')->group(function () {
        Route::get('/summary', [ExpenseReportController::class, 'expenseSummary'])->name('reports.summary');
        Route::get('/summary/data', [ExpenseReportController::class, 'expenseSummaryData'])->name('reports.summary.data');
        Route::get('/summary/table', [ExpenseReportController::class, 'expenseSummaryTable'])->name('reports.summary.table');

        // Approval Pipeline Report
        Route::get('/approval-pipeline', [ExpenseReportController::class, 'approvalPipeline'])->name('reports.approval-pipeline');
        Route::get('/approval-pipeline/ajax', [ExpenseReportController::class, 'approvalPipelineAjax'])->name('reports.approval-pipeline.ajax');
        Route::get('/approval-pipeline/statistics', [ExpenseReportController::class, 'approvalPipelineStatistics'])->name('reports.approval-pipeline.statistics');

        // Employee Expense Report
        Route::get('/employee-expense', [ExpenseController::class, 'employeeExpenseReport'])->name('employee-report');
        Route::get('/employee-expense/statistics', [ExpenseController::class, 'getEmployeeExpenseStatistics'])->name('employee-report.statistics');
    });

    // Expense Requests (Old Routes - Keep for backward compatibility)
    Route::group(['prefix' => 'expenseRequests'], function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('expenseRequests.index');
        Route::get('/indexAjax', [ExpenseController::class, 'indexAjax'])->name('expenseRequests.indexAjax');
        Route::get('/getByIdAjax/{id}', [ExpenseController::class, 'getByIdAjax'])->name('expenseRequests.getByIdAjax');
        Route::post('/actionAjax', [ExpenseController::class, 'actionAjax'])->name('expenseRequests.actionAjax');
    });

    // Departments
    Route::group(['prefix' => 'departments'], function () {
        Route::get('/', [DepartmentsController::class, 'index'])->name('departments.index');
        Route::get('/indexAjax', [DepartmentsController::class, 'indexAjax'])->name('departments.indexAjax');
        Route::post('/addOrUpdateDepartmentAjax', [DepartmentsController::class, 'addOrUpdateDepartmentAjax'])->name('departments.addOrUpdateDepartmentAjax');
        Route::get('/getListAjax', [DepartmentsController::class, 'getListAjax'])->name('departments.getListAjax');
        Route::get('/getParentDepartments', [DepartmentsController::class, 'getParentDepartments'])->name('departments.getParentDepartments');
        Route::get('/getDepartmentAjax/{id}', [DepartmentsController::class, 'getDepartmentAjax'])->name('departments.getDepartmentAjax');
        Route::delete('/deleteAjax/{id}', [DepartmentsController::class, 'deleteAjax'])->name('departments.deleteAjax');
        Route::post('/changeStatus/{id}', [DepartmentsController::class, 'changeStatus'])->name('departments.changeStatus');
    });

    // Designations
    Route::group(['prefix' => 'designations'], function () {
        Route::get('/', [DesignationController::class, 'index'])->name('designations.index');
        Route::get('/indexAjax', [DesignationController::class, 'indexAjax'])->name('designations.indexAjax');
        Route::get('/getDesignationListAjax', [DesignationController::class, 'getDesignationListAjax'])->name('getDesignationListAjax');
        Route::post('/addOrUpdateAjax', [DesignationController::class, 'addOrUpdateAjax'])->name('designations.addOrUpdateAjax');
        Route::get('/getByIdAjax/{id}', [DesignationController::class, 'getByIdAjax'])->name('designations.getByIdAjax');
        Route::delete('/deleteAjax/{id}', [DesignationController::class, 'deleteAjax'])->name('designations.deleteAjax');
        Route::post('/changeStatus/{id}', [DesignationController::class, 'changeStatus'])->name('designations.changeStatus');
        Route::get('/checkCodeValidationAjax', [DesignationController::class, 'checkCodeValidationAjax'])->name('designations.checkCodeValidationAjax');
    });

    // Organization Hierarchy
    Route::group(['prefix' => 'organizationHierarchy'], function () {
        Route::get('/', [OrganisationHierarchyController::class, 'index'])->name('organizationHierarchy.index');
    });
});

// Routes accessible to all authenticated users
Route::middleware(['web', 'auth'])->group(function () {
    // Dashboard - Accessible to all authenticated users (role-based routing handled in controller)
    Route::get('/', [DashboardController::class, 'index'])->name('tenant.dashboard');
    Route::get('getRecentActivities', [DashboardController::class, 'getRecentActivities'])->name('getRecentActivities');
    Route::get('getDepartmentPerformanceAjax', [DashboardController::class, 'getDepartmentPerformanceAjax'])
        ->name('getDepartmentPerformanceAjax');

    // Attendance - Web Check-in/Check-out (Accessible to all employees)
    Route::prefix('hrcore/attendance')->name('hrcore.attendance.')->group(function () {
        Route::get('/web-attendance', [AttendanceController::class, 'webAttendance'])->name('web-attendance');
        Route::get('/today-status', [AttendanceController::class, 'getTodayStatus'])->name('today-status');
        Route::post('/web-check-in', [AttendanceController::class, 'webCheckIn'])->name('web-check-in');
        Route::post('/web-check-out', [AttendanceController::class, 'webCheckOut'])->name('web-check-out');
        Route::post('/start-stop-break', [AttendanceController::class, 'startStopBreak'])->name('start-stop-break');
    });
});
