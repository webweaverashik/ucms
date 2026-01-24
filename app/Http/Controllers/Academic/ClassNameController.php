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

        $classNumeral       = $request->input('class_numeral_add');
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

        // Get branch-wise student counts for admin tabs
        $branchStudentCounts = [];
        if ($isAdmin) {
            foreach ($branches as $branch) {
                $branchStudentCounts[$branch->id] = $classname->students()
                    ->where('branch_id', $branch->id)
                    ->count();
            }
        }

        return view('classnames.view', compact('classname', 'branches', 'isAdmin', 'branchStudentCounts'));
    }

    /**
     * Get students for DataTable (AJAX server-side processing)
     */
    public function getStudentsAjax(Request $request, string $classname)
    {
        if (! auth()->user()->can('classes.view')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to view classes.',
            ], 403);
        }

        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        $class = ClassName::findOrFail($classname);

        // DataTables parameters
        $draw        = $request->input('draw', 1);
        $start       = $request->input('start', 0);
        $length      = $request->input('length', 10);
        $searchValue = $request->input('search.value', '');

        // Custom filters
        $branchFilter = $request->input('branch_id');
        $groupFilter  = $request->input('academic_group');
        $statusFilter = $request->input('status');

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection   = $request->input('order.0.dir', 'asc');

        // Check if checkbox column is present (user has students.deactivate permission and class is active)
        $hasCheckboxColumn = $user->can('students.deactivate') && $class->is_active;

        // Column mapping for ordering (without hidden filter columns - all filtering is server-side)
        // Adjust column indices based on whether checkbox column is present
        if ($hasCheckboxColumn) {
            $columns = [
                0 => 'id',                // Checkbox column - default to id (not orderable)
                1 => 'id',                // Row number - default to id (not orderable)
                2 => 'student_unique_id', // Student name column - order by student_unique_id
                3 => 'academic_group',
                4 => 'batch_id',
                5 => 'created_by',
                6 => 'created_at',
            ];
        } else {
            $columns = [
                0 => 'id',                // Row number - default to id (not orderable)
                1 => 'student_unique_id', // Student name column - order by student_unique_id
                2 => 'academic_group',
                3 => 'batch_id',
                4 => 'created_by',
                5 => 'created_at',
            ];
        }

        $orderColumn = $columns[$orderColumnIndex] ?? 'student_unique_id';

        // Base query
        $query = $class->students()
            ->whereNotNull('student_activation_id')
            ->select([
                'id',
                'student_unique_id',
                'name',
                'academic_group',
                'branch_id',
                'batch_id',
                'class_id',
                'student_activation_id',
                'created_by',
                'created_at',
            ])
            ->with([
                'branch:id,branch_name',
                'createdBy:id,name',
                'studentActivation:id,active_status',
                'batch:id,name',
            ]);

        // Apply branch filter
        if ($isAdmin) {
            if ($branchFilter) {
                $query->where('branch_id', $branchFilter);
            }
        } else {
            // Non-admin users can only see their branch
            $query->where('branch_id', $branchId);
        }

        // Apply academic group filter
        if ($groupFilter) {
            // Remove 'ucms_' prefix if present
            $group = str_replace('ucms_', '', $groupFilter);
            $query->where('academic_group', $group);
        }

        // Apply status filter
        if ($statusFilter) {
            if ($statusFilter === 'active') {
                $query->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'));
            } elseif ($statusFilter === 'suspended' || $statusFilter === 'inactive') {
                $query->whereHas('studentActivation', fn($q) => $q->where('active_status', 'inactive'));
            }
        }

        // Get total records before filtering
        $totalRecords = $class->students()
            ->whereNotNull('student_activation_id')
            ->when(! $isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->when($isAdmin && $branchFilter, fn($q) => $q->where('branch_id', $branchFilter))
            ->count();

        // Apply search
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('student_unique_id', 'like', "%{$searchValue}%")
                    ->orWhere('academic_group', 'like', "%{$searchValue}%")
                    ->orWhereHas('batch', fn($bq) => $bq->where('name', 'like', "%{$searchValue}%"))
                    ->orWhereHas('createdBy', fn($cq) => $cq->where('name', 'like', "%{$searchValue}%"));
            });
        }

        // Get filtered count
        $filteredRecords = $query->count();

        // Apply ordering and pagination
        $students = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        // Format data for DataTables
        $data = [];
        foreach ($students as $index => $student) {
            $isActive = optional($student->studentActivation)->active_status === 'active';

            // Build action menu HTML (pass class active status)
            $actionHtml = $this->buildStudentActionMenu($student, $isActive, $class->is_active);

            // Build group badge
            $groupBadge = $this->buildGroupBadge($student->academic_group);

            // Build student name with link
            $studentNameHtml = $this->buildStudentNameHtml($student, $isActive);

            // Build checkbox HTML
            $checkboxHtml = '<div class="form-check form-check-sm form-check-custom form-check-solid">
                <input class="form-check-input student-checkbox" type="checkbox" value="' . $student->id . '"
                    data-student-id="' . $student->id . '"
                    data-student-name="' . e($student->name) . '"
                    data-is-active="' . ($isActive ? '1' : '0') . '" />
            </div>';

            $data[] = [
                'DT_RowId'          => 'student_row_' . $student->id,
                'DT_RowAttr'        => [
                    'data-branch-id'  => $student->branch_id,
                    'data-student-id' => $student->id,
                ],
                'checkbox'          => $checkboxHtml,
                'row_number'        => $start + $index + 1,
                'student_name'      => $studentNameHtml,
                'academic_group'    => $groupBadge,
                'batch'             => $student->batch->name ?? '-',
                'created_by'        => $student->createdBy->name ?? '-',
                'created_at'        => $student->created_at->format('d-M-Y'),
                'actions'           => $actionHtml,
                // Additional data for toggle activation modal
                'student_id'        => $student->id,
                'student_unique_id' => $student->student_unique_id,
                'is_active'         => $isActive,
            ];
        }

        return response()->json([
            'draw'            => (int) $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
            'class_is_active' => $class->is_active,
        ]);
    }

    /**
     * Build student name HTML for DataTable
     */
    private function buildStudentNameHtml($student, $isActive)
    {
        $linkClass = $isActive ? 'text-gray-800 text-hover-primary' : 'text-danger';
        $tooltip   = $isActive ? '' : 'title="Inactive Student" data-bs-toggle="tooltip"';

        return '
            <div class="d-flex align-items-center">
                <div class="d-flex flex-column text-start">
                    <a href="' . route('students.show', $student->id) . '" class="' . $linkClass . '" ' . $tooltip . '>
                        ' . e($student->name) . '
                    </a>
                    <span class="fw-bold fs-base">' . e($student->student_unique_id) . '</span>
                </div>
            </div>';
    }

    /**
     * Build group badge HTML
     */
    private function buildGroupBadge($group)
    {
        $badges = [
            'Science'  => 'info',
            'Commerce' => 'primary',
            'Arts'     => 'warning',
        ];

        if (isset($badges[$group])) {
            return '<span class="badge badge-pill badge-' . $badges[$group] . '">' . e($group) . '</span>';
        }

        return '<span class="text-muted">-</span>';
    }

    /**
     * Build action menu HTML for student
     * @param bool $classIsActive - Whether the parent class is active (not alumni)
     */
    private function buildStudentActionMenu($student, $isActive, $classIsActive = true)
    {
        $user            = auth()->user();
        $canDeactivate   = $user->can('students.deactivate');
        $canDownloadForm = $user->can('students.form.download');
        $canEdit         = $user->can('students.edit');

        $menuItems = '';

        // Only show activate/deactivate if class is active (not alumni)
        if ($canDeactivate && $classIsActive) {
            if ($isActive) {
                $menuItems .= '
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link text-hover-warning px-3 toggle-activation-btn"
                            data-student-unique-id="' . e($student->student_unique_id) . '"
                            data-student-name="' . e($student->name) . '"
                            data-student-id="' . $student->id . '"
                            data-active-status="active">
                            <i class="bi bi-person-slash fs-2 me-2"></i> Deactivate
                        </a>
                    </div>';
            } else {
                $menuItems .= '
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link text-hover-success px-3 toggle-activation-btn"
                            data-student-unique-id="' . e($student->student_unique_id) . '"
                            data-student-name="' . e($student->name) . '"
                            data-student-id="' . $student->id . '"
                            data-active-status="inactive">
                            <i class="bi bi-person-check fs-3 me-2"></i> Activate
                        </a>
                    </div>';
            }
        }

        if ($canDownloadForm && $isActive) {
            $menuItems .= '
                <div class="menu-item px-3">
                    <a href="' . route('students.download', $student->id) . '" class="menu-link text-hover-primary px-3" target="_blank">
                        <i class="bi bi-download fs-3 me-2"></i> Download
                    </a>
                </div>';
        }

        if ($canEdit) {
            $menuItems .= '
                <div class="menu-item px-3">
                    <a href="' . route('students.edit', $student->id) . '" class="menu-link text-hover-primary px-3">
                        <i class="ki-outline ki-pencil fs-3 me-2"></i> Edit Student
                    </a>
                </div>';
        }

        if (empty($menuItems)) {
            return '<span class="text-muted">-</span>';
        }

        return '
            <a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                Actions <i class="ki-outline ki-down fs-5 m-0"></i>
            </a>
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">
                ' . $menuItems . '
            </div>';
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

    /**
     * Get class stats via AJAX
     */
    public function getStats(ClassName $classname)
    {
        if (! auth()->user()->can('classes.view')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to view classes.',
            ], 403);
        }

        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->isAdmin();

        $activeCount = $classname->activeStudents()
            ->when(! $isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->count();
        $inactiveCount = $classname->inactiveStudents()
            ->when(! $isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->count();
        $subjectsCount = $classname->subjects()->count();

        return response()->json([
            'success' => true,
            'stats'   => [
                'total'    => $activeCount + $inactiveCount,
                'active'   => $activeCount,
                'inactive' => $inactiveCount,
                'subjects' => $subjectsCount,
            ],
        ]);
    }

    /**
     * Get subjects for a class via AJAX
     */
    public function getSubjectsAjax(ClassName $classname)
    {
        if (! auth()->user()->can('classes.view')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission to view classes.',
            ], 403);
        }

        $user           = auth()->user();
        $manageSubjects = $user->can('subjects.manage');

        // Eager load subjects with student count
        $subjects = $classname->subjects()
            ->withCount('students')
            ->get();

        // Group subjects by academic_group
        $groupedSubjects = $subjects->groupBy('academic_group');
        $totalSubjects   = $subjects->count();

        // Build HTML for each group
        $groupsHtml = [];
        foreach ($groupedSubjects as $group => $groupSubjects) {
            $groupsHtml[] = $this->buildSubjectGroupHtml($group, $groupSubjects, $manageSubjects, $classname->is_active);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'total_subjects' => $totalSubjects,
                'groups_html'    => implode('', $groupsHtml),
                'is_empty'       => $totalSubjects === 0,
                'empty_html'     => $totalSubjects === 0 ? $this->buildEmptySubjectsHtml($manageSubjects, $classname->is_active) : '',
            ],
        ]);
    }

    /**
     * Build HTML for a subject group
     */
    private function buildSubjectGroupHtml($group, $subjects, $manageSubjects, $classIsActive)
    {
        $groupIcon = match ($group) {
            'Science'  => 'ki-flask',
            'Commerce' => 'ki-chart-line-up',
            'Arts'     => 'ki-paintbucket',
            default    => 'ki-abstract-26',
        };

        $subjectIcon = match ($group) {
            'Science'  => 'ki-flask',
            'Commerce' => 'ki-chart-pie-simple',
            'Arts'     => 'ki-brush',
            default    => 'ki-book',
        };

        $iconClass = strtolower($group ?? 'general');

        $subjectsHtml = '';
        foreach ($subjects as $subject) {
            $subjectsHtml .= $this->buildSubjectCardHtml($subject, $iconClass, $subjectIcon, $manageSubjects, $classIsActive);
        }

        return '
        <div class="academic-group-section">
            <div class="group-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="ki-outline ' . $groupIcon . ' fs-3 me-2 text-white"></i>
                    <h5 class="mb-0 fw-bold">' . e($group ?? 'General') . ' Group</h5>
                </div>
                <span class="subjects-count fs-7 fw-semibold">
                    <i class="ki-outline ki-book-open fs-6 me-1"></i>
                    ' . $subjects->count() . ' subjects
                </span>
            </div>
            <div class="p-4">
                <div class="row g-4">
                    ' . $subjectsHtml . '
                </div>
            </div>
        </div>';
    }

    /**
     * Build HTML for a single subject card
     */
    private function buildSubjectCardHtml($subject, $iconClass, $subjectIcon, $manageSubjects, $classIsActive)
    {
        $actionsHtml = '';
        if ($manageSubjects && $classIsActive) {
            $deleteButton = '';
            if ($subject->students_count == 0) {
                $deleteButton = '
                    <button type="button" class="btn btn-icon btn-sm action-delete delete-subject"
                        data-subject-id="' . $subject->id . '"
                        data-bs-toggle="tooltip" title="Delete Subject">
                        <i class="ki-outline ki-trash fs-5"></i>
                    </button>';
            }

            $actionsHtml = '
                <div class="subject-actions d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-icon btn-sm action-save check-icon d-none"
                        data-bs-toggle="tooltip" title="Save">
                        <i class="ki-outline ki-check fs-4"></i>
                    </button>
                    <button type="button" class="btn btn-icon btn-sm action-cancel cancel-icon d-none"
                        data-bs-toggle="tooltip" title="Cancel">
                        <i class="ki-outline ki-cross fs-4"></i>
                    </button>
                    <button type="button" class="btn btn-icon btn-sm action-edit edit-icon"
                        data-bs-toggle="tooltip" title="Edit Subject">
                        <i class="ki-outline ki-pencil fs-5"></i>
                    </button>
                    ' . $deleteButton . '
                </div>';
        }

        return '
        <div class="col-md-6 col-xl-4">
            <div class="subject-card subject-editable" data-id="' . $subject->id . '">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1 me-2">
                        <div class="subject-icon ' . $iconClass . ' me-3">
                            <i class="ki-outline ' . $subjectIcon . '"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <span class="subject-title subject-text fs-6 d-block text-truncate">
                                ' . e($subject->name) . '
                            </span>
                            <input type="text" class="subject-input form-control form-control-sm d-none fs-6"
                                value="' . e($subject->name) . '" />
                            <span class="text-muted fs-8">
                                <i class="ki-outline ki-people fs-8 me-1"></i>
                                ' . $subject->students_count . ' students enrolled
                            </span>
                        </div>
                    </div>
                    ' . $actionsHtml . '
                </div>
            </div>
        </div>';
    }

    /**
     * Build empty state HTML for subjects
     */
    private function buildEmptySubjectsHtml($manageSubjects, $classIsActive)
    {
        $addButton = '';
        if ($manageSubjects && $classIsActive) {
            $addButton = '
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_subject">
                    <i class="ki-outline ki-plus fs-3 me-1"></i> Add First Subject
                </a>';
        }

        return '
        <div class="text-center py-15">
            <div class="empty-state-icon">
                <i class="ki-outline ki-book-open"></i>
            </div>
            <h4 class="text-gray-800 fw-bold mb-3">No Subjects Added Yet</h4>
            <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                Start by adding your first subject for this class. Subjects help organize the curriculum for students.
            </p>
            ' . $addButton . '
        </div>';
    }
}