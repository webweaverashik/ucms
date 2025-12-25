<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group with "auth" middleware.
|
| Add this to your main web.php routes file:
| require __DIR__ . '/dashboard.php';
|
*/

Route::middleware(['auth', 'isLoggedIn'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // Main dashboard view (already in web.php)
    // Route::get('/', [DashboardController::class, 'index'])->name('index');

    // API endpoints for dashboard data
    Route::prefix('api')->name('api.')->group(function () {
        // Get all dashboard data at once
        Route::get('/all', [DashboardController::class, 'getAllData'])->name('all');

        // Individual data endpoints
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
        Route::get('/monthly-payments', [DashboardController::class, 'getMonthlyPayments'])->name('monthly-payments');
        Route::get('/student-distribution', [DashboardController::class, 'getStudentDistribution'])->name('student-distribution');
        Route::get('/attendance-analytics', [DashboardController::class, 'getAttendanceAnalytics'])->name('attendance-analytics');
        Route::get('/invoice-status', [DashboardController::class, 'getInvoiceStatus'])->name('invoice-status');
        Route::get('/recent-transactions', [DashboardController::class, 'getRecentTransactions'])->name('recent-transactions');
        Route::get('/top-employees', [DashboardController::class, 'getTopEmployees'])->name('top-employees');
        Route::get('/top-subjects', [DashboardController::class, 'getTopSubjects'])->name('top-subjects');
        Route::get('/login-activities', [DashboardController::class, 'getLoginActivities'])->name('login-activities');
        Route::get('/batch-stats', [DashboardController::class, 'getBatchStats'])->name('batch-stats');

        // Cache management
        Route::post('/clear-cache', [DashboardController::class, 'clearCache'])->name('clear-cache');
    });
});
