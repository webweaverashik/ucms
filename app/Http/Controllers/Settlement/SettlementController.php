<?php
namespace App\Http\Controllers\Settlement;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserWalletLog;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Display list of all users with their balances grouped by branch.
     */
    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            return back()->with('error', 'Access to this page is restricted.');
        }

        $branches = Branch::orderBy('branch_name')->get();

        // Get admin users
        $adminUsers = User::with('branch')
            ->role('admin')
            ->orderBy('current_balance', 'desc')
            ->orderBy('name')
            ->get();

        // Calculate branch-specific totals for admin users
        $adminBranchTotals = $this->getAdminBranchTotals($adminUsers->pluck('id')->toArray(), $branches->pluck('id')->toArray());

        // Group users by branch
        $usersByBranch   = [];
        $branchOnlyUsers = [];
        $branchTotals    = []; // Store branch totals including admin contributions
        $allUsers        = collect();

        foreach ($branches as $branch) {
            // Get non-admin users for this branch
            $branchUsers = User::with('branch')
                ->where('branch_id', $branch->id)
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'admin');
                })
                ->orderBy('current_balance', 'desc')
                ->orderBy('name')
                ->get();

            // Store branch-only users for reference
            $branchOnlyUsers[$branch->id] = $branchUsers;

            // Calculate admin's contribution to this branch's pending balance
            $adminBranchPending = 0;
            foreach ($adminUsers as $admin) {
                $key                 = $admin->id . '_' . $branch->id;
                $adminCollected      = $adminBranchTotals[$key]['collected'] ?? 0;
                $adminSettled        = $adminBranchTotals[$key]['settled'] ?? 0;
                $adminBranchPending += ($adminCollected - $adminSettled);
            }

            // Branch total = non-admin users balance + admin's branch-specific pending
            $branchTotals[$branch->id] = $branchUsers->sum('current_balance') + $adminBranchPending;

            // Create admin users with branch-specific totals for this branch
            $adminUsersForBranch = $adminUsers->map(function ($admin) use ($branch, $adminBranchTotals) {
                $adminClone                         = clone $admin;
                $key                                = $admin->id . '_' . $branch->id;
                $adminClone->branch_total_collected = $adminBranchTotals[$key]['collected'] ?? 0;
                $adminClone->branch_total_settled   = $adminBranchTotals[$key]['settled'] ?? 0;
                return $adminClone;
            });

            // Merge with admin users for display
            $usersByBranch[$branch->id] = $adminUsersForBranch->merge($branchUsers)->sortByDesc('current_balance');

            $allUsers = $allUsers->merge($branchUsers);
        }

        // Add admin users to allUsers for total calculations
        $allUsers = $allUsers->merge($adminUsers);

        $usersWithBalance = $allUsers->where('current_balance', '>', 0)->count();
        $totalPending     = $allUsers->sum('current_balance');

        return view('settlements.index', compact(
            'branches',
            'usersByBranch',
            'branchOnlyUsers',
            'branchTotals',
            'adminUsers',
            'usersWithBalance',
            'totalPending'
        ));
    }

    /**
     * Get branch-specific collection/settlement totals for admin users.
     */
    protected function getAdminBranchTotals(array $adminIds, array $branchIds): array
    {
        if (empty($adminIds) || empty($branchIds)) {
            return [];
        }

        // Get collection totals by branch for each admin
        $collections = DB::table('user_wallet_logs')
            ->join('payment_transactions', 'user_wallet_logs.payment_transaction_id', '=', 'payment_transactions.id')
            ->join('students', 'payment_transactions.student_id', '=', 'students.id')
            ->whereIn('user_wallet_logs.user_id', $adminIds)
            ->where('user_wallet_logs.type', 'collection')
            ->whereIn('students.branch_id', $branchIds)
            ->groupBy('user_wallet_logs.user_id', 'students.branch_id')
            ->select(
                'user_wallet_logs.user_id',
                'students.branch_id',
                DB::raw('SUM(user_wallet_logs.amount) as total_collected')
            )
            ->get();

        // Get settlement totals by branch for each admin (settlements don't have payment_transaction_id typically)
        // For settlements, we'll use a different approach - settlements made by admin for users of that branch
        // Or we can track settlements based on when they were made in context of a branch
        // For now, settlements will show the global total since they're not branch-specific

        $result = [];

        // Initialize with zeros
        foreach ($adminIds as $adminId) {
            foreach ($branchIds as $branchId) {
                $key          = $adminId . '_' . $branchId;
                $result[$key] = [
                    'collected' => 0,
                    'settled'   => 0,
                ];
            }
        }

        // Fill in collection totals
        foreach ($collections as $row) {
            $key                       = $row->user_id . '_' . $row->branch_id;
            $result[$key]['collected'] = (float) $row->total_collected;
        }

        return $result;
    }

    /**
     * Process settlement from a user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|numeric|min:1',
            'notes'   => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($request->user_id);

        try {
            $this->walletService->recordSettlement(
                user: $user,
                amount: $request->amount,
                description: $request->notes
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully settled ৳{$request->amount} from {$user->name}",
                    'new_balance' => $user->fresh()->current_balance,
                ]);
            }

            return back()->with('success', "Successfully settled ৳{$request->amount} from {$user->name}");
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show wallet logs for a specific user.
     */
    public function show(string $id)
    {
        if (! auth()->user()->isAdmin()) {
            return back()->with('error', 'Access to this page is restricted.');
        }

        $user = User::find($id);

        if (! $user) {
            return back()->with('error', 'User not found.');
        }

        $summary = $this->walletService->getSummary($user);

        return view('settlements.show', compact('user', 'summary'));
    }

    /**
     * Get wallet logs for a specific user via AJAX.
     */
    public function showData(Request $request, User $user)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $user->walletLogs()
            ->with(['paymentTransaction:id,payment_invoice_id', 'creator:id,name']);

        // Apply type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Get total count before pagination
        $totalRecords    = $user->walletLogs()->count();
        $filteredRecords = $query->count();

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'created_at', 'type', 'description', 'amount', 'old_balance', 'new_balance', 'created_by'];
        $orderBy = $columns[$orderColumn] ?? 'created_at';

        $query->orderBy($orderBy, $orderDir);

        // Apply pagination
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);

        $logs = $query->skip($start)->take($length)->get();

        $data = $logs->map(function ($log, $index) use ($start) {
            return [
                'DT_RowIndex'            => $start + $index + 1,
                'id'                     => $log->id,
                'created_at'             => $log->created_at->format('d M, Y'),
                'created_at_time'        => $log->created_at->format('h:i A'),
                'created_at_raw'         => $log->created_at->timestamp,
                'type'                   => $log->type,
                'type_label'             => $log->getTypeLabel(),
                'description'            => $log->description ?? '-',
                'payment_transaction_id' => $log->payment_transaction_id,
                'payment_invoice_id'     => $log->paymentTransaction?->payment_invoice_id,
                'amount'                 => $log->amount,
                'amount_formatted'       => ($log->amount >= 0 ? '+' : '') . '৳' . number_format($log->amount, 0),
                'old_balance'            => $log->old_balance,
                'old_balance_formatted'  => '৳' . number_format($log->old_balance, 0),
                'new_balance'            => $log->new_balance,
                'new_balance_formatted'  => '৳' . number_format($log->new_balance, 0),
                'creator_name'           => $log->creator->name ?? 'System',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw', 1)),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Export wallet logs for a specific user.
     */
    public function showExport(Request $request, User $user)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $user->walletLogs()
            ->with(['paymentTransaction:id,payment_invoice_id', 'creator:id,name']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $data = $logs->map(function ($log, $index) {
            return [
                'sl'          => $index + 1,
                'date'        => $log->created_at->format('d M, Y h:i A'),
                'type'        => $log->getTypeLabel(),
                'description' => $log->description ?? '-',
                'amount'      => $log->amount,
                'old_balance' => $log->old_balance,
                'new_balance' => $log->new_balance,
                'created_by'  => $log->creator->name ?? 'System',
            ];
        });

        return response()->json([
            'success'     => true,
            'data'        => $data,
            'user_name'   => $user->name,
            'exported_at' => now()->format('d M, Y h:i A'),
        ]);
    }

    /**
     * Record an adjustment for a user's wallet.
     */
    public function adjustment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|numeric|not_in:0',
            'reason'  => 'required|string|max:255',
        ]);

        $user   = User::findOrFail($request->user_id);
        $amount = (float) $request->amount;

        // Check if negative adjustment would result in negative balance
        if ($amount < 0 && $user->current_balance < abs($amount)) {
            $message = "Cannot decrease balance by ৳" . number_format(abs($amount), 2) . ". Current balance is only ৳" . number_format($user->current_balance, 2);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        try {
            $this->walletService->recordAdjustment(
                user: $user,
                amount: $amount,
                reason: $request->reason
            );

            $action  = $amount > 0 ? 'increased' : 'decreased';
            $message = "Successfully {$action} {$user->name}'s balance by ৳" . number_format(abs($amount), 2);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'     => true,
                    'message'     => $message,
                    'new_balance' => $user->fresh()->current_balance,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get all wallet logs (admin view).
     */
    public function logs(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return back()->with('error', 'Access to this page is restricted.');
        }

        $users = User::oldest('name')->get(['id', 'name']);

        return view('settlements.logs', compact('users'));
    }

    /**
     * Get all wallet logs via AJAX.
     */
    public function logsData(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = UserWalletLog::with(['user:id,name,photo_url', 'creator:id,name', 'paymentTransaction:id,payment_invoice_id']);

        // Apply user filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('creator', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Get total count before pagination
        $totalRecords    = UserWalletLog::count();
        $filteredRecords = $query->count();

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir    = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'created_at', 'user_id', 'type', 'description', 'amount', 'old_balance', 'new_balance', 'created_by'];
        $orderBy = $columns[$orderColumn] ?? 'created_at';

        $query->orderBy($orderBy, $orderDir);

        // Apply pagination
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);

        $logs = $query->skip($start)->take($length)->get();

        $data = $logs->map(function ($log, $index) use ($start) {
            return [
                'DT_RowIndex'            => $start + $index + 1,
                'id'                     => $log->id,
                'created_at'             => $log->created_at->format('d M, Y'),
                'created_at_time'        => $log->created_at->format('h:i A'),
                'created_at_raw'         => $log->created_at->timestamp,
                'user_id'                => $log->user_id,
                'user_name'              => $log->user->name ?? 'N/A',
                'user_photo'             => $log->user->photo_url ?? null,
                'user_initial'           => strtoupper(substr($log->user->name ?? 'U', 0, 1)),
                'type'                   => $log->type,
                'type_label'             => $log->getTypeLabel(),
                'description'            => $log->description ?? '-',
                'payment_transaction_id' => $log->payment_transaction_id,
                'payment_invoice_id'     => $log->paymentTransaction?->payment_invoice_id,
                'amount'                 => $log->amount,
                'amount_formatted'       => ($log->amount >= 0 ? '+' : '') . '৳' . number_format($log->amount, 0),
                'old_balance'            => $log->old_balance,
                'old_balance_formatted'  => '৳' . number_format($log->old_balance, 0),
                'new_balance'            => $log->new_balance,
                'new_balance_formatted'  => '৳' . number_format($log->new_balance, 0),
                'creator_name'           => $log->creator->name ?? 'System',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw', 1)),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Export all wallet logs.
     */
    public function logsExport(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = UserWalletLog::with(['user:id,name', 'creator:id,name', 'paymentTransaction:id,payment_invoice_id']);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $data = $logs->map(function ($log, $index) {
            return [
                'sl'          => $index + 1,
                'date'        => $log->created_at->format('d M, Y h:i A'),
                'user'        => $log->user->name ?? 'N/A',
                'type'        => $log->getTypeLabel(),
                'description' => $log->description ?? '-',
                'amount'      => $log->amount,
                'old_balance' => $log->old_balance,
                'new_balance' => $log->new_balance,
                'created_by'  => $log->creator->name ?? 'System',
            ];
        });

        return response()->json([
            'success'     => true,
            'data'        => $data,
            'exported_at' => now()->format('d M, Y h:i A'),
        ]);
    }
}
