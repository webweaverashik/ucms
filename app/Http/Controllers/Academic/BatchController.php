<?php
namespace App\Http\Controllers\Academic;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Academic\Batch;
use App\Http\Controllers\Controller;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('batches.manage')) {
            return redirect()->back()->with('warning', 'No permission to view batches.');
        }

        $batches = Batch::all();
        $branches = Branch::all();

        return view('batches.index', compact('branches', 'batches'));
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
        $validated = $request->validate([
            'batch_name' => ['required', 'string', 'max:15', 'regex:/^\S+$/'], // No spaces allowed
            'batch_branch' => 'required|integer',
        ], [
            'batch_name.regex' => 'Single word only',
        ]);
        
        
        Batch::create([
            'name' => $validated['batch_name'],
            'branch_id' => $validated['batch_branch'],
        ]);

        return redirect()->route('batches.index')->with('success', 'Batch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->back();
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
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return redirect()->back();
    }
}
