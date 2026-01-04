<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // User profile page
    public function profile()
    {
        $user = User::find(auth()->id());

        // Load all login activities for DataTable (client-side pagination)
        $loginActivities = $user->loginActivities()->latest()->take(20)->get();

        // Load all wallet logs for DataTable (client-side pagination)
        $walletLogs = $user->walletLogs()
            ->with(['creator:id,name', 'user:id,name', 'paymentTransaction:id,payment_invoice_id'])
            ->latest()
            ->get();

        return view('settings.users.profile', compact('user', 'loginActivities', 'walletLogs'));
    }

    // Update user profile
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile_number' => ['required', 'regex:/^01[3-9]\d{8}$/'],
        ];

        // Remove uniqueness rule if value didn't change (optional but nice)
        if ($request->email === $user->email) {
            unset($rules['email']);
        }
        if ($request->mobile_number === $user->mobile_number) {
            unset($rules['mobile_number']);
        }

        $validated = $request->validate($rules);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }
}
