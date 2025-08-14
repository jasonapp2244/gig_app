<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Mail\{
    WelcomeMail,
    forgotPasswordMail,
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
    public function getUser()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'User retrieved successfully',
            'data' => $user
        ]);
    }




    public function signup(AuthRequest $request): JsonResponse
    {
        try {
            // 1️⃣ Check if a user exists with this email
            $existingUserByEmail = User::where('email', $request->email)->first();

            // 2️⃣ If email already exists
            if ($existingUserByEmail) {
                // If already verified → block registration
                if ($existingUserByEmail->otp_status === 'verified') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Registration failed',
                        'errors' => [
                            'email' => ['This email is already registered.']
                        ]
                    ], 400);
                }

                // If email exists but not verified → ask for OTP verification
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email before registering'
                ], 400);
            }

            // 3️⃣ Check if phone number already exists
            $existingUserByPhone = User::where('phone_number', $request->phone_number)->first();

            if ($existingUserByPhone) {

                if ($existingUserByPhone->otp_status === 'verified') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Registration failed',
                        'errors' => [
                            'phone_number' => ['This phone number is already registered.']
                        ]
                    ], 400);
                }

                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your phone before registering'
                ], 400);
            }

            // 4️⃣ Create new user with OTP
            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'phone_number'    => $request->phone_number,
                'otp'             => rand(100000, 999999),
                'otp_status'      => 'unverified',
                'otp_expires_at'  => now()->addMinutes(10),
            ]);


            Mail::to($user->email)->send(new WelcomeMail($user, $user->otp));

            $adminEmail = env('Admin_Email', 'default_value');
            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new NewUserNotificationMail($user));
            }

            return response()->json([
                'status' => true,
                'message' => 'OTP sent to your email',
                'otp' => $user->otp,
                'data' => ['email' => $user->email]
            ], 200);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Registration failed');
        }
    }
    /**
     * Resend OTP for email verification
     */

    public function resendOtp(AuthRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found with this email address'
                ], 400);
            }


            // Prevent OTP spamming - limit to 1 request per minute
            // if ($user->otp_expires_at && $user->otp_expires_at->diffInMinutes(now()) < 1) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Please wait before requesting a new OTP'
            //     ], 429);
            // }


            $newOtp = rand(100000, 999999);

            $user->update([
                'otp' => $newOtp,
                'otp_expires_at' => now()->addMinutes(10),
                'otp_status' => 'unverified'
            ]);

            // Send the new OTP via email
            Mail::to($user->email)->send(new WelcomeMail($user, $newOtp));

            return response()->json([
                'status' => true,
                'message' => 'New OTP has been sent to your email',
                'new_otp' => $newOtp,
                'data' => [
                    'email' => $user->email,
                    'otp_expires_at' => $user->otp_expires_at
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to resend OTP');
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
                    'message' => 'Invalid OTP or Email not found'
                ], 400);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP has expired'
                ], 400);
            }

            $user->update([
                'email_verified_at' => now(),
                'otp' => null,
                'otp_status' => 'verified',
                'otp_expires_at' => null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Email verified successfully',
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user' => $user->only(['id', 'name', 'email', 'phone_number', 'email_verified_at'])
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'OTP verification failed');
        }
    }


    public function login(AuthRequest $request): JsonResponse
    {
        try {
            // Validation
            $request->validate([
                'email' => 'nullable|email',
                'password' => 'nullable|string',
                'service_provider' => 'nullable|in:google,facebook,apple',
                'service_provider_id' => 'nullable|string',
            ]);

            //SOCIAL LOGIN
            if ($request->filled('service_provider') && $request->filled('service_provider_id')) {
                $user = User::where('service_provider', $request->service_provider)
                    ->where('service_provider_id', $request->service_provider_id)
                    ->first();

                // If user doesn't exist, create new
                if (!$user) {
                    $user = User::create([
                        'name'                 => $request->name ?? 'Guest',
                        'email'                => $request->email ?? null,
                        'service_provider'     => $request->service_provider,
                        'service_provider_id'  => $request->service_provider_id,
                        'email_verified_at'    => now(),
                        'profile_image'        => $request->profile_image ?? null,
                        'status'               => 'active'
                    ]);
                }

                // Generate token
                $token = $user->createToken('auth_token')->plainTextToken;
                $user->fcm_token = $request->fcm_token ?? null;
                $user->device_type = $request->device_type ?? null;
                $user->status = 'active';
                $user->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Social login successful',
                    'token' => $token,
                    'user' => $user->only(['id', 'name', 'email', 'phone_number', 'service_provider'], 200)
                ]);
            }

            //EMAIL + PASSWORD LOGIN
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 400);
            }

            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email first'
                ], 400);
            }

            $user->status = 'active';
            $user->save();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email', 'phone_number', 'service_provider'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function forgotPassword(AuthRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        $otp = strtolower(Str::random(5)) . rand(100, 999);
        $expiry = now()->addMinutes(10);

        $user->update([
            'password' => $otp,
            'password_reset_token' => $otp,
            'password_reset_otp_expires_at' => $expiry
        ]);

        Mail::to($user->email)->send(new forgotPasswordMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email address',
            'expires_at' => $expiry->toDateTimeString()
        ]);
    }

    /**
     * Reset password
     */

    public function resetPassword(AuthRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();


            // dd($request->toArray());

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Old password is incorrect'
                ], 400);
            }

            $user->update([

                'password' => Hash::make($request->new_password),
                'last_password_reset_at' => now()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Password update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //Profile Update
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated.'
                ], 400);
            }


            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'phone_number' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'address_one' => 'nullable|string|max:255',
                'address_two' => 'nullable|string|max:255',
                'skills' => 'nullable|string|max:500',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'cv' => 'nullable|mimes:pdf,doc,docx|max:5120',
            ]);


            $user->fill([
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'phone_number' => $validated['phone_number'] ?? $user->phone_number,
                'city' => $validated['city'] ?? $user->city,
                'state' => $validated['state'] ?? $user->state,
                'country' => $validated['country'] ?? $user->country,
                'address_one' => $validated['address_one'] ?? $user->address_one,
                'address_two' => $validated['address_two'] ?? $user->address_two,

                'skills' => isset($validated['skills']) ? explode(',', $validated['skills']) : $user->skills,
            ]);



            if ($request->hasFile('profile_image')) {
                if ($user->profile_image && $user->profile_image !== 'default.jpg') {
                    Storage::delete('profile_images/' . $user->profile_image);
                }

                $image = $request->file('profile_image');
                $imageName = uniqid('profile_') . '.' . $image->getClientOriginalExtension();
                $image->storeAs('profile_images', $imageName);
                $user->profile_image = $imageName;
            }

            if ($request->hasFile('cv')) {
                if ($user->cv) {
                    Storage::delete('cv/' . $user->cv);
                }

                $cv = $request->file('cv');
                $cvName = uniqid('cv_') . '.' . $cv->getClientOriginalExtension();
                $cv->storeAs('cv', $cvName);
                $user->cv = $cvName;
            }

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
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



    /**
     * Update user FCM token
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = auth()->user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully'
        ]);
    }

    /**
     * Remove FCM token (logout)
     */
    public function removeFcmToken()
    {
        $user = auth()->user();
        $user->update(['fcm_token' => null]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token removed successfully'
        ]);
    }
}
