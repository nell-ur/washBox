<?php

namespace App\Http\Controllers\Staff;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Display settings page.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            return view('staff.settings.index', compact('user'));

        } catch (\Exception $e) {
            Log::error('Staff Settings Index Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading settings: ' . $e->getMessage());
        }
    }

    /**
     * Update general settings.
     */
    public function update(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'nullable|string|max:20',
            ]);

            $user->update($validated);

            return redirect()->route('staff.settings.index')
                ->with('success', 'Settings updated successfully!');

        } catch (\Exception $e) {
            Log::error('Staff Settings Update Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating settings: ' . $e->getMessage());
        }
    }

    /**
     * Display account settings page.
     */
    public function account()
    {
        try {
            $user = Auth::user();
            return view('staff.settings.account', compact('user'));

        } catch (\Exception $e) {
            Log::error('Staff Account Settings Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading account settings: ' . $e->getMessage());
        }
    }

    /**
     * Update account settings.
     */
    public function updateAccount(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
            ]);

            $user->update($validated);

            return redirect()->route('staff.settings.account')
                ->with('success', 'Account updated successfully!');

        } catch (\Exception $e) {
            Log::error('Staff Account Update Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating account: ' . $e->getMessage());
        }
    }

    /**
     * Display profile page.
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            return view('staff.settings.profile', compact('user'));

        } catch (\Exception $e) {
            Log::error('Staff Profile Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading profile: ' . $e->getMessage());
        }
    }

    /**
     * Update profile.
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'bio' => 'nullable|string|max:500',
                'phone' => 'nullable|string|max:20',
            ]);

            $user->update($validated);

            return redirect()->route('staff.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            Log::error('Staff Profile Update Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating profile: ' . $e->getMessage());
        }
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|current_password',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = Auth::user();
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            return redirect()->route('staff.profile')
                ->with('success', 'Password updated successfully!');

        } catch (\Exception $e) {
            Log::error('Staff Password Update Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating password: ' . $e->getMessage());
        }
    }
}
