<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsCampaignJob;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\SMS\SmsCampaign;
use App\Models\Student\Guardian;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsCampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('sms.campaign.view')) {
            return redirect()->back()->with('warning', 'No permission to view campaigns.');
        }

        $branchId = auth()->user()->branch_id;

        $campaigns = SmsCampaign::with(['branch', 'createdBy'])
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->latest()
            ->get();

        return view('sms.campaign.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (! auth()->user()->can('sms.campaign.create')) {
            return redirect()->back()->with('warning', 'No permission to create campaigns.');
        }

        $branches = Branch::when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('id', auth()->user()->branch_id);
        })->get();

        $classes = ClassName::all();

        return view('sms.campaign.create', compact('branches', 'classes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_title' => 'required|string|max:255',
            'branch_id'      => 'required|exists:branches,id',
            'class_id'       => 'required|exists:class_names,id',
            'message_type'   => 'required|in:TEXT,UNICODE',
            'message_body'   => 'required|string|max:500',
            'recipients'     => 'required|string', // JSON string: ["students","guardians"]
        ]);

        $selectedRecipients = json_decode($validated['recipients'], true);

        $mobileNumbers = collect();

        // ✅ If "students" selected
        if (in_array('students', $selectedRecipients)) {
            $studentNumbers = Student::where('branch_id', $validated['branch_id'])
                ->where('class_id', $validated['class_id'])
                ->whereHas('studentActivation', function ($q2) {
                    $q2->where('active_status', 'active');
                })
                ->whereHas('mobileNumbers', function ($q) {
                    $q->where('number_type', 'sms');
                })
                ->with([
                    'mobileNumbers' => function ($q) {
                        $q->where('number_type', 'sms');
                    },
                ])
                ->get()
                ->pluck('mobileNumbers.*.mobile_number') // nested array of numbers
                ->flatten();

            $mobileNumbers = $mobileNumbers->merge($studentNumbers);
        }

        // ✅ If "guardians" selected
        if (in_array('guardians', $selectedRecipients)) {
            $guardianNumbers = Guardian::whereHas('student', function ($q) use ($validated) {
                $q->where('branch_id', $validated['branch_id'])
                    ->where('class_id', $validated['class_id'])
                    ->whereHas('studentActivation', function ($q2) {
                        $q2->where('active_status', 'active');
                    });
            })->pluck('mobile_number');

            $mobileNumbers = $mobileNumbers->merge($guardianNumbers);
        }

        // ✅ Remove duplicates & reindex
        $mobileNumbers = $mobileNumbers->filter()->unique()->values();

        // ✅ Save campaign
        $campaign = SmsCampaign::create([
            'campaign_title' => $validated['campaign_title'],
            'branch_id'      => $validated['branch_id'],
            'message_type'   => $validated['message_type'],
            'message_body'   => $validated['message_body'],
            'recipients'     => $mobileNumbers->toJson(), // Store JSON array of numbers
            'created_by'     => auth()->id(),
        ]);

        // Clear the cache
        clearUCMSCaches();

        return response()->json([
            'success'          => true,
            'message'          => 'Campaign created successfully',
            'recipients_count' => $mobileNumbers->count(),
            'data'             => $campaign,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('sms.campaign.edit')) {
            return redirect()->back()->with('warning', 'No permission to edit campaign.');
        }

        $campaign = SmsCampaign::where('id', $id)->where('is_approved', false)->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $campaign->id,
                'campaign_title' => $campaign->campaign_title,
                'message_type'   => $campaign->message_type,
                'message_body'   => $campaign->message_body,
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
        if (! auth()->user()->can('sms.campaign.edit')) {
            return response()->json(['success' => false, 'message' => 'No permission.']);
        }

        $campaign = SmsCampaign::where('id', $id)->where('is_approved', false)->first();

        if (! $campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found.']);
        }

        $campaign->update([
            'message_type' => $request->message_type,
            'message_body' => $request->message_body,
        ]);

        return response()->json(['success' => true, 'message' => 'Campaign updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $campaign = SmsCampaign::find($id);

        if (! $campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        if ($campaign->is_approved === true) {
            return response()->json(['error' => 'Cannot delete approved campaign'], 422);
        }

        // Mark who deleted it
        $campaign->update([
            'deleted_by' => auth()->id(),
        ]);

        $campaign->delete();

        // Clear the cache
        clearUCMSCaches();

        return response()->json(['success' => true]);
    }

    /**
     * SMS Campaign approve
     */
    public function approve(string $id)
    {
        $campaign = SmsCampaign::where('id', $id)->where('is_approved', false)->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found or already approved.',
            ]);
        }

        $campaign->is_approved = true;
        $campaign->save();

        // Dispatch job
        dispatch(new SendSmsCampaignJob($campaign));

        return response()->json([
            'success' => true,
            'message' => 'Campaign approved and SMS sending started in background.',
        ]);
    }
}
