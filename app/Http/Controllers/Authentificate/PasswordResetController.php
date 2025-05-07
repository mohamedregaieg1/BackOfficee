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
use App\Jobs\SendPasswordResetLinkJob;

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

        $resetLink = 'http://localhost:4200/#/reset-password?token=' . $token;
        $htmlContent = "
<html>
<head>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .email-border {
            max-width: 650px;
            margin: 40px auto;
            border: 2px solid #000000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            font-size: 16px;
            color: #333333;
        }
        .email-header {
            background-color: #1e40af;
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
            font-size: 20px;
        }
        p {
            color: #555;
            font-size: 16px;
            margin: 10px 0;
        }
        .highlight {
            color: #1e40af;
            font-weight: bold;
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
            transition: all 0.3s ease;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }
        .button:hover {
            background: #007BFF;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.15);
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #888;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 15px;
            color: #444;
        }
        .icon {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Outer Border -->
    <div class='email-border'>
        <!-- Email Container -->
        <div class='email-container'>
            <!-- Header -->
            <div class='email-header'>
                PROCAN | Password Reset Request
            </div>

            <!-- Content -->
            <h2>Password Reset Request</h2>
            <p> Hello,</p>
            <p>We received a request to reset the password for your account.</p>
            <p>If you did not request this, please ignore this email.</p>

            <!-- Action Button -->
            <p>To reset your password, click the button below:</p>
            <center><a href='{$resetLink}' class='button'>Reset Your Password</a></center>

            <!-- Additional Information -->
            <div class='info-section'>
                <p><img src='https://img.icons8.com/ios-glyphs/30/time-span.png' class='icon' /> This link will expire in <strong class='highlight'>60 minutes</strong>.</p>
                <p><img src='https://img.icons8.com/ios-glyphs/30/help.png' class='icon' /> If you have any questions, feel free to contact our support team.</p>
            </div>

            <!-- Footer -->
            <p class='footer'>
                This is an automated message. Please do not reply directly to this email.<br>
                For any inquiries, contact the HR department at <a href='mailto:info@procan-group.com'>info@procan-group.com</a>.<br>
                PROCAN HR System © " . date('Y') . "
            </p>
        </div>
    </div>
</body>
</html>";
        dispatch(new SendPasswordResetLinkJob($request->email, $htmlContent));


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
