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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Change user password (separate endpoint).
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return back()->with('status', 'Password updated successfully.');
    }

    /**
     * Export user data (GDPR compliance).
     */
    public function exportData(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
            ],
            'wishlist' => $user->wishlist()->with('product')->get()->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'added_at' => $item->created_at?->toIso8601String(),
                ];
            }),
            'price_alerts' => $user->priceAlerts()->with('product')->get()->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'product_id' => $alert->product_id,
                    'product_name' => $alert->product->name ?? null,
                    'target_price' => $alert->target_price,
                    'is_active' => $alert->is_active,
                    'created_at' => $alert->created_at?->toIso8601String(),
                ];
            }),
            'reviews' => $user->reviews()->with('product')->get()->map(function ($review) {
                return [
                    'id' => $review->id,
                    'product_id' => $review->product_id,
                    'product_name' => $review->product->name ?? null,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'content' => $review->content,
                    'created_at' => $review->created_at?->toIso8601String(),
                ];
            }),
            'points' => $user->points()->get()->map(function ($point) {
                return [
                    'id' => $point->id,
                    'points' => $point->points,
                    'type' => $point->type,
                    'description' => $point->description,
                    'created_at' => $point->created_at?->toIso8601String(),
                ];
            }),
        ];

        $filename = 'user_data_export_' . $user->id . '_' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Delete user account (GDPR compliance).
     */
    public function deleteAccount(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'password' => 'required',
            'confirm_delete' => 'required|accepted',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (! Hash::check($request->input('password'), $user->password)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The password is incorrect.',
                    'errors' => ['password' => ['The password is incorrect.']],
                ], 422);
            }
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        // Soft delete user account
        $user->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your account has been deleted successfully.',
            ], 200);
        }

        return redirect()->route('home')->with('status', 'Your account has been deleted successfully.');
    }
}
