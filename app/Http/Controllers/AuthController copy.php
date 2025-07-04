<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\SendOtpMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function signup(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'name'         => 'required|string',
                'email'        => 'required|email|unique:users,email',
                'password'     => 'required|string|min:6',
                'phone_number' => 'required|string',
            ]);


            // dd($validator);
            if ($validator->fails()) {
                return response()->json([
                     'status' => false,
                     'code' => 422,
                     'message' => $validator->errors()->first()]);
            }

            $otp = rand(10000, 9999);
            // return response()->json(['otp' => $otp]);
            // dd($otp);

            $user = User::create([
                'name'             => $request->name,
                'email'            => $request->email,
                'password'         => Hash::make($request->password),
                'phone_number'     => $request->phone_number,
                'otp'              => $otp,
                'otp_status'       => 'unverified',
                'otp_expires_at'   => now()->addMinutes(10),
            ]);

            // Mail::to($user->email)->send(new SendOtpMail($user->name, $otp));

            return response()->json([
                'status' => true,
                'otp' => $otp,
                'message' => 'OTP sent to your email.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $e->getMessage()
                ],
                500
            );
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp'   => 'required',
            ]);

            // dd($request->toArray());
            $user = User::where('email', $request->email)->first();

            if (!$user || $user->otp !== $request->otp) {
                return response()->json(['status' => false, 'code' => 422, 'message' => 'Invalid OTP.']);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return response()->json(['status' => false, 'code' => 422, 'message' => 'OTP has expired.']);
            }

            $user->update([
                'otp_status' => 'verified',
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return response()->json(['status' => true, 'code' => 200, 'message' => 'Email verified successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 500, 'message' => $e->getMessage()]);
        }
    }
    public function resendOtp(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['status' => false, 'code' => 404, 'message' => 'User not found.']);
            }

            $otp = rand(100000, 999999);

            $user->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
                'otp_status' => 'unverified',
            ]);

            Mail::to($user->email)->send(new SendOtpMail($user->name, $otp));

            return response()->json(['status' => true, 'code' => 200, 'message' => 'OTP resent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'nullable|string',
                'service_provider' => 'nullable|string',
                'service_provider_id' => 'nullable|string',
            ]);


            if ($request->filled('service_provider') && $request->filled('service_provider_id')) {
                $user = User::firstOrCreate(
                    ['service_provider_id' => $request->service_provider_id],
                    [
                        'name'              => $request->name ?? 'No Name',
                        'email'             => $request->email ?? null,
                        'service_provider'  => $request->service_provider,
                        'email_verified_at' => now(),
                        'profile_image'     => $request->profile_image ?? null,
                    ]
                );
            } else {

                $user = User::where('email', $request->email)->first();
                if (!$user || !Hash::check($request->password, $user->password)) {
                    return response()->json(['status' => false, 'code' => 401, 'message' => 'Invalid credentials.']);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 500, 'message' => $e->getMessage()]);
        }
    }
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['status' => false, 'code' => 404, 'message' => 'User not found.']);
            }

            $newPassword = Str::random(8);
            $user->update([
                'password' => Hash::make($newPassword),
                'last_password_reset_at' => now(),
            ]);

            Mail::raw("Your new password is: $newPassword", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your New Password');
            });

            return response()->json([
                'status'  => true,
                'code'    => $newPassword,
                'message' => 'New Password sent successfully to your email.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 500, 'message' => $e->getMessage()]);
        }
    }

public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status'  => false,
                    'code'    => 401,
                    'message' => 'Old password is incorrect or user not found.',
                ]);
            }

            $user->update([
                'password' => Hash::make($request->new_password),
                'last_password_reset_at' => now(),
            ]);

            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Password reset successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['status' => true, 'code' => 200, 'message' => 'Logged out successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'code' => 500, 'message' => $e->getMessage()]);
        }
    }
}
