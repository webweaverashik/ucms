<?php
namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        foreach (['admin', 'manager', 'accountant', 'guardian', 'teacher', 'student'] as $role) {
            if ($user->hasRole($role)) {
                return view("dashboard.{$role}.index");
            }
        }

        // Optional: handle case where user has none of these roles
        abort(403, 'Unauthorized access');
    }
}
