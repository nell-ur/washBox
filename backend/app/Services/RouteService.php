<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use App\Models\PickupRequest;

class RouteService
{
    // OSRM (Open Source Routing Machine) - Free and Open Source
    protected $osrmBaseUrl = 'http://router.project-osrm.org/route/v1/driving/';

    // Mapbox Directions API (Optional - better accuracy, requires API key)
    protected $mapboxBaseUrl = 'https://api.mapbox.com/directions/v5/mapbox/driving/';
    protected $mapboxAccessToken;

    // Google Maps Directions API (Optional - most accurate, paid)
    protected $googleMapsBaseUrl = 'https://maps.googleapis.com/maps/api/directions/json';
    protected $googleMapsApiKey;

    public function __construct()
    {
        $this->mapboxAccessToken = config('services.mapbox.access_token');
        $this->googleMapsApiKey = config('services.google.maps_api_key');
    }

    /**
     * Get route from branch to pickup location
     */
    public function getRouteFromBranch(PickupRequest $pickup, $provider = 'osrm')
    {
        try {
            $branch = $pickup->branch;

            if (!$branch || !$pickup->latitude || !$pickup->longitude) {
                throw new \Exception('Invalid coordinates');
            }

            $start = [
                'longitude' => $branch->longitude,
                'latitude' => $branch->latitude
            ];

            $end = [
                'longitude' => $pickup->longitude,
                'latitude' => $pickup->latitude
            ];

            switch ($provider) {
                case 'mapbox':
                    return $this->getMapboxRoute($start, $end);
                case 'google':
                    return $this->getGoogleMapsRoute($start, $end);
                case 'osrm':
                default:
                    return $this->getOSRMRoute($start, $end);
            }

        } catch (\Exception $e) {
            Log::error('Route calculation failed: ' . $e->getMessage());
            return $this->getFallbackRoute($pickup);
        }
    }

    /**
     * Get route using OSRM (Open Source Routing Machine)
     */
    private function getOSRMRoute($start, $end)
    {
        $coordinates = "{$start['longitude']},{$start['latitude']};{$end['longitude']},{$end['latitude']}";

        $response = Http::timeout(10)->get($this->osrmBaseUrl . $coordinates, [
            'overview' => 'full',
            'geometries' => 'polyline',
            'steps' => 'true',
            'annotations' => 'true'
        ]);

        if (!$response->successful()) {
            throw new \Exception('OSRM API request failed: ' . $response->status());
        }

        $data = $response->json();

        if ($data['code'] !== 'Ok') {
            throw new \Exception('OSRM route not found');
        }

        $route = $data['routes'][0];

        return $this->formatRouteResponse($route, $start, $end);
    }

    /**
     * Get route using Mapbox Directions API
     */
    private function getMapboxRoute($start, $end)
    {
        if (!$this->mapboxAccessToken) {
            throw new \Exception('Mapbox access token not configured');
        }

        $coordinates = "{$start['longitude']},{$start['latitude']};{$end['longitude']},{$end['latitude']}";

        $response = Http::timeout(10)->get($this->mapboxBaseUrl . $coordinates, [
            'access_token' => $this->mapboxAccessToken,
            'overview' => 'full',
            'geometries' => 'polyline',
            'steps' => 'true',
            'annotations' => 'distance,duration'
        ]);

        if (!$response->successful()) {
            throw new \Exception('Mapbox API request failed: ' . $response->status());
        }

        $data = $response->json();

        if ($data['code'] !== 'Ok') {
            throw new \Exception('Mapbox route not found');
        }

        return $this->formatRouteResponse($data['routes'][0], $start, $end, 'mapbox');
    }

