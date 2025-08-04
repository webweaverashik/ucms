<?php
namespace App\Http\Controllers\Sheet;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Sheet\Sheet;
use App\Models\Sheet\SheetTopicTaken;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class SheetTopicTakenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notes_taken = SheetTopicTaken::with([
            'student',
            'sheetTopic.subject.class.sheet',
        ])
            ->whereHas('student', function ($query) {
                if (auth()->user()->branch_id != 0) {
                    $query->where('branch_id', auth()->user()->branch_id);
                }
            })
            ->latest('id')
            ->get();

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
        $students = Student::when($branchId != 0, function ($query) use ($branchId) {
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
    // In NoteDistributionController.php
    public function store(Request $request)
    {
        $studentId = $request->student_id;
        $sheetId   = $request->sheet_id;
        $topics    = $request->topics ?? [];

        // Get the sheet to verify class
        $sheet = Sheet::find($sheetId);

        if (! $sheet) {
            return response()->json(['message' => 'Sheet not found'], 404);
        }

        // Get already taken topics
        $existing = SheetTopicTaken::where('student_id', $studentId)
            ->whereHas('sheetTopic', function ($query) use ($sheet) {
                $query->whereHas('subject', function ($q) use ($sheet) {
                    $q->where('class_id', $sheet->class_id);
                });
            })
            ->pluck('sheet_topic_id')
            ->toArray();

        // Filter out topics that are already taken
        $newTopics = array_diff($topics, $existing);

        // Create new records
        foreach ($newTopics as $topicId) {
            SheetTopicTaken::create([
                'sheet_topic_id' => $topicId,
                'student_id'     => $studentId,
                'distributed_by' => auth()->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Sheet topics distribution saved successfully',
        ]);
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
