<?php

namespace App\Http\Controllers\Authentificate;
use App\Http\Controllers\Controller;
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
        $htmlContent = "
    <html>
    <head>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f8f9fa;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 40px auto;
                background: #ffffff;
                padding: 30px;
                border-radius: 10px;
                border: 1px solid #ddd;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            .logo-container {
                text-align: center;
                margin-bottom: 20px;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #007BFF;
            }
            h2 {
                color: #333;
                font-size: 22px;
                font-weight: bold;
                margin-bottom: 20px;
            }
            p {
                color: #555;
                font-size: 16px;
                line-height: 1.6;
                margin: 15px 0;
            }
            .button {
                display: inline-block;
                background: transparent;
                color: #007BFF;
                padding: 12px 28px;
                border: 2px solid #007BFF;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                font-size: 16px;
                transition: 0.3s;
            }
            .button:hover {
                background: #007BFF;
                color: #fff;
                transform: translateY(-2px);
            }
            .footer {
                margin-top: 30px;
                font-size: 12px;
                color: #888;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='logo-container'>
                <span class='logo'>PROCAN</span>
            </div>
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset the password for your account.</p>
            <p>If you did not request this, please ignore this email.</p>
            <p>To reset your password, click the button below:</p>
            <a href='{$resetLink}' class='button'>Reset Your Password</a>
            <p>This link will expire in 60 minutes.</p>
            <p>If you have any questions, feel free to contact our support team.</p>
            <p class='footer'>Best regards,<br>The Support Team</p>
        </div>
    </body>
    </html>";
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
            'new_password' => 'required|string|min:6|regex:/[A-Za-z]/|regex:/[0-9]/',
            'confirm_password' => 'required|string|same:new_password',
        ], [
            'token.required' => 'The token is required.',
            'new_password.required' => 'The password is required.',
            'new_password.min' => 'The password must be at least 6 characters long.',
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
