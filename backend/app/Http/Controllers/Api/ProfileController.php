<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get customer profile.
     *
     * GET /api/v1/profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'preferred_branch_id' => $customer->preferred_branch_id,
                    'avatar_url' => $customer->avatar_url ?? null,
                    'created_at' => $customer->created_at,
                ],
            ]
        ]);
    }

    /**
     * Update customer profile.
     *
     * PUT /api/v1/profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string|max:500',
            'preferred_branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update($request->only(['name', 'phone', 'address', 'preferred_branch_id']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'profile' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'preferred_branch_id' => $customer->preferred_branch_id,
                ],
            ]
        ]);
    }

    /**
     * Change password.
     *
     * PUT /api/v1/profile/password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }

        $customer->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Revoke all tokens
        $customer->tokens()->delete();

        // Create new token
        $token = $customer->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
            'data' => [
                'token' => $token,
            ]
        ]);
    }

    /**
     * Upload avatar.
     *
     * POST /api/v1/profile/avatar
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Delete old avatar if exists
        if ($customer->avatar) {
            Storage::disk('public')->delete($customer->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $customer->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar_url' => asset('storage/' . $path),
            ]
        ]);
    }

    /**
     * Delete avatar.
     *
     * DELETE /api/v1/profile/avatar
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAvatar(Request $request)
    {
        $customer = $request->user();

        if ($customer->avatar) {
            Storage::disk('public')->delete($customer->avatar);
            $customer->update(['avatar' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar deleted successfully',
        ]);
    }

    /**
     * Register device token for push notifications.
     *
     * POST /api/v1/device-token
     * Body: { "token": "fcm_token_here", "device_type": "android" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerDeviceToken(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_type' => 'required|in:android,ios',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Store or update device token
        \App\Models\DeviceToken::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'token' => $request->token,
            ],
            [
                'device_type' => $request->device_type,
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully',
        ]);
    }

    /**
     * Remove device token.
     *
     * DELETE /api/v1/device-token
     * Body: { "token": "fcm_token_here" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeDeviceToken(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        \App\Models\DeviceToken::where('customer_id', $customer->id)
            ->where('token', $request->token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device token removed successfully',
        ]);
    }

    /**
     * Get favorites.
     *
     * GET /api/v1/favorites
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function favorites(Request $request)
    {
        $customer = $request->user();

        // TODO: Implement favorites table and relationship

        return response()->json([
            'success' => true,
            'data' => [
                'favorites' => [],
            ]
        ]);
    }

    /**
     * Contact support.
     *
     * POST /api/v1/support/contact
     * Body: { "subject": "Issue with laundry", "message": "..." }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactSupport(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Send email to support or create support ticket

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent. We will get back to you soon!',
        ]);
    }

    /**
     * Get FAQs.
     *
     * GET /api/v1/support/faqs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function faqs()
    {
        $faqs = [
            [
                'question' => 'How do I place an laundry?',
                'answer' => 'Simply select your preferred branch, choose a service, enter the weight of your laundry, and submit your laundry.',
            ],
            [
                'question' => 'What payment methods do you accept?',
                'answer' => 'We accept cash, GCash, PayMaya, and credit/debit cards.',
            ],
            [
                'question' => 'How long does it take to process my laundry?',
                'answer' => 'Processing time depends on the service selected. Typically, it takes 24-48 hours for full service.',
            ],
            [
                'question' => 'Can I cancel my laundry?',
                'answer' => 'Yes, you can cancel your laundry before it has been received by the branch staff.',
            ],
            [
                'question' => 'Do you offer pickup and delivery?',
                'answer' => 'Yes, we offer pickup and delivery services. You can request a pickup when placing your laundry.',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'faqs' => $faqs,
            ]
        ]);
    }

    public function update(Request $request)
{
    $user = $request->user(); // Identifies the user via Sanctum token

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:20',
    ]);

    $user->update($validated);

    return response()->json([
        'status' => 'success',
        'message' => 'Profile updated.',
        'user' => $user
    ]);
}
}
