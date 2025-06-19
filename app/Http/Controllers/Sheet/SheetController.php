<?php
namespace App\Http\Controllers\Sheet;

use App\Models\Sheet\Sheet;
use Illuminate\Http\Request;
use App\Models\Academic\ClassName;
use App\Models\Sheet\SheetPayment;
use App\Http\Controllers\Controller;
use App\Models\Sheet\SheetTopicTaken;

class SheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sheets  = Sheet::latest()->get();
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
        //
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
        //
    }

    public function sheetPayments()
    {
        $user = auth()->user();

        $payments = SheetPayment::query()
            ->when(
                ! $user->hasRole('admin'),
                fn($query) => $query->whereHas('student', function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                })
            )
            ->latest()
            ->get();

        $sheet_groups = Sheet::all();

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

    public function getSheetTopics($sheetId, $studentId)
    {
        // Get the sheet with its topics through subjects
        $sheet = Sheet::with(['sheetTopics' => function ($query) {
            $query->with('subject');
        }])->find($sheetId);

        if (! $sheet) {
            return response()->json([
                'topics'            => [],
                'distributedTopics' => [],
            ]);
        }

        // Get already taken topics for this student
        $distributedTopics = SheetTopicTaken::where('student_id', $studentId)
            ->whereHas('sheetTopic', function ($query) use ($sheetId) {
                $query->whereHas('subject', function ($q) use ($sheetId) {
                    $q->where('class_id', Sheet::find($sheetId)->class_id);
                });
            })
            ->pluck('sheet_topic_id')
            ->toArray();

        return response()->json([
            'topics'            => $sheet->sheetTopics,
            'distributedTopics' => $distributedTopics,
        ]);
    }

}
