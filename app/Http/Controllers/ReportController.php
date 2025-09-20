<?php
namespace App\Http\Controllers;

use App\Models\Academic\ClassName;
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

        // Parse date range
        [$startDate, $endDate] = explode(' - ', $request->date_range);
        $startDate             = \Carbon\Carbon::createFromFormat('d-m-Y', trim($startDate))->startOfDay();
        $endDate               = \Carbon\Carbon::createFromFormat('d-m-Y', trim($endDate))->endOfDay();

                                                                           // Get all class names sorted ascending
        $classes = ClassName::orderBy('id')->pluck('name', 'id'); // id => name

        // Fetch transactions with student and class relation
        $query = PaymentTransaction::with(['student.class'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('is_approved', true);

        if ($request->branch_id) {
            $query->whereHas('student.branch', function ($q) use ($request) {
                $q->where('id', $request->branch_id);
            });
        }

        $transactions = $query->get();

        // Build report: { date => { class_name => amount, ... }, ... }
        $report = [];

        // Group transactions by date
        $transactionsByDate = $transactions->groupBy(function ($t) {
            return $t->created_at->format('d-m-Y');
        });

        foreach ($transactionsByDate as $date => $dailyTransactions) {
            $report[$date] = [];
            foreach ($classes as $id => $className) {
                // Sum of transactions for this class on this date, default 0
                $amount                    = $dailyTransactions->where('student.class_id', $id)->sum('amount_paid');
                $report[$date][$className] = $amount;
            }
        }

        return response()->json([
            'report'  => $report,
            'classes' => $classes->values(), // numeric array of class names
        ]);
    }

}
