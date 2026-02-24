<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\PickupRequest;
use App\Services\RouteService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogisticsController extends Controller
{
    protected $routeService;
    protected $geocodingService;

    /**
     * OSRM public demo server — replace with your own for production.
     * Self-host guide: https://github.com/Project-OSRM/osrm-backend
     */
    private const OSRM_BASE = 'https://router.project-osrm.org';

    public function __construct(?RouteService $routeService = null)
    {
        $this->routeService = $routeService ?? new RouteService();

        if (class_exists('\App\Services\GeocodingService')) {
            $this->geocodingService = new \App\Services\GeocodingService();
        }
    }

    // =========================================================================
    // GEOCODING
    // =========================================================================

    /**
     * Geocode an address to coordinates
     * Endpoint: POST /admin/logistics/geocode
     */
    public function geocodeAddress(Request $request)
    {
        try {
            $validated = $request->validate([
                'address' => 'required|string|min:3'
            ]);

            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'WashBox Laundry Management System'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'              => $validated['address'] . ', Philippines',
                    'format'         => 'json',
                    'limit'          => 1,
                    'countrycodes'   => 'ph',
                    'addressdetails' => 1,
                ]);

            if ($response->failed() || empty($response->json())) {
                return response()->json(['success' => false, 'error' => 'Address not found'], 404);
            }

            $result = $response->json()[0];

            return response()->json([
                'success'   => true,
                'latitude'  => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'address'   => $result['display_name'],
                'raw'       => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Geocoding failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Geocode a specific pickup's address
     * Endpoint: POST /admin/pickups/{pickup}/geocode
     */
    public function geocodePickup(PickupRequest $pickup, Request $request)
    {
        try {
            $address = $request->input('address', $pickup->pickup_address);

            if (!$address) {
                return response()->json(['success' => false, 'error' => 'No address to geocode'], 400);
            }

            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'WashBox Laundry Management System'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'            => $address . ', Philippines',
                    'format'       => 'json',
                    'limit'        => 1,
                    'countrycodes' => 'ph',
                ]);

            if ($response->failed() || empty($response->json())) {
                return response()->json(['success' => false, 'error' => 'Address not found'], 404);
            }

            $result   = $response->json()[0];
            $latitude  = (float) $result['lat'];
            $longitude = (float) $result['lon'];

            $pickup->update(['latitude' => $latitude, 'longitude' => $longitude]);

            Log::info('Pickup geocoded', ['pickup_id' => $pickup->id, 'lat' => $latitude, 'lon' => $longitude]);

            return response()->json([
                'success'   => true,
                'latitude'  => $latitude,
                'longitude' => $longitude,
                'address'   => $result['display_name'],
                'pickup'    => $pickup->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Pickup geocoding failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SINGLE PICKUP ROUTE  (OSRM /route/v1/driving)
    // =========================================================================

    /**
     * Get road-following route from nearest branch to a single pickup.
     * Endpoint: GET /admin/pickups/{pickup}/route
     */
    public function getPickupRoute(PickupRequest $pickup, Request $request)
    {
        try {
            $branch = $pickup->branch ?? Branch::first();

            // Auto-geocode if coordinates missing
            if (!$pickup->latitude || !$pickup->longitude) {
                if ($pickup->pickup_address && $this->geocodingService) {
                    $geocoded = $this->geocodingService->geocodeAddress($pickup->pickup_address);
                    if ($geocoded['success']) {
                        $pickup->update(['latitude' => $geocoded['latitude'], 'longitude' => $geocoded['longitude']]);
                    } else {
                        return response()->json(['success' => false, 'error' => 'Could not geocode: ' . $pickup->pickup_address], 400);
                    }
                } else {
                    return response()->json(['success' => false, 'error' => 'Pickup has no coordinates or address'], 400);
                }
            }

            if (!$branch) {
                return response()->json(['success' => false, 'error' => 'No branch found'], 400);
            }

            // ── Call OSRM Route API directly ──
            $osrmRoute = $this->callOsrmRoute(
                $branch->latitude, $branch->longitude,
                $pickup->latitude, $pickup->longitude
            );

            if ($osrmRoute) {
                $geometry = json_encode($osrmRoute['coordinates']); // [[lat,lng], ...]
                $eta      = now()->addSeconds((int) $osrmRoute['duration'])->format('h:i A');

                return response()->json([
                    'success' => true,
                    'route'   => [
                        'geometry' => $geometry,
                        'distance' => [
                            'value' => round($osrmRoute['distance']),
                            'text'  => round($osrmRoute['distance'] / 1000, 2) . ' km',
                        ],
                        'duration' => [
                            'value' => round($osrmRoute['duration']),
                            'text'  => ceil($osrmRoute['duration'] / 60) . ' min',
                        ],
                        'waypoints' => [
                            'start' => ['latitude' => (float) $branch->latitude, 'longitude' => (float) $branch->longitude],
                            'end'   => ['latitude' => (float) $pickup->latitude, 'longitude' => (float) $pickup->longitude],
                        ],
                    ],
                    'instructions'      => $osrmRoute['steps'] ?? [],
                    'provider'          => 'osrm',
                    'estimated_arrival' => $eta,
                ]);
            }

            // ── Fallback: RouteService ──
            try {
                $route = $this->routeService->getRouteFromBranch($pickup, 'osrm');
                if ($route['success']) {
                    return response()->json([
                        'success'           => true,
                        'route'             => $route['route'],
                        'instructions'      => $route['instructions'] ?? [],
                        'provider'          => $route['provider'],
                        'estimated_arrival' => $route['estimated_arrival'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('RouteService fallback failed', ['error' => $e->getMessage()]);
            }

            throw new \Exception('All routing providers failed');

        } catch (\Exception $e) {
            Log::error('Route calculation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get directions with turn-by-turn instructions
     */
    public function getDirections(PickupRequest $pickup)
    {
        try {
            $branch = $pickup->branch ?? Branch::first();

            if (!$branch || !$pickup->latitude || !$pickup->longitude) {
                return response()->json(['success' => false, 'error' => 'Invalid coordinates'], 400);
            }

            $osrmRoute = $this->callOsrmRoute(
                $branch->latitude, $branch->longitude,
                $pickup->latitude, $pickup->longitude
            );

            if ($osrmRoute) {
                return response()->json([
                    'success'  => true,
                    'pickup'   => ['id' => $pickup->id, 'customer' => $pickup->customer->name ?? 'Customer', 'address' => $pickup->pickup_address],
                    'branch'   => ['name' => $branch->name, 'address' => $branch->address],
                    'distance' => round($osrmRoute['distance'] / 1000, 2) . ' km',
                    'duration' => ceil($osrmRoute['duration'] / 60) . ' min',
                    'instructions'      => $osrmRoute['steps'] ?? [],
                    'provider'          => 'osrm',
                    'estimated_arrival' => now()->addSeconds((int) $osrmRoute['duration'])->format('h:i A'),
                ]);
            }

            // Fallback
            $route = $this->routeService->getRouteFromBranch($pickup, 'osrm');
            if (!$route['success']) throw new \Exception($route['error'] ?? 'Route calculation failed');

            return response()->json([
                'success'  => true,
                'pickup'   => ['id' => $pickup->id, 'customer' => $pickup->customer->name ?? 'Customer', 'address' => $pickup->pickup_address],
                'branch'   => ['name' => $branch->name, 'address' => $branch->address],
                'distance' => $route['route']['distance']['text'],
                'duration' => $route['route']['duration']['text'],
                'instructions'      => $route['instructions'],
                'provider'          => $route['provider'],
                'estimated_arrival' => $route['estimated_arrival'],
            ]);
        } catch (\Exception $e) {
            Log::error('Directions request failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start navigation for pickup
     */
    public function startNavigation(PickupRequest $pickup, Request $request)
    {
        try {
            $pickup->update(['status' => 'en_route', 'en_route_at' => now(), 'assigned_to' => Auth::id()]);
            return response()->json(['success' => true, 'message' => 'Navigation started for pickup #' . $pickup->id, 'pickup' => $pickup->fresh()]);
        } catch (\Exception $e) {
            Log::error('Navigation start failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MULTI-STOP OPTIMIZED ROUTE  (OSRM /trip/v1/driving)
    // =========================================================================

    /**
     * Get optimized multi-stop route for multiple pickups.
     * Uses OSRM Trip API for REAL road-following geometry + stop order optimization.
     *
     * Endpoint: POST /admin/logistics/optimize-route
     *
     * Priority chain:
     *   1. OSRM Trip API  (optimized order + road geometry)
     *   2. OSRM Route API (sequential order + road geometry)
     *   3. RouteService    (if available)
     *   4. Straight-line   (absolute last resort)
     */
    public function getOptimizedRoute(Request $request)
    {
        try {
            $pickupIds = $request->input('pickup_ids', []);
            $branchId  = $request->input('branch_id') ?? Branch::first()->id;

            Log::info('Route optimization requested', ['pickup_ids' => $pickupIds, 'branch_id' => $branchId]);

            if (empty($pickupIds)) {
                return response()->json(['success' => false, 'error' => 'No pickups selected'], 400);
            }

            $branch = Branch::find($branchId);
            if (!$branch) {
                return response()->json(['success' => false, 'error' => 'Branch not found'], 404);
            }

            $pickups = PickupRequest::with('customer')
                ->whereIn('id', $pickupIds)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            if ($pickups->isEmpty()) {
                return response()->json(['success' => false, 'error' => 'No valid pickups with coordinates'], 400);
            }

            // Build waypoints array: [lng, lat] for OSRM (branch first)
            $waypoints = [[(float) $branch->longitude, (float) $branch->latitude]];
            $stopNames = [$branch->name];

            foreach ($pickups as $pickup) {
                $waypoints[] = [(float) $pickup->longitude, (float) $pickup->latitude];
                $stopNames[] = $pickup->customer->name ?? 'Pickup #' . $pickup->id;
            }

            // ── 1. OSRM Trip API (optimized order + real roads) ──
            $tripResult = $this->callOsrmTrip($waypoints);
            if ($tripResult) {
                Log::info('Using OSRM Trip API for optimized route');
                return response()->json([
                    'success'      => true,
                    'optimization' => $this->buildTripResponse($tripResult, $stopNames, $branch, $pickups),
                ]);
            }

            // ── 2. OSRM Route API (sequential + real roads) ──
            Log::warning('OSRM Trip failed, trying OSRM Route (sequential)');
            $routeResult = $this->callOsrmRouteMulti($waypoints);
            if ($routeResult) {
                return response()->json([
                    'success'      => true,
                    'optimization' => $this->buildRouteResponse($routeResult, $stopNames, $branch, $pickups),
                ]);
            }

            // ── 3. RouteService fallback ──
            if (method_exists($this->routeService, 'getOptimizedRoute')) {
                try {
                    $optimization = $this->routeService->getOptimizedRoute($branchId, $pickupIds, 'osrm');
                    if ($optimization['success']) {
                        return response()->json([
                            'success'      => true,
                            'optimization' => $this->transformRouteData($optimization, $branch, $pickups),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('RouteService fallback failed', ['error' => $e->getMessage()]);
                }
            }

            // ── 4. Straight-line absolute last resort ──
            Log::warning('All OSRM calls failed, using straight-line fallback');
            return $this->createStraightLineFallback($branch, $pickups);

        } catch (\Exception $e) {
            Log::error('Route optimization failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // OSRM API CALLERS  (Direct HTTP — no external service dependency)
    // =========================================================================

    /**
     * OSRM Route API — single A→B route.
     * Returns real road-following geometry as [[lat, lng], ...].
     */
    private function callOsrmRoute(float $startLat, float $startLng, float $endLat, float $endLng): ?array
    {
        try {
            $url = self::OSRM_BASE . "/route/v1/driving/{$startLng},{$startLat};{$endLng},{$endLat}";

            $response = Http::timeout(15)->get($url, [
                'overview'   => 'full',
                'geometries' => 'geojson',
                'steps'      => 'true',
            ]);

            if ($response->failed()) {
                Log::warning('OSRM Route HTTP error', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'])) {
                Log::warning('OSRM Route no routes', ['code' => $data['code'] ?? 'unknown']);
                return null;
            }

            $route = $data['routes'][0];

            // GeoJSON [lng, lat] → Leaflet [lat, lng]
            $coordinates = array_map(fn($c) => [$c[1], $c[0]], $route['geometry']['coordinates']);

            // Extract turn-by-turn steps
            $steps = [];
            foreach ($route['legs'] as $leg) {
                foreach ($leg['steps'] ?? [] as $step) {
                    $steps[] = [
                        'instruction' => $step['maneuver']['instruction'] ?? ('Continue on ' . ($step['name'] ?: 'road')),
                        'distance'    => round($step['distance']),
                        'duration'    => round($step['duration']),
                        'name'        => $step['name'] ?? '',
                        'type'        => $step['maneuver']['type'] ?? 'turn',
                    ];
                }
            }

            Log::info('OSRM Route OK', ['points' => count($coordinates), 'dist' => round($route['distance']), 'dur' => round($route['duration'])]);

            return [
                'coordinates' => $coordinates,
                'distance'    => $route['distance'],    // meters
                'duration'    => $route['duration'],    // seconds
                'steps'       => $steps,
            ];
        } catch (\Exception $e) {
            Log::error('OSRM Route call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * OSRM Route API — sequential multi-waypoint A→B→C→D.
     * Fallback when Trip API fails. Still follows real roads.
     */
    private function callOsrmRouteMulti(array $waypoints): ?array
    {
        try {
            $coordStr = implode(';', array_map(fn($wp) => "{$wp[0]},{$wp[1]}", $waypoints));
            $url      = self::OSRM_BASE . "/route/v1/driving/{$coordStr}";

            $response = Http::timeout(20)->get($url, [
                'overview'   => 'full',
                'geometries' => 'geojson',
                'steps'      => 'true',
            ]);

            if ($response->failed()) return null;

            $data = $response->json();
            if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'])) return null;

            $route       = $data['routes'][0];
            $coordinates = array_map(fn($c) => [$c[1], $c[0]], $route['geometry']['coordinates']);

            Log::info('OSRM Route Multi OK', ['waypoints' => count($waypoints), 'points' => count($coordinates), 'dist' => round($route['distance'])]);

            return [
                'coordinates' => $coordinates,
                'distance'    => $route['distance'],
                'duration'    => $route['duration'],
                'waypoints'   => $data['waypoints'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('OSRM Route Multi failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * OSRM Trip API — optimized multi-stop route.
     * Re-orders stops for shortest total distance AND returns road-following geometry.
     */
    private function callOsrmTrip(array $waypoints): ?array
    {
        try {
            $coordStr = implode(';', array_map(fn($wp) => "{$wp[0]},{$wp[1]}", $waypoints));
            $url      = self::OSRM_BASE . "/trip/v1/driving/{$coordStr}";

            $response = Http::timeout(20)->get($url, [
                'overview'   => 'full',
                'geometries' => 'geojson',
                'steps'      => 'true',
                'roundtrip'  => 'false',
                'source'     => 'first',     // always start at branch
            ]);

            if ($response->failed()) {
                Log::warning('OSRM Trip HTTP error', ['status' => $response->status()]);
                return null;
            }

            $data = $response->json();

            if (($data['code'] ?? '') !== 'Ok' || empty($data['trips'])) {
                Log::warning('OSRM Trip no trips', ['code' => $data['code'] ?? 'unknown']);
                return null;
            }

            $trip        = $data['trips'][0];
            $coordinates = array_map(fn($c) => [$c[1], $c[0]], $trip['geometry']['coordinates']);

            Log::info('OSRM Trip OK', ['waypoints' => count($waypoints), 'points' => count($coordinates), 'dist' => round($trip['distance']), 'dur' => round($trip['duration'])]);

            return [
                'coordinates' => $coordinates,
                'distance'    => $trip['distance'],    // meters
                'duration'    => $trip['duration'],    // seconds
                'waypoints'   => $data['waypoints'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('OSRM Trip call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // =========================================================================
    // RESPONSE BUILDERS
    // =========================================================================

    /**
     * Build frontend response from OSRM Trip result (optimized order).
     */
    private function buildTripResponse(array $tripResult, array $stopNames, Branch $branch, $pickups): array
    {
        $distKm  = round($tripResult['distance'] / 1000, 2);
        $durMins = (int) ceil($tripResult['duration'] / 60);

        $stops         = [];
        $osrmWaypoints = $tripResult['waypoints'] ?? [];

        if (!empty($osrmWaypoints)) {
            foreach ($osrmWaypoints as $idx => $wp) {
                $origIdx = $wp['waypoint_index'] ?? $idx;
                $stops[] = [
                    'name'           => $stopNames[$origIdx] ?? 'Stop ' . ($idx + 1),
                    'type'           => $idx === 0 ? 'branch' : 'pickup',
                    'latitude'       => $wp['location'][1],
                    'longitude'      => $wp['location'][0],
                    'waypoint_index' => $origIdx,
                ];
            }
        } else {
            $stops = $this->buildFallbackStops($branch, $pickups);
        }

        return [
            'coordinates'    => $tripResult['coordinates'],
            'stops'          => $stops,
            'distance'       => $distKm . ' km',
            'duration'       => $this->formatDuration($durMins),
            'total_pickups'  => count($pickups),
            'estimated_time' => $this->formatDuration($durMins),
            'route_type'     => 'osrm_trip',
        ];
    }

    /**
     * Build frontend response from OSRM Route result (sequential order).
     */
    private function buildRouteResponse(array $routeResult, array $stopNames, Branch $branch, $pickups): array
    {
        $distKm  = round($routeResult['distance'] / 1000, 2);
        $durMins = (int) ceil($routeResult['duration'] / 60);

        return [
            'coordinates'    => $routeResult['coordinates'],
            'stops'          => $this->buildFallbackStops($branch, $pickups),
            'distance'       => $distKm . ' km',
            'duration'       => $this->formatDuration($durMins),
            'total_pickups'  => count($pickups),
            'estimated_time' => $this->formatDuration($durMins),
            'route_type'     => 'osrm_route',
        ];
    }

    /**
     * Build stops array in original order (branch + pickups).
     */
    private function buildFallbackStops(Branch $branch, $pickups): array
    {
        $stops   = [];
        $stops[] = [
            'name' => $branch->name, 'type' => 'branch',
            'latitude' => (float) $branch->latitude, 'longitude' => (float) $branch->longitude,
            'waypoint_index' => 0,
        ];
        foreach ($pickups as $i => $pickup) {
            $stops[] = [
                'name' => $pickup->customer->name ?? 'Pickup #' . $pickup->id, 'type' => 'pickup',
                'latitude' => (float) $pickup->latitude, 'longitude' => (float) $pickup->longitude,
                'waypoint_index' => $i + 1,
            ];
        }
        return $stops;
    }

    /**
     * Transform legacy RouteService response.
     */
    private function transformRouteData($serviceResponse, $branch, $pickups)
    {
        $coordinates = [];

        if (isset($serviceResponse['route']['geometry']) && is_string($serviceResponse['route']['geometry'])) {
            $coordinates = $this->decodePolyline($serviceResponse['route']['geometry']);
        } elseif (isset($serviceResponse['geometry']) && is_string($serviceResponse['geometry'])) {
            $coordinates = $this->decodePolyline($serviceResponse['geometry']);
        } elseif (isset($serviceResponse['coordinates']) && is_array($serviceResponse['coordinates'])) {
            $coordinates = $serviceResponse['coordinates'];
        }

        if (empty($coordinates)) {
            $coordinates[] = [(float) $branch->latitude, (float) $branch->longitude];
            foreach ($pickups as $pickup) {
                $coordinates[] = [(float) $pickup->latitude, (float) $pickup->longitude];
            }
        }

        $stops    = $this->buildFallbackStops($branch, $pickups);
        $distance = $serviceResponse['distance'] ?? $serviceResponse['route']['distance'] ?? null;
        $duration = $serviceResponse['duration'] ?? $serviceResponse['route']['duration'] ?? null;

        if (!$distance) {
            $totalDist = 0;
            for ($i = 0; $i < count($coordinates) - 1; $i++) {
                $totalDist += $this->calculateDistance($coordinates[$i][0], $coordinates[$i][1], $coordinates[$i + 1][0], $coordinates[$i + 1][1]);
            }
            $distance = round($totalDist, 2) . ' km';
        }

        if (!$duration) {
            $totalMinutes = count($pickups) * 5 + round(($totalDist ?? 0) / 40 * 60);
            $duration     = $totalMinutes . ' mins';
        }

        return [
            'coordinates'   => $coordinates,
            'stops'         => $stops,
            'distance'      => $distance,
            'duration'      => $duration,
            'total_pickups' => count($pickups),
            'route_type'    => 'route_service',
        ];
    }

    /**
     * Straight-line fallback (when OSRM is completely unreachable).
     */
    private function createStraightLineFallback($branch, $pickups)
    {
        $coordinates = [[(float) $branch->latitude, (float) $branch->longitude]];
        $stops       = $this->buildFallbackStops($branch, $pickups);

        foreach ($pickups as $pickup) {
            $coordinates[] = [(float) $pickup->latitude, (float) $pickup->longitude];
        }

        $totalDistance = 0;
        for ($i = 0; $i < count($coordinates) - 1; $i++) {
            $totalDistance += $this->calculateDistance($coordinates[$i][0], $coordinates[$i][1], $coordinates[$i + 1][0], $coordinates[$i + 1][1]);
        }

        $totalMinutes = round(($totalDistance / 40) * 60) + count($pickups) * 5;

        return response()->json([
            'success'      => true,
            'optimization' => [
                'coordinates'    => $coordinates,
                'stops'          => $stops,
                'distance'       => round($totalDistance, 2) . ' km',
                'duration'       => $this->formatDuration($totalMinutes),
                'total_pickups'  => count($pickups),
                'estimated_time' => $this->formatDuration($totalMinutes),
                'route_type'     => 'straight_line',
            ],
        ]);
    }

    // =========================================================================
    // OTHER ENDPOINTS
    // =========================================================================

    /** Get active delivery routes */
    public function getActiveRoutes()
    {
        try {
            $pickups = PickupRequest::with(['customer', 'branch', 'assignedStaff'])
                ->whereIn('status', ['accepted', 'en_route'])
                ->whereNotNull('latitude')->whereNotNull('longitude')->get();

            $routes = $pickups->groupBy('assigned_to')->map(fn($dps, $id) => [
                'driver_id'    => $id,
                'pickup_count' => $dps->count(),
                'pickups'      => $dps->map(fn($p) => [
                    'id' => $p->id, 'customer' => $p->customer->name, 'address' => $p->pickup_address,
                    'latitude' => $p->latitude, 'longitude' => $p->longitude, 'status' => $p->status,
                ]),
            ])->values();

            return response()->json(['success' => true, 'routes' => $routes, 'total_pickups' => $pickups->count()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** Create delivery route (placeholder) */
    public function createDeliveryRoute(Request $request)
    {
        try {
            $request->validate(['pickup_ids' => 'required|array', 'pickup_ids.*' => 'exists:pickup_requests,id']);
            return response()->json(['success' => true, 'message' => 'Route creation feature coming soon']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** Get route statistics */
    public function getRouteStats()
    {
        try {
            return response()->json([
                'success' => true,
                'stats'   => [
                    'total_routes_today' => PickupRequest::whereDate('created_at', today())->whereIn('status', ['accepted', 'en_route', 'picked_up'])->count(),
                    'active_routes'      => PickupRequest::whereIn('status', ['accepted', 'en_route'])->count(),
                    'completed_today'    => PickupRequest::whereDate('picked_up_at', today())->where('status', 'picked_up')->count(),
                    'pending_pickups'    => PickupRequest::where('status', 'pending')->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** Update vehicle location */
    public function updateVehicleLocation(Request $request)
    {
        try {
            $v = $request->validate(['pickup_id' => 'required|exists:pickup_requests,id', 'latitude' => 'required|numeric', 'longitude' => 'required|numeric']);
            PickupRequest::findOrFail($v['pickup_id'])->update(['current_latitude' => $v['latitude'], 'current_longitude' => $v['longitude'], 'last_location_update' => now()]);
            return response()->json(['success' => true, 'message' => 'Location updated']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** Complete pickup */
    public function completePickup(PickupRequest $pickup)
    {
        try {
            $pickup->update(['status' => 'picked_up', 'picked_up_at' => now()]);
            return response()->json(['success' => true, 'message' => 'Pickup completed', 'pickup' => $pickup->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start navigation for multiple pickups simultaneously.
     * Endpoint: POST /admin/logistics/start-multi-pickup
     */
    public function startMultiPickup(Request $request)
    {
        try {
            $validated = $request->validate(['pickup_ids' => 'required|array', 'pickup_ids.*' => 'exists:pickup_requests,id']);

            $updated = PickupRequest::whereIn('id', $validated['pickup_ids'])
                ->whereIn('status', ['pending', 'accepted'])
                ->update(['status' => 'en_route', 'en_route_at' => now(), 'assigned_to' => Auth::id()]);

            Log::info('Multi-pickup navigation started', ['user_id' => Auth::id(), 'pickup_ids' => $validated['pickup_ids'], 'updated' => $updated]);

            return response()->json(['success' => true, 'message' => "Navigation started for {$updated} pickup(s)", 'updated' => $updated]);
        } catch (\Exception $e) {
            Log::error('Multi-pickup navigation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending pickups as JSON (for AJAX).
     * Endpoint: GET /admin/logistics/pending-pickups
     */
    public function getPendingPickups()
    {
        try {
            $pickups = PickupRequest::with('customer')
                ->where('status', 'pending')
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id, 'customer' => $p->customer->name ?? 'Unknown',
                    'address' => $p->pickup_address, 'latitude' => $p->latitude,
                    'longitude' => $p->longitude, 'status' => $p->status,
                ]);

            return response()->json(['success' => true, 'pickups' => $pickups]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Haversine distance in km */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Format minutes as human-readable */
    private function formatDuration($minutes)
    {
        if ($minutes < 60) return $minutes . ' mins';
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return $m == 0 ? $h . 'h' : $h . 'h ' . $m . 'm';
    }

    /** Decode encoded polyline (polyline5/polyline6) */
    private function decodePolyline($encoded, $precision = 6)
    {
        $points = [];
        $index  = 0;
        $len    = strlen($encoded);
        $lat    = 0;
        $lng    = 0;
        $factor = pow(10, $precision);

        while ($index < $len) {
            $shift = 0; $result = 0;
            do { $b = ord($encoded[$index++]) - 63; $result |= ($b & 0x1f) << $shift; $shift += 5; } while ($b >= 0x20);
            $lat += (($result & 1) ? ~($result >> 1) : ($result >> 1));

            $shift = 0; $result = 0;
            do { $b = ord($encoded[$index++]) - 63; $result |= ($b & 0x1f) << $shift; $shift += 5; } while ($b >= 0x20);
            $lng += (($result & 1) ? ~($result >> 1) : ($result >> 1));

            $points[] = [$lat / $factor, $lng / $factor];
        }
        return $points;
    }
}
