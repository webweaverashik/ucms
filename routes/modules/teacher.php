<?php

use App\Http\Controllers\Teacher\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
| Teacher management: CRUD, activation, password reset
| Middleware: ['web', 'auth', 'isLoggedIn'] (applied in bootstrap/app.php)
*/

Route::prefix('teachers')->name('teachers.')->group(function () {
    Route::post('toggle-active', [TeacherController::class, 'toggleActive'])->name('toggleActive');
    Route::put('{teacher}/password', [TeacherController::class, 'teacherPasswordReset'])->name('password.reset');
    Route::get('{id}/ajax-data', [TeacherController::class, 'getTeacherData']);
});

Route::resource('teachers', TeacherController::class);