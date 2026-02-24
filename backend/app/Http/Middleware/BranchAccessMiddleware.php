<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Branch Access Middleware
 *
 * CRITICAL: Ensures staff can only access data from their assigned branch.
 * This middleware is applied to ALL staff routes to enforce branch scoping.
 *
 * Usage:
 * - Applied automatically to all routes in staff.php
 * - Adds branch_id to query scope for all database queries
 * - Prevents staff from accessing other branches' data
 */
class BranchAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for admin users (they have access to all branches)
        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        // For staff users, verify they have a branch assignment
        if ($user && $user->role === 'staff') {
            // Get staff's assigned branch
            $staffBranchId = $user->staff->branch_id ?? null;

            if (!$staffBranchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff user has no branch assigned.',
                    'error' => 'BRANCH_NOT_ASSIGNED'
                ], 403);
            }

            // Add branch_id to request for use in controllers
            $request->merge(['staff_branch_id' => $staffBranchId]);

            // Set global scope for all queries in this request
            // This ensures all Eloquent queries automatically filter by branch
            config(['app.current_branch_id' => $staffBranchId]);

            // Verify branch access for specific resource requests
            if ($request->route('branch_id') || $request->input('branch_id')) {
                $requestedBranchId = $request->route('branch_id') ?? $request->input('branch_id');

                if ($requestedBranchId != $staffBranchId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. You can only access data from your assigned branch.',
                        'error' => 'BRANCH_ACCESS_DENIED',
                        'your_branch_id' => $staffBranchId,
                        'requested_branch_id' => $requestedBranchId
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    /**
     * Verify that a specific resource belongs to the staff's branch
     *
     * @param object $resource The resource to verify (Laundry, Customer, etc.)
     * @param int $staffBranchId The staff's assigned branch ID
     * @return bool
     */
    protected function verifyResourceBranch($resource, int $staffBranchId): bool
    {
        if (!$resource) {
            return false;
        }

        // Check if resource has branch_id
        if (property_exists($resource, 'branch_id')) {
            return $resource->branch_id === $staffBranchId;
        }

        // Check if resource has branch relationship
        if (method_exists($resource, 'branch')) {
            return $resource->branch?->id === $staffBranchId;
        }

        return false;
    }
}
