<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\Cost\CostTypeController;
use App\Http\Controllers\Misc\MiscController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
| Users, branches, cost types, bulk operations
| ⚠️ IMPORTANT: Route names are kept EXACTLY as original web.php
*/

// Users
Route::post('settings/users/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
Route::put('settings/users/{user}/password', [UserController::class, 'userPasswordReset'])->name('users.password.reset');

// User Profile
Route::get('profile', [ProfileController::class, 'profile'])->name('users.profile');
Route::put('profile/update', [ProfileController::class, 'updateProfile'])->name('users.profile.update');

// Cost Types
Route::prefix('settings')->group(function () {
    Route::get('cost-types', [CostTypeController::class, 'index'])->name('cost-types.index');
    Route::post('cost-types', [CostTypeController::class, 'store'])->name('cost-types.store');
    Route::put('cost-types/{costType}', [CostTypeController::class, 'update'])->name('cost-types.update');
    Route::post('cost-types/toggle-active', [CostTypeController::class, 'toggleActive'])->name('cost-types.toggleActive');
});

// Settings redirect
Route::get('settings', function () {
    return redirect()->route('users.index');
});

// Branches
Route::get('settings/branches', [BranchController::class, 'index'])->name('branches.index');
Route::post('settings/branches', [BranchController::class, 'store'])->name('branches.store');
Route::put('settings/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');

// Miscellaneous / Bulk Admission
Route::get('settings/bulk-admission', [MiscController::class, 'index'])->name('bulk.admission.index');
Route::post('settings/bulk-admission', [MiscController::class, 'bulkAdmission'])->name('bulk.admission.upload');

// User Resource - Note: 'settings/users' path with 'users' naming (original)
Route::resource('settings/users', UserController::class)->names('users');