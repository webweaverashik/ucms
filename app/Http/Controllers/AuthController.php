<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\LoginActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Show login form
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')->with('warning', 'You are already logged in.');
        }
        return view('auth.login')->with('warning', 'Please login first.');
    }

    // Handle login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if user exists (including soft-deleted users)
        $user = User::withTrashed()->where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        // Step 1: Check if the user is soft-deleted
        if ($user->trashed()) {
            return back()->with('error', 'You are not allowed to login.');
        }

        // Step 2: Check if the user is active
        if ($user->is_active == 0) {
            return back()->with('error', 'You account is deactivated. Please, contact your admin.');
        }

        // Step 3: Attempt login if both checks pass
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user(); // Get authenticated user

            // Log login activity
            LoginActivity::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'device' => $this->detectDevice($request->header('User-Agent')),
            ]);

            return redirect()->route('dashboard')->with('success', 'Login successful');
        }

        return back()->with('error', 'Invalid Credentials');
    }

    // Helper function to detect device type
    private function detectDevice($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
