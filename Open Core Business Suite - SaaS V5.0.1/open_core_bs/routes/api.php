<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceRegularizationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompensatoryOffController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserSettingsController;
use App\Http\Controllers\UserStatusController;
use Illuminate\Support\Facades\Route;

// Publicly accessible routes (no auth required)
Route::middleware('api')->group(function () {

    // WASM Integration API - Public endpoints for NCMAZ shell
    require __DIR__ . '/api-wasm.php';

    Route::group(['prefix' => 'V1'], function () {

        Route::get('hello', function () {
            return response()->json(['message' => 'Hello World!']);
        });

        // App settings - no tenant context needed
        Route::group(['prefix' => 'settings/'], function () {
            Route::get('getAppSettings', [SettingsController::class, 'getAppSettings'])->name('getAppSettings');
        });
    });
});

// Settings routes that need tenant context in SaaS mode (for module filtering by plan)
$settingsMiddleware = ['api'];
if (isSaaSMode()) {
    $settingsMiddleware[] = 'api.tenant.context';
}

Route::middleware($settingsMiddleware)->group(function () {
    Route::group(['prefix' => 'V1/settings'], function () {
        Route::get('getModuleSettings', [SettingsController::class, 'getModuleSettings'])->name('getModuleSettings');
    });
});

// Public routes that need tenant context in SaaS mode (login, checkUsername, etc.)
$publicTenantMiddleware = ['api'];
if (isSaaSMode()) {
    $publicTenantMiddleware[] = 'api.tenant.context';
}

