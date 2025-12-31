<?php
namespace App\Http\Controllers\Cost;

use App\Http\Controllers\Controller;
use App\Models\Cost\CostType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CostTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $costTypes = CostType::withCount('costEntries')->get();

        return view('settings.cost-types.index', compact('costTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:cost_types,name',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $costType = CostType::create([
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cost type created successfully!',
            'data'    => $costType->load('costEntries'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CostType $costType)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:cost_types,name,' . $costType->id,
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $costType->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cost type updated successfully!',
            'data'    => $costType->fresh()->loadCount('costEntries'),
        ]);
    }

    /**
     * Toggle active status of the specified resource.
     */
    public function toggleActive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:cost_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid cost type',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $costType = CostType::findOrFail($request->id);
        $costType->update(['is_active' => ! $costType->is_active]);

        return response()->json([
            'success' => true,
            'message' => $costType->is_active ? 'Cost type activated!' : 'Cost type deactivated!',
            'data'    => $costType,
        ]);
    }
}
