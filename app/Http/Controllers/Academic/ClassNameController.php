<?php
namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('classes.view')) {
            return back()->with('warning', 'No permission to view classes.');
        }

        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        // Get all branches for admin batch tabs
        $branches = $isAdmin
            ? Branch::select('id', 'branch_name')->orderBy('branch_name')->get()
            : collect();

        // Build query with student counts
        $classesQuery = ClassName::query()->withoutGlobalScope('active');

        if ($isAdmin) {
            // For admin: get counts for all branches combined
            $classes = $classesQuery
                ->withCount([
                    'students as students_count',
                    'activeStudents as active_students_count',
                    'inactiveStudents as inactive_students_count',
                ])
                ->latest('updated_at')
                ->orderByDesc('id')
                ->get();

            // Add branch-wise counts for each class (for batch tabs)
            foreach ($classes as $class) {
                $branchCounts = [];
                foreach ($branches as $branch) {
                    $branchCounts[$branch->id] = [
                        'active'   => $class->activeStudents()->where('branch_id', $branch->id)->count(),
                        'inactive' => $class->inactiveStudents()->where('branch_id', $branch->id)->count(),
                        'total'    => $class->students()->where('branch_id', $branch->id)->count(),
                    ];
                }
                $class->branch_counts = $branchCounts;
            }
        } else {
            // For non-admin: get counts only for their branch
            $classes = $classesQuery
                ->withCount([
                    'students as students_count'                  => function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    },
                    'activeStudents as active_students_count'     => function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    },
                    'inactiveStudents as inactive_students_count' => function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    },
                ])
                ->latest('updated_at')
                ->orderByDesc('id')
                ->get();
        }

        // Group by is_active status
        $groupedClasses  = $classes->groupBy('is_active');
        $activeClasses   = $groupedClasses->get(1, collect())->merge($groupedClasses->get(true, collect()));
        $inactiveClasses = $groupedClasses->get(0, collect())->merge($groupedClasses->get(false, collect()));

        // Calculate stats - ONLY from active classes
        $stats = [
            'total_classes'    => $classes->count(),
            'active_classes'   => $activeClasses->count(),
            'inactive_classes' => $inactiveClasses->count(),
            // Regular students = total students from ACTIVE classes only
            'regular_students' => $activeClasses->sum('students_count'),
            // Active students from ACTIVE classes only
            'active_students'  => $activeClasses->sum('active_students_count'),
            // Alumni = students from INACTIVE classes
            'alumni_students'  => $inactiveClasses->sum('students_count'),
        ];

        return view('classnames.index', [
            'active_classes'   => $activeClasses,
            'inactive_classes' => $inactiveClasses,
            'stats'            => $stats,
            'branches'         => $branches,
            'is_admin'         => $isAdmin,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('classes.create')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to create classes.',
            ], 403);
        }

        $validated = $request->validate([
            'class_name_add'    => 'required|string|max:255',
            'class_numeral_add' => [
                'required',
                'regex:/^(0[4-9]|1[0-2])$/',
            ],
            'description_add'   => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, &$classname) {
            $classname = ClassName::create([
                'name'          => $validated['class_name_add'],
                'class_numeral' => $validated['class_numeral_add'],
                'description'   => $validated['description_add'] ?? null,
            ]);

            // Auto-create related sheet group
            $classname->sheet()->create([
                'price' => 2000,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('classes.view')) {
            return redirect()->back()->with('warning', 'No permission to view classes.');
        }

        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        $classname = ClassName::withoutGlobalScope('active')
            ->withCount([
                'activeStudents'   => function ($q) use ($isAdmin, $branchId) {
                    if (! $isAdmin) {
                        $q->where('branch_id', $branchId);
                    }
                },
                'inactiveStudents' => function ($q) use ($isAdmin, $branchId) {
                    if (! $isAdmin) {
                        $q->where('branch_id', $branchId);
                    }
                },
            ])
            ->find($id);

        if (! $classname) {
            return redirect()->route('classnames.index')->with('warning', 'Class not found.');
        }

        return view('classnames.view', compact('classname'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (! auth()->user()->can('classes.edit')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to edit classes.',
            ], 403);
        }

        $class = ClassName::withoutGlobalScope('active')->findOrFail($id);

        // Check if this is a toggle-only request
        if ($request->has('toggle_only') && $request->toggle_only === 'true') {
            $validated = $request->validate([
                'activation_status' => 'required|in:active,inactive',
            ]);

            $class->update([
                'is_active' => $validated['activation_status'] === 'active',
            ]);

            $statusText = $validated['activation_status'] === 'active' ? 'activated' : 'deactivated';

            if (function_exists('clearServerCache')) {
                clearServerCache();
            }

            return response()->json([
                'success' => true,
                'message' => "Class {$statusText} successfully.",
            ]);
        }

        // Full update from edit modal
        $validated = $request->validate([
            'class_name_edit'  => 'required|string|max:255',
            'description_edit' => 'nullable|string|max:1000',
        ]);

        $class->update([
            'name'        => $validated['class_name_edit'],
            'description' => $validated['description_edit'],
        ]);

        if (function_exists('clearServerCache')) {
            clearServerCache();
        }

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (! auth()->user()->can('classes.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to delete classes.',
            ], 403);
        }

        $class = ClassName::withoutGlobalScope('active')->findOrFail($id);

        if ($class->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This class cannot be deleted because it has students.',
            ]);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully.',
        ]);
    }

    /**
     * Get class data by class ID using AJAX request
     */
    public function getClassName(ClassName $class)
    {
        if (! auth()->user()->can('classes.view')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to view classes.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'class_id'          => $class->id,
                'class_name'        => $class->name,
                'is_active'         => $class->is_active,
                'class_numeral'     => $class->class_numeral,
                'class_description' => $class->description,
            ],
        ]);
    }

    /**
     * Get branch-wise student counts for a class (Admin only)
     */
    public function getBranchCounts(ClassName $class)
    {
        if (! auth()->user()->can('classes.view')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to view classes.',
            ], 403);
        }

        if (! auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can view branch-wise data.',
            ], 403);
        }

        $branches     = Branch::select('id', 'branch_name', 'branch_prefix')->orderBy('branch_name')->get();
        $branchCounts = [];

        foreach ($branches as $branch) {
            $branchCounts[$branch->id] = [
                'name'     => $branch->branch_name,
                'prefix'   => $branch->branch_prefix,
                'active'   => $class->activeStudents()->where('branch_id', $branch->id)->count(),
                'inactive' => $class->inactiveStudents()->where('branch_id', $branch->id)->count(),
                'total'    => $class->students()->where('branch_id', $branch->id)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'class_id' => $class->id,
                'all'      => [
                    'active'   => $class->activeStudents()->count(),
                    'inactive' => $class->inactiveStudents()->count(),
                    'total'    => $class->students()->count(),
                ],
                'branches' => $branchCounts,
            ],
        ]);
    }
}
