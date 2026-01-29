<?php
namespace App\Http\Controllers\Sheet;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Models\Branch;
use App\Models\Sheet\Sheet;
use App\Models\Sheet\SheetPayment;
use App\Models\Sheet\SheetTopic;
use App\Models\Sheet\SheetTopicTaken;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SheetTopicTakenController extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->hasRole('admin');

        // Get all branches
        $branches = Branch::all();

        // Get distribution counts for tabs
        $distributionCounts = [];
        if ($isAdmin) {
            foreach ($branches as $branch) {
                $distributionCounts[$branch->id] = SheetTopicTaken::whereHas('student', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->count();
            }
        }

        // Get all active sheet groups for filter dropdown
        $sheetGroups = Sheet::whereHas('class', fn($q) => $q->active())->with('class')->get();

        return view('notes.index', compact('branches', 'distributionCounts', 'sheetGroups', 'isAdmin', 'branchId'));
    }

    /**
     * Get distributions data for AJAX DataTable
     */
    public function getData(Request $request)
    {
        $user         = auth()->user();
        $userBranchId = $user->branch_id;
        $isAdmin      = $user->hasRole('admin');

        // Get branch filter from request
        $branchId = $request->get('branch_id');

        // Base query
        $query = SheetTopicTaken::with([
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
            'sheetTopic:id,topic_name,subject_id',
            'sheetTopic.subject:id,name,class_id',
            'sheetTopic.subject.class:id,name,class_numeral',
            'sheetTopic.subject.class.sheet:id,class_id',
            'distributedBy:id,name',
        ]);

        // Apply branch filter
        if ($isAdmin && $branchId) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        } elseif (! $isAdmin && $userBranchId != 0) {
            $query->whereHas('student', function ($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        // Get total count before filtering
        $totalRecords = $query->count();

        // Search filter
        $searchValue = $request->get('search')['value'] ?? '';
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('sheetTopic', function ($q) use ($searchValue) {
                    $q->where('topic_name', 'like', "%{$searchValue}%");
                })
                    ->orWhereHas('sheetTopic.subject', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('sheetTopic.subject.class', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('class_numeral', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('student', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('student_unique_id', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('distributedBy', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Sheet Group filter
        $sheetGroupFilter = $request->get('sheet_group_filter');
        if (! empty($sheetGroupFilter)) {
            $sheet = Sheet::find($sheetGroupFilter);
            if ($sheet) {
                $query->whereHas('sheetTopic.subject', function ($q) use ($sheet) {
                    $q->where('class_id', $sheet->class_id);
                });
            }
        }

        // Subject filter
        $subjectFilter = $request->get('subject_filter');
        if (! empty($subjectFilter)) {
            $query->whereHas('sheetTopic', function ($q) use ($subjectFilter) {
                $q->where('subject_id', $subjectFilter);
            });
        }

        // Topic filter
        $topicFilter = $request->get('topic_filter');
        if (! empty($topicFilter)) {
            $query->where('sheet_topic_id', $topicFilter);
        }

        // Get filtered count
        $filteredRecords = $query->count();

        // Sorting
        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection   = $request->get('order')[0]['dir'] ?? 'desc';
        $columns          = ['id', 'sheet_topic_id', 'sheet_topic_id', 'sheet_topic_id', 'student_id', 'distributed_by', 'created_at'];
        $orderColumn      = $columns[$orderColumnIndex] ?? 'id';

        if ($orderColumn === 'id') {
            $query->orderBy('id', 'desc');
        } else {
            $query->orderBy($orderColumn, $orderDirection);
        }

        // Pagination
        $start         = $request->get('start', 0);
        $length        = $request->get('length', 10);
        $distributions = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $data    = [];
        $counter = $start + 1;

        foreach ($distributions as $note) {
            $sheetGroup = $note->sheetTopic?->subject?->class;
            $sheetId    = $sheetGroup?->sheet?->id ?? 0;

            $data[] = [
                'DT_RowId'           => 'row_' . $note->id,
                'sl'                 => $counter++,
                'topic_name'         => $note->sheetTopic->topic_name ?? '',
                'subject'            => $note->sheetTopic->subject->name ?? '',
                'subject_id'         => $note->sheetTopic->subject->id ?? 0,
                'sheet_group'        => $sheetGroup
                    ? '<a href="' . route('sheets.show', $sheetId) . '" class="text-gray-800 text-hover-primary" target="_blank">'
                . $sheetGroup->name . ' (' . $sheetGroup->class_numeral . ')</a>'
                    : '',
                'sheet_group_raw'    => $sheetGroup ? $sheetGroup->name . ' (' . $sheetGroup->class_numeral . ')' : '',
                'sheet_id'           => $sheetId,
                'student'            => '<div class="d-flex align-items-center">
                    <div class="symbol symbol-circle symbol-35px me-3">
                        <span class="symbol-label bg-light-primary text-primary fw-bold">'
                . substr($note->student->name ?? '', 0, 1) .
                '</span>
                    </div>
                    <div class="d-flex flex-column">
                        <a href="' . route('students.show', $note->student->id) . '" class="text-gray-800 text-hover-primary fw-bold" target="_blank">'
                . ($note->student->name ?? '') .
                '</a>
                        <span class="text-muted fs-7">' . ($note->student->student_unique_id ?? '') . '</span>
                    </div>
                </div>',
                'student_raw'        => ($note->student->name ?? '') . ', ' . ($note->student->student_unique_id ?? ''),
                'student_id'         => $note->student->id ?? 0,
                'distributed_by'     => $note->distributedBy
                    ? '<span class="badge badge-light-info">' . $note->distributedBy->name . '</span>'
                    : '<span class="badge badge-light">System</span>',
                'distributed_by_raw' => $note->distributedBy->name ?? 'System',
                'distributed_at'     => '<span class="text-muted">' . $note->created_at->format('d M Y') . '</span>
                    <span class="d-block text-muted fs-7">' . $note->created_at->format('h:i A') . '</span>',
                'distributed_at_raw' => $note->created_at->format('Y-m-d H:i:s'),
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
     * Get all distributions for export (without pagination)
     */
    public function getExportData(Request $request)
    {
        $user         = auth()->user();
        $userBranchId = $user->branch_id;
        $isAdmin      = $user->hasRole('admin');
        $branchId     = $request->get('branch_id');

        $query = SheetTopicTaken::with([
            'student:id,name,student_unique_id,branch_id',
            'sheetTopic:id,topic_name,subject_id',
            'sheetTopic.subject:id,name,class_id',
            'sheetTopic.subject.class:id,name,class_numeral',
            'distributedBy:id,name',
        ]);

        // Apply branch filter
        if ($isAdmin && $branchId) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        } elseif (! $isAdmin && $userBranchId != 0) {
            $query->whereHas('student', function ($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        // Search filter
        $searchValue = $request->get('search');
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('sheetTopic', function ($q) use ($searchValue) {
                    $q->where('topic_name', 'like', "%{$searchValue}%");
                })
                    ->orWhereHas('sheetTopic.subject', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('sheetTopic.subject.class', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('class_numeral', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('student', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('student_unique_id', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('distributedBy', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Sheet Group filter
        $sheetGroupFilter = $request->get('sheet_group_filter');
        if (! empty($sheetGroupFilter)) {
            $sheet = Sheet::find($sheetGroupFilter);
            if ($sheet) {
                $query->whereHas('sheetTopic.subject', function ($q) use ($sheet) {
                    $q->where('class_id', $sheet->class_id);
                });
            }
        }

        // Subject filter
        $subjectFilter = $request->get('subject_filter');
        if (! empty($subjectFilter)) {
            $query->whereHas('sheetTopic', function ($q) use ($subjectFilter) {
                $q->where('subject_id', $subjectFilter);
            });
        }

        // Topic filter
        $topicFilter = $request->get('topic_filter');
        if (! empty($topicFilter)) {
            $query->where('sheet_topic_id', $topicFilter);
        }

        $distributions = $query->orderBy('id', 'desc')->get();

        $data    = [];
        $counter = 1;

        foreach ($distributions as $note) {
            $sheetGroup = $note->sheetTopic?->subject?->class;

            $data[] = [
                'sl'             => $counter++,
                'topic_name'     => $note->sheetTopic->topic_name ?? '',
                'subject'        => $note->sheetTopic->subject->name ?? '',
                'sheet_group'    => $sheetGroup ? $sheetGroup->name . ' (' . $sheetGroup->class_numeral . ')' : '',
                'student'        => ($note->student->name ?? '') . ', ' . ($note->student->student_unique_id ?? ''),
                'distributed_by' => $note->distributedBy->name ?? 'System',
                'distributed_at' => $note->created_at->format('d M Y, h:i A'),
            ];
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::query()
            ->forUserBranch()
            ->hasSheetPayment()
            ->with('sheetPayments.invoice.invoiceType')
            ->orderBy('student_unique_id')
            ->select('id', 'name', 'student_unique_id', 'branch_id', 'student_activation_id')
            ->get();

        return view('notes.single-distribution', compact('students'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $studentId = $request->student_id;
        $sheetId   = $request->sheet_id;
        $topics    = $request->topics ?? [];

        // Get the sheet to verify class
        $sheet = Sheet::find($sheetId);
        if (! $sheet) {
            return response()->json(['message' => 'Sheet not found'], 404);
        }

        // Get already taken topics
        $existing = SheetTopicTaken::where('student_id', $studentId)
            ->whereHas('sheetTopic', function ($query) use ($sheet) {
                $query->whereHas('subject', function ($q) use ($sheet) {
                    $q->where('class_id', $sheet->class_id);
                });
            })
            ->pluck('sheet_topic_id')
            ->toArray();

        // Filter out topics that are already taken
        $newTopics = array_diff($topics, $existing);

        // Create new records
        foreach ($newTopics as $topicId) {
            SheetTopicTaken::create([
                'sheet_topic_id' => $topicId,
                'student_id'     => $studentId,
                'distributed_by' => auth()->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Sheet topics distribution saved successfully',
        ]);
    }

    /* --- Added After 01.01.2026 12.45 AM --- */

    /**
     * Show bulk distribution page
     */
    public function bulkCreate()
    {
        if (! auth()->user()->can('notes.distribute')) {
            return redirect()->back()->with('warning', 'No permission to distribute notes.');
        }

        // Get all active sheet groups
        $sheetGroups = Sheet::whereHas('class', fn($q) => $q->active())->with('class')->get();

        return view('notes.bulk-distribution', compact('sheetGroups'));
    }

    /**
     * Store bulk distribution
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'sheet_id'      => 'required|exists:sheets,id',
            'topic_id'      => 'required|exists:sheet_topics,id',
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $sheetId    = $request->sheet_id;
        $topicId    = $request->topic_id;
        $studentIds = $request->student_ids;

        // Get the sheet to verify class
        $sheet = Sheet::find($sheetId);
        $topic = SheetTopic::find($topicId);

        if (! $sheet || ! $topic) {
            return response()->json(['message' => 'Sheet or Topic not found'], 404);
        }

        // Verify the topic belongs to this sheet's class
        $topicSubject = Subject::find($topic->subject_id);
        if (! $topicSubject || $topicSubject->class_id !== $sheet->class_id) {
            return response()->json(['message' => 'Topic does not belong to this sheet group'], 400);
        }

        // Get already distributed students for this topic
        $existingDistributions = SheetTopicTaken::where('sheet_topic_id', $topicId)->whereIn('student_id', $studentIds)->pluck('student_id')->toArray();

        // Filter out students who already have this topic
        $newStudentIds = array_diff($studentIds, $existingDistributions);

        if (empty($newStudentIds)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'All selected students already have this topic distributed.',
                ],
                400,
            );
        }

        // Verify all students have paid for this sheet
        $paidStudentIds = SheetPayment::where('sheet_id', $sheetId)
            ->whereIn('student_id', $newStudentIds)
            ->whereHas('invoice', function ($query) {
                $query->whereIn('status', ['paid', 'partially_paid'])->whereHas('invoiceType', function ($q) {
                    $q->where('type_name', 'Sheet Fee');
                });
            })
            ->pluck('student_id')
            ->toArray();

        // Only distribute to students who have paid
        $validStudentIds = array_intersect($newStudentIds, $paidStudentIds);

        if (empty($validStudentIds)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'No valid students found. Students must have paid for this sheet.',
                ],
                400,
            );
        }

        // Create distribution records
        $distributedCount = 0;
        $userId           = auth()->user()->id;

        DB::beginTransaction();
        try {
            foreach ($validStudentIds as $studentId) {
                SheetTopicTaken::create([
                    'sheet_topic_id' => $topicId,
                    'student_id'     => $studentId,
                    'distributed_by' => $userId,
                ]);
                $distributedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to distribute: ' . $e->getMessage(),
                ],
                500,
            );
        }

        $skippedCount = count($studentIds) - $distributedCount;

        return response()->json([
            'success' => true,
            'message' => "Successfully distributed to {$distributedCount} student(s)." . ($skippedCount > 0 ? " {$skippedCount} student(s) were skipped." : ''),
            'distributed_count' => $distributedCount,
            'skipped_count'     => $skippedCount,
        ]);
    }

    /** Till Now */
}
