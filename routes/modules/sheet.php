<?php

use App\Http\Controllers\Sheet\SheetController;
use App\Http\Controllers\Sheet\SheetPaymentController;
use App\Http\Controllers\Sheet\SheetTopicController;
use App\Http\Controllers\Sheet\SheetTopicTakenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sheet Routes
|--------------------------------------------------------------------------
| Sheets, notes, note distribution, sheet payments
*/

// Sheet Payments Routes
Route::prefix('sheets/payments')
    ->name('sheet-payments.')
    ->group(function () {
        Route::get('/', [SheetPaymentController::class, 'index'])->name('index');
        Route::get('/data', [SheetPaymentController::class, 'getData'])->name('data');
        Route::get('/export', [SheetPaymentController::class, 'export'])->name('export');
    });

// Sheets - Paid sheets for student
Route::get('/sheets/paid/{student}', [SheetController::class, 'getPaidSheets'])->name('sheets.paid');
Route::get('/sheets/{sheet}/topics/{student}', [SheetController::class, 'getSheetTopics'])->name('sheets.topics');

// Sheet Topics API Routes (for AJAX)
Route::prefix('sheets')->group(function () {
    Route::get('{sheet}/subjects-list', [SheetController::class, 'getSubjectsList'])->name('sheets.subjects.list');
    Route::get('{sheet}/subjects/{subject}/topics', [SheetController::class, 'getSubjectTopics'])->name('sheets.subject.topics');
    Route::get('{sheet}/topics-list', [SheetController::class, 'getTopicsList'])->name('sheets.topics.list');
    Route::get('{sheet}/topics/{topic}/pending-students', [SheetController::class, 'getPendingStudents'])->name('sheets.pending.students');
});

// Notes Distribution
Route::prefix('notes/distribution')
    ->name('notes.distribution.')
    ->group(function () {
        Route::get('/', [SheetTopicTakenController::class, 'index'])->name('index');
        Route::get('/ajax-data', [SheetTopicTakenController::class, 'getData'])->name('ajax-data');
        Route::get('/export-data', [SheetTopicTakenController::class, 'getExportData'])->name('export-data');
    });

// Single Distribution
Route::get('notes/single-distribution', [SheetTopicTakenController::class, 'create'])->name('notes.single.create');
Route::post('sheet-topics/distribute', [SheetTopicTakenController::class, 'store'])->name('sheet-topics.distribute');

// Bulk Distribution
Route::get('notes/bulk-distribution', [SheetTopicTakenController::class, 'bulkCreate'])->name('notes.bulk.create');
Route::post('sheet-topics/bulk-distribute', [SheetTopicTakenController::class, 'bulkStore'])->name('sheet-topics.bulk.distribute');

// Notes (Sheet Topics) - Update status
Route::put('notes/{sheetTopic}/status', [SheetTopicController::class, 'updateStatus'])->name('notes.updateStatus');

// Resource controllers
Route::resources([
    'sheets' => SheetController::class,
    'notes'  => SheetTopicController::class,
]);
