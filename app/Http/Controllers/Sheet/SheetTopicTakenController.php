<?php
namespace App\Http\Controllers\Sheet;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Models\Sheet\Sheet;
use App\Models\Sheet\SheetPayment;
use App\Models\Sheet\SheetTopic;
use App\Models\Sheet\SheetTopicTaken;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SheetTopicTakenController extends Controller
{
    /**
     * Display a listing of the resource (Updated to include sheetGroups for filter)
     */
    public function index()
    {
        $notes_taken = SheetTopicTaken::with(['student', 'sheetTopic.subject.class.sheet', 'distributedBy'])
            ->whereHas('student', function ($query) {
                if (auth()->user()->branch_id != 0) {
                    $query->where('branch_id', auth()->user()->branch_id);
                }
            })
            ->latest('id')
            ->get();

        // Get all active sheet groups for filter dropdown
        $sheetGroups = Sheet::whereHas('class', function ($query) {
            $query->where('is_active', true);
        })
            ->with('class')
            ->get();

        return view('notes.index', compact('notes_taken', 'sheetGroups'));
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
                $query->whereNotNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->orderBy('student_unique_id')
            ->select('id', 'name', 'student_unique_id')
            ->get();

        return view('notes.single-distribution', compact('students'));
    }

    /**
     * Store a newly created resource in storage.
     */
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

    /* --- Added After 01.01.2026 12.45 AM --- */
    /**
     * Show bulk distribution page
     */
    public function bulkCreate()
    {
        if (! auth()->user()->can('notes.distribute')) {
            return redirect()->back()->with('warning', 'No permission to distribute notes.');
        }

        // Get all active sheet groups
        $sheetGroups = Sheet::whereHas('class', function ($query) {
            $query->where('is_active', true);
        })->with('class')->get();

        return view('notes.bulk-distribution', compact('sheetGroups'));
    }

    /**
     * Store bulk distribution
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'sheet_id'      => 'required|exists:sheets,id',
            'topic_id'      => 'required|exists:sheet_topics,id',
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $sheetId    = $request->sheet_id;
        $topicId    = $request->topic_id;
        $studentIds = $request->student_ids;

        // Get the sheet to verify class
        $sheet = Sheet::find($sheetId);
        $topic = SheetTopic::find($topicId);

        if (! $sheet || ! $topic) {
            return response()->json(['message' => 'Sheet or Topic not found'], 404);
        }

        // Verify the topic belongs to this sheet's class
        $topicSubject = Subject::find($topic->subject_id);
        if (! $topicSubject || $topicSubject->class_id !== $sheet->class_id) {
            return response()->json(['message' => 'Topic does not belong to this sheet group'], 400);
        }

        // Get already distributed students for this topic
        $existingDistributions = SheetTopicTaken::where('sheet_topic_id', $topicId)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->toArray();

        // Filter out students who already have this topic
        $newStudentIds = array_diff($studentIds, $existingDistributions);

        if (empty($newStudentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'All selected students already have this topic distributed.',
            ], 400);
        }

        // Verify all students have paid for this sheet
        $paidStudentIds = SheetPayment::where('sheet_id', $sheetId)
            ->whereIn('student_id', $newStudentIds)
            ->whereHas('invoice', function ($query) {
                $query->whereIn('status', ['paid', 'partially_paid'])
                    ->whereHas('invoiceType', function ($q) {
                        $q->where('type_name', 'Sheet Fee');
                    });
            })
            ->pluck('student_id')
            ->toArray();

        // Only distribute to students who have paid
        $validStudentIds = array_intersect($newStudentIds, $paidStudentIds);

        if (empty($validStudentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid students found. Students must have paid for this sheet.',
            ], 400);
        }

        // Create distribution records
        $distributedCount = 0;
        $userId           = auth()->user()->id;

        DB::beginTransaction();
        try {
            foreach ($validStudentIds as $studentId) {
                SheetTopicTaken::create([
                    'sheet_topic_id' => $topicId,
                    'student_id'     => $studentId,
                    'distributed_by' => $userId,
                ]);
                $distributedCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to distribute: ' . $e->getMessage(),
            ], 500);
        }

        $skippedCount = count($studentIds) - $distributedCount;

        return response()->json([
            'success' => true,
            'message' => "Successfully distributed to {$distributedCount} student(s)." .
            ($skippedCount > 0 ? " {$skippedCount} student(s) were skipped." : ''),
            'distributed_count' => $distributedCount,
            'skipped_count'     => $skippedCount,
        ]);
    }
    /** Till Now */
}
