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

        $classNumeral = $request->input('class_numeral_add');
        $requiresYearPrefix = in_array($classNumeral, ['10', '11', '12']);

        // Calculate valid year prefix range (25 to current year's last 2 digits)
        $currentYearPrefix = (int) date('y');
        $validYearPrefixes = array_map(fn($i) => str_pad($i, 2, '0', STR_PAD_LEFT), range(25, $currentYearPrefix));

        $validationRules = [
            'class_name_add'    => 'required|string|max:255',
            'class_numeral_add' => [
                'required',
                'regex:/^(0[4-9]|1[0-2])$/',
            ],
            'description_add'   => 'nullable|string|max:1000',
        ];

        // Year prefix is required only for classes 10, 11, 12
        if ($requiresYearPrefix) {
            $validationRules['year_prefix_add'] = [
                'required',
                'string',
                'in:' . implode(',', $validYearPrefixes),
            ];
        } else {
            $validationRules['year_prefix_add'] = 'nullable';
        }

        $validated = $request->validate($validationRules, [
            'year_prefix_add.in'       => 'Please select a valid year prefix.',
            'year_prefix_add.required' => 'Year prefix is required for Class 10, 11, and 12.',
        ]);

        DB::transaction(function () use ($validated, $requiresYearPrefix, &$classname) {
            $classname = ClassName::create([
                'name'          => $validated['class_name_add'],
                'year_prefix'   => $requiresYearPrefix ? $validated['year_prefix_add'] : null,
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
     * Optimized to reduce N+1 query issues
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('classes.view')) {
            return redirect()->back()->with('warning', 'No permission to view classes.');
        }

        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        // Get branches for admin tabs
        $branches = $isAdmin
            ? Branch::select('id', 'branch_name', 'branch_prefix')->orderBy('branch_name')->get()
            : collect();

        // Optimized query with eager loading to prevent N+1
        $classname = ClassName::query()
            ->withCount([
                'activeStudents'   => fn($q)   => $q->when(! $isAdmin, fn($q) => $q->where('branch_id', $branchId)),
                'inactiveStudents' => fn($q) => $q->when(! $isAdmin, fn($q) => $q->where('branch_id', $branchId)),
            ])
            ->with([
                // Eager load students with all nested relationships
                'students'         => function ($q) use ($isAdmin, $branchId) {
                    $q->when(! $isAdmin, fn($q) => $q->where('branch_id', $branchId))
                        ->select(['id', 'student_unique_id', 'name', 'academic_group', 'branch_id', 'batch_id', 'class_id', 'student_activation_id', 'created_by', 'created_at'])
                        ->with([
                            'branch:id,branch_name',
                            'createdBy:id,name',
                            'studentActivation:id,active_status',
                            'batch:id,name',
                        ]);
                },
                // Eager load secondary classes with student count
                'secondaryClasses' => function ($q) {
                    $q->withCount('students');
                },
                // Eager load subjects with student count
                'subjects'         => function ($q) {
                    $q->withCount('students');
                },
            ])
            ->findOrFail($id);

        if (! $classname) {
            return redirect()->route('classnames.index')->with('warning', 'Class not found.');
        }

        // Group students by branch for admin (done in PHP to avoid additional queries)
        $studentsByBranch = [];
        if ($isAdmin) {
            $studentsByBranch = $classname->students->groupBy('branch_id');
        }

        return view('classnames.view', compact('classname', 'branches', 'isAdmin', 'studentsByBranch'));
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
        // Determine the effective numeral (could be changing from 11 to 12)
        $effectiveNumeral = $class->class_numeral;
        if ($class->class_numeral === '11' && $request->has('class_numeral_edit') && $request->input('class_numeral_edit') === '12') {
            $effectiveNumeral = '12';
        }

        $requiresYearPrefix = in_array($effectiveNumeral, ['10', '11', '12']);

        // Calculate valid year prefix range (25 to current year's last 2 digits)
        $currentYearPrefix = (int) date('y');
        $validYearPrefixes = array_map(fn($i) => str_pad($i, 2, '0', STR_PAD_LEFT), range(25, $currentYearPrefix));

        $validationRules = [
            'class_name_edit'  => 'required|string|max:255',
            'description_edit' => 'nullable|string|max:1000',
        ];

        // Year prefix is required only for classes 10, 11, 12
        if ($requiresYearPrefix) {
            $validationRules['year_prefix_edit'] = [
                'required',
                'string',
                'in:' . implode(',', $validYearPrefixes),
            ];
        } else {
            $validationRules['year_prefix_edit'] = 'nullable';
        }

        // Only validate class_numeral_edit if current numeral is 11 and new value is provided
        if ($class->class_numeral === '11' && $request->has('class_numeral_edit')) {
            $validationRules['class_numeral_edit'] = [
                'required',
                'in:11,12',
            ];
        }

        $validated = $request->validate($validationRules, [
            'year_prefix_edit.in'       => 'Please select a valid year prefix.',
            'year_prefix_edit.required' => 'Year prefix is required for Class 10, 11, and 12.',
            'class_numeral_edit.in'     => 'Class numeral can only be changed from 11 to 12.',
        ]);

        $updateData = [
            'name'        => $validated['class_name_edit'],
            'year_prefix' => $requiresYearPrefix ? $validated['year_prefix_edit'] : null,
            'description' => $validated['description_edit'] ?? null,
        ];

        // Only update class_numeral if it's changing from 11 to 12
        if ($class->class_numeral === '11' && isset($validated['class_numeral_edit']) && $validated['class_numeral_edit'] === '12') {
            $updateData['class_numeral'] = '12';
        }

        $class->update($updateData);

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
                'year_prefix'       => $class->year_prefix,
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

        $branches = Branch::select('id', 'branch_name', 'branch_prefix')->orderBy('branch_name')->get();

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