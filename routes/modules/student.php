<?php

use App\Http\Controllers\PdfController;
use App\Http\Controllers\Student\AlumniStudentController;
use App\Http\Controllers\Student\GuardianController;
use App\Http\Controllers\Student\PendingStudentController;
use App\Http\Controllers\Student\ReferenceController;
use App\Http\Controllers\Student\SiblingController;
use App\Http\Controllers\Student\StudentActivationController;
use App\Http\Controllers\Student\StudentAttendanceController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentDataController;
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

// =========================================================================
// Active Students - AJAX Data Endpoints
// =========================================================================
Route::prefix('students')->name('students.')->group(function () {
    Route::get('branch-counts', [StudentDataController::class, 'getBranchCounts'])->name('branch-counts');
    Route::get('data', [StudentDataController::class, 'getStudentsData'])->name('data');
});

// =========================================================================
// Pending Students
// =========================================================================
Route::prefix('students/pending')->name('students.pending.')->group(function () {
    Route::get('/', [PendingStudentController::class, 'index'])->name('index');
    Route::get('data', [PendingStudentController::class, 'getData'])->name('data');
    Route::get('branch-counts', [PendingStudentController::class, 'getBranchCounts'])->name('branch-counts');
});

// =========================================================================
// Alumni Students
// =========================================================================
Route::prefix('students/alumni')->name('students.alumni.')->group(function () {
    Route::get('/', [AlumniStudentController::class, 'index'])->name('index');
    Route::get('data', [AlumniStudentController::class, 'getData'])->name('data');
    Route::get('branch-counts', [AlumniStudentController::class, 'getBranchCounts'])->name('branch-counts');
});

// =========================================================================
// Student Activation
// =========================================================================
Route::post('students/{id}/approve', [StudentActivationController::class, 'approve'])->name('students.activate');
Route::post('students/toggle-active', [StudentActivationController::class, 'toggleActive'])->name('students.toggleActive');
Route::post('students/bulk-toggle-active', [StudentActivationController::class, 'bulkToggleActive'])->name('students.bulkToggleActive');

// =========================================================================
// Student Utilities
// =========================================================================
Route::get('students/{id}/download-form', [PdfController::class, 'downloadAdmissionForm'])->name('students.download');
Route::get('students/{student}/invoice-months-data', [StudentController::class, 'getInvoiceMonthsData']);
Route::get('students/{id}/sheet-fee', [StudentController::class, 'getSheetFee']);
Route::get('students/class/{id}/secondary-classes', [StudentController::class, 'getSecondaryClasses']);
Route::post('student/statement/download', [PdfController::class, 'downloadStatement'])->name('student.statement.download');

// =========================================================================
// Student Promote
// =========================================================================
Route::get('students/promote', [StudentPromoteController::class, 'index'])->name('students.promote');

// =========================================================================
// Student Transfer
// =========================================================================
Route::prefix('students')->name('students.transfer.')->group(function () {
    Route::get('transfer', [StudentTransferController::class, 'index'])->name('index');
    Route::get('{student}/info', [StudentTransferController::class, 'studentInfo'])->name('studentInfo');
    Route::get('{student}/available-branches', [StudentTransferController::class, 'availableBranches'])->name('availableBranches');
    Route::post('transfer/store', [StudentTransferController::class, 'store'])->name('store');
});
Route::get('branches/{branch}/batches', [StudentTransferController::class, 'batchesByBranch'])->name('students.transfer.batchesByBranch');

// =========================================================================
// Student Attendance
// =========================================================================
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::get('/', [StudentAttendanceController::class, 'index'])->name('index');
    Route::get('/batches/{branchId}', [StudentAttendanceController::class, 'getBatches'])->name('get_batches');
    Route::post('/get-students', [StudentAttendanceController::class, 'getStudents'])->name('get_students');
    Route::post('/store-bulk', [StudentAttendanceController::class, 'storeBulk'])->name('store_bulk');
});

// =========================================================================
// Referrers AJAX
// =========================================================================
Route::prefix('admin')->group(function () {
    Route::get('/referrers/teachers', [ReferenceController::class, 'getTeachers'])->name('admin.referrers.teachers');
    Route::get('/referrers/students', [ReferenceController::class, 'getStudents'])->name('admin.referrers.students');
});

// =========================================================================
// Resource Controllers
// =========================================================================
Route::resources([
    'students'  => StudentController::class,
    'guardians' => GuardianController::class,
    'siblings'  => SiblingController::class,
]);

// =========================================================================
// AJAX Data Endpoints for DataTables (Guardians & Siblings)
// =========================================================================
Route::get('guardians-data', [GuardianController::class, 'getData'])->name('guardians.data');
Route::get('guardians-count', [GuardianController::class, 'getCount'])->name('guardians.count');
Route::get('siblings-data', [SiblingController::class, 'getData'])->name('siblings.data');
Route::get('siblings-count', [SiblingController::class, 'getCount'])->name('siblings.count');
