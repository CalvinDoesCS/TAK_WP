<?php

use Illuminate\Support\Facades\Route;

// Employee Authentication Routes (Public)
// Note: Employee dashboard and other routes now use the unified DashboardController
// accessed via the main '/' route with role-based views

// Employee routes have been consolidated with main auth routes
// Employee login: /auth/login (unified login for all users)
// Employee logout: /auth/logout (unified logout for all users)
// All authentication now uses the main AuthController

// Legacy redirects for backward compatibility
Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('/login', function () {
        return redirect()->route('auth.login');
    })->name('login');

    Route::post('/logout', function () {
        return redirect()->route('auth.logout');
    })->name('logout');
});
