<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        // Check if user is customer
        if (auth()->user()->role !== 'customer') {
            // Redirect admin to their dashboard
            if (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard')->with('error', 'Access denied.');
            }

            // Redirect staff to their dashboard
            if (auth()->user()->role === 'staff') {
                return redirect()->route('staff.dashboard')->with('error', 'Access denied.');
            }

            // If unknown role, logout and redirect to login
            auth()->logout();
            return redirect()->route('login')->with('error', 'Invalid user role.');
        }

        return $next($request);
    }
}
