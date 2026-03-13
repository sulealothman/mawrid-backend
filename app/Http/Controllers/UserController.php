<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        return User::select('id', 'name', 'email')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return new UserResource($user);
    }

    public function me(Request $request)
    {
        return response()->json(
            new UserResource($request->user()->load('preferences'))->withToken($request->bearerToken())
        );
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', 'required', 'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => [
            'sometimes',
            'required',
            'string',
            'regex:/^(\+|00)?[0-9]{9,15}$/',
            Rule::unique('users')->ignore($user->id),
        ],
        ]);

        $user->update($validated);

        return response()->json(
            new UserResource($request->user()->load('preferences'))->withToken($request->bearerToken())
        );
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:255|confirmed', 
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => ['the_current_password_is_incorrect'],
                'status' => 422
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'language'            => 'nullable|string|in:en,ar',
            'dark_mode'           => 'nullable|boolean',
            'sidebar_collapse'    => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
        ]);

        $preferences = $user->preferences ?: new UserPreference(['user_id' => $user->id]);
        $preferences->fill(array_filter($validated, fn ($v) => !is_null($v)));
        $preferences->save();

        return response()->json(
            new UserResource($request->user()->load('preferences'))->withToken($request->bearerToken())
        );
    }

    public function resetPreferences(Request $request)
    {
        $user = $request->user();

        $preferences = $user->preferences ?: new UserPreference(['user_id' => $user->id]);

        $preferences->fill([
            'language'            => 'en',
            'dark_mode'           => false,
            'sidebar_collapse'    => false,
            'email_notifications' => true,
        ]);
        $preferences->save();

        return response()->json(
            new UserResource($request->user()->load('preferences'))->withToken($request->bearerToken())
        );
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $file = $validated['image'];
        $filename = (string)Str::uuid() . '.' . $file->extension();

        $path = Storage::disk('s3')->putFileAs('avatars', $file, $filename);

        $user->avatar = $path;
        $user->save();

        return response()->json(
            new UserResource($request->user()->load('preferences'))->withToken($request->bearerToken())
        );
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('s3')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return response()->json(
            new UserResource($request->user()->load('preferences'))->withToken($request->bearerToken())
        );
    }

    public function deactivateAccount(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return response()->json([
            'message' => 'account_deactivated_successfully',
        ]);
    }
}
