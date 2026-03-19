<?php
namespace App\Http\Controllers;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\Cost\Cost;
use App\Models\Cost\CostType;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Classes that require academic group selection (09, 10, 11, 12)
     */
    private const GROUP_REQUIRED_CLASSES = ['09', '10', '11', '12'];

    /**
     * Available academic groups
     */
    private const ACADEMIC_GROUPS = ['Science', 'Commerce', 'Arts'];

    /*
     * Attendance Report
     */
    public function attendanceReport()
    {
        $branchId = auth()->user()->branch_id;

        $branches = Branch::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('id', $branchId);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        $classnames = ClassName::select('id', 'name', 'class_numeral')->get();

        $batches = Batch::with('branch:id,branch_name')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name', 'day_off', 'branch_id')
            ->get();

        // Pass academic groups and group-required class numerals to view
        $academicGroups       = self::ACADEMIC_GROUPS;
        $groupRequiredClasses = self::GROUP_REQUIRED_CLASSES;

        return view('reports.attendance.index', compact(
            'branches',
            'classnames',
            'batches',
            'academicGroups',
            'groupRequiredClasses'
        ));
    }

    /*
     * Attendance AJAX Data
     */
    public function attendanceReportData(Request $request)
    {
        // --- 1. Validate and Parse Input ---
        // Validate required inputs
        $request->validate([
            'date_range'     => 'required|string',
            'branch_id'      => 'required|integer|exists:branches,id',
            'class_id'       => 'required|integer|exists:class_names,id',
            'batch_id'       => 'required|integer|exists:batches,id',
            'academic_group' => 'nullable|in:Science,Commerce,Arts',
        ]);

        // Parse the date range string "start_date - end_date"
        $dateRange = explode(' - ', $request->date_range);

        // Check if the range was successfully split into two parts
        if (count($dateRange) !== 2) {
            return response()->json(
                [
                    'message' => 'Invalid date range format. Expected "start_date - end_date".',
                    'data'    => [],
                ],
                400,
            );
        }

        $startDate = Carbon::parse(trim($dateRange[0]))->startOfDay();
        $endDate   = Carbon::parse(trim($dateRange[1]))->endOfDay();

        // Get the class to check if it requires academic group
        $classModel    = ClassName::find($request->class_id);
        $supportsGroup = $classModel && in_array($classModel->class_numeral, self::GROUP_REQUIRED_CLASSES);

        // Check if "All Groups" is selected (no specific group filter)
        $isAllGroups = $supportsGroup && ! $request->filled('academic_group');

        // --- 2. Build the Query ---
        $attendances = StudentAttendance::with([
            'student'   => function ($q) use ($request, $supportsGroup) {
                $q->select('id', 'name', 'student_unique_id', 'academic_group', 'class_id');
                // Filter by academic group if provided and class supports it
                if ($supportsGroup && $request->filled('academic_group')) {
                    $q->where('academic_group', $request->academic_group);
                }
            },
            'batch'     => function ($q) {
                $q->select('id', 'name', 'branch_id');
            },
            'branch'    => function ($q) {
                $q->select('id', 'branch_name');
            },
            'classname' => function ($q) {
                $q->select('id', 'name', 'class_numeral');
            },
            'recorder'  => function ($q) {
                $q->select('id', 'name');
            },
        ])
            ->where('batch_id', $request->batch_id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id);

        // Filter attendances by student's academic group if provided
        if ($supportsGroup && $request->filled('academic_group')) {
            $attendances->whereHas('student', function ($q) use ($request) {
                $q->where('academic_group', $request->academic_group);
            });
        }

        $attendances = $attendances->get();

        // --- 3. Return as JSON ---
        return response()->json([
            'message'        => 'Attendance data retrieved successfully.',
            'data'           => $attendances,
            'supports_group' => $supportsGroup,
            'is_all_groups'  => $isAllGroups,
            'date_range'     => $request->date_range,
        ]);
    }

    /**
     * Finance report page (Revenue vs Cost)
     */
    public function financeReportIndex()
    {
        $user = auth()->user();

        if (! $user->isAdmin() && ! $user->isManager()) {
            return redirect()->route('reports.cost-records.index')->with('error', 'Unauthorized access to this page.');
        }

        $isAdmin = $user->isAdmin();

        $branches = Branch::when(! $isAdmin, function ($q) use ($user) {
            $q->where('id', $user->branch_id);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        return view('reports.finance.index', compact('branches', 'isAdmin'));
    }

    /**
     * Generate finance report
     */
    public function financeReportGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'date_range' => 'required|string',
            'branch_id'  => 'nullable|integer|exists:branches,id',
        ]);

        try {
            [$start, $end] = explode(' - ', $request->date_range);
            $startDate     = Carbon::createFromFormat('d-m-Y', trim($start))->startOfDay();
            $endDate       = Carbon::createFromFormat('d-m-Y', trim($end))->endOfDay();
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid date format',
                ],
                422,
            );
        }

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        // Get transactions with relationships
        $transactions = PaymentTransaction::with(['student', 'createdBy'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_approved', true)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('student.branch', fn($b) => $b->where('id', $branchId));
            })
            ->get();

        // Get class IDs with transactions
        $classIdsWithRevenue = $transactions->pluck('student.class_id')->filter()->unique()->values()->toArray();

        // Get classes data
        $classesData = ClassName::orderBy('id')
            ->where(function ($query) use ($classIdsWithRevenue) {
                $query->active()
                    ->orWhereIn('id', $classIdsWithRevenue);
            })
            ->select('id', 'name', 'is_active')
            ->get();

        $classes     = $classesData->pluck('name', 'id');
        $classesInfo = $classesData
            ->map(
                fn($class) => [
                    'id'        => $class->id,
                    'name'      => $class->name,
                    'is_active' => $class->is_active,
                ],
            )
            ->values();

        // Get collectors
        $collectors = $transactions->pluck('createdBy')->filter()->unique('id')->sortBy('name')->mapWithKeys(fn($user) => [$user->id => $user->name]);

        // Get costs with entries
        $costs = Cost::with('entries')->betweenDates($startDate->toDateString(), $endDate->toDateString())->forBranch($branchId)->get()->keyBy(fn($c) => $c->cost_date->format('d-m-Y'));

        $transactionsByDate = $transactions->groupBy(fn($t) => $t->created_at->format('d-m-Y'));

        // Get dates with data
        $dates  = collect();
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $d = $cursor->format('d-m-Y');
            if ($transactionsByDate->has($d) || $costs->has($d)) {
                $dates->push($d);
            }
            $cursor->addDay();
        }

        $report          = [];
        $costReport      = [];
        $collectorReport = [];

        // Initialize class totals
        $classTotals = [];
        foreach ($classes as $id => $name) {
            $classTotals[$name] = 0;
        }

        foreach ($dates as $date) {
            $dailyTx = $transactionsByDate->get($date, collect());

            // Class-wise revenue
            foreach ($classes as $id => $name) {
                $amount                = (float) $dailyTx->where('student.class_id', $id)->sum('amount_paid');
                $report[$date][$name]  = $amount;
                $classTotals[$name]   += $amount;
            }

            // Collector-wise collection
            foreach ($collectors as $collectorId => $collectorName) {
                $collectorReport[$date][$collectorId] = (float) $dailyTx->where('created_by', $collectorId)->sum('amount_paid');
            }

            // Cost total from entries
            $cost              = $costs->get($date);
            $costReport[$date] = $cost ? (float) $cost->totalAmount() : 0;
        }

        return response()->json([
            'success'         => true,
            'report'          => $report,
            'costs'           => $costReport,
            'classes'         => $classes->values(),
            'classesInfo'     => $classesInfo,
            'classTotals'     => $classTotals,
            'collectors'      => $collectors,
            'collectorReport' => $collectorReport,
        ]);
    }

    /**
     * Cost Records page
     */
    public function costRecordsIndex()
    {
        $user    = Auth::user();
        $isAdmin = $user->isAdmin();

        $branches = Branch::when(! $isAdmin, function ($q) use ($user) {
            $q->where('id', $user->branch_id);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        // Get cost types for filter
        $costTypes = CostType::active()
            ->orderBy('name')
            ->select('id', 'name', 'description')
            ->get();

        // Get cost counts per branch
        $costCounts = [];
        foreach ($branches as $branch) {
            $costCounts[$branch->id] = Cost::where('branch_id', $branch->id)->count();
        }

        return view('reports.cost-records.index', compact('branches', 'isAdmin', 'costTypes', 'costCounts'));
    }

    /**
     * Get cost records data (AJAX)
     */
    public function getCostRecordsData(Request $request): JsonResponse
    {
        try {
            $user    = Auth::user();
            $isAdmin = $user->isAdmin();

            // Get branch ID
            $branchId = $request->input('branch_id');
            if (! $isAdmin) {
                $branchId = $user->branch_id;
            }

            // Build query
            $query = Cost::with([
                'branch:id,branch_name,branch_prefix',
                'createdBy:id,name',
                'entries.costType:id,name,description',
            ]);

            // Apply branch filter
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Apply date range filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                try {
                    $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay();
                    $endDate   = Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay();
                    $query->whereBetween('cost_date', [$startDate, $endDate]);
                } catch (\Exception $e) {
                    // Invalid date format, skip filter
                }
            }

            // Apply cost type filter
            if ($request->filled('cost_type_ids')) {
                $costTypeIds = explode(',', $request->cost_type_ids);
                $costTypeIds = array_filter($costTypeIds, fn($id) => is_numeric($id));
                if (! empty($costTypeIds)) {
                    $query->whereHas('entries', function ($q) use ($costTypeIds) {
                        $q->whereIn('cost_type_id', $costTypeIds);
                    });
                }
            }

            // Apply search filter
            if ($request->filled('search_value')) {
                $search = $request->search_value;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('createdBy', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('entries.costType', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('entries', function ($q2) use ($search) {
                            $q2->where('description', 'like', "%{$search}%");
                        });
                });
            }

            // Order by date descending, then by id descending
            $query->orderBy('cost_date', 'desc')->orderBy('id', 'desc');

            $costs = $query->get();

            // Transform data
            $costs->each(function ($cost) {
                $cost->total_amount  = $cost->totalAmount();
                $cost->entries_count = $cost->entries->count();
            });

            // Get branch name for export
            $branchName = 'All Branches';
            if ($branchId) {
                $branch = Branch::find($branchId);
                if ($branch) {
                    $branchName = $branch->branch_name;
                }
            }

            return response()->json([
                'success'     => true,
                'data'        => $costs,
                'branch_name' => $branchName,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => [],
                'message' => 'Failed to load cost records: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export cost records data
     */
    public function exportCostRecords(Request $request): JsonResponse
    {
        try {
            $user    = Auth::user();
            $isAdmin = $user->isAdmin();

            // Get branch ID
            $branchId = $request->input('branch_id');
            if (! $isAdmin) {
                $branchId = $user->branch_id;
            }

            // Build query
            $query = Cost::with([
                'branch:id,branch_name,branch_prefix',
                'createdBy:id,name',
                'entries.costType:id,name,description',
            ]);

            // Apply branch filter
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Apply date range filter
            $startDateStr = null;
            $endDateStr   = null;
            if ($request->filled('start_date') && $request->filled('end_date')) {
                try {
                    $startDate    = Carbon::createFromFormat('d-m-Y', $request->start_date);
                    $endDate      = Carbon::createFromFormat('d-m-Y', $request->end_date);
                    $startDateStr = $startDate->format('d-m-Y');
                    $endDateStr   = $endDate->format('d-m-Y');
                    $query->whereBetween('cost_date', [$startDate->startOfDay(), $endDate->endOfDay()]);
                } catch (\Exception $e) {
                    // Invalid date format, skip filter
                }
            }

            // Apply cost type filter
            if ($request->filled('cost_type_ids')) {
                $costTypeIds = explode(',', $request->cost_type_ids);
                $costTypeIds = array_filter($costTypeIds, fn($id) => is_numeric($id));
                if (! empty($costTypeIds)) {
                    $query->whereHas('entries', function ($q) use ($costTypeIds) {
                        $q->whereIn('cost_type_id', $costTypeIds);
                    });
                }
            }

            // Apply search filter
            if ($request->filled('search_value')) {
                $search = $request->search_value;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('createdBy', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('entries.costType', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('entries', function ($q2) use ($search) {
                            $q2->where('description', 'like', "%{$search}%");
                        });
                });
            }

            // Order by date descending
            $query->orderBy('cost_date', 'desc')->orderBy('id', 'desc');

            $costs = $query->get();

            // Get branch name
            $branchName = 'All Branches';
            if ($branchId) {
                $branch = Branch::find($branchId);
                if ($branch) {
                    $branchName = $branch->branch_name;
                }
            }

            // Transform for export
            $exportData = [];
            $sl         = 0;
            foreach ($costs as $cost) {
                $sl++;

                // Build cost entries string with descriptions
                $entriesArr = [];
                foreach ($cost->entries as $entry) {
                    $typeName = $entry->costType->name ?? 'Unknown';
                    $amount   = number_format($entry->amount);

                    // Check if it's "Others" type with description
                    if (strtolower($typeName) === 'others' && $entry->description) {
                        $entriesArr[] = "Others ({$entry->description}): Tk {$amount}";
                    } else {
                        $entriesArr[] = "{$typeName}: Tk {$amount}";
                    }
                }
                $entriesStr = implode(', ', $entriesArr);

                $exportData[] = [
                    'sl'           => $sl,
                    'date'         => $cost->cost_date->format('d-m-Y'),
                    'cost_entries' => $entriesStr,
                    'total_amount' => $cost->totalAmount(),
                    'created_by'   => $cost->createdBy->name ?? '-',
                ];
            }

            // Calculate grand total
            $grandTotal = array_sum(array_column($exportData, 'total_amount'));

            return response()->json([
                'success' => true,
                'data'    => $exportData,
                'meta'    => [
                    'title'       => 'Cost Records Report',
                    'branch_name' => $branchName,
                    'date_range'  => $startDateStr && $endDateStr ? "{$startDateStr} to {$endDateStr}" : 'All Time',
                    'export_time'   => now()->format('d-m-Y h:i A'),
                    'total_records' => count($exportData),
                    'grand_total'   => $grandTotal,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => [],
                'message' => 'Failed to export cost records: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cost summary (AJAX)
     */
    public function getCostSummary(Request $request): JsonResponse
    {
        try {
            // Sanitize branch_id BEFORE validation
            $branchIdInput = $request->input('branch_id');
            $branchIdClean = null;

            if ($branchIdInput !== '' && $branchIdInput !== null && $branchIdInput !== 'null' && $branchIdInput !== 'undefined') {
                $branchIdClean = (int) $branchIdInput;
                if ($branchIdClean <= 0) {
                    $branchIdClean = null;
                }
            }

            // Merge sanitized value back
            $request->merge(['branch_id' => $branchIdClean]);

            $request->validate([
                'start_date' => 'required|date_format:d-m-Y',
                'end_date'   => 'required|date_format:d-m-Y',
                'branch_id'  => 'nullable|integer|exists:branches,id',
            ]);

            $user    = Auth::user();
            $isAdmin = $user->isAdmin();

            // Use sanitized branch ID
            $branchId = $branchIdClean;

            // Non-admin can only see their branch
            if (! $isAdmin) {
                $branchId = $user->branch_id;
            }

            $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay();
            $endDate   = Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay();

            // Build query
            $query = Cost::with(['entries.costType'])
                ->whereBetween('cost_date', [$startDate, $endDate]);

            // Apply branch filter
            if ($branchId !== null) {
                $query->where('branch_id', $branchId);
            }

            $costs = $query->get();

            // Aggregate by cost type - group all "Others" entries together
            $costTypeSummary = [];
            $totalCost       = 0;
            $totalEntries    = 0;
            $uniqueDates     = [];

            foreach ($costs as $cost) {
                $uniqueDates[$cost->cost_date->format('Y-m-d')] = true;

                foreach ($cost->entries as $entry) {
                    $typeId   = $entry->cost_type_id;
                    $typeName = $entry->costType->name ?? 'Unknown';
                    $typeDesc = $entry->costType->description ?? '';
                    $amount   = (int) $entry->amount;

                    $totalCost += $amount;
                    $totalEntries++;

                    // Group all "Others" entries under a single "Others" category
                    $isOthers = strtolower($typeName) === 'others';
                    $key      = $isOthers ? 'others' : $typeId;

                    if (! isset($costTypeSummary[$key])) {
                        $costTypeSummary[$key] = [
                            'cost_type_id'          => $isOthers ? 'others' : $typeId,
                            'cost_type_name'        => $typeName,
                            'cost_type_description' => $typeDesc,
                            'total_amount'          => 0,
                            'entry_count'           => 0,
                        ];
                    }

                    $costTypeSummary[$key]['total_amount'] += $amount;
                    $costTypeSummary[$key]['entry_count']++;
                }
            }

            // Sort by total amount descending
            usort($costTypeSummary, fn($a, $b) => $b['total_amount'] - $a['total_amount']);

            // Calculate unique days and daily average
            $uniqueDays   = count($uniqueDates);
            $dailyAverage = $uniqueDays > 0 ? (int) round($totalCost / $uniqueDays) : 0;

            // Get branch name
            $branchName = 'All Branches';
            if ($branchId !== null) {
                $branch = Branch::find($branchId);
                if ($branch) {
                    $branchName = $branch->branch_name;
                }
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'summary'       => array_values($costTypeSummary),
                    'total_cost'    => (int) $totalCost,
                    'total_entries' => (int) $totalEntries,
                    'unique_days'   => (int) $uniqueDays,
                    'daily_average' => (int) $dailyAverage,
                    'date_range'    => [
                        'start' => $startDate->format('d-m-Y'),
                        'end'   => $endDate->format('d-m-Y'),
                    ],
                    'branch_name'   => $branchName,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => [
                    'summary'       => [],
                    'total_cost'    => 0,
                    'total_entries' => 0,
                    'unique_days'   => 0,
                    'daily_average' => 0,
                ],
                'message' => 'Failed to generate cost summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export cost summary
     */
    public function exportCostSummary(Request $request): JsonResponse
    {
        try {
            // Sanitize branch_id
            $branchIdInput = $request->input('branch_id');
            $branchIdClean = null;

            if ($branchIdInput !== '' && $branchIdInput !== null && $branchIdInput !== 'null' && $branchIdInput !== 'undefined') {
                $branchIdClean = (int) $branchIdInput;
                if ($branchIdClean <= 0) {
                    $branchIdClean = null;
                }
            }

            $request->merge(['branch_id' => $branchIdClean]);

            $request->validate([
                'start_date' => 'required|date_format:d-m-Y',
                'end_date'   => 'required|date_format:d-m-Y',
                'branch_id'  => 'nullable|integer|exists:branches,id',
            ]);

            $user    = Auth::user();
            $isAdmin = $user->isAdmin();

            $branchId = $branchIdClean;

            if (! $isAdmin) {
                $branchId = $user->branch_id;
            }

            $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay();
            $endDate   = Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay();

            $query = Cost::with(['entries.costType'])
                ->whereBetween('cost_date', [$startDate, $endDate]);

            if ($branchId !== null) {
                $query->where('branch_id', $branchId);
            }

            $costs = $query->get();

            // Aggregate by cost type
            $costTypeSummary = [];
            $totalCost       = 0;
            $totalEntries    = 0;

            foreach ($costs as $cost) {
                foreach ($cost->entries as $entry) {
                    $typeId   = $entry->cost_type_id;
                    $typeName = $entry->costType->name ?? 'Unknown';
                    $typeDesc = $entry->costType->description ?? '';
                    $amount   = (int) $entry->amount;

                    $totalCost += $amount;
                    $totalEntries++;

                    $isOthers = strtolower($typeName) === 'others';
                    $key      = $isOthers ? 'others' : $typeId;

                    if (! isset($costTypeSummary[$key])) {
                        $costTypeSummary[$key] = [
                            'cost_type_name'        => $typeName,
                            'cost_type_description' => $typeDesc,
                            'total_amount'          => 0,
                            'entry_count'           => 0,
                        ];
                    }

                    $costTypeSummary[$key]['total_amount'] += $amount;
                    $costTypeSummary[$key]['entry_count']++;
                }
            }

            // Sort by total amount descending
            usort($costTypeSummary, fn($a, $b) => $b['total_amount'] - $a['total_amount']);

            // Calculate percentages
            foreach ($costTypeSummary as &$item) {
                $item['percentage'] = $totalCost > 0 ? round(($item['total_amount'] / $totalCost) * 100, 1) : 0;
            }

            // Get branch name
            $branchName = 'All Branches';
            if ($branchId !== null) {
                $branch = Branch::find($branchId);
                if ($branch) {
                    $branchName = $branch->branch_name;
                }
            }

            return response()->json([
                'success' => true,
                'data'    => array_values($costTypeSummary),
                'meta'    => [
                    'title'         => 'Cost Summary Report',
                    'branch_name'   => $branchName,
                    'date_range'    => $startDate->format('d-m-Y') . ' to ' . $endDate->format('d-m-Y'),
                    'export_time'   => now()->format('d-m-Y h:i A'),
                    'total_cost'    => $totalCost,
                    'total_entries' => $totalEntries,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => [],
                'message' => 'Failed to export cost summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annual Due Report page
     */
    public function annualDueReportIndex()
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized access to the page.');
        }

        $branches = Branch::select('id', 'branch_name', 'branch_prefix')->get();

        return view('reports.annual-due.index', compact('branches'));
    }

    /**
     * Annual Due Report – AJAX Data
     *
     * Returns two separate datasets:
     *
     * 1. TUITION FEE dues  → uses `month_year` column (format: MM_YYYY)
     *    Grouped by month_year + class + batch
     *
     * 2. OTHER FEE dues    → uses `created_at` to determine the month
     *    (month_year is NULL for non-Tuition invoices)
     *    Grouped by created_at month + invoice_type + class + batch
     *
     * Base query (matches raw SQL):
     *   SELECT SUM(amount_due) FROM payment_invoices
     *   WHERE status != 'paid' AND deleted_at IS NULL
     *   AND student_id IN (SELECT id FROM students WHERE branch_id = ?);
     */
    public function annualDueReportData(Request $request): JsonResponse
    {
        // Only admin can access
        if (! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'year'      => 'required|integer|min:2020|max:2099',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $year     = (int) $request->year;
        $branchId = (int) $request->branch_id;
        $branch   = Branch::find($branchId);

        $monthNames = [
            1  => 'January', 2  => 'February', 3  => 'March',
            4  => 'April', 5    => 'May', 6       => 'June',
            7  => 'July', 8     => 'August', 9    => 'September',
            10 => 'October', 11 => 'November', 12 => 'December',
        ];

        // Build all possible month_year values for the selected year (01_2025 … 12_2025)
        $monthYearValues = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthYearValues[] = str_pad($m, 2, '0', STR_PAD_LEFT) . '_' . $year;
        }

        // Student IDs subquery — EXCLUDE soft-deleted students
        $studentIdsSubquery = function ($query) use ($branchId) {
            $query->select('id')
                ->from('students')
                ->where('branch_id', $branchId)
                ->whereNull('deleted_at'); // Exclude soft-deleted students
        };

        // Get Tuition Fee type ID
        $tuitionTypeId = PaymentInvoiceType::where('type_name', 'Tuition Fee')->value('id');

        // ════════════════════════════════════════════════════════════
        //  SECTION 1: TUITION FEE DUES (month_year column)
        // ════════════════════════════════════════════════════════════

        // Query ALL tuition invoices (all statuses: due, partially_paid, paid)
        // - Total Payable = sum of total_amount of ALL invoices
        // - Due = sum of amount_due of ALL invoices (paid invoices have amount_due=0)
        // - Soft-deleted invoices auto-excluded (SoftDeletes trait)
        // - Soft-deleted students excluded via whereNull('deleted_at') in subquery
        $tuitionInvoices = PaymentInvoice::whereIn('student_id', $studentIdsSubquery)
            ->when($tuitionTypeId, fn($q) => $q->where('invoice_type_id', $tuitionTypeId))
            ->whereIn('month_year', $monthYearValues)
            ->with([
                'student'       => fn($q)       => $q->select('id', 'name', 'class_id', 'batch_id'),
                'student.class' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'student.batch' => fn($q) => $q->select('id', 'name'),
            ])
            ->get();

        $tuitionGrandTotal       = $tuitionInvoices->sum('amount_due');
        $tuitionTotalInvoices    = $tuitionInvoices->count();
        $tuitionCollectableTotal = $tuitionInvoices->sum('total_amount');

        // ── Pre-populate tuition summary with ALL active classes ──
        $allActiveClasses = ClassName::active()->orderBy('id')->select('id', 'name')->get();

        // Each month now has 'collectable' and 'due' sub-keys
        $tuitionSummary = [];
        foreach ($allActiveClasses as $cls) {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[$m] = ['collectable' => 0, 'due' => 0];
            }
            $tuitionSummary[$cls->name] = ['class_id' => $cls->id, 'months' => $months];
        }

        // Track monthly totals for collectable and due
        $tuitionMonthlyCollectable = array_fill(1, 12, 0);
        $tuitionMonthlyDue         = array_fill(1, 12, 0);

        // Process ALL tuition invoices (including paid ones)
        // Total Payable = sum(total_amount) of ALL invoices
        // Due = sum(amount_due) of ALL invoices (paid have 0)
        foreach ($tuitionInvoices as $inv) {
            $parts     = explode('_', $inv->month_year);
            $monthNum  = (int) $parts[0];
            $className = $inv->student->class->name ?? 'Unknown';
            $classId   = $inv->student->class_id ?? 0;

            // Ensure class exists in summary (for inactive classes with dues)
            if (! isset($tuitionSummary[$className])) {
                $months = [];
                for ($m = 1; $m <= 12; $m++) {
                    $months[$m] = ['collectable' => 0, 'due' => 0];
                }
                $tuitionSummary[$className] = ['class_id' => (int) $classId, 'months' => $months];
            }

            // Total Payable: sum of total_amount (ALL invoices including paid)
            $tuitionSummary[$className]['months'][$monthNum]['collectable'] += $inv->total_amount;
            $tuitionMonthlyCollectable[$monthNum]                           += $inv->total_amount;

            // Due: sum of amount_due (paid=0, partially_paid=remaining, due=full)
            $tuitionSummary[$className]['months'][$monthNum]['due'] += $inv->amount_due;
            $tuitionMonthlyDue[$monthNum]                           += $inv->amount_due;
        }

        // Group ALL invoices by month + class + batch for detailed view
        // (but only include groups that have outstanding dues)
        $tuitionGrouped = $tuitionInvoices->groupBy(function ($inv) {
            $parts = explode('_', $inv->month_year);
            return (int) $parts[0]
                . '|' . ($inv->student->class_id ?? 0)
                . '|' . ($inv->student->batch_id ?? 0);
        });

        $tuitionDetailed = [];

        foreach ($tuitionGrouped as $key => $invoices) {
            [$monthNum, $classId, $batchId] = explode('|', $key);
            $monthNum                       = (int) $monthNum;

            $first     = $invoices->first();
            $className = $first->student->class->name ?? 'Unknown';
            $batchName = $first->student->batch->name ?? 'Unknown';
            $dueAmount = (int) $invoices->sum('amount_due');

            // Only include groups with outstanding dues in detailed view
            if ($dueAmount <= 0) {
                continue;
            }

            // Count students with dues (not all students with invoices)
            $studentCnt = $invoices->where('amount_due', '>', 0)->pluck('student_id')->unique()->count();

            $tuitionDetailed[] = [
                'month'         => $monthNames[$monthNum],
                'month_num'     => $monthNum,
                'class'         => $className,
                'class_id'      => (int) $classId,
                'batch'         => $batchName,
                'batch_id'      => (int) $batchId,
                'student_count' => $studentCnt,
                'due_amount'    => $dueAmount,
            ];
        }

        // Sort detailed by month → class → batch
        usort($tuitionDetailed, fn($a, $b) =>
            $a['month_num'] <=> $b['month_num']
                ?: strcmp($a['class'], $b['class'])
                ?: strcmp($a['batch'], $b['batch'])
        );

        // Format tuition summary with collectable and due
        $fmtTuitionSummary  = [];
        uasort($tuitionSummary, fn($a, $b) => $a['class_id'] <=> $b['class_id']);

        foreach ($tuitionSummary as $className => $data) {
            $monthData        = [];
            $totalCollectable = 0;
            $totalDue         = 0;

            foreach ($data['months'] as $m => $amounts) {
                $monthData[$monthNames[$m]]  = [
                    'collectable' => $amounts['collectable'],
                    'due'         => $amounts['due'],
                ];
                $totalCollectable += $amounts['collectable'];
                $totalDue         += $amounts['due'];
            }

            $fmtTuitionSummary[$className] = [
                'class_id'          => $data['class_id'],
                'months'            => $monthData,
                'total_collectable' => $totalCollectable,
                'total_due'         => $totalDue,
            ];
        }

        // Format monthly collectable and due totals
        $fmtMonthlyCollectable = [];
        $fmtMonthlyDue         = [];
        foreach ($tuitionMonthlyCollectable as $m => $amt) {
            $fmtMonthlyCollectable[$monthNames[$m]] = $amt;
        }
        foreach ($tuitionMonthlyDue as $m => $amt) {
            $fmtMonthlyDue[$monthNames[$m]] = $amt;
        }

        // ════════════════════════════════════════════════════════════
        //  SECTION 2: OTHER INVOICE TYPE DUES (created_at month)
        // ════════════════════════════════════════════════════════════

        // Query other fee invoices with outstanding balance (amount_due > 0)
        $otherQuery = PaymentInvoice::where('amount_due', '>', 0)
            ->whereIn('student_id', $studentIdsSubquery)
            ->whereYear('created_at', $year)
            ->with([
                'student'       => fn($q)       => $q->withTrashed()->select('id', 'name', 'class_id', 'batch_id'),
                'student.class' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'student.batch' => fn($q) => $q->select('id', 'name'),
                'invoiceType:id,type_name',
            ]);

        if ($tuitionTypeId) {
            $otherQuery->where('invoice_type_id', '!=', $tuitionTypeId);
        }

        $otherInvoices      = $otherQuery->get();
        $otherGrandTotal    = $otherInvoices->sum('amount_due');
        $otherTotalInvoices = $otherInvoices->count();

        // Group by month(created_at) + invoice_type + class + batch
        $otherGrouped = $otherInvoices->groupBy(function ($inv) {
            return $inv->created_at->month
            . '|' . $inv->invoice_type_id
                . '|' . ($inv->student->class_id ?? 0)
                . '|' . ($inv->student->batch_id ?? 0);
        });

        $otherDetailed = [];
        $otherSummary  = []; // typeName => [1 => amount, …, 12 => amount]

        foreach ($otherGrouped as $key => $invoices) {
            [$monthNum, $typeId, $classId, $batchId] = explode('|', $key);
            $monthNum                                = (int) $monthNum;

            $first     = $invoices->first();
            $typeName  = $first->invoiceType->type_name ?? 'Unknown';
            $className = $first->student->class->name ?? 'Unknown';
            $batchName = $first->student->batch->name ?? 'Unknown';
            $dueAmount = (int) $invoices->sum('amount_due');

            // Only process groups with outstanding dues
            if ($dueAmount <= 0) {
                continue;
            }

            $studentCnt = $invoices->where('amount_due', '>', 0)->pluck('student_id')->unique()->count();

            $otherDetailed[] = [
                'month'           => $monthNames[$monthNum],
                'month_num'       => $monthNum,
                'invoice_type'    => $typeName,
                'invoice_type_id' => (int) $typeId,
                'class'           => $className,
                'class_id'        => (int) $classId,
                'batch'           => $batchName,
                'batch_id'        => (int) $batchId,
                'student_count'   => $studentCnt,
                'due_amount'      => $dueAmount,
            ];

            if (! isset($otherSummary[$typeName])) {
                $otherSummary[$typeName] = array_fill(1, 12, 0);
            }
            $otherSummary[$typeName][$monthNum] += $dueAmount;
        }

        // Sort other detailed by month → invoice_type → class
        usort($otherDetailed, fn($a, $b) =>
            $a['month_num'] <=> $b['month_num']
                ?: strcmp($a['invoice_type'], $b['invoice_type'])
                ?: strcmp($a['class'], $b['class'])
        );

        // Format other summary
        $fmtOtherSummary = [];
        ksort($otherSummary);

        foreach ($otherSummary as $typeName => $months) {
            $monthData = [];
            foreach ($months as $m => $amt) {
                $monthData[$monthNames[$m]] = $amt;
            }
            $fmtOtherSummary[$typeName] = [
                'months' => $monthData,
                'total'  => array_sum($months),
            ];
        }

        // ════════════════════════════════════════════════════════════
        //  RESPONSE
        // ════════════════════════════════════════════════════════════

        return response()->json([
            'success'        => true,

            // Tuition Fee section
            'tuition'        => [
                'detailed'            => $tuitionDetailed,
                'summary'             => $fmtTuitionSummary,
                'monthly_collectable' => $fmtMonthlyCollectable,
                'monthly_due'         => $fmtMonthlyDue,
                'grand_total'         => $tuitionGrandTotal,
                'collectable_total'   => $tuitionCollectableTotal,
                'total_invoices'      => $tuitionTotalInvoices,
                'total_classes'       => count($fmtTuitionSummary),
            ],

            // Other Fee Types section
            'other'          => [
                'detailed'       => $otherDetailed,
                'summary'        => $fmtOtherSummary,
                'grand_total'    => $otherGrandTotal,
                'total_invoices' => $otherTotalInvoices,
                'total_types'    => count($fmtOtherSummary),
            ],

            // Combined totals
            'grand_total'    => $tuitionGrandTotal + $otherGrandTotal,
            'total_invoices' => $tuitionTotalInvoices + $otherTotalInvoices,

            // Branch / Year info
            'branch_name'    => $branch->branch_name ?? '',
            'branch_prefix'  => $branch->branch_prefix ?? '',
            'year'           => $year,
        ]);
    }

    /**
     * Annual Due Report – Invoice Details (AJAX)
     *
     * Returns individual invoices for a specific group
     * (month + class + batch for tuition, or month + invoice_type + class + batch for other fees)
     *
     * Used by the modal when clicking on the student count badge.
     */
    public function annualDueInvoices(Request $request): JsonResponse
    {
        // Only admin can access
        if (! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type'            => 'required|in:tuition,other',
            'year'            => 'required|integer|min:2020|max:2099',
            'branch_id'       => 'required|integer|exists:branches,id',
            'month_num'       => 'required|integer|min:1|max:12',
            'class_id'        => 'required|integer',
            'batch_id'        => 'required|integer',
            'invoice_type_id' => 'nullable|integer|exists:payment_invoice_types,id',
        ]);

        $year     = (int) $request->year;
        $branchId = (int) $request->branch_id;
        $classId  = (int) $request->class_id;
        $batchId  = (int) $request->batch_id;
        $monthNum = (int) $request->month_num;

        $tuitionTypeId = PaymentInvoiceType::where('type_name', 'Tuition Fee')->value('id');

        // Get student IDs matching branch + class + batch — EXCLUDE soft-deleted students
        $studentIds = DB::table('students')
            ->where('branch_id', $branchId)
            ->where('class_id', $classId)
            ->where('batch_id', $batchId)
            ->whereNull('deleted_at') // Exclude soft-deleted students
            ->pluck('id');

        // Query invoices with outstanding dues only (amount_due > 0)
        // Soft-deleted invoices are automatically excluded (PaymentInvoice uses SoftDeletes)
        $query = PaymentInvoice::where('amount_due', '>', 0)
            ->whereIn('student_id', $studentIds)
            ->with(['student' => fn($q) => $q->select('id', 'name', 'student_unique_id')])
            ->select('id', 'invoice_number', 'student_id', 'amount_due', 'invoice_type_id');

        if ($request->type === 'tuition') {
            // Tuition Fee: match by month_year column
            $monthYear = str_pad($monthNum, 2, '0', STR_PAD_LEFT) . '_' . $year;

            $query->where('invoice_type_id', $tuitionTypeId)
                ->where('month_year', $monthYear);
        } else {
            // Other Fees: match by created_at year + month
            if ($tuitionTypeId) {
                $query->where('invoice_type_id', '!=', $tuitionTypeId);
            }

            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum);

            // Optionally filter by specific invoice type
            if ($request->filled('invoice_type_id')) {
                $query->where('invoice_type_id', $request->invoice_type_id);
            }
        }

        $invoices = $query->orderBy('id')->get();

        return response()->json([
            'success'        => true,
            'data'           => $invoices->map(function ($inv, $idx) {
                return [
                    'sl'             => $idx + 1,
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'student_id'     => $inv->student_id,
                    'student_name'   => $inv->student->name ?? 'N/A',
                    'student_uid'    => $inv->student->student_unique_id ?? '',
                    'amount_due'     => (int) $inv->amount_due,
                ];
            })->values(),
            'total_due'      => (int) $invoices->sum('amount_due'),
            'total_invoices' => $invoices->count(),
        ]);
    }
}
