<?php
namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\SecondaryClass;
use Illuminate\Http\Request;

class SecondaryClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can view special classes.',
            ], 403);
        }

        $secondaryClasses = SecondaryClass::with('class:id,name')
            ->withCount('students')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $secondaryClasses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can create special classes.',
            ], 403);
        }

        $validated = $request->validate([
            'class_id'     => 'required|exists:class_names,id',
            'name'         => 'required|string|max:255',
            'payment_type' => 'required|in:one_time,monthly',
            'fee_amount'   => 'required|numeric|min:0',
        ]);

        $secondaryClass = SecondaryClass::create([
            'class_id'     => $validated['class_id'],
            'name'         => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'fee_amount'   => $validated['fee_amount'],
            'is_active'    => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Special class created successfully.',
            'data'    => $secondaryClass->load('class:id,name')->loadCount('students'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can view special class details.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $secondaryClass->id,
                'class_id'       => $secondaryClass->class_id,
                'name'           => $secondaryClass->name,
                'payment_type'   => $secondaryClass->payment_type,
                'fee_amount'     => $secondaryClass->fee_amount,
                'is_active'      => $secondaryClass->is_active,
                'students_count' => $secondaryClass->students()->count(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can update special classes.',
            ], 403);
        }

        // Toggle activation only
        if ($request->has('toggle_only') && $request->toggle_only === 'true') {
            $secondaryClass->update([
                'is_active' => ! $secondaryClass->is_active,
            ]);

            return response()->json([
                'success'   => true,
                'message'   => $secondaryClass->is_active ? 'Special class activated.' : 'Special class deactivated.',
                'is_active' => $secondaryClass->is_active,
            ]);
        }

        // Full update
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'payment_type' => 'required|in:one_time,monthly',
            'fee_amount'   => 'required|numeric|min:0',
        ]);

        $secondaryClass->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Special class updated successfully.',
            'data'    => $secondaryClass->fresh()->loadCount('students'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can delete special classes.',
            ], 403);
        }

        if ($secondaryClass->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete special class with enrolled students.',
            ]);
        }

        $secondaryClass->update(['deleted_by' => auth()->id()]);
        $secondaryClass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Special class deleted successfully.',
        ]);
    }

    /**
     * Get secondary classes by parent class ID
     */
    public function getByClass($classId)
    {
        $secondaryClasses = SecondaryClass::where('class_id', $classId)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $secondaryClasses,
        ]);
    }
}
