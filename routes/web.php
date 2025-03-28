<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Academic\ShiftController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Student\GuardianController;
use App\Http\Controllers\Student\ReferenceController;
use App\Http\Controllers\Academic\ClassNameController;


Route::get('/', function() {
    return view('landing.index');
})->name('home');

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


    
    // Custom routes
    Route::post('users/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');

    // AJAX Routes
    Route::prefix('admin')->group(function () {
        Route::get('/referrers/teachers', [ReferenceController::class, 'getTeachers'])->name('admin.referrers.teachers');
        Route::get('/referrers/students', [ReferenceController::class, 'getStudents'])->name('admin.referrers.students');
    });

    Route::get('/get-subjects', [SubjectController::class, 'getSubjects']);





    
    // resource controller routes
    Route::resource('users', UserController::class);
    Route::resource('students', StudentController::class);
    Route::resource('guardians', GuardianController::class);
    Route::resource('teachers', TeacherController::class);
    Route::resource('classnames', ClassNameController::class);
    Route::resource('shifts', ShiftController::class);
    Route::resource('subjects', SubjectController::class);


});

// Handle GET /logout for logged-out users (redirect to login)
Route::get('/logout', function () {
    return redirect()->route('login');
})->name('logout.get');




// Testing mail server
Route::get('/send-test-email', function () {
    Mail::raw('This is a test email from Laravel 12!', function ($message) {
        $message->to('ashik.ane.doict@gmail.com')
                ->subject('Laravel 12 Gmail SMTP Test');
    });

    return 'Test email sent successfully!';
});