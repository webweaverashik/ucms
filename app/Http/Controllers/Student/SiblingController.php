<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiblingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('siblings.view')) {
            return redirect()->back()->with('warning', 'No permission to view siblings.');
        }

        $userBranchId = auth()->user()->branch_id;
        $isAdmin      = auth()->user()->hasRole('admin');

        $students = Student::when($userBranchId != 0, fn($q) => $q->where('branch_id', $userBranchId))
            ->select('id', 'name', 'student_unique_id')
            ->orderBy('student_unique_id')
            ->get();

        $branches = Branch::all();

        return view('siblings.index', compact('branches', 'students', 'isAdmin'));
    }

    /**
     * Get siblings data for DataTable via AJAX.
     */
    public function getData(Request $request)
    {
        if (! auth()->user()->can('siblings.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userBranchId = auth()->user()->branch_id;
        $isAdmin      = auth()->user()->hasRole('admin');
        $branchId     = $request->get('branch_id');

        $query = Sibling::with([
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
        ]);

        // Filter by branch
        if ($isAdmin && $branchId) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $branchId));
        } elseif ($userBranchId != 0) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $userBranchId));
        }

        // Apply search filter
        if ($search = $request->get('search')['value'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('class', 'like', "%{$search}%")
                    ->orWhere('institution_name', 'like', "%{$search}%")
                    ->orWhere('relationship', 'like', "%{$search}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('student_unique_id', 'like', "%{$search}%"));
            });
        }

        // Apply relationship filter
        if ($relationship = $request->get('relationship')) {
            $query->where('relationship', strtolower($relationship));
        }

        // Get total count before pagination
        $totalRecords = Sibling::when($isAdmin && $branchId, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId)))
            ->when($userBranchId != 0 && ! $isAdmin, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('branch_id', $userBranchId)))
            ->count();

        $filteredRecords = $query->count();

        // Apply ordering
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'name', 'relationship', 'relationship', 'student_id', 'class', 'year', 'institution_name', 'relationship', 'branch', 'id'];
        $orderBy = $columns[$orderColumn] ?? 'id';

        if ($orderBy === 'branch') {
            $query->orderBy('id', $orderDir);
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        // Pagination
        $start  = $request->get('start', 0);
        $length = $request->get('length', 10);

        $siblings = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data = [];
        foreach ($siblings as $index => $sibling) {
            $genderIcon = $sibling->relationship == 'brother' ? '<i class="las la-mars"></i> Male' : '<i class="las la-venus"></i> Female';

            $studentInfo = '';
            if ($sibling->student) {
                $studentUrl  = route('students.show', $sibling->student->id);
                $studentInfo = '<a href="' . $studentUrl . '" class="text-gray-700 text-hover-primary fs-6">' .
                e($sibling->student->name) . ', ' . e($sibling->student->student_unique_id) . '</a>';
            } else {
                $studentInfo = '<span class="badge badge-light-danger">-</span>';
            }

            $actions = '';
            if (auth()->user()->can('siblings.edit')) {
                $actions .= '<a href="#" title="Edit Sibling" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_sibling" data-sibling-id="' . $sibling->id . '" class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-pencil fs-2"></i></a>';
            }
            if (auth()->user()->can('siblings.delete')) {
                $actions .= '<a href="#" title="Delete Sibling" data-bs-toggle="tooltip" class="btn btn-icon text-hover-danger w-30px h-30px delete-sibling" data-sibling-id="' . $sibling->id . '"><i class="ki-outline ki-trash fs-2"></i></a>';
            }

            $data[] = [
                'DT_RowIndex'  => $start + $index + 1,
                'name'         => e($sibling->name),
                'gender'       => $genderIcon,
                'student'      => $studentInfo,
                'class'        => e($sibling->class),
                'year'         => e($sibling->year),
                'institution'  => e($sibling->institution_name),
                'relationship' => ucfirst($sibling->relationship),
                'actions'      => $actions,
            ];
        }

        return response()->json([
            'draw'            => intval($request->get('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Get sibling count by branch for tabs.
     */
    public function getCount(Request $request)
    {
        if (! auth()->user()->can('siblings.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $branchId = $request->get('branch_id');

        $count = Sibling::whereHas('student', fn($q) => $q->where('branch_id', $branchId))->count();

        return response()->json(['count' => $count]);
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
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Sibling $sibling)
    {
        if (! auth()->user()->can('siblings.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $sibling->id,
                'student_id'       => $sibling->student_id,
                'name'             => $sibling->name,
                'year'             => $sibling->year,
                'class'            => $sibling->class,
                'institution_name' => $sibling->institution_name,
                'relationship'     => $sibling->relationship,
            ],
        ]);
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
        if (! auth()->user()->can('siblings.edit')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'sibling_name'         => 'required|string|max:255',
            'sibling_year'         => 'required|string',
            'sibling_class'        => 'required|string',
            'sibling_institution'  => 'required|string',
            'sibling_relationship' => 'required|string|in:brother,sister',
        ]);

        $sibling = Sibling::findOrFail($id);

        $updateData = [
            'name'             => $validated['sibling_name'],
            'year'             => $validated['sibling_year'],
            'class'            => $validated['sibling_class'],
            'institution_name' => $validated['sibling_institution'],
            'relationship'     => $validated['sibling_relationship'],
        ];

        $sibling->update($updateData);

        // Clear the cache
        if (function_exists('clearUCMSCaches')) {
            clearUCMSCaches();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sibling updated successfully',
            'data'    => $sibling,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sibling $sibling)
    {
        if (! auth()->user()->can('siblings.delete')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $sibling->update(['deleted_by' => Auth::id()]);
        $sibling->delete();

        // Clear the cache
        if (function_exists('clearUCMSCaches')) {
            clearUCMSCaches();
        }

        return response()->json(['success' => true, 'message' => 'Sibling deleted successfully']);
    }
}