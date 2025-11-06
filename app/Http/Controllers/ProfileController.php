<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user profile.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        try {
            $this->validateRequest($request, $user);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
        $this->updateProfileData($request, $user);

        if ($request->filled('new_password')) {
            $passwordUpdateResult = $this->updatePassword($request, $user);
            if ($passwordUpdateResult instanceof RedirectResponse) {
                // Convert redirect with errors to JSON format
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update password.',
                ], 422);
            }
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

    /**
     * Validate the request data.
     */
    private function validateRequest(Request $request, User $user): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);
    }

    /**
     * Update the user's profile data.
     */
    private function updateProfileData(Request $request, User $user): void
    {
        $name = $request->input('name');
        if (\is_string($name)) {
            $user->name = $name;
        }

        $email = $request->input('email');
        if (\is_string($email)) {
            $user->email = $email;
        }
    }

    /**
     * Update the user's password.
     */
    private function updatePassword(Request $request, User $user): ?RedirectResponse
    {
        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');

        if (! \is_string($currentPassword) || ! Hash::check($currentPassword, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        if (\is_string($newPassword)) {
            $user->password = Hash::make($newPassword);
        }

        return null;
    }
}