    /**
     * Get route using Google Maps Directions API
     */
    private function getGoogleMapsRoute($start, $end)
    {
        if (!$this->googleMapsApiKey) {
            throw new \Exception('Google Maps API key not configured');
        }

        $origin = "{$start['latitude']},{$start['longitude']}";
        $destination = "{$end['latitude']},{$end['longitude']}";

        $response = Http::timeout(10)->get($this->googleMapsBaseUrl, [
            'origin' => $origin,
            'destination' => $destination,
            'key' => $this->googleMapsApiKey,
            'mode' => 'driving',
            'alternatives' => 'false'
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Maps API request failed: ' . $response->status());
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new \Exception('Google Maps route not found: ' . $data['status']);
        }

        return $this->formatGoogleMapsResponse($data['routes'][0], $start, $end);
    }

    /**
     * Format OSRM/Mapbox route response
     */
    private function formatRouteResponse($route, $start, $end, $provider = 'osrm')
    {
        $distance = $route['distance'] ?? 0; // in meters
        $duration = $route['duration'] ?? 0; // in seconds

        // Extract turn-by-turn instructions
        $instructions = [];
        if (isset($route['legs'][0]['steps'])) {
            foreach ($route['legs'][0]['steps'] as $step) {
                $instructions[] = [
                    'distance' => round($step['distance'] / 1000, 2) . ' km',
                    'duration' => round($step['duration'] / 60, 1) . ' min',
                    'instruction' => strip_tags($step['maneuver']['instruction'] ?? 'Continue'),
                    'type' => $step['maneuver']['type'] ?? 'turn',
                    'modifier' => $step['maneuver']['modifier'] ?? 'straight',
                ];
            }
        }

        return [
            'success' => true,
            'provider' => $provider,
            'route' => [
                'distance' => [
                    'meters' => $distance,
                    'kilometers' => round($distance / 1000, 2),
                    'text' => $this->formatDistance($distance)
                ],
                'duration' => [
                    'seconds' => $duration,
                    'minutes' => round($duration / 60, 1),
                    'text' => $this->formatDuration($duration)
                ],
                'geometry' => $route['geometry'] ?? null,
                'summary' => $route['legs'][0]['summary'] ?? '',
                'waypoints' => [
                    'start' => $start,
                    'end' => $end
                ]
            ],
            'instructions' => $instructions,
            'estimated_arrival' => now()->addSeconds($duration)->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Format Google Maps route response
     */
    private function formatGoogleMapsResponse($route, $start, $end)
    {
        $leg = $route['legs'][0];
        $distance = $leg['distance']['value'] ?? 0; // in meters
        $duration = $leg['duration']['value'] ?? 0; // in seconds

        // Extract turn-by-turn instructions
        $instructions = [];
        foreach ($leg['steps'] as $step) {
            $instructions[] = [
                'distance' => $step['distance']['text'],
                'duration' => $step['duration']['text'],
                'instruction' => strip_tags($step['html_instructions']),
                'maneuver' => $step['maneuver'] ?? null,
            ];
        }

        // Decode Google's polyline
        $polyline = isset($route['overview_polyline']['points'])
            ? $this->decodeGooglePolyline($route['overview_polyline']['points'])
            : null;

        return [
            'success' => true,
            'provider' => 'google',
            'route' => [
                'distance' => [
                    'meters' => $distance,
                    'kilometers' => round($distance / 1000, 2),
                    'text' => $leg['distance']['text']
                ],
                'duration' => [
                    'seconds' => $duration,
                    'minutes' => round($duration / 60, 1),
                    'text' => $leg['duration']['text']
                ],
                'geometry' => $polyline,
                'summary' => $leg['duration']['text'] . ', ' . $leg['distance']['text'],
                'waypoints' => [
                    'start' => $start,
                    'end' => $end
                ]
            ],
            'instructions' => $instructions,
            'estimated_arrival' => now()->addSeconds($duration)->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Decode Google Polyline
     */
    private function decodeGooglePolyline($encoded)
    {
        $points = [];
        $index = $len = 0;
        $lat = $lng = 0;

        while ($index < strlen($encoded)) {
            $b = $shift = $result = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $dlat;

            $shift = $result = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dlng;

            $points[] = [
                'latitude' => $lat * 1e-5,
                'longitude' => $lng * 1e-5
            ];
        }

        return $points;
    }

    /**
     * Get optimized route for multiple pickups (Delivery Route Optimization)
     */
    public function getOptimizedRoute($branchId, array $pickupIds, $provider = 'osrm')
    {
        try {
            $branch = Branch::findOrFail($branchId);
            $pickups = PickupRequest::whereIn('id', $pickupIds)
                ->where('status', '!=', 'cancelled')
                ->get();

            if ($pickups->isEmpty()) {
                throw new \Exception('No valid pickups found');
            }

            // Build coordinates array: Branch -> Pickups -> Branch
            $coordinates = [];
            $coordinates[] = [$branch->longitude, $branch->latitude]; // Start at branch

            foreach ($pickups as $pickup) {
                $coordinates[] = [$pickup->longitude, $pickup->latitude];
            }

            $coordinates[] = [$branch->longitude, $branch->latitude]; // Return to branch

            // Get distance matrix
            $matrix = $this->getDistanceMatrix($coordinates, $provider);

            // Solve Traveling Salesman Problem (TSP)
            $optimizedLaundry = $this->solveTSP($matrix['durations']);

            // Build optimized waypoints
            $waypoints = [];
            $totalDistance = 0;
            $totalDuration = 0;
            $segments = [];

            for ($i = 0; $i < count($optimizedLaundry) - 1; $i++) {
                $fromIndex = $optimizedLaundry[$i];
                $toIndex = $optimizedLaundry[$i + 1];

                $distance = $matrix['distances'][$fromIndex][$toIndex] ?? 0;
                $duration = $matrix['durations'][$fromIndex][$toIndex] ?? 0;

                $totalDistance += $distance;
                $totalDuration += $duration;

                $segments[] = [
                    'from_index' => $fromIndex,
                    'to_index' => $toIndex,
                    'distance' => $distance,
                    'duration' => $duration
                ];

                // Map indices to actual pickups
                if ($i > 0 && $i < count($optimizedLaundry) - 2) {
                    $pickupIndex = $fromIndex - 1; // Adjust for branch at index 0
                    if (isset($pickups[$pickupIndex])) {
                        $waypoints[] = [
                            'type' => 'pickup',
                            'pickup' => $pickups[$pickupIndex],
                            'laundry' => $i
                        ];
                    }
                }
            }

            // Get detailed route for the optimized path
            $detailedRoute = $this->getDetailedRouteForWaypoints($coordinates, $optimizedLaundry, $provider);

            return [
                'success' => true,
                'optimization' => [
                    'total_distance' => [
                        'meters' => $totalDistance,
                        'kilometers' => round($totalDistance / 1000, 2),
                        'text' => $this->formatDistance($totalDistance)
                    ],
                    'total_duration' => [
                        'seconds' => $totalDuration,
                        'minutes' => round($totalDuration / 60, 1),
                        'text' => $this->formatDuration($totalDuration)
                    ],
                    'waypoints' => $waypoints,
                    'segments' => $segments,
                    'optimized_laundry' => $optimizedLaundry,
                    'route_geometry' => $detailedRoute['geometry'] ?? null,
                    'estimated_fuel_cost' => $this->calculateFuelCost($totalDistance),
                    'estimated_completion' => now()->addSeconds($totalDuration)->format('Y-m-d H:i:s')
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Route optimization failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get distance matrix for multiple coordinates
     */
    private function getDistanceMatrix($coordinates, $provider = 'osrm')
    {
        if ($provider === 'osrm') {
            return $this->getOSRMDistanceMatrix($coordinates);
        }

        // For other providers, you might need to implement differently
        throw new \Exception('Distance matrix not implemented for provider: ' . $provider);
    }

    /**
     * Get OSRM distance matrix
     */
    private function getOSRMDistanceMatrix($coordinates)
    {
        $coordinatesString = implode(';', array_map(function($coord) {
            return $coord[0] . ',' . $coord[1];
        }, $coordinates));

        $indices = implode(';', array_keys($coordinates));

        $response = Http::timeout(15)->get(
            'http://router.project-osrm.org/table/v1/driving/' . $coordinatesString,
            [
                'sources' => $indices,
                'destinations' => $indices
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('OSRM table service failed');
        }

        $data = $response->json();

        return [
            'distances' => $data['distances'] ?? [],
            'durations' => $data['durations'] ?? []
        ];
    }

    /**
     * Simple TSP solver (Nearest Neighbor algorithm)
     */
    private function solveTSP($distanceMatrix, $startIndex = 0)
    {
        $numPoints = count($distanceMatrix);
        $visited = array_fill(0, $numPoints, false);
        $route = [];
        $current = $startIndex;

        $visited[$current] = true;
        $route[] = $current;

        // Visit all points
        for ($i = 1; $i < $numPoints; $i++) {
            $nearest = null;
            $minDistance = PHP_INT_MAX;

            for ($j = 0; $j < $numPoints; $j++) {
                if (!$visited[$j] && isset($distanceMatrix[$current][$j])) {
                    $distance = $distanceMatrix[$current][$j];
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $nearest = $j;
                    }
                }
            }

            if ($nearest !== null) {
                $current = $nearest;
                $visited[$current] = true;
                $route[] = $current;
            }
        }

        // Return to start
        $route[] = $startIndex;

        return $route;
    }

    /**
     * Get detailed route for waypoints in optimized laundry
     */
    private function getDetailedRouteForWaypoints($coordinates, $laundry, $provider)
    {
        // Build coordinate string in optimized laundry
        $laundryCoordinates = [];
        foreach ($laundry as $index) {
            $laundryCoordinates[] = $coordinates[$index];
        }

        $coordinatesString = implode(';', array_map(function($coord) {
            return $coord[0] . ',' . $coord[1];
        }, $laundryCoordinates));

        // Get route with all waypoints
        if ($provider === 'osrm') {
            $response = Http::get($this->osrmBaseUrl . $coordinatesString, [
                'overview' => 'full',
                'geometries' => 'polyline',
                'steps' => 'true'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['routes'][0] ?? [];
            }
        }

        return [];
    }

    /**
     * Calculate straight-line distance (as fallback)
     */
    private function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta/2) * sin($latDelta/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta/2) * sin($lonDelta/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Fallback route calculation (straight line)
     */
    private function getFallbackRoute(PickupRequest $pickup)
    {
        $branch = $pickup->branch;

        if (!$branch) {
            return [
                'success' => false,
                'error' => 'Branch not found'
            ];
        }

        $distance = $this->calculateHaversineDistance(
            $branch->latitude,
            $branch->longitude,
            $pickup->latitude,
            $pickup->longitude
        );

        // Estimate travel time (assuming 30 km/h average speed)
        $estimatedTime = ($distance / 1000) / 30 * 3600; // in seconds

        return [
            'success' => true,
            'provider' => 'fallback',
            'route' => [
                'distance' => [
                    'meters' => $distance,
                    'kilometers' => round($distance / 1000, 2),
                    'text' => $this->formatDistance($distance)
                ],
                'duration' => [
                    'seconds' => $estimatedTime,
                    'minutes' => round($estimatedTime / 60, 1),
                    'text' => $this->formatDuration($estimatedTime)
                ],
                'geometry' => null,
                'summary' => 'Straight line estimate',
                'waypoints' => [
                    'start' => ['latitude' => $branch->latitude, 'longitude' => $branch->longitude],
                    'end' => ['latitude' => $pickup->latitude, 'longitude' => $pickup->longitude]
                ]
            ],
            'instructions' => [],
            'estimated_arrival' => now()->addSeconds($estimatedTime)->format('Y-m-d H:i:s'),
            'note' => 'This is an estimated straight-line distance. Actual road distance may vary.'
        ];
    }

    /**
     * Format distance for display
     */
    private function formatDistance($meters)
    {
        if ($meters < 1000) {
            return round($meters) . ' meters';
        }
        return round($meters / 1000, 1) . ' km';
    }

    /**
     * Format duration for display
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return round($seconds) . ' seconds';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . ' minutes';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Calculate estimated fuel cost
     */
    private function calculateFuelCost($meters, $fuelPrice = 60, $fuelEfficiency = 10)
    {
        $km = $meters / 1000;
        $liters = $km / $fuelEfficiency;
        return round($liters * $fuelPrice, 2);
    }

    /**
     * Update pickup request with route information
     */
    public function updatePickupWithRoute(PickupRequest $pickup, $routeData)
    {
        try {
            $pickup->update([
                'distance_from_branch' => $routeData['route']['distance']['kilometers'] ?? null,
                'estimated_travel_time' => $routeData['route']['duration']['minutes'] ?? null,
                'route_data' => json_encode($routeData),
                'estimated_pickup_time' => isset($routeData['estimated_arrival'])
                    ? \Carbon\Carbon::parse($routeData['estimated_arrival'])
                    : null,
                'route_instructions' => isset($routeData['instructions'])
                    ? json_encode($routeData['instructions'])
                    : null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update pickup with route: ' . $e->getMessage());
            return false;
        }
    }
}
