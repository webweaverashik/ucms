<?php
namespace App\Http\Controllers\Sheet;

use Illuminate\Http\Request;
use App\Models\Student\Student;
use App\Models\Academic\Subject;
use App\Models\Academic\ClassName;
use App\Http\Controllers\Controller;
use App\Models\Sheet\SheetTopicTaken;

class SheetTopicTakenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notes_taken = SheetTopicTaken::whereHas('student', function ($query) {
            if (auth()->user()->branch_id != 0) {
                $query->where('branch_id', auth()->user()->branch_id);
            }
        })->get();

        $class_names  = ClassName::select('name', 'class_numeral')->get();
        $subjectNames = Subject::select('name')->distinct()->orderBy('name')->pluck('name');

        return view('notes.index', compact('notes_taken', 'class_names', 'subjectNames'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branchId = auth()->user()->branch_id;

        // Simplified students query
        return $students = Student::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->orderBy('student_unique_id')
            ->select('id', 'name', 'student_unique_id')
            ->get();

        return view('notes.distribution', compact('students'));
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
