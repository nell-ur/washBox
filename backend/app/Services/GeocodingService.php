<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    /**
     * Geocode an address to coordinates using Nominatim (OpenStreetMap)
     * Free tier with rate limiting: 1 request per second
     */
    public function geocodeAddress(string $address, array $options = [])
    {
        try {
            // Create cache key
            $cacheKey = 'geocode_' . md5(strtolower(trim($address)));

            // Check cache first (cache for 30 days)
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info('Geocoding: Using cached result for address', ['address' => $address]);
                return $cached;
            }

            // Add default country bias for Philippines
            $countryCode = $options['country'] ?? 'PH';

            // Nominatim API (OpenStreetMap - Free)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'WashBox Laundry Management System',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => strtolower($countryCode),
                    'addressdetails' => 1,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Geocoding API request failed: ' . $response->status());
            }

            $data = $response->json();

            if (empty($data)) {
                Log::warning('Geocoding: No results found', ['address' => $address]);
                return [
                    'success' => false,
                    'error' => 'Address not found',
                    'address' => $address,
                ];
            }

            $result = $data[0];

            $geocoded = [
                'success' => true,
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'display_name' => $result['display_name'],
                'address_details' => $result['address'] ?? null,
                'osm_type' => $result['osm_type'] ?? null,
                'osm_id' => $result['osm_id'] ?? null,
                'importance' => $result['importance'] ?? null,
                'original_address' => $address,
            ];

            // Cache the result
            Cache::put($cacheKey, $geocoded, now()->addDays(30));

            Log::info('Geocoding: Successfully geocoded address', [
                'address' => $address,
                'lat' => $geocoded['latitude'],
                'lon' => $geocoded['longitude'],
            ]);

            return $geocoded;

        } catch (\Exception $e) {
            Log::error('Geocoding failed', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'address' => $address,
            ];
        }
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(float $latitude, float $longitude)
    {
        try {
            $cacheKey = 'reverse_geocode_' . md5("{$latitude},{$longitude}");

            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'WashBox Laundry Management System',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'addressdetails' => 1,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Reverse geocoding failed');
            }

            $data = $response->json();

            $result = [
                'success' => true,
                'address' => $data['display_name'] ?? 'Unknown location',
                'address_details' => $data['address'] ?? null,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            Cache::put($cacheKey, $result, now()->addDays(30));

            return $result;

        } catch (\Exception $e) {
            Log::error('Reverse geocoding failed', [
                'lat' => $latitude,
                'lon' => $longitude,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch geocode multiple addresses
     * Implements rate limiting (1 request per second for Nominatim)
     */
    public function geocodeBatch(array $addresses)
    {
        $results = [];

        foreach ($addresses as $address) {
            $results[] = $this->geocodeAddress($address);

            // Rate limiting: 1 request per second for Nominatim
            sleep(1);
        }

        return $results;
    }

    /**
     * Search for places near coordinates
     */
    public function searchNearby(float $latitude, float $longitude, int $radiusMeters = 1000)
    {
        try {
            // Nominatim doesn't support radius search directly
            // We'll use a bounding box approximation
            $latDelta = ($radiusMeters / 111000); // ~111km per degree
            $lonDelta = $latDelta / cos(deg2rad($latitude));

            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'WashBox Laundry Management System',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'viewbox' => implode(',', [
                        $longitude - $lonDelta,
                        $latitude + $latDelta,
                        $longitude + $lonDelta,
                        $latitude - $latDelta,
                    ]),
                    'bounded' => 1,
                    'limit' => 20,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Search failed');
            }

            return [
                'success' => true,
                'results' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('Nearby search failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate coordinates
     */
    public function validateCoordinates(?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        return (
            $latitude >= -90 && $latitude <= 90 &&
            $longitude >= -180 && $longitude <= 180
        );
    }

    /**
     * Calculate if coordinates are within Philippines bounds
     */
    public function isInPhilippines(float $latitude, float $longitude): bool
    {
        // Philippines approximate bounding box
        return (
            $latitude >= 4.5 && $latitude <= 21.3 &&
            $longitude >= 116.0 && $longitude <= 127.0
        );
    }
}
