<?php
namespace App\Http\Controllers\Miscellaneous;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Academic\ClassName;
use App\Http\Controllers\Controller;

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
        return $request;
    }
}
