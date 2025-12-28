<?php
namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectTaken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->back();
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
            'subject_class' => 'required|exists:class_names,id',
            'subject_name'  => 'required|string|max:255',
            'subject_group' => 'required|string|in:General,Science,Commerce,Arts',
        ]);

        Subject::create([
            'class_id'       => $validated['subject_class'],
            'name'           => $validated['subject_name'],
            'academic_group' => $validated['subject_group'],
        ]);

        return response()->json(['success' => true]);
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
    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
        ]);

        $subject->update([
            'name' => $request->subject_name,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get subjects by class ID using AJAX request
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id'        => 'required|exists:class_names,id',
            'group'           => 'required|in:General,Science,Commerce,Arts',
            'include_general' => 'required|boolean',
        ]);

        $classId        = $validated['class_id'];
        $group          = $validated['group'];
        $includeGeneral = $validated['include_general'];

        // Get class numeral for selection mode
        $class        = ClassName::find($classId);
        $classNumeral = $class ? $class->class_numeral : 0;

        // Get General compulsory subjects
        $generalCompulsory = Subject::where('class_id', $classId)
            ->where('academic_group', 'General')
            ->where('subject_type', 'compulsory')
            ->orderBy('name')
            ->get();

        $groupCompulsory = collect();
        $groupOptional   = collect();

        if ($includeGeneral && $group !== 'General') {
            $groupCompulsory = Subject::where('class_id', $classId)
                ->where('academic_group', $group)
                ->where('subject_type', 'compulsory')
                ->orderBy('name')
                ->get();

            $groupOptional = Subject::where('class_id', $classId)
                ->where('academic_group', $group)
                ->where('subject_type', 'optional')
                ->orderBy('name')
                ->get();
        }

        // Determine selection mode based on class numeral AND group
        $selectionMode = $this->getSelectionMode($classNumeral, $group);

        return response()->json([
            'success'        => true,
            'subjects'       => [
                'general_compulsory' => $generalCompulsory,
                'group_compulsory'   => $groupCompulsory,
                'group_optional'     => $groupOptional,
            ],
            'group'          => $group,
            'class_numeral'  => $classNumeral,
            'has_optional'   => $groupOptional->count() > 0,
            'optional_count' => $groupOptional->count(),
            'selection_mode' => $selectionMode,
        ]);
    }

    private function getSelectionMode(int $classNumeral, string $group): array
    {
        // Class 9-10 (SSC Level)
        if ($classNumeral >= 9 && $classNumeral <= 10) {
            return match ($group) {
                'Science' => [
                    'type'          => 'main_and_4th',
                    'requires_main' => true,
                    'requires_4th'  => true,
                    'main_label'    => 'Main Subject',
                    '4th_label'     => '4th Subject',
                    'instruction'   => 'Select one subject as Main and one different subject as 4th Subject',
                ],
                'Commerce', 'Arts' => [
                    'type'          => '4th_only',
                    'requires_main' => false,
                    'requires_4th'  => true,
                    'main_label'    => null,
                    '4th_label'     => '4th Subject',
                    'instruction'   => 'Select one subject as 4th Subject',
                ],
                default   => [
                    'type'          => 'none',
                    'requires_main' => false,
                    'requires_4th'  => false,
                    'main_label'    => null,
                    '4th_label'     => null,
                    'instruction'   => null,
                ],
            };
        }

        // Class 11-12 (HSC Level)
        if ($classNumeral >= 11 && $classNumeral <= 12) {
            return match ($group) {
                'Science', 'Commerce', 'Arts' => [
                    'type'          => 'main_and_4th',
                    'requires_main' => true,
                    'requires_4th'  => true,
                    'main_label'    => 'Main Subject',
                    '4th_label'     => '4th Subject',
                    'instruction'   => 'Select one subject as Main and one different subject as 4th Subject',
                ],
                default => [
                    'type'          => 'none',
                    'requires_main' => false,
                    'requires_4th'  => false,
                    'main_label'    => null,
                    '4th_label'     => null,
                    'instruction'   => null,
                ],
            };
        }

        // Class 1-8 (No optional subjects)
        return [
            'type'          => 'none',
            'requires_main' => false,
            'requires_4th'  => false,
            'main_label'    => null,
            '4th_label'     => null,
            'instruction'   => null,
        ];
    }

    /**
     * Get subjects taken by the student
     */
    public function getTakenSubjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id'        => 'required|exists:class_names,id',
            'group'           => 'required|in:General,Science,Commerce,Arts',
            'include_general' => 'required|boolean',
            'student_id'      => 'required|exists:students,id',
        ]);

        $classId        = $validated['class_id'];
        $group          = $validated['group'];
        $includeGeneral = $validated['include_general'];
        $studentId      = $validated['student_id'];

        // Get class numeral
        $class        = ClassName::find($classId);
        $classNumeral = $class ? $class->class_numeral : 0;

        $generalCompulsory = Subject::where('class_id', $classId)
            ->where('academic_group', 'General')
            ->where('subject_type', 'compulsory')
            ->orderBy('name')
            ->get();

        $groupCompulsory = collect();
        $groupOptional   = collect();

        if ($includeGeneral && $group !== 'General') {
            $groupCompulsory = Subject::where('class_id', $classId)
                ->where('academic_group', $group)
                ->where('subject_type', 'compulsory')
                ->orderBy('name')
                ->get();

            $groupOptional = Subject::where('class_id', $classId)
                ->where('academic_group', $group)
                ->where('subject_type', 'optional')
                ->orderBy('name')
                ->get();
        }

        $takenSubjects = SubjectTaken::where('student_id', $studentId)
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->subject_id => [
                        'subject_id'     => $item->subject_id,
                        'is_4th_subject' => $item->is_4th_subject,
                    ],
                ];
            });

        $fourthSubjectId    = null;
        $mainOptionalId     = null;
        $optionalSubjectIds = $groupOptional->pluck('id')->toArray();

        foreach ($takenSubjects as $subjectId => $data) {
            if (in_array($subjectId, $optionalSubjectIds)) {
                if ($data['is_4th_subject']) {
                    $fourthSubjectId = $subjectId;
                } else {
                    $mainOptionalId = $subjectId;
                }
            }
        }

        $selectionMode = $this->getSelectionMode($classNumeral, $group);

        return response()->json([
            'success'           => true,
            'subjects'          => [
                'general_compulsory' => $generalCompulsory,
                'group_compulsory'   => $groupCompulsory,
                'group_optional'     => $groupOptional,
            ],
            'taken_subjects'    => $takenSubjects,
            'fourth_subject_id' => $fourthSubjectId,
            'main_optional_id'  => $mainOptionalId,
            'group'             => $group,
            'class_numeral'     => $classNumeral,
            'has_optional'      => $groupOptional->count() > 0,
            'selection_mode'    => $selectionMode,
        ]);
    }
}
