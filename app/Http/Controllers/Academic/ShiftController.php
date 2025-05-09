<?php
namespace App\Http\Controllers\Academic;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Academic\Shift;
use App\Http\Controllers\Controller;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shifts = Shift::withoutTrashed()->get();
        $branches = Branch::all();

        return view('shifts.index', compact('branches', 'shifts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_name' => ['required', 'string', 'max:15', 'regex:/^\S+$/'], // No spaces allowed
            'shift_branch' => 'required|integer',
        ], [
            'shift_name.regex' => 'Single word only',
        ]);
        
        
        Shift::create([
            'name' => $validated['shift_name'],
            'branch_id' => $validated['shift_branch'],
        ]);

        return redirect()->route('shifts.index')->with('success', 'Shift created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
