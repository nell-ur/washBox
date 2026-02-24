<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks if:
     * 1. User is authenticated
     * 2. User has 'staff' role
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('staff.login')
                ->with('error', 'Please login to access the staff portal.');
        }

        // Check if user has staff role
        if (Auth::user()->role !== 'staff') {
            // Log out non-staff users
            Auth::logout();

            return redirect()->route('staff.login')
                ->with('error', 'Access denied. Staff credentials required.');
        }

        // User is authenticated AND is staff
        // Allow access
        return $next($request);
    }
}
