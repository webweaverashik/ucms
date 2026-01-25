<?php

use App\Http\Controllers\PdfController;
use App\Http\Controllers\Student\GuardianController;
use App\Http\Controllers\Student\ReferenceController;
use App\Http\Controllers\Student\SiblingController;
use App\Http\Controllers\Student\StudentActivationController;
use App\Http\Controllers\Student\StudentAttendanceController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentPromoteController;
use App\Http\Controllers\Student\StudentTransferController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
| All student-related routes: CRUD, transfers, attendance, etc.
| Middleware: ['web', 'auth', 'isLoggedIn'] applied in bootstrap/app.php
|
*/

// Students
Route::get('students/branch-counts', [StudentController::class, 'getBranchCounts'])->name('students.branch-counts');
Route::get('students/data', [StudentController::class, 'getStudentsData'])->name('students.data');
Route::get('students/pending', [StudentController::class, 'pending'])->name('students.pending');
Route::get('students/alumni', [StudentController::class, 'alumniStudent'])->name('students.alumni.index');

// Student Activation
Route::post('students/{id}/approve', [StudentActivationController::class, 'approve'])->name('students.activate');
Route::post('students/toggle-active', [StudentActivationController::class, 'toggleActive'])->name('students.toggleActive');
Route::post('students/bulk-toggle-active', [StudentActivationController::class, 'bulkToggleActive'])->name('students.bulkToggleActive');

Route::get('students/{id}/download-form', [PdfController::class, 'downloadAdmissionForm'])->name('students.download');
Route::get('students/{student}/invoice-months-data', [StudentController::class, 'getInvoiceMonthsData']);
Route::get('students/{id}/sheet-fee', [StudentController::class, 'getSheetFee']);
Route::get('students/promote', [StudentPromoteController::class, 'index'])->name('students.promote');
Route::post('student/statement/download', [PdfController::class, 'downloadStatement'])->name('student.statement.download');
Route::get('students/class/{id}/secondary-classes', [StudentController::class, 'getSecondaryClasses']);

// Student Transfer
Route::get('students/transfer', [StudentTransferController::class, 'index'])->name('students.transfer');
Route::get('students/{student}/info', [StudentTransferController::class, 'studentInfo'])->name('students.transfer.studentInfo');
Route::get('students/{student}/available-branches', [StudentTransferController::class, 'availableBranches'])->name('students.transfer.availableBranches');
Route::get('branches/{branch}/batches', [StudentTransferController::class, 'batchesByBranch'])->name('students.transfer.batchesByBranch');
Route::post('students/transfer/store', [StudentTransferController::class, 'store'])->name('students.transfer.store');

// Student Attendance
Route::prefix('attendances')
    ->name('attendances.')
    ->group(function () {
        Route::get('/', [StudentAttendanceController::class, 'index'])->name('index');
        Route::get('/batches/{branchId}', [StudentAttendanceController::class, 'getBatches'])->name('get_batches');
        Route::post('/get-students', [StudentAttendanceController::class, 'getStudents'])->name('get_students');
        Route::post('/store-bulk', [StudentAttendanceController::class, 'storeBulk'])->name('store_bulk');
    });

// Referrers AJAX
Route::prefix('admin')->group(function () {
    Route::get('/referrers/teachers', [ReferenceController::class, 'getTeachers'])->name('admin.referrers.teachers');
    Route::get('/referrers/students', [ReferenceController::class, 'getStudents'])->name('admin.referrers.students');
});

// Resource controllers
Route::resources([
    'students'  => StudentController::class,
    'guardians' => GuardianController::class,
    'siblings'  => SiblingController::class,
]);
