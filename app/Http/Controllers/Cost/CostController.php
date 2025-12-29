<?php
namespace App\Http\Controllers\Cost;

use App\Http\Controllers\Controller;
use App\Models\Cost\Cost;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostController extends Controller
{
    /**
     * Display a listing of costs.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date_format:d-m-Y',
            'end_date'   => 'nullable|date_format:d-m-Y',
            'branch_id'  => 'nullable|exists:branches,id',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        $query = Cost::with(['branch:id,branch_name,branch_prefix', 'createdBy:id,name'])->forBranch($branchId);

        if ($request->start_date && $request->end_date) {
            $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->toDateString();
            $endDate   = Carbon::createFromFormat('d-m-Y', $request->end_date)->toDateString();
            $query->betweenDates($startDate, $endDate);
        }

        $costs = $query->orderBy('cost_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $costs,
        ]);
    }

    /**
     * Store a newly created cost or update if date exists.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cost_date'   => 'required|date_format:d-m-Y',
            'amount'      => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'nullable|string|max:500',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        if (! $branchId) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Branch is required',
                ],
                422,
            );
        }

        $costDate = Carbon::createFromFormat('d-m-Y', $request->cost_date)->toDateString();

        // Check if cost exists for this date and branch (update or create)
        $cost = Cost::updateOrCreate(
            [
                'cost_date' => $costDate,
                'branch_id' => $branchId,
            ],
            [
                'amount'      => $request->amount,
                'description' => $request->description,
                'created_by'  => $user->id,
            ],
        );

        $cost->load(['branch:id,branch_name,branch_prefix', 'createdBy:id,name']);

        $wasCreated = $cost->wasRecentlyCreated;

        return response()->json(
            [
                'success' => true,
                'message' => $wasCreated ? 'Cost added successfully' : 'Cost updated successfully',
                'data'    => $cost,
            ],
            $wasCreated ? 201 : 200,
        );
    }

    /**
     * Display the specified cost.
     */
    public function show(Cost $cost): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthorized access',
                ],
                403,
            );
        }

        $cost->load(['branch:id,branch_name,branch_prefix', 'createdBy:id,name']);

        return response()->json([
            'success' => true,
            'data'    => $cost,
        ]);
    }

    /**
     * Update the specified cost.
     */
    public function update(Request $request, Cost $cost): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthorized access',
                ],
                403,
            );
        }

        $request->validate([
            'cost_date'   => 'required|date_format:d-m-Y',
            'amount'      => 'required|numeric|min:0.01|max:999999999.99',
            'description' => 'nullable|string|max:500',
        ]);

        $newCostDate = Carbon::createFromFormat('d-m-Y', $request->cost_date)->toDateString();

        // Check if another cost exists for the new date (excluding current)
        $existingCost = Cost::where('cost_date', $newCostDate)->where('branch_id', $cost->branch_id)->where('id', '!=', $cost->id)->first();

        if ($existingCost) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'A cost record already exists for this date',
                ],
                422,
            );
        }

        $cost->update([
            'cost_date'   => $newCostDate,
            'amount'      => $request->amount,
            'description' => $request->description,
        ]);

        $cost->load(['branch:id,branch_name,branch_prefix', 'createdBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Cost updated successfully',
            'data'    => $cost,
        ]);
    }

    /**
     * Remove the specified cost.
     */
    public function destroy(Cost $cost): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Unauthorized access',
                ],
                403,
            );
        }

        $cost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cost deleted successfully',
        ]);
    }

    /**
     * Get cost for a specific date.
     */
    public function getByDate(Request $request): JsonResponse
    {
        $request->validate([
            'date'      => 'required|date_format:d-m-Y',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;
        $costDate = Carbon::createFromFormat('d-m-Y', $request->date)->toDateString();

        $cost = Cost::forBranch($branchId)
            ->where('cost_date', $costDate)
            ->with(['branch:id,branch_name,branch_prefix', 'createdBy:id,name'])
            ->first();

        return response()->json([
            'success' => true,
            'exists'  => $cost !== null,
            'data'    => $cost,
        ]);
    }
}
