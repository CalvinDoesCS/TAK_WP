<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRegularizationController;
use App\Http\Controllers\CompensatoryOffController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use Illuminate\Support\Facades\Route;

// ===================================================================
// EMPLOYEE SELF-SERVICE ROUTES
// All self-service routes under /my prefix - always use auth()->id()
// ===================================================================
Route::middleware(['auth:web'])->prefix('hrcore')->name('hrcore.')->group(function () {
    Route::prefix('my')->name('my.')->group(function () {
        // Profile Management
        Route::get('/profile', [EmployeeController::class, 'selfServiceProfile'])->name('profile');
        Route::post('/profile/update', [EmployeeController::class, 'updateSelfProfile'])->name('profile.update');
        Route::post('/profile/photo', [EmployeeController::class, 'updateProfilePhoto'])->name('profile.photo');
        Route::post('/profile/password', [EmployeeController::class, 'changePassword'])->name('profile.password');

        // My Attendance
        Route::get('/attendance', [AttendanceController::class, 'myAttendance'])->name('attendance');
        Route::get('/reports', [AttendanceController::class, 'myReports'])->name('reports');

        // My Attendance Regularization (must come before /attendance/{id})
        Route::get('/attendance/regularization', [AttendanceRegularizationController::class, 'myRegularizations'])->name('attendance.regularization');
        Route::get('/attendance/regularization/datatable', [AttendanceRegularizationController::class, 'myRegularizationsAjax'])->name('attendance.regularization.datatable');
        Route::post('/attendance/regularization', [AttendanceRegularizationController::class, 'storeMyRegularization'])->name('attendance.regularization.store');
        Route::get('/attendance/regularization/{id}', [AttendanceRegularizationController::class, 'showMyRegularization'])->name('attendance.regularization.show');
        Route::get('/attendance/regularization/{id}/edit', [AttendanceRegularizationController::class, 'editMyRegularization'])->name('attendance.regularization.edit');
        Route::put('/attendance/regularization/{id}', [AttendanceRegularizationController::class, 'updateMyRegularization'])->name('attendance.regularization.update');
        Route::delete('/attendance/regularization/{id}', [AttendanceRegularizationController::class, 'deleteMyRegularization'])->name('attendance.regularization.delete');

        // My Attendance Details (wildcard route - must come after specific routes)
        Route::get('/attendance/{id}', [AttendanceController::class, 'showMyAttendance'])->name('attendance.show');

        // My Leave Management
        Route::get('/leaves', [LeaveController::class, 'myLeaves'])->name('leaves');
        Route::get('/leaves/datatable', [LeaveController::class, 'myLeavesAjax'])->name('leaves.datatable');
        Route::get('/leaves/balance', [LeaveController::class, 'myBalance'])->name('leaves.balance');
        Route::get('/leaves/apply', [LeaveController::class, 'applyLeave'])->name('leaves.apply');
        Route::post('/leaves/apply', [LeaveController::class, 'storeMyLeave'])->name('leaves.store');
        Route::get('/leaves/{id}', [LeaveController::class, 'showMyLeave'])->name('leaves.show');
        Route::post('/leaves/{id}/cancel', [LeaveController::class, 'cancelMyLeave'])->name('leaves.cancel');

        // My Expenses
        Route::get('/expenses', [ExpenseController::class, 'myExpenses'])->name('expenses');
        Route::get('/expenses/datatable', [ExpenseController::class, 'myExpensesAjax'])->name('expenses.datatable');
        Route::get('/expenses/create', [ExpenseController::class, 'createMyExpense'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'storeMyExpense'])->name('expenses.store');
        Route::get('/expenses/{id}', [ExpenseController::class, 'showMyExpense'])->name('expenses.show');
        Route::get('/expenses/{id}/edit', [ExpenseController::class, 'editMyExpense'])->name('expenses.edit');
        Route::put('/expenses/{id}', [ExpenseController::class, 'updateMyExpense'])->name('expenses.update');
        Route::delete('/expenses/{id}', [ExpenseController::class, 'deleteMyExpense'])->name('expenses.delete');

        // My Holidays
        Route::get('/holidays', [HolidayController::class, 'myHolidays'])->name('holidays');

        // My Compensatory Offs
        Route::get('/compensatory-offs', [CompensatoryOffController::class, 'myCompOffs'])->name('compensatory-offs');
        Route::get('/compensatory-offs/datatable', [CompensatoryOffController::class, 'myCompOffsAjax'])->name('compensatory-offs.datatable');
        Route::get('/compensatory-offs/available-balance', [CompensatoryOffController::class, 'getAvailableBalance'])->name('compensatory-offs.available-balance');
        Route::post('/compensatory-offs', [CompensatoryOffController::class, 'requestCompOff'])->name('compensatory-offs.request');
        Route::get('/compensatory-offs/{id}/edit-data', [CompensatoryOffController::class, 'getCompOffForEdit'])->name('compensatory-offs.edit-data');
        Route::put('/compensatory-offs/{id}', [CompensatoryOffController::class, 'updateCompOff'])->name('compensatory-offs.update');
    });
});
