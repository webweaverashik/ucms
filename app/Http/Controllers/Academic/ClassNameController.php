<?php
namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use Illuminate\Http\Request;

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

        $classes = ClassName::withCount('activeStudents')
            ->latest('updated_at')
            ->orderByDesc('id') // tie-breaker: id descending
            ->get()
            ->groupBy('is_active');

        return view('classnames.index', [
            'active_classes'   => $classes[true] ?? collect(),
            'inactive_classes' => $classes[false] ?? collect(),
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
        $validated = $request->validate([
            'class_name_add'    => 'required|string|max:255',
            'class_numeral_add' => [
                'required',
                'regex:/^(0[4-9]|1[0-2])$/', // Allows only 04 to 12
            ],
            'description_add'   => 'nullable|string|max:1000',
        ]);

        $classname = ClassName::create([
            'name'          => $validated['class_name_add'],
            'class_numeral' => $validated['class_numeral_add'],
            'description'   => $validated['description_add'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('classes.view')) {
            return redirect()->back()->with('warning', 'No permission to view classes.');
        }

        $classname = ClassName::withoutGlobalScope('active')
            ->withCount(['activeStudents', 'inactiveStudents'])
            ->find($id);
            
        // $classname = ClassName::withoutGlobalScope('active')
        //     ->withCount(['activeStudents', 'inactiveStudents'])
        //     ->with(['subjects.students']) // Eager load students for each subject
        //     ->find($id);

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
        $validated = $request->validate([
            'class_name_edit'   => 'required|string|max:255',
            'description_edit'  => 'nullable|string|max:1000',
            'activation_status' => 'required|in:active,inactive',
        ]);

        $class = ClassName::findOrFail($id);

        $class->update([
            'name'        => $validated['class_name_edit'],
            'description' => $validated['description_edit'],
            'is_active'   => $validated['activation_status'] == 'active' ? true : false,
        ]);

        // Return JSON response
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $class = ClassName::findOrFail($id);

        if ($class->activeStudents()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'This class cannot be deleted because it has active students.']);
        }

        $class->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get class names by class ID using AJAX request
     */
    public function getClassName(ClassName $class)
    {
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
}
