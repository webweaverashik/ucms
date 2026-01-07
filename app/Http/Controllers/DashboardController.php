<?php
namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role
     */
    // public function index()
    // {
    //     $user = auth()->user();

    //     foreach (['admin', 'manager', 'accountant'] as $role) {
    //         if ($user->hasRole($role)) {
    //             // Get initial dashboard data for the view
    //             $branchId = in_array($role, ['admin']) ? null : $user->branch_id;
    //             $data     = $branchId;

    //             return view("dashboard.{$role}.index", compact('data'));
    //             // return redirect()->route("students.index");
    //         }
    //     }

    //     abort(403, 'Unauthorized access');
    // }

    public function index()
    {
        return redirect()->route('students.index');
    }

}
