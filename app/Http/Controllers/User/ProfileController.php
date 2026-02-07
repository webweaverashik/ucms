<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile_number' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:100'], // max 100KB
        ];

        // Remove uniqueness rule if value didn't change (optional but nice)
        if ($request->email === $user->email) {
            unset($rules['email']);
        }

        if ($request->mobile_number === $user->mobile_number) {
            unset($rules['mobile_number']);
        }

        $validated = $request->validate($rules);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo_url && file_exists(public_path($user->photo_url))) {
                unlink(public_path($user->photo_url));
            }

            $photo = $request->file('photo');
            $filename = 'user_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $destinationPath = public_path('uploads/users');

            // Create directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $photo->move($destinationPath, $filename);
            $validated['photo_url'] = 'uploads/users/' . $filename;
        }

        // Handle photo removal
        if ($request->has('remove_photo') && $request->remove_photo == '1') {
            if ($user->photo_url && file_exists(public_path($user->photo_url))) {
                unlink(public_path($user->photo_url));
            }
            $validated['photo_url'] = null;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'photo_url' => $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png'),
        ]);
    }
}
