<?php
namespace App\Http\Controllers\Settlement;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserWalletLog;
use App\Services\WalletService;
use Illuminate\Http\Request;

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

        // Get admin users (shown in all tabs)
        $adminUsers = User::with('branch')
            ->role('admin')
            ->orderBy('current_balance', 'desc')
            ->orderBy('name')
            ->get();

        // Group users by branch (excluding admins to avoid duplicates in counting)
        $usersByBranch = [];
        $allUsers      = collect();

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

            // Merge with admin users for display
            $usersByBranch[$branch->id] = $adminUsers->merge($branchUsers)->sortByDesc('current_balance');
            $allUsers                   = $allUsers->merge($branchUsers);
        }

        // Add admin users to allUsers for total calculations
        $allUsers = $allUsers->merge($adminUsers);

        $usersWithBalance = $allUsers->where('current_balance', '>', 0)->count();
        $totalPending     = $allUsers->sum('current_balance');

        return view('settlements.index', compact(
            'branches',
            'usersByBranch',
            'adminUsers',
            'usersWithBalance',
            'totalPending'
        ));
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
    public function show(User $user)
    {
        if (! auth()->user()->isAdmin()) {
            return back()->with('error', 'Access to this page is restricted.');
        }

        // Load all logs for client-side DataTable
        $logs = $user->walletLogs()
            ->with(['paymentTransaction.student', 'creator'])
            ->get();

        $summary = $this->walletService->getSummary($user);

        return view('settlements.show', compact('user', 'logs', 'summary'));
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
            $message = "Cannot decrease balance by ৳" . number_format(abs($amount), 2) .
            ". Current balance is only ৳" . number_format($user->current_balance, 2);

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

        // Load all logs for client-side DataTable
        $logs = UserWalletLog::with(['user:id,name', 'creator:id,name', 'paymentTransaction:id,payment_invoice_id'])
            ->latest('created_at')
            ->get();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('settlements.logs', compact('logs', 'users'));
    }
}
