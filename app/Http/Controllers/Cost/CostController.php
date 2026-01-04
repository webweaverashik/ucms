<?php
namespace App\Http\Controllers\Cost;

use Carbon\Carbon;
use App\Models\Cost\Cost;
use Illuminate\Http\Request;
use App\Models\Cost\CostType;
use App\Models\Cost\CostEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CostController extends Controller
{
    /**
     * Get all active cost types
     */
    public function types(): JsonResponse
    {
        $costTypes = CostType::active()
            ->orderBy('name')
            ->select('id', 'name', 'description')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $costTypes,
        ]);
    }

    /**
     * Check if cost exists for today
     */
    public function checkToday(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $exists = Cost::where('branch_id', $request->branch_id)
            ->whereDate('cost_date', Carbon::today())
            ->exists();

        return response()->json([
            'success' => true,
            'exists'  => $exists,
        ]);
    }

    /**
     * Store a new cost with entries
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cost_date'              => 'required|date_format:d-m-Y',
            'branch_id'              => 'required|exists:branches,id',
            'entries'                => 'required|array|min:1',
            'entries.*.cost_type_id' => 'required|exists:cost_types,id',
            'entries.*.amount'       => 'required|integer|min:1',
            'entries.*.description'  => 'nullable|string|max:255',
        ]);

        $user     = Auth::user();
        $costDate = Carbon::createFromFormat('d-m-Y', $request->cost_date);

        // Non-admin users can only add for their branch
        if (! $user->isAdmin() && $user->branch_id != $request->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only add costs for your own branch.',
            ], 403);
        }

        // Check if cost already exists for this date and branch
        $existingCost = Cost::where('branch_id', $request->branch_id)
            ->whereDate('cost_date', $costDate)
            ->first();

        if ($existingCost) {
            return response()->json([
                'success' => false,
                'message' => 'Cost record already exists for this date and branch.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $cost = Cost::create([
                'branch_id'  => $request->branch_id,
                'cost_date'  => $costDate,
                'created_by' => $user->id,
            ]);

            foreach ($request->entries as $entry) {
                CostEntry::create([
                    'cost_id'      => $cost->id,
                    'cost_type_id' => $entry['cost_type_id'],
                    'amount'       => $entry['amount'],
                    'description'  => $entry['description'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost added successfully!',
                'data'    => $cost->load('entries.costType'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single cost with entries
     */
    public function show(int $id): JsonResponse
    {
        $cost = Cost::with(['branch:id,branch_name,branch_prefix', 'createdBy:id,name', 'entries.costType:id,name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $cost,
        ]);
    }

    /**
     * Update cost entries (amounts only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'entries'          => 'required|array|min:1',
            'entries.*.id'     => 'required|exists:cost_entries,id',
            'entries.*.amount' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can update cost records.',
            ], 403);
        }

        $cost = Cost::findOrFail($id);

        DB::beginTransaction();

        try {
            foreach ($request->entries as $entryData) {
                $entry = CostEntry::where('id', $entryData['id'])
                    ->where('cost_id', $cost->id)
                    ->first();

                if ($entry) {
                    $entry->update(['amount' => $entryData['amount']]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost updated successfully!',
                'data'    => $cost->fresh()->load('entries.costType'),
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
     * Add a new entry to existing cost
     */
    public function addEntry(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'cost_type_id' => 'required|exists:cost_types,id',
            'amount'       => 'required|integer|min:1',
            'description'  => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can add entries.',
            ], 403);
        }

        $cost     = Cost::findOrFail($id);
        $costType = CostType::findOrFail($request->cost_type_id);

        // Check for duplicate (except for "Others" type which can have multiple)
        if (strtolower($costType->name) !== 'others') {
            $existingEntry = CostEntry::where('cost_id', $cost->id)
                ->where('cost_type_id', $request->cost_type_id)
                ->exists();

            if ($existingEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'This cost type already exists for this record.',
                ], 422);
            }
        }

        try {
            $entry = CostEntry::create([
                'cost_id'      => $cost->id,
                'cost_type_id' => $request->cost_type_id,
                'amount'       => $request->amount,
                'description'  => $request->description ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Entry added successfully!',
                'data'    => $entry->load('costType'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a cost entry
     */
    public function deleteEntry(int $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete entries.',
            ], 403);
        }

        $entry = CostEntry::findOrFail($id);
        $cost  = $entry->cost;

        // Check if this is the last entry
        if ($cost->entries()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last entry. Delete the entire cost record instead.',
            ], 422);
        }

        try {
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entry deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a cost record with all entries
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete cost records.',
            ], 403);
        }

        $cost = Cost::findOrFail($id);

        DB::beginTransaction();

        try {
            // Delete all entries first
            $cost->entries()->delete();

            // Delete the cost
            $cost->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost record deleted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cost: ' . $e->getMessage(),
            ], 500);
        }
    }
}
