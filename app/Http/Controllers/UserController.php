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
        if (! auth()->user()->can('users.manage')) {
            return redirect()->back()->with('warning', 'Not Allowed.');
        }

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
        $commonRules = [
            'user_name'   => 'required|string|max:255',
            'user_email'  => 'required|string|email|max:255|unique:users,email',
            'user_mobile' => 'required|string|size:11',
            'user_role'   => 'required|string|in:admin,manager,accountant',
        ];

        // Only validate branch if role is NOT admin
        if ($request->user_role !== 'admin') {
            $commonRules['user_branch'] = 'required|integer|exists:branches,id';
        }

        $request->validate($commonRules);

        $branch_id = $request->user_role === 'admin' ? 0 : $request->user_branch;

        $user = User::create([
            'name'          => $request->user_name,
            'email'         => $request->user_email,
            'mobile_number' => $request->user_mobile,
            'password'      => Hash::make('ucms@123'),
            'branch_id'     => $branch_id,
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
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'mobile_number' => $user->mobile_number,
                'branch_id'     => $user->branch_id,
                'role'          => $user->getRoleNames()->first(),
            ],
        ]);
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
        $user = User::findOrFail($id);

        $commonRules = [
            'user_name_edit'   => 'required|string|max:255',
            'user_email_edit'  => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'user_mobile_edit' => 'required|string|size:11',
            'user_role_edit'   => 'required|string|in:admin,manager,accountant',
        ];

        // Only validate branch if role is NOT admin
        if ($request->user_role_edit !== 'admin') {
            $commonRules['user_branch_edit'] = 'required|integer|exists:branches,id';
        }

        $request->validate($commonRules);

        $branch_id = $request->user_role_edit === 'admin' ? 0 : $request->user_branch_edit;

        // Update the user record
        $user->update([
            'name'          => $request->user_name_edit,
            'email'         => $request->user_email_edit,
            'mobile_number' => $request->user_mobile_edit,
            'branch_id'     => $branch_id,
        ]);

        $user->syncRoles($request->user_role_edit);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
        ]);
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
    public function userPasswordReset(Request $request, User $user)
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true]);
    }
}
