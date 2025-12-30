<?php
namespace App\Http\Controllers\Cost;

use App\Http\Controllers\Controller;
use App\Models\Cost\Cost;
use App\Models\Cost\CostEntry;
use App\Models\Cost\CostType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $query = Cost::with([
            'branch:id,branch_name,branch_prefix',
            'createdBy:id,name',
            'entries.costType:id,name',
        ])->forBranch($branchId);

        if ($request->start_date && $request->end_date) {
            $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->toDateString();
            $endDate   = Carbon::createFromFormat('d-m-Y', $request->end_date)->toDateString();
            $query->betweenDates($startDate, $endDate);
        }

        $costs = $query->orderBy('cost_date', 'desc')->get();

        // Add total_amount to each cost
        $costs->each(function ($cost) {
            $cost->total_amount = $cost->totalAmount();
        });

        return response()->json([
            'success' => true,
            'data'    => $costs,
        ]);
    }

    /**
     * Get active cost types for Tagify.
     */
    public function getCostTypes(): JsonResponse
    {
        $costTypes = CostType::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $costTypes,
        ]);
    }

    /**
     * Store a newly created cost with entries.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cost_date'              => 'required|date_format:d-m-Y',
            'branch_id'              => 'nullable|exists:branches,id',
            'entries'                => 'required|array|min:1',
            'entries.*.cost_type_id' => 'required|exists:cost_types,id',
            'entries.*.amount'       => 'required|integer|min:1',
        ]);

        $user     = Auth::user();
        $branchId = $user->branch_id ?: $request->branch_id;

        if (! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Branch is required',
            ], 422);
        }

        $costDate = Carbon::createFromFormat('d-m-Y', $request->cost_date)->toDateString();

        // Check if cost already exists for this date and branch
        $existingCost = Cost::where('cost_date', $costDate)
            ->where('branch_id', $branchId)
            ->first();

        if ($existingCost) {
            return response()->json([
                'success' => false,
                'message' => 'A cost record already exists for this date. Please edit the existing record.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create cost record
            $cost = Cost::create([
                'cost_date'  => $costDate,
                'branch_id'  => $branchId,
                'created_by' => $user->id,
            ]);

            // Create cost entries
            foreach ($request->entries as $entry) {
                CostEntry::create([
                    'cost_id'      => $cost->id,
                    'cost_type_id' => $entry['cost_type_id'],
                    'amount'       => $entry['amount'],
                ]);
            }

            DB::commit();

            $cost->load([
                'branch:id,branch_name,branch_prefix',
                'createdBy:id,name',
                'entries.costType:id,name',
            ]);
            $cost->total_amount = $cost->totalAmount();

            return response()->json([
                'success' => true,
                'message' => 'Cost added successfully',
                'data'    => $cost,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified cost.
     */
    public function show(Cost $cost): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $cost->load([
            'branch:id,branch_name,branch_prefix',
            'createdBy:id,name',
            'entries.costType:id,name',
        ]);
        $cost->total_amount = $cost->totalAmount();

        return response()->json([
            'success' => true,
            'data'    => $cost,
        ]);
    }

    /**
     * Update the specified cost entries.
     */
    public function update(Request $request, Cost $cost): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'entries'          => 'required|array|min:1',
            'entries.*.id'     => 'required|exists:cost_entries,id',
            'entries.*.amount' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Update existing entries only
            foreach ($request->entries as $entryData) {
                $entry = CostEntry::where('id', $entryData['id'])
                    ->where('cost_id', $cost->id)
                    ->first();

                if ($entry) {
                    $entry->update(['amount' => $entryData['amount']]);
                }
            }

            DB::commit();

            $cost->load([
                'branch:id,branch_name,branch_prefix',
                'createdBy:id,name',
                'entries.costType:id,name',
            ]);
            $cost->total_amount = $cost->totalAmount();

            return response()->json([
                'success' => true,
                'message' => 'Cost updated successfully',
                'data'    => $cost,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified cost.
     */
    public function destroy(Cost $cost): JsonResponse
    {
        $user = Auth::user();

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Delete all entries first
            $cost->entries()->delete();

            // Delete cost
            $cost->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a specific cost entry.
     */
    public function destroyEntry(CostEntry $entry): JsonResponse
    {
        $user = Auth::user();
        $cost = $entry->cost;

        // Check if user has access to this cost
        if ($user->branch_id && $cost->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Check if this is the last entry
        $entryCount = $cost->entries()->count();
        if ($entryCount <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last entry. Delete the entire cost record instead.',
            ], 422);
        }

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Entry deleted successfully',
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
            ->with([
                'branch:id,branch_name,branch_prefix',
                'createdBy:id,name',
                'entries.costType:id,name',
            ])
            ->first();

        if ($cost) {
            $cost->total_amount = $cost->totalAmount();
        }

        return response()->json([
            'success' => true,
            'exists'  => $cost !== null,
            'data'    => $cost,
        ]);
    }
}