<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function studentReport()
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

        return view('reports.students.index', compact('students'));
    }
    public function financeReport()
    {
        $branches = Branch::when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('id', auth()->user()->branch_id);
        })->select('id', 'branch_name', 'branch_prefix')->get();

        return view('reports.finance.index', compact('branches'));
    }

    public function financeReportGenerate(Request $request)
    {
        $request->validate([
            'date_range' => 'required|string',
            'branch_id'  => 'nullable|integer|exists:branches,id',
        ]);

        \Log::info('Finance Report Request:', $request->all());

        // Parse daterangepicker value (example: "2025-09-01 - 2025-09-19")
        [$startDate, $endDate] = explode(' - ', $request->date_range);

        $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', trim($startDate))->startOfDay();
        $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', trim($endDate))->endOfDay();

        $query = PaymentTransaction::query()
            ->with(['student.class', 'student.branch'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('is_approved', true);

        if ($request->branch_id) {
            $query->whereHas('student.branch', function ($q) use ($request) {
                $q->where('id', $request->branch_id);
            });
        }

        $transactions = $query->get([
            'id',
            'student_id',
            'student_classname',
            'payment_invoice_id',
            'payment_type',
            'amount_paid',
            'remaining_amount',
            'voucher_no',
            'created_at',
        ]);

        // Group by branch & class for summary
        $report = $transactions->groupBy(function ($transaction) {
            return $transaction->student->branch->branch_name
            . ' - ' . $transaction->student->class->name;
        })->map(function ($group) {
            return [
                'total_paid'   => $group->sum('amount_paid'),
                'transactions' => $group,
            ];
        });

        \Log::info('Finance Report Result:', $report->toArray());

        return response()->json($report);

    }
}
