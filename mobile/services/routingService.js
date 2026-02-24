export const RoutingService = {
  // Get route between multiple points
  async getRoute(points, profile = 'driving', alternatives = false) {
    if (points.length < 2) {
      throw new Error('At least 2 points required');
    }

    const coordinates = points
      .map(p => `${p.longitude},${p.latitude}`)
      .join(';');

    const params = new URLSearchParams({
      overview: 'full',
      geometries: 'geojson',
      steps: 'true',
      alternatives: alternatives.toString(),
    });

    try {
      const response = await fetch(
        `https://router.project-osrm.org/route/v1/${profile}/${coordinates}?${params.toString()}`
      );
      
      if (!response.ok) {
        throw new Error('Routing failed');
      }

      const data = await response.json();
      
      if (data.code !== 'Ok') {
        throw new Error(data.message || 'Routing error');
      }

      return {
        distance: data.routes[0].distance, // meters
        duration: data.routes[0].duration, // seconds
        geometry: data.routes[0].geometry,
        steps: data.routes[0].legs[0]?.steps || [],
        coordinates: this.decodePolyline(data.routes[0].geometry.coordinates),
      };
    } catch (error) {
      console.error('Error getting route:', error);
      
      // Fallback: create straight line between points
      return this.createFallbackRoute(points);
    }
  },

  // Decode polyline from OSRM
  decodePolyline(coordinates) {
    // OSRM returns coordinates as [lon, lat]
    return coordinates.map(coord => ({
      latitude: coord[1],
      longitude: coord[0],
    }));
  },

  // Fallback route (straight line)
  createFallbackRoute(points) {
    const coordinates = points;
    const distance = this.calculateTotalDistance(points);
    const duration = distance / 5; // Assume 5 m/s average speed
    
    return {
      distance,
      duration,
      coordinates,
      steps: [],
      isFallback: true,
    };
  },

  // Calculate total distance of route
  calculateTotalDistance(points) {
    let totalDistance = 0;
    for (let i = 1; i < points.length; i++) {
      const distance = this.calculateDistance(points[i - 1], points[i]);
      totalDistance += distance;
    }
    return totalDistance;
  },

  // Calculate distance between two points (Haversine formula)
  calculateDistance(coord1, coord2) {
    const R = 6371e3; // Earth radius in meters
    const φ1 = coord1.latitude * Math.PI / 180;
    const φ2 = coord2.latitude * Math.PI / 180;
    const Δφ = (coord2.latitude - coord1.latitude) * Math.PI / 180;
    const Δλ = (coord2.longitude - coord1.longitude) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  },

  // Get ETA for delivery
  async getETA(origin, destination, profile = 'driving') {
    const route = await this.getRoute([origin, destination], profile);
    
    return {
      distance: route.distance,
      duration: route.duration,
      formattedDistance: this.formatDistance(route.distance),
      formattedDuration: this.formatDuration(route.duration),
      arrivalTime: this.calculateArrivalTime(route.duration),
    };
  },

  // Format distance
  formatDistance(meters) {
    if (meters < 1000) {
      return `${Math.round(meters)} m`;
    }
    return `${(meters / 1000).toFixed(1)} km`;
  },

  // Format duration
  formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (hours > 0) {
      return `${hours}h ${minutes}m`;
    }
    return `${minutes} min`;
  },

  // Calculate arrival time
  calculateArrivalTime(durationSeconds) {
    const now = new Date();
    const arrival = new Date(now.getTime() + durationSeconds * 1000);
    
    return {
      timestamp: arrival.getTime(),
      formatted: arrival.toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit' 
      }),
    };
  },
};