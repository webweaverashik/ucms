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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if (! auth()->user()->can('sms.campaign.edit')) {
            return redirect()->back()->with('warning', 'No permission to edit SMS campaigns.');
        }

        return view('sms.campaign.edit');
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
