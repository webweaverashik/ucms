<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
/**
 * Show the "forgot password" form.
 */
    public function showLinkRequestForm()
    {
        return view('auth.password.email');
    }

    /**
     * Handle sending the reset link email.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'We could not find an active account with that email address.',
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status'  => 'success',
                'message' => __($status),
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => __($status),
        ], 500);
    }

    /**
     * Show the reset password form.
     */
    public function showResetForm(Request $request, $token = null)
    {
        $email = $request->email;

        if (! $token || ! $email) {
            return redirect()->route('password.request')
                ->with('error', 'Invalid password reset link.');
        }

        // Find token row
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return redirect()->route('password.request')
                ->with('error', 'Invalid password reset link.');
        }

        // Tokens in DB are hashed, so verify hash matches token
        if (! Hash::check($token, $record->token)) {
            return redirect()->route('password.request')
                ->with('error', 'Invalid password reset link.');
        }

        // Check expiration (default 60 minutes)
        $expires   = config('auth.passwords.users.expire', 60);
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes($expires)->isPast()) {
            return redirect()->route('password.request')
                ->with('warning', 'This reset link has expired.');
        }

        // Valid token → show reset form
        return view('auth.password.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Handle the actual password reset.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'toc'      => 'required|accepted', // Terms checkbox
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            // Success - return JSON success message
            return response()->json([
                'status'  => 'success',
                'message' => 'Your password has been reset successfully.',
            ]);
        }

        // Failure - invalid or expired token etc.
        return response()->json([
            'status'  => 'error',
            'message' => __($status),
        ], 422);
    }
}
