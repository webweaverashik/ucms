<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (auth()->user()->cannot('branches.manage')) {
            return redirect()->back()->with('error', 'No permission to manage branches.');
        }

        $branches = Branch::with('activeStudents')->get();

        return view('settings.branch.index', compact('branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'branch_name'   => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', 'unique:branches,branch_name'],
                'branch_prefix' => ['required', 'string', 'size:1', 'alpha', 'unique:branches,branch_prefix'],
                'address'       => 'nullable|string|max:500',
                'phone_number'  => 'nullable|string|max:20',
            ],
            [
                'branch_name.regex'    => 'Branch name must be one word and may contain only letters, numbers, and hyphen.',
                'branch_prefix.size'   => 'The branch prefix must be exactly 1 letter.',
                'branch_prefix.alpha'  => 'The branch prefix must be a letter.',
                'branch_name.unique'   => 'This branch name already exists.',
                'branch_prefix.unique' => 'This branch prefix is already in use.',
            ],
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ],
                422,
            );
        }

        DB::transaction(function () use ($request, &$branch) {
            $branch = Branch::create([
                'branch_name'   => $request->branch_name,
                'branch_prefix' => strtoupper($request->branch_prefix),
                'address'       => $request->address,
                'phone_number'  => $request->phone_number,
            ]);

            // Auto-create 4 default batches
            $batches = [
                ['name' => 'Usha', 'day_off' => 'Friday'],
                ['name' => 'Orun', 'day_off' => 'Saturday'],
                ['name' => 'Proloy', 'day_off' => 'Monday'],
                ['name' => 'Dhumketu', 'day_off' => 'Tuesday'],
            ];

            foreach ($batches as $batch) {
                $branch->batches()->create($batch);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully with default batches!',
            'data'    => $branch->load(['activeStudents', 'batches']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'branch_name'   => 'required|string|max:255|unique:branches,branch_name,' . $branch->id,
                'branch_prefix' => 'required|string|size:1|alpha|unique:branches,branch_prefix,' . $branch->id,
                'address'       => 'required|string|max:500',
                'phone_number'  => 'required|string|max:11',
            ],
            [
                'branch_prefix.size'  => 'The branch prefix must be exactly 1 letter.',
                'branch_prefix.alpha' => 'The branch prefix must be a letter.',
            ],
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ],
                422,
            );
        }

        $branch->update([
            'branch_name'   => $request->branch_name,
            'branch_prefix' => strtoupper($request->branch_prefix),
            'address'       => $request->address,
            'phone_number'  => $request->phone_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully!',
            'data'    => $branch->fresh()->load('activeStudents'),
        ]);
    }
}
