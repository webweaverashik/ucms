<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userBranchId = auth()->user()->branch_id;

        if (auth()->user()->branch_id != 0) {
            $guardians = Guardian::whereHas('student', function ($query) use ($userBranchId) {
                $query->where('branch_id', $userBranchId);
            })
                ->withoutTrashed()
                ->get();
        } else {
            $guardians = Guardian::withoutTrashed()->get(); // SuperAdmin can view everything
        }

        return view('guardians.index', compact('guardians'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
