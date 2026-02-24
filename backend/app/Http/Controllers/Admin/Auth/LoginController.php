<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle login attempt.
     *
     * This method:
     * 1. Validates credentials
     * 2. Checks rate limiting
     * 3. Attempts authentication
     * 4. Verifies admin role
     * 5. Redirects to dashboard on success
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        // Validate input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Rate limiting - prevent brute force attacks
        // Key: IP address + email
        $key = 'login.' . $request->ip() . '.' . $request->email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Attempt to authenticate
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            // Check if user is admin
            if (Auth::user()->role !== 'admin') {
                // Logout non-admin users
                Auth::logout();

                // Increment rate limiter
                RateLimiter::hit($key, 60);

                throw ValidationException::withMessages([
                    'email' => 'Access denied. Admin credentials required.',
                ]);
            }

            // Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();

            // Clear rate limiter on successful login
            RateLimiter::clear($key);

            // ===================================================
            // SUCCESS! Redirect to Admin Dashboard
            // ===================================================
            return redirect()->intended(route('admin.dashboard'));
        }

        // Failed login - increment rate limiter
        RateLimiter::hit($key, 60);

        // Return with error
        throw ValidationException::withMessages([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    /**
     * Handle logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
{
    // Ensure we use the 'web' guard which supports the logout() method
    Auth::guard('web')->logout();

    // Clear the session data
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('admin.login')
        ->with('success', 'You have been logged out successfully.');
}
}
