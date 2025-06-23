<?php
namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $institutions = Institution::orderBy('name')->get();

        return view('institutions.index', compact('institutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_name_add' => 'required|string|max:255',
            'eiin_number_add'      => 'required|string',
            'institution_type_add' => 'required|string|in:school,college',
        ]);

        Institution::create([
            'name'        => $validated['institution_name_add'],
            'eiin_number' => $validated['eiin_number_add'],
            'type'        => $validated['institution_type_add'],
        ]);

        // Return JSON response
        return response()->json(['success' => true]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Institution $institution)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $institution->id,
                'name'        => $institution->name,
                'eiin_number' => $institution->eiin_number,
                'type'        => $institution->type,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'institution_name_edit' => 'required|string|max:255',
            'eiin_number_edit'      => 'required|string',
            'institution_type_edit' => 'required|string|in:school,college',
        ]);

        $institution = Institution::findOrFail($id);

        $institution->update([
            'name'        => $validated['institution_name_edit'],
            'eiin_number' => $validated['eiin_number_edit'],
            'type'        => $validated['institution_type_edit'],
        ]);

        // Return JSON response
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Institution $institution)
    {
        // Set institution_id to NULL for related students
        $institution->students()->update(['institution_id' => null]);

        // Delete the guardian
        $institution->delete();

        // Return JSON response
        return response()->json(['success' => true]);
    }
}
