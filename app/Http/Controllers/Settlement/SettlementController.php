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
        $branches = Branch::orderBy('branch_name')->get();

        // Get admin users (shown in all tabs)
        $adminUsers = User::with('branch')->role('admin')->orderBy('current_balance', 'desc')->orderBy('name')->get();

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

        return view('settlements.index', compact('branches', 'usersByBranch', 'adminUsers', 'usersWithBalance', 'totalPending'));
    }

    /**
     * Show settlement form for a specific user.
     */
    public function create(User $user)
    {
        $recentLogs = $user->walletLogs()->with('creator')->limit(10)->get();

        return view('settlements.create', compact('user', 'recentLogs'));
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
            $this->walletService->recordSettlement(user: $user, amount: $request->amount, description: $request->notes);

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
                return response()->json(
                    [
                        'success' => false,
                        'message' => $e->getMessage(),
                    ],
                    422,
                );
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show wallet logs for a specific user.
     */
    public function show(User $user)
    {
        // Load all logs for client-side DataTable
        $logs = $user
            ->walletLogs()
            ->with(['paymentTransaction.student', 'creator'])
            ->get();

        $summary = $this->walletService->getSummary($user);

        return view('settlements.show', compact('user', 'logs', 'summary'));
    }

    /**
     * Get all wallet logs (admin view).
     */
    public function logs(Request $request)
    {
        // Load all logs for client-side DataTable
        $logs = UserWalletLog::with(['user', 'creator', 'paymentTransaction'])
            ->latest('created_at')
            ->get();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('settlements.logs', compact('logs', 'users'));
    }
}