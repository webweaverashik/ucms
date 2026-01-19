<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
| Attendance reports, finance reports, cost records
| ⚠️ IMPORTANT: Route names are kept EXACTLY as original web.php
*/

// Reports index
Route::get('reports', [ReportController::class, 'attendanceReport'])->name('reports.index');

// Attendance Reports
Route::get('reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance.index');
Route::get('reports/attendance/data', [ReportController::class, 'attendanceReportData'])->name('reports.attendance.data');

// Finance Reports
Route::prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('finance', [ReportController::class, 'financeReportIndex'])->name('finance.index');
        Route::post('finance', [ReportController::class, 'financeReportGenerate'])->name('finance.generate');
        Route::get('finance/costs', [ReportController::class, 'getReportCosts'])->name('finance.costs');

        // Cost Records (separate page)
        Route::get('cost-records', [ReportController::class, 'costRecordsIndex'])->name('cost-records.index');
    });