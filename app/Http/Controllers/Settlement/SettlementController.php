<?php
namespace App\Http\Controllers\Settlement;

use App\Http\Controllers\Controller;
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
     * Display list of users with pending balances.
     */
    public function index()
    {
        $users = User::where('current_balance', '>', 0)->with('branch')->orderBy('current_balance', 'desc')->get();

        $totalPending = $users->sum('current_balance');

        return view('settlements.index', compact('users', 'totalPending'));
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

            return back()->with('success', "Successfully settled ৳{$request->amount} from {$user->name}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show wallet logs for a specific user.
     */
    public function show(User $user)
    {
        $logs = $user
            ->walletLogs()
            ->with(['paymentTransaction.student', 'creator'])
            ->paginate(20);

        $summary = $this->walletService->getSummary($user);

        return view('settlements.show', compact('user', 'logs', 'summary'));
    }

    /**
     * Get all wallet logs (admin view).
     */
    public function logs(Request $request)
    {
        $query = UserWalletLog::with(['user', 'creator', 'paymentTransaction'])->latest('created_at');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->betweenDates($request->from_date, $request->to_date);
        }

        $logs  = $query->paginate(30);
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('settlements.logs', compact('logs', 'users'));
    }
}
