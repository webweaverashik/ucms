<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student\Guardian;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GuardianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('guardians.view')) {
            return redirect()->back()->with('warning', 'No permission to view guardians.');
        }

        $userBranchId = auth()->user()->branch_id;
        $isAdmin      = auth()->user()->hasRole('admin');

        $students = Student::when($userBranchId != 0, fn($q) => $q->where('branch_id', $userBranchId))
            ->select('id', 'name', 'student_unique_id')
            ->orderBy('student_unique_id')
            ->get();

        $branches = Branch::all();

        return view('guardians.index', compact('branches', 'students', 'isAdmin'));
    }

    /**
     * Get guardians data for DataTable via AJAX.
     */
    public function getData(Request $request)
    {
        if (! auth()->user()->can('guardians.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $userBranchId = auth()->user()->branch_id;
        $isAdmin      = auth()->user()->hasRole('admin');
        $branchId     = $request->get('branch_id');

        $query = Guardian::with([
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
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('relationship', 'like', "%{$search}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('student_unique_id', 'like', "%{$search}%"));
            });
        }

        // Apply relationship filter
        if ($relationship = $request->get('relationship')) {
            $query->where('relationship', strtolower($relationship));
        }

        // Apply gender filter
        if ($gender = $request->get('gender')) {
            $genderValue = str_replace('gd_', '', $gender);
            $query->where('gender', $genderValue);
        }

        // Get total count before pagination
        $totalRecords = Guardian::when($isAdmin && $branchId, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('branch_id', $branchId)))
            ->when($userBranchId != 0 && ! $isAdmin, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('branch_id', $userBranchId)))
            ->count();

        $filteredRecords = $query->count();

        // Apply ordering
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'name', 'gender', 'gender', 'student_id', 'relationship', 'branch', 'id'];
        $orderBy = $columns[$orderColumn] ?? 'id';

        if ($orderBy === 'branch') {
            $query->orderBy('id', $orderDir);
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        // Pagination
        $start  = $request->get('start', 0);
        $length = $request->get('length', 10);

        $guardians = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data = [];
        foreach ($guardians as $index => $guardian) {
            $genderIcon = $guardian->gender == 'male' ? '<i class="las la-mars"></i>' : '<i class="las la-venus"></i>';

            $studentInfo = '';
            if ($guardian->student) {
                $studentUrl  = route('students.show', $guardian->student->id);
                $studentInfo = '<a href="' . $studentUrl . '"><span class="text-hover-success fs-6">' .
                e($guardian->student->name) . ', ' . e($guardian->student->student_unique_id) . '</span></a>';
            } else {
                $studentInfo = '<span class="badge badge-light-danger">No Student Assigned</span>';
            }

            $actions = '';
            if (auth()->user()->can('guardians.edit')) {
                $actions .= '<a href="#" title="Edit Guardian" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_guardian" data-guardian-id="' . $guardian->id . '" class="btn btn-icon text-hover-primary w-30px h-30px"><i class="ki-outline ki-pencil fs-2"></i></a>';
            }
            if (auth()->user()->can('guardians.delete')) {
                $actions .= '<a href="#" title="Delete Guardian" data-bs-toggle="tooltip" class="btn btn-icon text-hover-danger w-30px h-30px delete-guardian" data-guardian-id="' . $guardian->id . '"><i class="ki-outline ki-trash fs-2"></i></a>';
            }

            $data[] = [
                'DT_RowIndex'  => $start + $index + 1,
                'name'         => '<span class="text-gray-800 fs-6 fw-semibold">' . e($guardian->name) . '</span>',
                'mobile'       => '<span class="text-gray-600">' . e($guardian->mobile_number) . '</span>',
                'gender'       => $genderIcon . ' ' . ucfirst($guardian->gender),
                'student'      => $studentInfo,
                'relationship' => ucfirst($guardian->relationship),
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
     * Get guardian count by branch for tabs.
     */
    public function getCount(Request $request)
    {
        if (! auth()->user()->can('guardians.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $branchId = $request->get('branch_id');

        $count = Guardian::whereHas('student', fn($q) => $q->where('branch_id', $branchId))->count();

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
    public function show(Guardian $guardian)
    {
        if (! auth()->user()->can('guardians.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $guardian->id,
                'student_id'    => $guardian->student_id,
                'name'          => $guardian->name,
                'mobile_number' => $guardian->mobile_number,
                'gender'        => $guardian->gender,
                'relationship'  => $guardian->relationship,
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
        if (! auth()->user()->can('guardians.edit')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'guardian_name'          => 'required|string|max:255',
            'guardian_mobile_number' => 'required|string|max:11',
            'guardian_gender'        => 'required|in:male,female',
            'guardian_relationship'  => 'required|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_password'      => 'nullable|string|min:8|confirmed',
        ]);

        $guardian = Guardian::findOrFail($id);

        $updateData = [
            'name'          => $validated['guardian_name'],
            'mobile_number' => $validated['guardian_mobile_number'],
            'gender'        => $validated['guardian_gender'],
            'relationship'  => $validated['guardian_relationship'],
        ];

        if ($request->filled('guardian_password')) {
            $updateData['password'] = Hash::make($request->input('guardian_password'));
        }

        $guardian->update($updateData);

        // Clear the cache
        if (function_exists('clearUCMSCaches')) {
            clearUCMSCaches();
        }

        return response()->json([
            'success' => true,
            'message' => 'Guardian updated successfully',
            'data'    => $guardian,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guardian $guardian)
    {
        if (! auth()->user()->can('guardians.delete')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $guardian->update(['deleted_by' => Auth::id()]);
        $guardian->delete();

        // Clear the cache
        if (function_exists('clearUCMSCaches')) {
            clearUCMSCaches();
        }

        return response()->json(['success' => true, 'message' => 'Guardian deleted successfully']);
    }
}