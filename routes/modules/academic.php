<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sheet\SheetController;
use App\Http\Controllers\Academic\BatchController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Sheet\SheetTopicController;
use App\Http\Controllers\Academic\ClassNameController;
use App\Http\Controllers\Sheet\SheetPaymentController;
use App\Http\Controllers\Academic\InstitutionController;
use App\Http\Controllers\Sheet\SheetTopicTakenController;
use App\Http\Controllers\Academic\SecondaryClassController;

/*
|--------------------------------------------------------------------------
| Academic Routes
|--------------------------------------------------------------------------
| Classes, subjects, sheets, notes distribution
| ⚠️ IMPORTANT: Route names are kept EXACTLY as original web.php
*/

// Class Names
Route::prefix('classnames')
    ->name('classnames.')
    ->group(function () {
        Route::get('ajax-data/{class}', [ClassNameController::class, 'getClassName'])->name('ajax');
        Route::get('branch-counts/{class}', [ClassNameController::class, 'getBranchCounts'])->name('branch-counts');

        // Secondary Classes (nested under classnames)
        Route::prefix('{classname}/secondary-classes')
            ->name('secondary-classes.')
            ->group(function () {
                Route::get('{secondaryClass}', [SecondaryClassController::class, 'showWithClass'])->name('show');
                Route::post('{secondaryClass}/enroll', [SecondaryClassController::class, 'enrollStudent'])->name('enroll');
                Route::put('{secondaryClass}/students/{student}', [SecondaryClassController::class, 'updateStudentEnrollment'])->name('update-student');
                Route::delete('{secondaryClass}/students/{student}', [SecondaryClassController::class, 'withdrawStudent'])->name('withdraw');
                Route::get('{secondaryClass}/check-unpaid/{student}', [SecondaryClassController::class, 'checkUnpaidInvoices'])->name('check-unpaid');
                Route::get('{secondaryClass}/available-students', [SecondaryClassController::class, 'getAvailableStudents'])->name('available-students');
                Route::post('{secondaryClass}/students/{student}/toggle-activation', [SecondaryClassController::class, 'toggleStudentActivation'])->name('toggle-activation');
            });
    });
Route::resource('classnames', ClassNameController::class);

// Secondary Classes (standalone)
Route::prefix('secondary-classes')
    ->name('secondary-classes.')
    ->group(function () {
        Route::get('by-class/{classId}', [SecondaryClassController::class, 'getByClass'])->name('by-class');
    });
Route::resource('secondary-classes', SecondaryClassController::class);

// Subjects
Route::get('get-subjects', [SubjectController::class, 'getSubjects']);
Route::get('get-taken-subjects', [SubjectController::class, 'getTakenSubjects']);

// Sheet Payments Routes
Route::prefix('sheets/payments')
    ->name('sheet-payments.')
    ->group(function () {
        Route::get('/', [SheetPaymentController::class, 'index'])->name('index');
        Route::get('/data', [SheetPaymentController::class, 'getData'])->name('data');
        Route::get('/export', [SheetPaymentController::class, 'export'])->name('export');
    });

// Sheets - Note
Route::get('/sheets/paid/{student}', [SheetController::class, 'getPaidSheets'])->name('sheets.paid');
Route::get('/sheets/{sheet}/topics/{student}', [SheetController::class, 'getSheetTopics'])->name('sheets.topics');

// Sheet Topics API Routes (for AJAX)
Route::prefix('sheets')->group(function () {
    Route::get('{sheet}/subjects-list', [SheetController::class, 'getSubjectsList'])->name('sheets.subjects.list');
    Route::get('{sheet}/subjects/{subject}/topics', [SheetController::class, 'getSubjectTopics'])->name('sheets.subject.topics');
    Route::get('{sheet}/topics-list', [SheetController::class, 'getTopicsList'])->name('sheets.topics.list');
    Route::get('{sheet}/topics/{topic}/pending-students', [SheetController::class, 'getPendingStudents'])->name('sheets.pending.students');
});

// Notes
Route::put('notes/{sheetTopic}/status', [SheetTopicController::class, 'updateStatus'])->name('notes.updateStatus');
Route::get('notes/distribution', [SheetTopicTakenController::class, 'index'])->name('notes.distribution.index');
Route::get('notes/single-distribution', [SheetTopicTakenController::class, 'create'])->name('notes.single.create');
Route::post('sheet-topics/distribute', [SheetTopicTakenController::class, 'store'])->name('sheet-topics.distribute');

// Bulk Distribution
Route::get('notes/bulk-distribution', [SheetTopicTakenController::class, 'bulkCreate'])->name('notes.bulk.create');
Route::post('sheet-topics/bulk-distribute', [SheetTopicTakenController::class, 'bulkStore'])->name('sheet-topics.bulk.distribute');

// Institutions
Route::get('institutions/by-type/{type}', [InstitutionController::class, 'getByType'])->name('institutions.by-type');

// Resource controllers
Route::resources([
    'institutions' => InstitutionController::class,
    'batches'      => BatchController::class,
    'subjects'     => SubjectController::class,
    'sheets'       => SheetController::class,
    'notes'        => SheetTopicController::class,
]);
