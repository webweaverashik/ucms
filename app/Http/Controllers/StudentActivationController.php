<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Student\StudentActivation;

class StudentActivationController extends Controller
{
    public function activate(Request $request, Student $student)
    {
        $request->validate([
            'active_status' => 'required|in:active,inactive',
            'reason'        => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $student) {
            // Create Activation Entry
            $activation = StudentActivation::create([
                'student_id'    => $student->id,
                'active_status' => $request->active_status,
                'reason'        => $request->reason,
                'updated_by'    => Auth::id(),
            ]);

            // Update Student's Activation ID
            $student->update(['student_activation_id' => $activation->id]);

            return response()->json(['success' => true, 'message' => 'Student activation updated successfully']);
        });
    }
}
