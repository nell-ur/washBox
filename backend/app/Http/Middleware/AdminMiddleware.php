<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('admin.login')->with('error', 'Please login to continue.');
        }

        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            // Redirect staff to their dashboard
            if (Auth::user()->role === 'staff') {
                return redirect()->route('staff.dashboard')->with('error', 'Access denied. Staff members cannot access admin area.');
            }

            // Redirect customers to their area (if applicable)
            if (Auth::user()->role === 'customer') {
                return redirect('/')->with('error', 'Access denied.');
            }

            // If unknown role, logout and redirect to login
            auth()->logout();
            return redirect()->route('admin.login')->with('error', 'Invalid user role.');
        }

        return $next($request);
    }
}
