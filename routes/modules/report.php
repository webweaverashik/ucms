<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
| Attendance reports, finance reports, cost records
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
        Route::get('finance/annual-due', [ReportController::class, 'annualDueReportIndex'])->name('annual-due.index');
        Route::get('finance/annual-due/data', [ReportController::class, 'annualDueReportData'])->name('annual-due.data');
        Route::get('finance/annual-due/invoices', [ReportController::class, 'annualDueInvoices'])->name('annual-due.invoices');

        // Cost Records (separate page with AJAX)
        Route::get('cost-records', [ReportController::class, 'costRecordsIndex'])->name('cost-records.index');
        Route::get('cost-records/data', [ReportController::class, 'getReportCostsData'])->name('cost-records.data');
        Route::get('cost-records/export', [ReportController::class, 'exportCostRecords'])->name('cost-records.export');

        // Cost Summary
        Route::get('cost-summary', [ReportController::class, 'getCostSummary'])->name('cost-summary');
        Route::get('cost-summary/export', [ReportController::class, 'exportCostSummary'])->name('cost-summary.export');
    });
