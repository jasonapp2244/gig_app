<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Mail\{
    WelcomeMail,
    PasswordResetMail,
    NewUserNotificationMail
};
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register new user with OTP verification
     */
public function signup(AuthRequest $request): JsonResponse
{
    try {
        // Check if user already exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'errors' => [
                    'email' => ['This email is already registered.']
                ]
            ], 422);
        }

        if (User::where('phone_number', $request->phone_number)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'errors' => [
                    'phone_number' => ['This phone number is already registered.']
                ]
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'otp' => rand(100000, 999999),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // First send welcome email to user
        Mail::to($user->email)->send(new WelcomeMail($user, $user->otp));

        // Then notify admin (without waiting)
        $adminEmail = "noumanzindanii@gmail.com";
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new NewUserNotificationMail($user));
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email',
            'data' => ['email' => $user->email]
        ], 201);

    } catch (\Exception $e) {
        return $this->handleError($e, 'Registration failed');
    }
}

    /**
     * Verify OTP for email verification
     */
    public function verifyOtp(AuthRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();

            if ($user->otp !== $request->otp) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid OTP'
                ], 422);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP has expired'
                ], 422);
            }

            $user->update([
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Email verified successfully',
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user' => $user->only(['id', 'name', 'email', 'phone_number'])
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'OTP verification failed');
        }
    }

    /**
     * Authenticate user
     */
    public function login(AuthRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email first'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user' => $user->only(['id', 'name', 'email', 'phone_number'])
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Login failed');
        }
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(AuthRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $token = Str::random(60);

            $user->update([
                'password_reset_token' => $token,
                'password_reset_token_expires_at' => now()->addHours(1)
            ]);

            $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token;

            // Mail::to($user->email)->send(new PasswordResetMail($resetUrl));

            return response()->json([
                'status' => true,
                'message' => 'Password reset link sent to your email'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Password reset failed');
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(AuthRequest $request): JsonResponse
    {
        try {
            $user = User::where('password_reset_token', $request->token)
                ->where('password_reset_token_expires_at', '>', now())
                ->firstOrFail();

            $user->update([
                'password' => Hash::make($request->password),
                'password_reset_token' => null,
                'password_reset_token_expires_at' => null,
                'email_verified_at' => $user->email_verified_at ?? now() // Verify email if not already
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password reset successfully'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Password reset failed');
        }
    }

    /**
     * Logout user
     */
    public function logout(): JsonResponse
    {
        try {
            auth()->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Logout failed');
        }
    }

    /**
     * Handle errors consistently
     */
    private function handleError(\Exception $e, string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
