<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BranchController extends Controller
{
    /**
     * Get all active branches
     *
     * GET /api/v1/branches
     */
    public function index()
    {
        try {
            $branches = Branch::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                        'city' => $branch->city,
                        'province' => $branch->province,
                        'address' => $branch->address,
                        'phone' => $branch->phone,
                        'email' => $branch->email,
                        'latitude' => $branch->latitude,
                        'longitude' => $branch->longitude,
                        'operating_hours' => $branch->operating_hours,
                        'is_open' => $this->isBranchOpen($branch),
                        'is_active' => $branch->is_active,
                        'created_at' => $branch->created_at->toIso8601String(),
                        'updated_at' => $branch->updated_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Branches retrieved successfully',
                'data' => [
                    'branches' => $branches,
                    'count' => $branches->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single branch details
     *
     * GET /api/v1/branches/{id}
     */
    public function show($id)
    {
        try {
            $branch = Branch::where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Branch retrieved successfully',
                'data' => [
                    'branch' => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                        'city' => $branch->city,
                        'province' => $branch->province,
                        'address' => $branch->address,
                        'phone' => $branch->phone,
                        'email' => $branch->email,
                        'branch' => $branch, 
                        'latitude' => $branch->latitude,
                        'longitude' => $branch->longitude,
                        'operating_hours' => $branch->operating_hours,
                        'is_open' => $this->isBranchOpen($branch),
                        'is_active' => $branch->is_active,
                        'created_at' => $branch->created_at->toIso8601String(),
                        'updated_at' => $branch->updated_at->toIso8601String(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Check if branch is currently open based on operating hours
     */
    private function isBranchOpen($branch)
    {
        // If branch is not active, it's not open
        if (!$branch->is_active) {
            return false;
        }

        $now = Carbon::now();
        $currentDay = $now->format('l'); // Monday, Tuesday, etc.
        $currentTime = $now->format('H:i:s');

        // Check if we have operating hours in JSON format
        if ($branch->operating_hours) {
            $hours = $branch->operating_hours;

            // If operating_hours is a string, decode it
            if (is_string($hours)) {
                $hours = json_decode($hours, true);
            }

            // Check if we have hours for today
            if (isset($hours[$currentDay])) {
                $todayHours = $hours[$currentDay];

                // Check if closed for the day
                if (isset($todayHours['closed']) && $todayHours['closed']) {
                    return false;
                }

                // Check if we have open/close times
                if (isset($todayHours['open']) && isset($todayHours['close'])) {
                    $openTime = $todayHours['open'] . ':00';
                    $closeTime = $todayHours['close'] . ':00';

                    return ($currentTime >= $openTime && $currentTime <= $closeTime);
                }
            }
        }

        // Default fallback: check if it's within business hours
        // Monday to Saturday: 8AM to 6PM, Sunday: 9AM to 5PM
        $dayOfWeek = $now->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

        if ($dayOfWeek == 0) { // Sunday
            $openTime = '09:00:00';
            $closeTime = '17:00:00';
        } else if ($dayOfWeek == 6) { // Saturday
            $openTime = '08:00:00';
            $closeTime = '17:00:00';
        } else { // Monday to Friday
            $openTime = '08:00:00';
            $closeTime = '18:00:00';
        }

        return ($currentTime >= $openTime && $currentTime <= $closeTime);
    }

    /**
     * Get nearest branch based on coordinates
     *
     * GET /api/v1/branches/nearest?lat=xxx&lng=xxx
     */
    public function nearest(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $branches = Branch::where('is_active', true)->get();

            $nearest = $branches->map(function ($branch) use ($request) {
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $branch->latitude ?? 0,
                    $branch->longitude ?? 0
                );

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'city' => $branch->city,
                    'province' => $branch->province,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'email' => $branch->email,
                    'distance' => round($distance, 2),
                    'is_open' => $this->isBranchOpen($branch),
                ];
            })->sortBy('distance')->first();

            if (!$nearest) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active branches found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nearest branch found',
                'data' => [
                    'branch' => $nearest,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find nearest branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get branch operating hours
     *
     * GET /api/v1/branches/{id}/operating-hours
     */
    public function operatingHours($id)
    {
        try {
            $branch = Branch::where('is_active', true)
                ->findOrFail($id);

            // Get operating hours from database
            $hours = $branch->operating_hours;

            if (is_string($hours)) {
                $hours = json_decode($hours, true);
            }

            // If no hours in database, return default hours
            if (!$hours || empty($hours)) {
                $hours = [
                    'Monday' => ['open' => '08:00', 'close' => '18:00'],
                    'Tuesday' => ['open' => '08:00', 'close' => '18:00'],
                    'Wednesday' => ['open' => '08:00', 'close' => '18:00'],
                    'Thursday' => ['open' => '08:00', 'close' => '18:00'],
                    'Friday' => ['open' => '08:00', 'close' => '18:00'],
                    'Saturday' => ['open' => '09:00', 'close' => '17:00'],
                    'Sunday' => ['open' => '09:00', 'close' => '17:00'],
                ];
            }

            // Format hours for response
            $formattedHours = [];
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            foreach ($days as $day) {
                if (isset($hours[$day])) {
                    $dayHours = $hours[$day];
                    if (isset($dayHours['closed']) && $dayHours['closed']) {
                        $formattedHours[] = [
                            'day' => $day,
                            'open' => 'Closed',
                            'close' => 'Closed',
                            'is_open' => false
                        ];
                    } else {
                        $formattedHours[] = [
                            'day' => $day,
                            'open' => $dayHours['open'] ?? '08:00',
                            'close' => $dayHours['close'] ?? '18:00',
                            'is_open' => true
                        ];
                    }
                } else {
                    $formattedHours[] = [
                        'day' => $day,
                        'open' => '08:00',
                        'close' => '18:00',
                        'is_open' => true
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operating hours retrieved successfully',
                'data' => [
                    'branch' => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                    ],
                    'hours' => $formattedHours,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
