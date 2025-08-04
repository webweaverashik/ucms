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
            'sheetPaymentsCount as sheetPayments_count' => function ($query) {
                $query->whereHas('invoice', function ($q) {
                    $q->where('invoice_type', 'sheet_fee');
                });
            },
        ])->with('class')->latest()->get();

        $classes = ClassName::all();

        return view('sheets.index', compact('sheets', 'classes'));
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
            'sheet_class_id' => 'required|integer|exists:class_names,id',
            'sheet_price'    => 'required|numeric|min:100',
        ]);

        // Check for duplicate class_id
        if (Sheet::where('class_id', $validated['sheet_class_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A sheet for this class already exists.',
            ], 409); // 409 Conflict
        }

        Sheet::create([
            'class_id' => $validated['sheet_class_id'],
            'price'    => $validated['sheet_price'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('sheets.view')) {
            return redirect()->back()->with('warning', 'No permission to view sheets.');
        }

        $sheet = Sheet::find($id);

        if (! $sheet) {
            return redirect()->route('sheets.index')->with('warning', 'Sheet group not found.');
        }

        return view('sheets.view', compact('sheet'));
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
                })
            )
            ->latest()
            ->get();

        $sheet_groups = Sheet::with('class')->get(); // $sheet->class

        return view('sheets.sheet-payments', compact('payments', 'sheet_groups'));
    }

    public function getPaidSheets($studentId)
    {
        $payments = SheetPayment::with('sheet')
            ->where('student_id', $studentId)
            ->whereHas('invoice', function ($query) {
                $query->where('invoice_type', 'sheet_fee')
                    ->whereIn('status', ['paid', 'partially_paid']);
            })
            ->get();

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

        $topics = SheetTopic::whereIn('subject_id', $subjects->pluck('id'))
            ->with('subject')
            ->get();

        $distributedTopics = SheetTopicTaken::where('student_id', $studentId)
            ->whereIn('sheet_topic_id', $topics->pluck('id'))
            ->pluck('sheet_topic_id')
            ->toArray();

        return response()->json([
            'topics'            => $topics,
            'distributedTopics' => $distributedTopics,
            'studentGroup'      => $student->academic_group,
            'classNumeral'      => $student->class->class_numeral,
            'debug'             => [ // Temporary debug info
                'student_group'    => $student->academic_group,
                'class_numeral'    => $student->class->class_numeral,
                'subject_count'    => $subjects->count(),
                'science_subjects' => $subjects->where('academic_group', 'Science')->count(),
            ],
        ]);
    }

}
