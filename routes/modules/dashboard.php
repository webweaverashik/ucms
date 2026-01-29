<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
| AJAX endpoints for dashboard statistics and data
*/

Route::prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        // Summary (initial load - all stats in one call)
        Route::get('/summary', [DashboardController::class, 'getSummary'])->name('summary');

        // Individual stat endpoints
        Route::get('/student-stats', [DashboardController::class, 'getStudentStats'])->name('student-stats');
        Route::get('/invoice-stats', [DashboardController::class, 'getInvoiceStats'])->name('invoice-stats');
        Route::get('/collection-stats', [DashboardController::class, 'getCollectionStats'])->name('collection-stats');
        Route::get('/cost-stats', [DashboardController::class, 'getCostStats'])->name('cost-stats');
        Route::get('/attendance-stats', [DashboardController::class, 'getAttendanceStats'])->name('attendance-stats');
        Route::get('/recent-transactions', [DashboardController::class, 'getRecentTransactions'])->name('recent-transactions');

        // Admin only
        Route::get('/pending-discounted', [DashboardController::class, 'getPendingDiscountedTransactions'])->name('pending-discounted');
    });