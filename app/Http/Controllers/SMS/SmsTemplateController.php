<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Models\SMS\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        return $templates = SmsTemplate::all();
        return view('sms.templates', compact('templates'));
    }

    public function update(Request $request, SmsTemplate $template)
    {
        $template->update($request->all());
        
        return response()->json(['success' => 'SMS Template updated successfully.']);
    }

    public function toggleStatus(SmsTemplate $template)
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        return response()->json(['success' => 'SMS Template status updated successfully.', 'is_active' => $template->is_active]);
    }
}
