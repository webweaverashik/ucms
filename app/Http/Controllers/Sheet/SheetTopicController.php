<?php
namespace App\Http\Controllers\Sheet;

use Illuminate\Http\Request;
use App\Models\Student\Student;
use App\Models\Sheet\SheetTopic;
use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentTransaction;

class SheetTopicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = auth()->user()->branch_id;

        // Simplified transactions query
        $transactions = PaymentTransaction::whereHas('student', function ($query) use ($branchId) {
            if ($branchId != 0) {
                $query->where('branch_id', $branchId);
            }
        })
            ->latest('id')
            ->get();

        // Simplified students query
        $students = Student::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->orderBy('student_unique_id')
            ->get();

        return view('sheets.distribution', compact('transactions', 'students'));
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
        $validated = $request->validate([
            'sheet_subject_id' => 'required|integer|exists:subjects,id',
            'notes_name'       => 'required|string|max:255',
        ]);

        SheetTopic::create([
            'subject_id' => $validated['sheet_subject_id'],
            'topic_name' => $validated['notes_name'],
        ]);

        return response()->json(['success' => true]);
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
    public function update(Request $request, SheetTopic $note)
    {
        $request->validate([
            'topic_name' => 'required|string|max:255',
        ]);

        $note->update([
            'topic_name' => $request->topic_name,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Update the notes status
     */
    public function updateStatus(SheetTopic $sheetTopic, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $sheetTopic->update(['status' => $validated['status']]);

        return response()->json(['success' => true]);
    }
}
