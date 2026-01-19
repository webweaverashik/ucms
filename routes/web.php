<?php

use App\Http\Controllers\Academic\BatchController;
use App\Http\Controllers\Academic\ClassNameController;
use App\Http\Controllers\Academic\InstitutionController;
use App\Http\Controllers\Academic\SecondaryClassController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\AutoInvoiceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Cost\CostController;
use App\Http\Controllers\Cost\CostTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Misc\MiscController;
use App\Http\Controllers\Payment\PaymentInvoiceCommentController;
use App\Http\Controllers\Payment\PaymentInvoiceController;
use App\Http\Controllers\Payment\PaymentTransactionController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settlement\SettlementController;
use App\Http\Controllers\Sheet\SheetController;
use App\Http\Controllers\Sheet\SheetTopicController;
use App\Http\Controllers\Sheet\SheetTopicTakenController;
use App\Http\Controllers\SMS\SmsCampaignController;
use App\Http\Controllers\SMS\SmsController;
use App\Http\Controllers\SMS\SmsTemplateController;
use App\Http\Controllers\Student\GuardianController;
use App\Http\Controllers\Student\ReferenceController;
use App\Http\Controllers\Student\SiblingController;
use App\Http\Controllers\Student\StudentActivationController;
use App\Http\Controllers\Student\StudentAttendanceController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentPromoteController;
use App\Http\Controllers\Student\StudentTransferController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth', 'isLoggedIn'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Only allow POST method for actual logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Handle GET /logout: Redirect back if logged in
    Route::get('/logout', function () {
        return redirect()->back();
    })->name('logout.get');

    // ------- Custom routes start -------
    // Users
    Route::post('settings/users/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    Route::put('settings/users/{user}/password', [UserController::class, 'userPasswordReset'])->name('users.password.reset');

    // User Profile
    Route::get('profile', [ProfileController::class, 'profile'])->name('users.profile');
    Route::put('profile/update', [ProfileController::class, 'updateProfile'])->name('users.profile.update');

    // Students
    // fetching branch counts (for admin tabs)
    Route::get('students/branch-counts', [StudentController::class, 'getBranchCounts'])->name('students.branch-counts');
    Route::get('students/data', [StudentController::class, 'getStudentsData'])->name('students.data');
    Route::get('students/pending', [StudentController::class, 'pending'])->name('students.pending');
    Route::get('students/alumni', [StudentController::class, 'alumniStudent'])->name('students.alumni.index');
    Route::post('students/{id}/approve', [StudentActivationController::class, 'approve'])->name('students.activate');
    Route::post('students/toggle-active', [StudentActivationController::class, 'toggleActive'])->name('students.toggleActive');
    Route::get('students/{id}/download-form', [PdfController::class, 'downloadAdmissionForm'])->name('students.download');
    Route::get('students/{student}/invoice-months-data', [StudentController::class, 'getInvoiceMonthsData']);
    Route::get('students/{id}/sheet-fee', [StudentController::class, 'getSheetFee']);
    Route::get('students/promote', [StudentPromoteController::class, 'index'])->name('students.promote');
    Route::post('student/statement/download', [PdfController::class, 'downloadStatement'])->name('student.statement.download');
    Route::get('students/class/{id}/secondary-classes', [StudentController::class, 'getSecondaryClasses']);

    /* --- Student Transfer Starts --- */
    Route::get('students/transfer', [StudentTransferController::class, 'index'])->name('students.transfer');

    Route::get('students/{student}/info', [StudentTransferController::class, 'studentInfo'])->name('students.transfer.studentInfo');
    Route::get('students/{student}/available-branches', [StudentTransferController::class, 'availableBranches'])->name('students.transfer.availableBranches');
    Route::get('branches/{branch}/batches', [StudentTransferController::class, 'batchesByBranch'])->name('students.transfer.batchesByBranch');
    Route::post('students/transfer/store', [StudentTransferController::class, 'store'])->name('students.transfer.store');
    /* --- Student Transfer Ends --- */

    // Student Attendance
    Route::prefix('attendances')
        ->name('attendances.')
        ->group(function () {
            Route::get('/', [StudentAttendanceController::class, 'index'])->name('index');
            Route::get('/batches/{branchId}', [StudentAttendanceController::class, 'getBatches'])->name('get_batches');
            Route::post('/get-students', [StudentAttendanceController::class, 'getStudents'])->name('get_students');

            Route::post('/store-bulk', [StudentAttendanceController::class, 'storeBulk'])->name('store_bulk');
        });

    // Teachers
    Route::post('teachers/toggle-active', [TeacherController::class, 'toggleActive'])->name('teachers.toggleActive');
    Route::put('teachers/{teacher}/password', [TeacherController::class, 'teacherPasswordReset'])->name('teachers.password.reset');
    Route::get('teachers/{id}/ajax-data', [TeacherController::class, 'getTeacherData']);

    // Invoices
    Route::get('students/{student}/due-invoices', [PaymentInvoiceController::class, 'getDueInvoices'])->name('students.due.invoices');
    Route::get('invoices/{invoice}/view-ajax', [PaymentInvoiceController::class, 'viewAjax'])->name('invoices.view.ajax');

    /*
    |--------------------------------------------------------------------------
    | Invoice Comments Routes
    |--------------------------------------------------------------------------
    */
    Route::post('invoice-comments', [PaymentInvoiceCommentController::class, 'store'])->name('invoice.comments.store');
    Route::get('invoices/{invoice}/comments', [PaymentInvoiceCommentController::class, 'getComments'])->name('invoice.comments.index');

    // Auto-invoice
    Route::get('autoinvoice', [AutoInvoiceController::class, 'index'])->name('auto.invoice.index');
    Route::get('autoinvoice/current', [AutoInvoiceController::class, 'generateCurrent'])->name('auto.invoice.current');
    Route::get('autoinvoice/due', [AutoInvoiceController::class, 'generateDue'])->name('auto.invoice.due');

    // Transactions
    Route::get('transactions/{id}/download-payslip', [PdfController::class, 'downloadPaySlip'])->name('transactions.download');
    Route::post('transactions/{id}/approve', [PaymentTransactionController::class, 'approve'])->name('transactions.approve');

    // Institutions
    Route::get('institutions/by-type/{type}', [InstitutionController::class, 'getByType'])->name('institutions.by-type');

    // Subjects
    Route::get('get-subjects', [SubjectController::class, 'getSubjects']);
    Route::get('get-taken-subjects', [SubjectController::class, 'getTakenSubjects']);

    // Sheets
    Route::get('sheets/payments', [SheetController::class, 'sheetPayments'])->name('sheet.payments');
    Route::get('/sheets/paid/{student}', [SheetController::class, 'getPaidSheets'])->name('sheets.paid');
    Route::get('/sheets/{sheet}/topics/{student}', [SheetController::class, 'getSheetTopics'])->name('sheets.topics');

    // Notes
    Route::put('notes/{sheetTopic}/status', [SheetTopicController::class, 'updateStatus'])->name('notes.updateStatus');
    Route::get('notes/distribution', [SheetTopicTakenController::class, 'index'])->name('notes.distribution.index');
    Route::get('notes/single-distribution', [SheetTopicTakenController::class, 'create'])->name('notes.single.create');
    Route::post('sheet-topics/distribute', [SheetTopicTakenController::class, 'store'])->name('sheet-topics.distribute');

    // ============================================
    // Bulk Distribution Routes
    // ============================================
    // Bulk distribution page
    Route::get('notes/bulk-distribution', [SheetTopicTakenController::class, 'bulkCreate'])->name('notes.bulk.create');

    // Sheet Topics API Routes (for AJAX)
    Route::prefix('sheets')->group(function () {
        // Get all subjects for a sheet group (used in index filter)
        Route::get('{sheet}/subjects-list', [SheetController::class, 'getSubjectsList'])->name('sheets.subjects.list');

        // Get topics for a specific subject within a sheet group (used in index filter)
        Route::get('{sheet}/subjects/{subject}/topics', [SheetController::class, 'getSubjectTopics'])->name('sheets.subject.topics');

        // Get all topics for a sheet group (used in bulk distribution)
        Route::get('{sheet}/topics-list', [SheetController::class, 'getTopicsList'])->name('sheets.topics.list');

        // Get pending students for a specific topic (bulk distribution)
        Route::get('{sheet}/topics/{topic}/pending-students', [SheetController::class, 'getPendingStudents'])->name('sheets.pending.students');
    });

    // Bulk Distribution Store
    Route::post('sheet-topics/bulk-distribute', [SheetTopicTakenController::class, 'bulkStore'])->name('sheet-topics.bulk.distribute');

    // -------------Bulk Distribution Ends-------------

    /*
    |--------------------------------------------------------------------------
    | Class Names Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('classnames')
        ->name('classnames.')
        ->group(function () {
            Route::get('ajax-data/{class}', [ClassNameController::class, 'getClassName'])->name('ajax');
            Route::get('branch-counts/{class}', [ClassNameController::class, 'getBranchCounts'])->name('branch-counts');
        });
    Route::resource('classnames', ClassNameController::class);

    /*
    |--------------------------------------------------------------------------
    | Secondary Classes Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('classnames')
        ->name('classnames.')
        ->group(function () {
            // Existing routes
            Route::get('ajax-data/{class}', [ClassNameController::class, 'getClassName'])->name('ajax');
            Route::get('branch-counts/{class}', [ClassNameController::class, 'getBranchCounts'])->name('branch-counts');

            // NEW: Secondary Classes Nested Routes
            // This enables the link: classnames/{id}/secondary-classes/{id}
            Route::prefix('{classname}/secondary-classes')
                ->name('secondary-classes.')
                ->group(function () {
                    Route::get('{secondaryClass}', [SecondaryClassController::class, 'showWithClass'])->name('show');
                    Route::post('{secondaryClass}/enroll', [SecondaryClassController::class, 'enrollStudent'])->name('enroll');
                    Route::put('{secondaryClass}/students/{student}', [SecondaryClassController::class, 'updateStudentEnrollment'])->name('update-student');
                    Route::delete('{secondaryClass}/students/{student}', [SecondaryClassController::class, 'withdrawStudent'])->name('withdraw');
                    Route::get('{secondaryClass}/check-unpaid/{student}', [SecondaryClassController::class, 'checkUnpaidInvoices'])->name('check-unpaid');
                    Route::get('{secondaryClass}/available-students', [SecondaryClassController::class, 'getAvailableStudents'])->name('available-students');

                    // NEW: Toggle student activation route
                    Route::post('{secondaryClass}/students/{student}/toggle-activation', [SecondaryClassController::class, 'toggleStudentActivation'])->name('toggle-activation');
                });
        });

    // Keep these existing routes
    Route::prefix('secondary-classes')
        ->name('secondary-classes.')
        ->group(function () {
            Route::get('by-class/{classId}', [SecondaryClassController::class, 'getByClass'])->name('by-class');
        });
    Route::resource('secondary-classes', SecondaryClassController::class);

    /*
    |--------------------------------------------------------------------------
    | Report Routes
    |--------------------------------------------------------------------------
    */
    // Reports
    Route::get('reports', [ReportController::class, 'attendanceReport'])->name('reports.index');

    // Attendance Reports
    Route::get('reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance.index');
    Route::get('reports/attendance/data', [ReportController::class, 'attendanceReportData'])->name('reports.attendance.data');

    // Costs CRUD
    Route::prefix('costs')
        ->name('costs.')
        ->group(function () {
            Route::get('types', [CostController::class, 'types'])->name('types');
            Route::get('check-today', [CostController::class, 'checkToday'])->name('check-today');
            Route::post('/', [CostController::class, 'store'])->name('store');
            Route::get('{id}', [CostController::class, 'show'])->name('show');
            Route::put('{id}', [CostController::class, 'update'])->name('update');
            Route::delete('{id}', [CostController::class, 'destroy'])->name('destroy');
            Route::post('{id}/entries', [CostController::class, 'addEntry'])->name('add-entry');
            Route::delete('entries/{id}', [CostController::class, 'deleteEntry'])->name('delete-entry');
        });

    // Finance Reports
    Route::prefix('reports')
        ->name('reports.')
        ->group(function () {
            // Revenue vs Cost Report
            Route::get('finance', [ReportController::class, 'financeReportIndex'])->name('finance.index');
            Route::post('finance', [ReportController::class, 'financeReportGenerate'])->name('finance.generate');
            Route::get('finance/costs', [ReportController::class, 'getReportCosts'])->name('finance.costs');

            // Cost Records (separate page)
            Route::get('cost-records', [ReportController::class, 'costRecordsIndex'])->name('cost-records.index');
        });

    // Cost Type
    Route::prefix('settings')->group(function () {
        Route::get('cost-types', [CostTypeController::class, 'index'])->name('cost-types.index');
        Route::post('cost-types', [CostTypeController::class, 'store'])->name('cost-types.store');
        Route::put('cost-types/{costType}', [CostTypeController::class, 'update'])->name('cost-types.update');
        Route::post('cost-types/toggle-active', [CostTypeController::class, 'toggleActive'])->name('cost-types.toggleActive');
    });

    // ----- SMS Routes Start -----
    Route::get('sms', [SmsController::class, 'sendSingleIndex']);
    Route::get('sms/send-single', [SmsController::class, 'sendSingleIndex'])->name('sms.single.index');
    Route::post('sms/send-single', [SmsController::class, 'sendSingle'])->name('sms.single.send');

    Route::post('/sms/send-campaign/{id}/approve', [SmsCampaignController::class, 'approve'])->name('sms-campaigns.approve');

    Route::get('sms/logs', [SmsController::class, 'smsLog'])->name('sms.logs.index');
    Route::get('sms/balance', [SmsController::class, 'checkBalance'])->name('sms.balance');
    Route::get('sms/status', [SmsController::class, 'checkSmsStatus'])->name('sms.status');

    Route::get('sms/templates', [SmsTemplateController::class, 'index'])->name('sms.templates.index');
    Route::prefix('sms/templates')
        ->name('sms.templates.')
        ->group(function () {
            Route::patch('{template}/toggle', [SmsTemplateController::class, 'toggleStatus'])->name('toggle');
            Route::patch('{template}/update-body', [SmsTemplateController::class, 'updateBody'])->name('updateBody');
        });

    // ----- SMS Routes End -----

    // ----- Settings Start -----
    Route::get('settings', function () {
        return redirect()->route('users.index');
    });
    Route::get('settings/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('settings/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('settings/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');

    // ----- Settings End -----

    // Miscellaneous
    Route::get('settings/bulk-admission', [MiscController::class, 'index'])->name('bulk.admission.index');
    Route::post('settings/bulk-admission', [MiscController::class, 'bulkAdmission'])->name('bulk.admission.upload');

    // Clear the cache
    Route::get('clear-cache', function () {
        clearUCMSCaches();
        clearServerCache();

        return response()->json(['success' => true]);
    })->name('clear.cache');

    // ------- Custom routes end -------

    // AJAX Routes
    Route::prefix('admin')->group(function () {
        Route::get('/referrers/teachers', [ReferenceController::class, 'getTeachers'])->name('admin.referrers.teachers');
        Route::get('/referrers/students', [ReferenceController::class, 'getStudents'])->name('admin.referrers.students');
    });

    // Settlements
    Route::prefix('settlements')
        ->name('settlements.')
        ->group(function () {
            Route::get('/', [SettlementController::class, 'index'])->name('index');
            Route::get('/logs', [SettlementController::class, 'logs'])->name('logs');
            Route::post('/', [SettlementController::class, 'store'])->name('store');
            Route::post('/adjustment', [SettlementController::class, 'adjustment'])->name('adjustment');
            Route::get('/{user}', [SettlementController::class, 'show'])->name('show');
        });

    // Resource Routes
    Route::resources([
        'settings/users'    => UserController::class,
        'students'          => StudentController::class,
        'guardians'         => GuardianController::class,
        'siblings'          => SiblingController::class,
        'teachers'          => TeacherController::class,
        'institutions'      => InstitutionController::class,
        'batches'           => BatchController::class,
        'subjects'          => SubjectController::class,
        'invoices'          => PaymentInvoiceController::class,
        'transactions'      => PaymentTransactionController::class,
        'sheets'            => SheetController::class,
        'notes'             => SheetTopicController::class,
        'sms/send-campaign' => SmsCampaignController::class,
    ]);
});

// Handle GET /logout for logged-out users (redirect to login)
Route::get('/logout', function () {
    return redirect()->route('login');
})->name('logout.get');

Route::controller(PasswordController::class)
    ->middleware('guest')
    ->group(function () {
        Route::get('forgot-password', 'showLinkRequestForm')->name('password.request');
        Route::post('forgot-password', 'sendResetLinkEmail')->name('password.email');
        Route::get('reset-password', function () {
            return redirect()->route('password.request');
        })->name('password.reset.request');
        Route::get('reset-password/{token}', 'showResetForm')->name('password.reset');
        Route::post('reset-password', 'reset')->name('password.update');
    });

// Testing mail server
Route::get('/send-test-email', function () {
    Mail::raw('This is a test email from Laravel 12!', function ($message) {
        $message->to('ashik.ane.doict@gmail.com')->subject('Laravel 12 Gmail SMTP Test');
    });

    return 'Test email sent successfully!';
});
