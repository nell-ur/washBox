import React, { useState, useEffect, useRef } from 'react';
import { View, StyleSheet, Text, TouchableOpacity, Alert } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import OSMMap from '../common/OSMMap';
import { LocationService } from '../../services/locationService';
import { RoutingService } from '../../services/routingService';
import LocationCard from './LocationCard';

// Dumaguete City center — default fallback for WashBox
const DUMAGUETE_DEFAULT = {
  latitude: 9.3068,
  longitude: 123.3054,
  latitudeDelta: 0.05,
  longitudeDelta: 0.05,
};

const PickupDeliveryMap = ({
  pickupLocation,
  deliveryLocation,   // kept for order-tracking screens; null in pickup modal
  driverLocation,
  showRoute = true,
  onLocationSelect,
  interactive = true,
  style,
}) => {
  const mapRef = useRef(null);
  const [region, setRegion] = useState(null);
  const [route, setRoute] = useState(null);
  const [eta, setEta] = useState(null);
  const [selectedMarker, setSelectedMarker] = useState(null);
  const [userLocation, setUserLocation] = useState(null);

  useEffect(() => {
    initializeMap();
  }, [pickupLocation, deliveryLocation]);

  const initializeMap = async () => {
    try {
      const location = await LocationService.getCurrentLocation();
      setUserLocation(location);

      let initialRegion;

      if (pickupLocation && deliveryLocation) {
        // Both locations — fit them plus user
        initialRegion = calculateRegionForCoordinates([pickupLocation, deliveryLocation, location]);
      } else if (pickupLocation) {
        // Pickup only (standard pickup modal case)
        initialRegion = { ...pickupLocation, latitudeDelta: 0.01, longitudeDelta: 0.01 };
      } else {
        // No locations yet — center on user
        initialRegion = { ...location, latitudeDelta: 0.02, longitudeDelta: 0.02 };
      }

      setRegion(initialRegion);

      // Only calculate route when we have both a pickup and a driver/delivery location
      if (pickupLocation && deliveryLocation && showRoute) {
        calculateRoute();
      }
    } catch (error) {
      console.error('Error initializing map:', error);
      // Default to Dumaguete City, not San Francisco
      setRegion(DUMAGUETE_DEFAULT);
    }
  };

  const calculateRoute = async () => {
    try {
      const points = [
        pickupLocation,
        ...(driverLocation ? [driverLocation] : []),
        deliveryLocation,
      ];

      const routeData = await RoutingService.getRoute(points);
      setRoute(routeData);

      const etaData = await RoutingService.getETA(
        driverLocation || pickupLocation,
        deliveryLocation
      );
      setEta(etaData);
    } catch (error) {
      console.error('Error calculating route:', error);
    }
  };

  const calculateRegionForCoordinates = (coordinates) => {
    const lats = coordinates.map(c => c.latitude);
    const lngs = coordinates.map(c => c.longitude);

    const minLat = Math.min(...lats);
    const maxLat = Math.max(...lats);
    const minLng = Math.min(...lngs);
    const maxLng = Math.max(...lngs);

    return {
      latitude: (minLat + maxLat) / 2,
      longitude: (minLng + maxLng) / 2,
      latitudeDelta: Math.max((maxLat - minLat) * 1.4, 0.01),
      longitudeDelta: Math.max((maxLng - minLng) * 1.4, 0.01),
    };
  };

  const handleMarkerPress = (marker) => {
    setSelectedMarker(marker);
    onLocationSelect?.(marker);
  };

  const handleCenterUser = async () => {
    try {
      const location = await LocationService.getCurrentLocation();
      mapRef.current?.animateToRegion(
        { ...location, latitudeDelta: 0.01, longitudeDelta: 0.01 },
        500
      );
    } catch {
      Alert.alert('Error', 'Unable to get your location');
    }
  };

  const handleFitAll = () => {
    const coords = [
      pickupLocation,
      deliveryLocation,
      userLocation,
    ].filter(Boolean);

    if (coords.length > 0) {
      mapRef.current?.animateToRegion(calculateRegionForCoordinates(coords), 500);
    }
  };

  const getMarkers = () => {
    const markers = [];

    if (userLocation) {
      markers.push({
        coordinate: userLocation,
        title: 'Your Location',
        type: 'user',
        customIcon: true,
        isActive: true,
      });
    }

    // Pickup marker — always shown when available
    if (pickupLocation) {
      markers.push({
        coordinate: pickupLocation,
        title: 'Pickup Location',
        type: 'pickup',
        customIcon: true,
        isActive: true,
      });
    }

    // Delivery marker — only used in order-tracking context, not in pickup modal
    // (delivery is same address as pickup so we don't show a separate pin)
    if (deliveryLocation) {
      markers.push({
        coordinate: deliveryLocation,
        title: 'Delivery Location',
        type: 'pickup',   // reuse pickup pin style since address is the same
        customIcon: true,
        isActive: true,
      });
    }

    if (driverLocation) {
      markers.push({
        coordinate: driverLocation,
        title: 'Driver',
        type: 'driver',
        customIcon: true,
        isActive: true,
      });
    }

    return markers;
  };

  const getPolylines = () => {
    const polylines = [];

    if (route?.coordinates) {
      polylines.push({
        coordinates: route.coordinates,
        color: '#0EA5E9',
        width: 4,
      });
    }

    // Dashed line from driver to pickup
    if (driverLocation && pickupLocation) {
      polylines.push({
        coordinates: [driverLocation, pickupLocation],
        color: '#10B981',
        width: 2,
        dashed: true,
      });
    }

    return polylines;
  };

  const getCircles = () => {
    if (!pickupLocation) return [];

    return [{
      center: pickupLocation,
      radius: 5000,
      fillColor: 'rgba(16, 185, 129, 0.04)',
      strokeColor: 'rgba(16, 185, 129, 0.25)',
      strokeWidth: 1,
    }];
  };

  const hasLocations = !!(pickupLocation || userLocation);

  return (
    <View style={[styles.container, style]}>
      <OSMMap
        ref={mapRef}
        initialRegion={region}
        markers={getMarkers()}
        polylines={getPolylines()}
        circles={getCircles()}
        onMarkerPress={handleMarkerPress}
        onRegionChange={setRegion}
        zoomEnabled={interactive}
        scrollEnabled={interactive}
        style={styles.map}
      />

      {/* Controls */}
      <View style={styles.controls}>
        <TouchableOpacity style={styles.controlButton} onPress={handleCenterUser}>
          <Ionicons name="locate" size={22} color="#0EA5E9" />
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.controlButton, !hasLocations && styles.controlDisabled]}
          onPress={handleFitAll}
          disabled={!hasLocations}
        >
          <Ionicons name="expand" size={22} color={hasLocations ? '#0EA5E9' : '#64748B'} />
        </TouchableOpacity>
      </View>

      {/* ETA — only shown for order tracking (when driver is present) */}
      {eta && driverLocation && (
        <View style={styles.etaContainer}>
          <View style={styles.etaRow}>
            <Ionicons name="time-outline" size={16} color="#0EA5E9" />
            <Text style={styles.etaText}>
              {eta.formattedDuration}  ·  {eta.formattedDistance}
            </Text>
          </View>
          <Text style={styles.etaSubText}>
            Est. arrival {eta.arrivalTime.formatted}
          </Text>
        </View>
      )}

      {/* Selected Location Card */}
      {selectedMarker && (
        <LocationCard
          location={selectedMarker}
          onClose={() => setSelectedMarker(null)}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  map: {
    flex: 1,
  },
  controls: {
    position: 'absolute',
    top: 16,
    right: 16,
    gap: 8,
  },
  controlButton: {
    backgroundColor: '#0F1332',
    width: 42,
    height: 42,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 4,
  },
  controlDisabled: {
    opacity: 0.4,
  },
  etaContainer: {
    position: 'absolute',
    bottom: 16,
    left: 16,
    right: 16,
    backgroundColor: '#0F1332',
    padding: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 5,
  },
  etaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 4,
  },
  etaText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#F1F5F9',
  },
  etaSubText: {
    fontSize: 12,
    color: '#94A3B8',
    marginLeft: 22,
  },
});

export default PickupDeliveryMap;