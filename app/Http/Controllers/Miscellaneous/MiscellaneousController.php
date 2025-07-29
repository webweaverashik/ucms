<?php
namespace App\Http\Controllers\Miscellaneous;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Imports\StudentsImport;
use App\Models\Academic\ClassName;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class MiscellaneousController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('warning', 'Activity Not Allowed.');
        }

        $branches = Branch::all();
        $classes  = ClassName::all();

        return view('misc.bulk-admission', compact('branches', 'classes'));
    }

    public function bulkAdmission(Request $request)
    {
        $request->validate([
            'student_excel_file' => 'required|mimes:xlsx|max:100',
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('excel_file'));

            return back()->with('success', 'Students imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
