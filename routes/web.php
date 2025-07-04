<?php

use App\Http\Controllers\Academic\ClassNameController;
use App\Http\Controllers\Academic\InstitutionController;
use App\Http\Controllers\Academic\ShiftController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutoInvoiceController;
use App\Http\Controllers\Payment\PaymentInvoiceController;
use App\Http\Controllers\Payment\PaymentTransactionController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Sheet\SheetController;
use App\Http\Controllers\Sheet\SheetTopicController;
use App\Http\Controllers\Sheet\SheetTopicTakenController;
use App\Http\Controllers\Student\GuardianController;
use App\Http\Controllers\Student\ReferenceController;
use App\Http\Controllers\Student\SiblingController;
use App\Http\Controllers\Student\StudentActivationController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth', 'isLoggedIn'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.admin');
    })->name('dashboard');

    // Only allow POST method for actual logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Handle GET /logout: Redirect back if logged in
    Route::get('/logout', function () {
        return redirect()->back();
    })->name('logout.get');

    // ------- Custom routes start -------
    // Users
    Route::post('users/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    Route::put('users/{user}/password', [UserController::class, 'userPasswordReset'])->name('users.password.reset');

    // Students
    Route::get('students/pending', [StudentController::class, 'pending'])->name('students.pending');
    Route::post('students/{id}/approve', [StudentActivationController::class, 'approve'])->name('students.activate');
    Route::post('students/toggle-active', [StudentActivationController::class, 'toggleActive'])->name('students.toggleActive');
    Route::get('students/{id}/download-form', [PdfController::class, 'downloadAdmissionForm'])->name('students.download');
    Route::get('students/{student}/last-invoice-month', [StudentController::class, 'getLastInvoiceMonth']);
    Route::get('students/{id}/sheet-fee', [StudentController::class, 'getSheetFee']);
    Route::get('students/transfer', [StudentController::class, 'transferStudent'])->name('students.transfer');
    Route::get('students/promote', [StudentController::class, 'promoteStudents'])->name('students.promote');

    // Invoices
    Route::get('students/{student}/due-invoices', [PaymentInvoiceController::class, 'getDueInvoices'])->name('students.due.invoices');
    Route::get('invoices/{invoice}/view-ajax', [PaymentInvoiceController::class, 'viewAjax'])->name('invoices.view.ajax');
    Route::get('autoinvoice', [AutoInvoiceController::class, 'generate'])->name('auto.invoice');

    // Transactions
    Route::get('transactions/{id}/download-payslip', [PdfController::class, 'downloadPaySlip'])->name('transactions.download');

    // Subjects
    Route::get('get-subjects', [SubjectController::class, 'getSubjects']);
    Route::get('get-taken-subjects', [SubjectController::class, 'getTakenSubjects']);

    // Sheets
    Route::get('sheets/payments', [SheetController::class, 'sheetPayments'])->name('sheet.payments');
    Route::get('/sheets/paid/{student}', [SheetController::class, 'getPaidSheets'])->name('sheets.paid');
    Route::get('/sheets/{sheet}/topics/{student}', [SheetController::class, 'getSheetTopics'])->name('sheets.topics');



    // Notes
    Route::put('notes/{sheetTopic}/status', [SheetTopicController::class, 'updateStatus'])->name('notes.updateStatus');
    Route::get('notes/distribution', [SheetTopicTakenController::class, 'index'])->name('notes.distribution');
    Route::get('notes/distribution/create', [SheetTopicTakenController::class, 'create'])->name('notes.distribution.create');
    Route::post('sheet-topics/distribute', [SheetTopicTakenController::class, 'store'])->name('sheet-topics.distribute');


    // Class Names
    Route::get('classnames/ajax-data/{class}', [ClassNameController::class, 'getClassName'])->name('classnames.ajax');





    

    // ------- Custom routes end -------

    // AJAX Routes
    Route::prefix('admin')->group(function () {
        Route::get('/referrers/teachers', [ReferenceController::class, 'getTeachers'])->name('admin.referrers.teachers');
        Route::get('/referrers/students', [ReferenceController::class, 'getStudents'])->name('admin.referrers.students');
    });

    // Resource Routes
    Route::resource('users', UserController::class);
    Route::resource('students', StudentController::class);
    Route::resource('guardians', GuardianController::class);
    Route::resource('siblings', SiblingController::class);
    Route::resource('teachers', TeacherController::class);
    Route::resource('institutions', InstitutionController::class);
    Route::resource('classnames', ClassNameController::class);
    Route::resource('shifts', ShiftController::class);
    Route::resource('subjects', SubjectController::class);  
    Route::resource('invoices', PaymentInvoiceController::class);
    Route::resource('transactions', PaymentTransactionController::class);
    Route::resource('sheets', SheetController::class);
    Route::resource('notes', SheetTopicController::class);
});

// Handle GET /logout for logged-out users (redirect to login)
Route::get('/logout', function () {
    return redirect()->route('login');
})->name('logout.get');

// Testing mail server
Route::get('/send-test-email', function () {
    Mail::raw('This is a test email from Laravel 12!', function ($message) {
        $message->to('ashik.ane.doict@gmail.com')->subject('Laravel 12 Gmail SMTP Test');
    });

    return 'Test email sent successfully!';
});
