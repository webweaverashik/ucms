<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Models\SMS\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('sms.templates.manage')) {
            return redirect()->back()->with('error', 'No permission to manage SMS templates.');
        }

        $templates = SmsTemplate::all();
        return view('sms.templates', compact('templates'));
    }

    public function toggleStatus(SmsTemplate $template)
    {
        $template->update([
            'is_active' => ! $template->is_active,
        ]);

        return response()->json(['success' => true, 'is_active' => $template->is_active]);
    }

    public function updateBody(Request $request, SmsTemplate $template)
    {
        $validated = $request->validate([
            'body' => 'required|string', // or any limit you want
        ]);

        $template->update(['body' => $validated['body']]);

        return response()->json(['success' => true, 'body' => $template->body]);
    }
}
