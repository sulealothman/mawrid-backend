<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {

        if(User::where('email', $request->email)->orWhere('phone', $request->phone)->exists()) {
            return response()->json([
                'message' => 'email_or_phone_already_exists',
                'status' => 409
            ], 409);
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json((new UserResource($user->load('preferences')))->withToken($token));

    }

    public function login(LoginRequest $request)
    {

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'invalid_login_credentials',
                'status' => 400
            ], 400);
        }
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'invalid_login_credentials',
                'status' => 401
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json((new UserResource($user->load('preferences')))->withToken($token));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully!',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $lang = $request->header('Accept-Language', 'en');

        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'email_not_registered',
                'status' => 422
            ], 422);
        }

        $token = Str::random(64);


        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        Mail::to($request->email)->queue(new PasswordResetMail($token, $request->email, $lang));

        return response()->json([
            'message' => 'Password reset link has been sent to your email',
            'status' => 200
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'message' => 'Password has been changed successfully'])
            : response()->json([
                'message' => 'invalid_token_or_email',
                'status' => 400
            ], 400);
    }

}