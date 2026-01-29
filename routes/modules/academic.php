<?php

use App\Http\Controllers\Academic\BatchController;
use App\Http\Controllers\Academic\ClassNameController;
use App\Http\Controllers\Academic\InstitutionController;
use App\Http\Controllers\Academic\SecondaryClassController;
use App\Http\Controllers\Academic\SubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Academic Routes
|--------------------------------------------------------------------------
| Classes, subjects, batches, institutions, secondary classes
*/

// Class Names
Route::prefix('classnames')
    ->name('classnames.')
    ->group(function () {
        Route::get('ajax-data/{class}', [ClassNameController::class, 'getClassName'])->name('ajax');
        Route::get('branch-counts/{class}', [ClassNameController::class, 'getBranchCounts'])->name('branch-counts');

        // AJAX endpoint for students datatable (server-side processing)
        Route::get('{classname}/students-ajax', [ClassNameController::class, 'getStudentsAjax'])->name('students-ajax');

        // AJAX endpoint for class stats
        Route::get('{classname}/stats', [ClassNameController::class, 'getStats'])->name('stats');

        // AJAX endpoint for subjects
        Route::get('{classname}/subjects-ajax', [ClassNameController::class, 'getSubjectsAjax'])->name('subjects-ajax');

        // Secondary Classes (nested under classnames)
        Route::prefix('{classname}/secondary-classes')
            ->name('secondary-classes.')
            ->group(function () {
                Route::get('{secondaryClass}', [SecondaryClassController::class, 'showWithClass'])->name('show');

                // AJAX endpoints for DataTables and stats
                Route::get('{secondaryClass}/enrolled-students-ajax', [SecondaryClassController::class, 'getEnrolledStudentsAjax'])->name('enrolled-students-ajax');
                Route::get('{secondaryClass}/stats-ajax', [SecondaryClassController::class, 'getStatsAjax'])->name('stats-ajax');
                Route::get('{secondaryClass}/branch-counts-ajax', [SecondaryClassController::class, 'getBranchCountsAjax'])->name('branch-counts-ajax');

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

// Institutions
Route::get('institutions/by-type/{type}', [InstitutionController::class, 'getByType'])->name('institutions.by-type');

// Resource controllers
Route::resources([
    'institutions' => InstitutionController::class,
    'batches'      => BatchController::class,
    'subjects'     => SubjectController::class,
]);
