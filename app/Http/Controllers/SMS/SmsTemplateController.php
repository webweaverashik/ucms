<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Models\SMS\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $templates = SmsTemplate::all();
        return view('settings.sms_templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required',
            'message_type' => 'required|in:TEXT,UNICODE',
            'body'         => 'required',
        ]);

        SmsTemplate::create($request->only('title', 'message_type', 'body', 'is_active'));
        return back()->with('success', 'SMS Template created successfully.');
    }

    public function toggleStatus(SmsTemplate $template)
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        return response()->json(['success' => 'SMS Template status updated successfully.', 'is_active' => $template->is_active]);
    }
}
