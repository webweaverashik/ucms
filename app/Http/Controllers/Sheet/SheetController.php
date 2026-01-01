<?php
namespace App\Http\Controllers\Sheet;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Sheet\Sheet;
use App\Models\Sheet\SheetPayment;
use App\Models\Sheet\SheetTopic;
use App\Models\Sheet\SheetTopicTaken;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class SheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('sheets.view')) {
            return redirect()->back()->with('warning', 'No permission to view sheets.');
        }

        $sheets = Sheet::withCount([
            'sheetPayments as sheetPayments_count' => function ($query) {
                $query->whereHas('invoice.invoiceType', function ($q) {
                    $q->where('type_name', 'Sheet Fee');
                });
            },
        ])
            ->withWhereHas('class', function ($query) {
                $query->active();
            })
            ->latest('id')
            ->get();

        $classes = ClassName::all();

        return view('sheets.index', compact('sheets', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('sheets.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sheet_class_id' => 'required|integer|exists:class_names,id',
            'sheet_price'    => 'required|numeric|min:100',
        ]);

        // Check for duplicate class_id
        if (Sheet::where('class_id', $validated['sheet_class_id'])->exists()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'A sheet for this class already exists.',
                ],
                409,
            ); // 409 Conflict
        }

        Sheet::create([
            'class_id' => $validated['sheet_class_id'],
            'price'    => $validated['sheet_price'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Display the specified sheet.
     */
    public function show(string $id)
    {
        if (auth()->user()->cannot('sheets.view')) {
            return redirect()->back()->with('warning', 'No permission to view sheets.');
        }

        // Eager load all necessary relationships
        $sheet = Sheet::with(['class.subjects.sheetTopics.sheetsTaken', 'sheetPayments.invoice.paymentTransactions', 'sheetPayments.student'])->find($id);

        if (! $sheet) {
            return redirect()->route('sheets.index')->with('warning', 'Sheet group not found.');
        }

        // Calculate statistics
        $subjects = $sheet->class->subjects;

        $stats = [
            'totalNotes'        => $subjects->sum(fn($s) => $s->sheetTopics->count()),
            'activeNotes'       => $subjects->sum(fn($s) => $s->sheetTopics->where('status', 'active')->count()),
            'inactiveNotes'     => $subjects->sum(fn($s) => $s->sheetTopics->where('status', 'inactive')->count()),
            'subjectsWithNotes' => $subjects->filter(fn($s) => $s->sheetTopics->isNotEmpty())->count(),
            'totalRevenue'      => $sheet->sheetPayments->sum(fn($payment) => $payment->invoice?->paymentTransactions->sum('amount_paid') ?? 0),
            'totalDue'          => $sheet->sheetPayments->sum(fn($payment) => $payment->invoice->amount_due ?? 0),
            'totalSales'        => $sheet->sheetPayments->count(),
        ];

        return view('sheets.view', compact('sheet', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back()->with('warning', 'URL Not Allowed');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'sheet_price_edit' => 'required|numeric|min:100',
        ]);

        $sheet = Sheet::findOrFail($id);

        $sheet->update([
            'price' => $validated['sheet_price_edit'],
        ]);

        // Return JSON response
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return redirect()->back()->with('warning', 'URL Not Allowed');
    }

    public function sheetPayments()
    {
        $user = auth()->user();

        $payments = SheetPayment::query()
            ->with([
                'sheet.class',                 // $payment->sheet->class
                'invoice.paymentTransactions', // $payment->invoice->paymentTransactions
                'student',                     // $payment->student
            ])
            ->when(
                ! $user->hasRole('admin'),
                fn($query) => $query->whereHas('student', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                }),
            )
            ->latest()
            ->get();

        $sheet_groups = Sheet::whereHas('class', function ($query) {
            $query->where('is_active', true);
        })->get(); // $sheet->class

        return view('sheets.sheet_payments', compact('payments', 'sheet_groups'));
    }

    public function getPaidSheets($studentId)
    {
        $payments = SheetPayment::with('sheet')->where('student_id', $studentId)->whereHas('invoice', fn($q) => $q->whereIn('status', ['paid', 'partially_paid'])->whereHas('invoiceType', fn($i) => $i->where('type_name', 'Sheet Fee')))->get();

        $sheets = $payments->map(function ($payment) {
            return [
                'id'             => $payment->sheet_id,
                'name'           => $payment->sheet->class->name ?? 'Unknown Sheet',
                'payment_status' => $payment->invoice->status ?? 'unknown',
            ];
        });

        return response()->json(['sheets' => $sheets]);
    }

    public function getSheetTopics(Sheet $sheet, $studentId)
    {
        $student = Student::with('class')->findOrFail($studentId);

        // Debugging - uncomment if needed
        // \Log::debug("Student Data", [
        //     'class_numeral'  => $student->class->class_numeral,
        //     'academic_group' => $student->academic_group,
        // ]);

        $subjects = Subject::where('class_id', $sheet->class_id)
            ->where(function ($query) use ($student) {
                $query->where('academic_group', 'General');

                if (in_array($student->class->class_numeral, ['09', '10', '11', '12'])) {
                    $query->orWhere('academic_group', $student->academic_group);
                }
            })
            ->get();

        // Debug subjects query
        // \Log::debug("Subjects Query Results", $subjects->toArray());

        $topics = SheetTopic::whereIn('subject_id', $subjects->pluck('id'))->with('subject')->get();

        $distributedTopics = SheetTopicTaken::where('student_id', $studentId)->whereIn('sheet_topic_id', $topics->pluck('id'))->pluck('sheet_topic_id')->toArray();

        return response()->json([
            'topics'            => $topics,
            'distributedTopics' => $distributedTopics,
            'studentGroup'      => $student->academic_group,
            'classNumeral'      => $student->class->class_numeral,
            'debug'             => [
                // Temporary debug info
                'student_group'    => $student->academic_group,
                'class_numeral'    => $student->class->class_numeral,
                'subject_count'    => $subjects->count(),
                'science_subjects' => $subjects->where('academic_group', 'Science')->count(),
            ],
        ]);
    }

    /* --- Added After 01.01.2026 12.45 AM --- */
    /**
     * Get all topics for a sheet group
     * Used in bulk distribution dropdown
     */
    public function getTopicsList(Sheet $sheet)
    {
        // Get all subjects for this sheet's class
        $subjects = Subject::where('class_id', $sheet->class_id)->get();

        // Get all active topics for these subjects
        $topics = SheetTopic::whereIn('subject_id', $subjects->pluck('id'))->where('status', 'active')->with('subject:id,name,academic_group')->orderBy('subject_id')->orderBy('topic_name')->get();

        $formattedTopics = $topics->map(function ($topic) {
            return [
                'id'             => $topic->id,
                'name'           => $topic->topic_name,
                'subject'        => $topic->subject->name ?? 'Unknown',
                'academic_group' => $topic->subject->academic_group ?? 'General',
                'status'         => $topic->status,
            ];
        });

        return response()->json([
            'success' => true,
            'topics'  => $formattedTopics,
            'sheet'   => [
                'id'         => $sheet->id,
                'class_name' => $sheet->class->name ?? 'Unknown',
            ],
        ]);
    }

    /**
     * Get students who paid for sheet but haven't received specific topic
     * Used in bulk distribution
     */
    public function getPendingStudents(Sheet $sheet, SheetTopic $topic)
    {
        $branchId = auth()->user()->branch_id;

        // Get all students who paid for this sheet
        $paidPayments = SheetPayment::where('sheet_id', $sheet->id)
            ->whereHas('invoice', function ($query) {
                $query->whereIn('status', ['paid', 'partially_paid'])->whereHas('invoiceType', function ($q) {
                    $q->where('type_name', 'Sheet Fee');
                });
            })
            ->with(['student:id,name,student_unique_id,class_id,academic_group,branch_id', 'student.class:id,name,class_numeral'])
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->whereHas('student', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            })
            ->get();

        // Get students who already have this topic
        $distributedStudentIds = SheetTopicTaken::where('sheet_topic_id', $topic->id)->pluck('student_id')->toArray();

        // Get topic's academic group
        $topicSubject       = Subject::find($topic->subject_id);
        $topicAcademicGroup = $topicSubject->academic_group ?? 'General';

        // Filter students
        $pendingStudents         = [];
        $alreadyDistributedCount = 0;
        $totalPaidCount          = $paidPayments->count();

        foreach ($paidPayments as $payment) {
            $student = $payment->student;

            if (! $student) {
                continue;
            }

            // Check if already distributed
            if (in_array($student->id, $distributedStudentIds)) {
                $alreadyDistributedCount++;
                continue;
            }

            // Check academic group compatibility
            // General topics are available to all students
            // Group-specific topics are only for students in that group
            if ($topicAcademicGroup !== 'General') {
                $classNumeral = $student->class->class_numeral ?? '';

                // Only check academic group for classes 09, 10, 11, 12
                if (in_array($classNumeral, ['09', '10', '11', '12'])) {
                    if ($student->academic_group !== $topicAcademicGroup) {
                        continue; // Skip students from different academic groups
                    }
                }
            }

            $pendingStudents[] = [
                'id'                => $student->id,
                'name'              => $student->name,
                'student_unique_id' => $student->student_unique_id,
                'class_name'        => $student->class->name ?? 'N/A',
                'academic_group'    => $student->academic_group ?? 'General',
            ];
        }

        return response()->json([
            'success'  => true,
            'students' => $pendingStudents,
            'stats'    => [
                'total_paid'          => $totalPaidCount,
                'already_distributed' => $alreadyDistributedCount,
                'pending'             => count($pendingStudents),
            ],
            'topic'    => [
                'id'             => $topic->id,
                'name'           => $topic->topic_name,
                'subject'        => $topicSubject->name ?? 'Unknown',
                'academic_group' => $topicAcademicGroup,
            ],
            'sheet'    => [
                'id'         => $sheet->id,
                'class_name' => $sheet->class->name ?? 'Unknown',
            ],
        ]);
    }
    /* --- Till Now --- */
}
