<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use Carbon\Carbon;
class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'The email is required.',
            'email.email' => 'The email must be valid.',
            'email.exists' => 'No user found with this email.',
        ]);

        $key = 'reset_password_attempts_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Too many attempts. Please try again in $seconds seconds."
            ], 429);
        }
        PasswordResetToken::where('email', $request->email)->delete();

        $token = Str::random(60);

        PasswordResetToken::create([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $resetLink = 'http://localhost:4200/reset-password?token='.$token;  
        $htmlContent = "<p>Hello,</p>
        <p>We received a request to reset your password for your account.</p>
        <p>If you did not make this request, please ignore this email.</p>
        <p>To reset your password, please click the link below:</p>
        <p><a href='{$resetLink}'>Reset your password</a></p>
        <p>This link will expire in 60 minutes.</p>
        <p>If you have any questions, feel free to contact us.</p>
        <p>Best regards,<br>The Support Team</p>";
        Mail::html($htmlContent, function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Password Reset Link');
        });

        return response()->json(['message' => 'Password reset link sent successfully.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'new_password' => 'required|string|min:8|regex:/[A-Za-z]/|regex:/[0-9]/',
            'confirm_password' => 'required|string|same:new_password',
        ], [
            'token.required' => 'The token is required.',
            'new_password.required' => 'The password is required.',
            'new_password.min' => 'The password must be at least 8 characters long.',
            'new_password.regex' => 'The password must contain letters and numbers.',
            'confirm_password.required' => 'The password confirmation is required.',
            'confirm_password.same' => 'The password and confirmation do not match.',
        ]);

        $resetToken = PasswordResetToken::where('token', $request->token)->first();

        if (!$resetToken) {
            return response()->json(['error' => 'Invalid or expired token.'], 400);
        }

        if (Carbon::parse($resetToken->created_at)->diffInMinutes(now()) > 60) {
            return response()->json(['error' => 'The token has expired. Please request a new link.'], 400);
        }

        $user = User::where('email', $resetToken->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        PasswordResetToken::where('email', $resetToken->email)->delete();

        return response()->json(['message' => 'The password has been successfully reset.']);
    }
}
