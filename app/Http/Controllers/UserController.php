<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users    = User::withoutTrashed()->orderby('id', 'desc')->get();
        $branches = Branch::all();

        return view('users.index', compact('users', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;

        $validated = $request->validate([
            'user_name'   => 'required|string|max:255',
            'user_email'  => 'required|string|email|max:255|unique:users,email',
            'user_branch' => 'required',
            'user_role'   => 'required',
        ]);

        $user = User::create([
            'name'          => $request->user_name,
            'email'         => $request->user_email,
            'mobile_number' => $request->user_mobile,
            'password'      => Hash::make('ucms@123'),
            'branch_id'     => $request->user_branch,
        ]);

        $user->assignRole($request->user_role);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
        ]);
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
        return redirect()->back()->with('warning', 'Activity Not Allowed');
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
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Toggle active and inactive users
     */
    public function toggleActive(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Error. Please, contact support.']);
        }

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User activation status updated.']);
    }

    /**
     * Reset user password
     */
    public function userPasswordReset(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        if (! $user) {
            return redirect()->back()->with('error', 'User not found');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()->back()->with('success', 'Successfully updated password.');
    }
}
