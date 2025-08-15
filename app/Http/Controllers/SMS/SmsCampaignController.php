<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Models\SMS\SmsCampaign;
use Illuminate\Http\Request;

class SmsCampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('sms.campaign.view')) {
            return redirect()->back()->with('warning', 'No permission to view SMS campaigns.');
        }

        $campaigns = SmsCampaign::all();

        return view('sms.campaign.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (! auth()->user()->can('sms.campaign.create')) {
            return redirect()->back()->with('warning', 'No permission to create SMS campaigns.');
        }

        return view('sms.campaign.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        if (! auth()->user()->can('sms.campaign.edit')) {
            return redirect()->back()->with('warning', 'No permission to edit SMS campaigns.');
        }

        $campaign = SmsCampaign::find($id);

        if (! $campaign) {
            return redirect()->back()->with('warning', 'Campaign not found.');
        }

        return view('sms.campaign.edit', compact('campaign'));
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
    public function approve($id)
    {
        $campaign = SmsCampaign::find($id)->where('is_approved', false)->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.',
            ]);
        }

        $campaign->is_approved = true;
        $campaign->save();

        return response()->json([
            'success' => true,
            'message' => 'Campaign approved successfully.',
        ]);
    }
}
