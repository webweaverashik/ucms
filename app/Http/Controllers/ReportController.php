<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use Illuminate\Support\Facades\DB;
use App\Models\Payment\PaymentTransaction;

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

        // Parse daterangepicker value (example: "14-09-2025 - 20-09-2025")
        [$startDate, $endDate] = explode(' - ', $request->date_range);

        $startDate = Carbon::createFromFormat('d-m-Y', trim($startDate))->startOfDay();
        $endDate   = Carbon::createFromFormat('d-m-Y', trim($endDate))->endOfDay();

        $query = PaymentTransaction::query()
            ->with(['student.class'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('is_approved', true);

        if ($request->branch_id) {
            $query->whereHas('student.branch', function ($q) use ($request) {
                $q->where('id', $request->branch_id);
            });
        }

        $transactions = $query->get();

        // Get all classes involved, ordered by id
        $classes = ClassName::whereIn(
            'id', $transactions->pluck('student.class_id')->unique()
        )->orderBy('id')->get(['id', 'name']);

        $classNames = $classes->pluck('name')->toArray();

        // Prepare pivot table: date vs class revenue
        $report = [];
        foreach ($transactions as $txn) {
            $date      = $txn->created_at->format('d-m-Y');
            $className = $txn->student->class->name ?? 'Unknown';

            if (! isset($report[$date][$className])) {
                $report[$date][$className] = 0;
            }
            $report[$date][$className] += $txn->amount_paid;
        }

        // Ensure all classes appear in each date row
        foreach ($report as $date => &$row) {
            foreach ($classNames as $className) {
                if (! isset($row[$className])) {
                    $row[$className] = 0;
                }
            }
        }

        return response()->json([
            'classes' => $classNames, // sorted by class_id
            'report'  => $report,
        ]);
    }

}