Route::middleware($publicTenantMiddleware)->group(function () {
    Route::group(['prefix' => 'V1'], function () {
        Route::post('checkUsername', [AuthController::class, 'checkEmail'])->name('api.auth.checkUserName');
        Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
        // loginWithUid moved to UidLogin module (Modules/UidLogin/routes/api.php)
        Route::post('createDemoUser', [AuthController::class, 'createDemoUser'])->name('createDemoUser');

        // Open Auth Routes
        Route::group(['prefix' => 'auth/'], function () {
            Route::get('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
        });
    });

    // V2 Login API (also needs tenant context)
    Route::group(['prefix' => 'V2'], function () {
        Route::post('login', [AuthController::class, 'loginV2'])->name('api.auth.v2.login');
        Route::post('createDemoUser', [AuthController::class, 'createDemoUserV2'])->name('api.auth.v2.createDemoUser');
    });
});

// Protected routes
// Apply tenant context middleware conditionally based on SaaS mode
// IMPORTANT: Tenant context must come BEFORE auth so JWT user lookup uses tenant database
$apiMiddleware = [];
if (isSaaSMode()) {
    $apiMiddleware[] = 'api.tenant.context';
}
$apiMiddleware[] = 'auth:api';

Route::middleware($apiMiddleware)->group(function () {
    Route::group([
        'middleware' => 'api',
        'as' => 'api.',
    ], function ($router) {
        Route::group(['prefix' => 'V1/'], function () {

            // Authentication
            Route::group(['prefix' => 'auth/'], function () {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('changePassword', [AuthController::class, 'changePassword'])->name('changePassword');
            });

            Route::prefix('userSettings/')->name('userSettings.')->group(function () {
                Route::get('getAll', [UserSettingsController::class, 'getAll'])->name('getAll');
                Route::post('getByKey', [UserSettingsController::class, 'getByKey'])->name('getByKey');
                Route::post('addOrUpdate', [UserSettingsController::class, 'addOrUpdate'])->name('addOrUpdate');
                Route::delete('delete', [UserSettingsController::class, 'delete'])->name('delete');
            });

            // Account
            Route::group(['prefix' => 'account/'], function () {
                Route::get('me', [AccountController::class, 'me'])->name('me');
                Route::get('getAccountStatus', [AccountController::class, 'getAccountStatus'])->name('getAccountStatus');
                Route::get('getProfilePicture', [AccountController::class, 'getProfilePicture'])->name('getProfilePicture');
                Route::post('updateProfilePicture', [AccountController::class, 'updateProfilePicture'])->name('updateProfilePicture');
                Route::get('getLanguage', [AccountController::class, 'getLanguage'])->name('getLanguage');
                Route::post('updateLanguage', [AccountController::class, 'updateLanguage'])->name('updateLanguage');
                Route::post('updateProfile', [AccountController::class, 'updateProfile'])->name('updateProfile');
                Route::post('deleteAccountRequest', [AccountController::class, 'deleteAccountRequest'])->name('deleteAccountRequest');
            });

            // FCM Token Management (Global - usable by all apps)
            Route::prefix('fcm')->name('fcm.')->group(function () {
                Route::post('register', [FcmTokenController::class, 'registerToken'])->name('register');
                Route::get('tokens', [FcmTokenController::class, 'getUserTokens'])->name('tokens');
                Route::post('deactivate', [FcmTokenController::class, 'deactivateToken'])->name('deactivate');
                Route::delete('delete', [FcmTokenController::class, 'deleteToken'])->name('delete');
                Route::post('refresh', [FcmTokenController::class, 'refreshToken'])->name('refresh');
                Route::post('deactivate-all', [FcmTokenController::class, 'deactivateAllTokens'])->name('deactivateAll');
            });

            // Attendance
            Route::group(['prefix' => 'attendance/'], function () {
                Route::get('checkStatus', [AttendanceController::class, 'checkStatus'])->name('checkStatus');
                Route::post('checkInOut', [AttendanceController::class, 'checkInOut'])->name('checkInOut');
                Route::get('canCheckOut', [AttendanceController::class, 'canCheckOut'])->name('canCheckOut');
                Route::post('setEarlyCheckoutReason', [AttendanceController::class, 'setEarlyCheckoutReason'])->name('setEarlyCheckoutReason');
                Route::get('getHistory', [AttendanceController::class, 'getHistory'])->name('getHistory');
            });

            // Tracking
            Route::post('tracking/activity-status', [AttendanceController::class, 'statusUpdate'])->name('statusUpdate');

            // Attendance Regularization
            Route::group(['prefix' => 'attendance-regularization'], function () {
                Route::get('getAll', [AttendanceRegularizationController::class, 'getAll'])->name('attendanceRegularization.getAll');
                Route::get('getTypes', [AttendanceRegularizationController::class, 'getTypes'])->name('attendanceRegularization.getTypes');
                Route::get('getCounts', [AttendanceRegularizationController::class, 'getCounts'])->name('attendanceRegularization.getCounts');
                Route::get('getAvailableDates', [AttendanceRegularizationController::class, 'getAvailableDates'])->name('attendanceRegularization.getAvailableDates');
                Route::get('{id}', [AttendanceRegularizationController::class, 'getById'])->name('attendanceRegularization.getById');
                Route::post('create', [AttendanceRegularizationController::class, 'create'])->name('attendanceRegularization.create');
                Route::post('{id}', [AttendanceRegularizationController::class, 'update'])->name('attendanceRegularization.updatePost');
                Route::put('{id}', [AttendanceRegularizationController::class, 'update'])->name('attendanceRegularization.update');
                Route::delete('{id}', [AttendanceRegularizationController::class, 'delete'])->name('attendanceRegularization.delete');
            });

            // Leave Management - Optimized API
            Route::prefix('leave')->name('leave.')->group(function () {
                // Leave Types
                Route::get('types', [LeaveController::class, 'getLeaveTypes'])->name('types');

                // Leave Balance
                Route::get('balance', [LeaveController::class, 'getLeaveBalance'])->name('balance');

                // Leave Requests
                Route::get('requests', [LeaveController::class, 'getLeaveRequests'])->name('requests');
                Route::post('requests', [LeaveController::class, 'createLeaveRequest'])->name('requests.create');
                Route::get('requests/{id}', [LeaveController::class, 'getLeaveRequest'])->name('requests.show');
                Route::put('requests/{id}', [LeaveController::class, 'updateLeaveRequest'])->name('requests.update');
                Route::delete('requests/{id}', [LeaveController::class, 'cancelLeaveRequest'])->name('requests.cancel');
                Route::post('requests/{id}/upload', [LeaveController::class, 'uploadLeaveDocument'])->name('requests.upload');

                // Leave Statistics & Calendar
                Route::get('statistics', [LeaveController::class, 'getLeaveStatistics'])->name('statistics');
                Route::get('team-calendar', [LeaveController::class, 'getTeamCalendar'])->name('team-calendar');
            });

            // Compensatory Off Management
            Route::prefix('comp-off')->name('comp-off.')->group(function () {
                Route::get('list', [CompensatoryOffController::class, 'getCompensatoryOffs'])->name('list');
                Route::get('balance', [CompensatoryOffController::class, 'getBalance'])->name('balance');
                Route::get('statistics', [CompensatoryOffController::class, 'getStatistics'])->name('statistics');
                Route::post('request', [CompensatoryOffController::class, 'createCompensatoryOff'])->name('request');
                Route::get('{id}', [CompensatoryOffController::class, 'getCompensatoryOff'])->name('show');
                Route::put('{id}', [CompensatoryOffController::class, 'updateCompensatoryOff'])->name('update');
                Route::delete('{id}', [CompensatoryOffController::class, 'deleteCompensatoryOff'])->name('delete');
            });

            // Expense
            Route::group(['prefix' => 'expense'], function () {
                Route::get('getExpenseTypes ', [ExpenseController::class, 'getExpenseTypes'])->name('getExpenseTypes');
                Route::post('createExpenseRequest', [ExpenseController::class, 'createExpenseRequest'])->name('createExpenseRequest');
                Route::get('getExpenseRequests', [ExpenseController::class, 'getExpenseRequests'])->name('getExpenseRequests');
                Route::post('uploadExpenseDocument', [ExpenseController::class, 'uploadExpenseDocument'])->name('uploadExpenseDocument');
                Route::post('cancel', [ExpenseController::class, 'cancel'])->name('cancel');
            });

            // User
            Route::group(['prefix', 'user'], function () {
                Route::get('user/search/{query}', [UserController::class, 'searchUsers'])->name('searchUsers');
                Route::get('user/getAll', [UserController::class, 'getUsersList'])->name('getAllUsers');
                Route::get('userStatus', [UserController::class, 'getUserStatus'])->name('getUserStatus');
                Route::get('user/{id}', [UserController::class, 'getUserInfo'])->name('getUserInfo');
                Route::post('user/updateStatus', [UserController::class, 'updateUserStatus'])->name('updateUserStatus');
            });

            // Employees Search (for Select2 AJAX)
            Route::get('employees/search', [UserController::class, 'searchEmployees'])->name('api.employees.search');

            // Holidays
            Route::group(['prefix' => 'holidays'], function () {
                Route::get('getAll', [HolidayController::class, 'getAll'])->name('holidays.getAll');
                Route::get('my-holidays', [HolidayController::class, 'getMyHolidays'])->name('holidays.myHolidays');
                Route::get('upcoming', [HolidayController::class, 'getUpcoming'])->name('holidays.upcoming');
                Route::get('by-year-grouped', [HolidayController::class, 'getByYearGrouped'])->name('holidays.byYearGrouped');
                Route::get('{id}', [HolidayController::class, 'getById'])->name('holidays.getById');
                Route::post('create', [HolidayController::class, 'create'])->name('holidays.create');
                Route::put('{id}', [HolidayController::class, 'update'])->name('holidays.update');
                Route::delete('{id}', [HolidayController::class, 'delete'])->name('holidays.delete');
                Route::post('{id}/toggle-status', [HolidayController::class, 'toggleStatus'])->name('holidays.toggleStatus');
            });

            // Notification
            Route::group(['prefix' => 'notification'], function () {
                Route::get('getAll', [NotificationController::class, 'getAll'])->name('getAll');
                Route::post('markAsRead/{id}', [NotificationController::class, 'markAsRead'])->name('markAsRead');

                // Notification Preferences
                Route::prefix('preferences')->name('preferences.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\NotificationPreferenceController::class, 'index'])->name('index');
                    Route::put('/', [App\Http\Controllers\Api\NotificationPreferenceController::class, 'update'])->name('update');
                    Route::delete('/', [App\Http\Controllers\Api\NotificationPreferenceController::class, 'destroy'])->name('destroy');
                });
            });

            // User Status API
            Route::prefix('user-statuses')->name('user-statuses.')->group(function () {
                Route::get('/', [UserStatusController::class, 'index'])->name('index');
                Route::get('/me', [UserStatusController::class, 'me'])->name('me');
                Route::get('/options', [UserStatusController::class, 'options'])->name('options');
                Route::get('/statistics', [UserStatusController::class, 'statistics'])->name('statistics');
                Route::get('/by-status/{status}', [UserStatusController::class, 'usersByStatus'])->name('usersByStatus');
                Route::get('/{userId}', [UserStatusController::class, 'show'])->name('show');
                Route::post('/bulk', [UserStatusController::class, 'bulk'])->name('bulk');
                Route::post('/update', [UserStatusController::class, 'update'])->name('update');
                Route::post('/bulk-update', [UserStatusController::class, 'bulkUpdate'])->name('bulkUpdate');
                Route::post('/clear', [UserStatusController::class, 'clear'])->name('clear');
            });
        });
    });
});
