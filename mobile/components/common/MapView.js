import React, { useEffect, useRef, useState, useCallback, useMemo } from 'react';
import { View, StyleSheet } from 'react-native';

// Leaflet loading with better error handling
let L = null;
let isLeafletLoaded = false;

const loadLeaflet = () => {
  if (typeof window === 'undefined') return false;
  
  if (window.L && !isLeafletLoaded) {
    L = window.L;
    isLeafletLoaded = true;
    return true;
  }
  
  if (!L) {
    try {
      L = require('leaflet');
      require('leaflet/dist/leaflet.css');
      
      // Fix for default marker icons in Leaflet
      delete L.Icon.Default.prototype._getIconUrl;
      L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/dist/images/marker-shadow.png',
      });
      
      window.L = L;
      isLeafletLoaded = true;
      return true;
    } catch (error) {
      console.error('Failed to load Leaflet:', error);
      return false;
    }
  }
  
  return isLeafletLoaded;
};

const MapView = React.forwardRef(({
  style,
  initialRegion,
  region,
  onRegionChangeComplete,
  children,
  showsUserLocation = false,
  zoomEnabled = true,
  scrollEnabled = true,
  ...props
}, ref) => {
  const mapRef = useRef(null);
  const mapInstance = useRef(null);
  const markersRef = useRef(new Map()); // Use Map for better management
  const layersRef = useRef(new Map());
  const [isMapReady, setIsMapReady] = useState(false);
  const [loadError, setLoadError] = useState(false);
  const childrenRef = useRef(children);
  
  // Keep children reference updated
  useEffect(() => {
    childrenRef.current = children;
  }, [children]);

  // Load Leaflet
  useEffect(() => {
    const loaded = loadLeaflet();
    if (!loaded) {
      setLoadError(true);
    }
  }, []);

  // Initialize map
  useEffect(() => {
    if (!mapRef.current || !L || mapInstance.current || loadError) return;

    try {
      const center = initialRegion || region || { latitude: 14.5995, longitude: 120.9842 };
      
      mapInstance.current = L.map(mapRef.current, {
        zoomControl: zoomEnabled,
        scrollWheelZoom: scrollEnabled,
        dragging: scrollEnabled,
        doubleClickZoom: zoomEnabled,
      }).setView([center.latitude, center.longitude], 13);

      // Add OSM tiles (base layer)
      const baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
      }).addTo(mapInstance.current);
      
      layersRef.current.set('base', baseLayer);

      // Handle map movement with debouncing
      let moveTimeout;
      const handleMoveEnd = () => {
        if (onRegionChangeComplete && mapInstance.current) {
          const center = mapInstance.current.getCenter();
          const bounds = mapInstance.current.getBounds();
          
          onRegionChangeComplete({
            latitude: center.lat,
            longitude: center.lng,
            latitudeDelta: Math.abs(bounds.getNorth() - bounds.getSouth()),
            longitudeDelta: Math.abs(bounds.getEast() - bounds.getWest()),
          });
        }
      };

      mapInstance.current.on('moveend', () => {
        clearTimeout(moveTimeout);
        moveTimeout = setTimeout(handleMoveEnd, 50);
      });

      setIsMapReady(true);

      // Cleanup function
      return () => {
        clearTimeout(moveTimeout);
        mapInstance.current?.off('moveend');
      };
    } catch (error) {
      console.error('Failed to initialize map:', error);
      setLoadError(true);
    }

    return () => {};
  }, [loadError, onRegionChangeComplete, zoomEnabled, scrollEnabled]);

  // Update region with animation
  useEffect(() => {
    if (!mapInstance.current || !region) return;
    
    const currentCenter = mapInstance.current.getCenter();
    const newCenter = [region.latitude, region.longitude];
    
    // Only animate if center changed significantly
    if (Math.abs(currentCenter.lat - newCenter[0]) > 0.0001 || 
        Math.abs(currentCenter.lng - newCenter[1]) > 0.0001) {
      mapInstance.current.setView(newCenter, mapInstance.current.getZoom(), {
        animate: true,
        duration: 0.5,
      });
    }
  }, [region?.latitude, region?.longitude]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      // Clear all markers
      markersRef.current.forEach((marker, id) => {
        if (marker && mapInstance.current) {
          mapInstance.current.removeLayer(marker);
        }
      });
      markersRef.current.clear();
      
      // Clear all layers except base
      layersRef.current.forEach((layer, id) => {
        if (id !== 'base' && layer && mapInstance.current) {
          mapInstance.current.removeLayer(layer);
        }
      });
      layersRef.current.clear();
      
      // Remove map instance
      if (mapInstance.current) {
        mapInstance.current.remove();
        mapInstance.current = null;
      }
      setIsMapReady(false);
    };
  }, []);

  // Forward ref methods
  React.useImperativeHandle(ref, () => ({
    animateToRegion: (region, duration = 500) => {
      if (mapInstance.current) {
        mapInstance.current.flyTo([region.latitude, region.longitude], 13, {
          duration: duration / 1000,
        });
      }
    },
    animateToCoordinate: (coordinate, duration = 500) => {
      if (mapInstance.current) {
        mapInstance.current.flyTo([coordinate.latitude, coordinate.longitude], 13, {
          duration: duration / 1000,
        });
      }
    },
    fitToCoordinates: (coordinates, options = {}) => {
      if (mapInstance.current && coordinates.length > 0) {
        const latLngs = coordinates.map(coord => 
          L.latLng(coord.latitude, coord.longitude)
        );
        const bounds = L.latLngBounds(latLngs);
        mapInstance.current.fitBounds(bounds, {
          padding: options.padding || [50, 50],
          maxZoom: options.maxZoom || 15,
          animate: options.animated !== false,
        });
      }
    },
    getMap: () => mapInstance.current,
    isReady: () => isMapReady && !!mapInstance.current,
    getCamera: async () => {
      if (!mapInstance.current) return null;
      const center = mapInstance.current.getCenter();
      const zoom = mapInstance.current.getZoom();
      return {
        center: {
          latitude: center.lat,
          longitude: center.lng,
        },
        zoom,
        heading: 0,
        pitch: 0,
        altitude: 0,
      };
    },
  }));

  // Render children with proper context
  const renderChildren = useMemo(() => {
    if (!isMapReady || !childrenRef.current) return null;

    return React.Children.map(childrenRef.current, (child) => {
      if (!React.isValidElement(child)) return null;
      
      return React.cloneElement(child, {
        mapInstance: mapInstance.current,
        markersRef,
        layersRef,
        isMapReady,
        retryCount: 0,
        L,
      });
    });
  }, [isMapReady, childrenRef.current]);

  if (loadError) {
    return (
      <View style={[styles.container, style, styles.errorContainer]}>
        <div style={styles.errorText}>Failed to load map. Please refresh the page.</div>
      </View>
    );
  }

  return (
    <View style={[styles.container, style]}>
      <div 
        ref={mapRef}
        style={styles.mapContainer}
      />
      {!isMapReady && (
        <div style={styles.loadingOverlay}>
          <div style={styles.loadingText}>Loading map...</div>
        </div>
      )}
      {renderChildren}
    </View>
  );
});

// Enhanced createMapComponent factory
const createMapComponent = (Component, setupFn, options = {}) => {
  const { skipLeafletCheck = false } = options;
  
  return React.forwardRef((props, ref) => {
    const {
      mapInstance,
      markersRef,
      layersRef,
      isMapReady,
      retryCount = 0,
      L: leaflet,
      ...otherProps
    } = props;
    
    const internalRef = useRef(null);
    const [isComponentReady, setIsComponentReady] = useState(false);
    const [componentError, setComponentError] = useState(false);
    const componentId = useRef(`component_${Math.random().toString(36).substr(2, 9)}`);
    
    const maxRetries = otherProps.maxRetries || 5;
    const retryDelay = otherProps.retryDelay || 200;

    // Setup component
    const setupComponent = useCallback(() => {
      if (componentError && retryCount >= maxRetries) return null;
      
      // Get leaflet instance
      const L = leaflet || window.L;
      if (!skipLeafletCheck && !L) return null;
      
      // Get map instance
      const map = mapInstance || window.leafletMap;
      if (!map) return null;
      
      // Remove existing component
      if (internalRef.current) {
        const cleanupResult = internalRef.current._cleanup?.();
        if (cleanupResult) {
          markersRef?.current?.delete(componentId.current);
          layersRef?.current?.delete(componentId.current);
        }
      }
      
      try {
        const cleanup = setupFn({
          map,
          L,
          props: otherProps,
          internalRef,
          markersRef,
          layersRef,
          componentId: componentId.current,
        });
        
        // Store cleanup function
        if (internalRef.current && cleanup) {
          internalRef.current._cleanup = cleanup;
        }
        
        setIsComponentReady(true);
        setComponentError(false);
        return cleanup;
      } catch (error) {
        console.error(`Failed to setup ${Component}:`, error);
        setComponentError(true);
        return null;
      }
    }, [mapInstance, leaflet, otherProps, componentError, retryCount]);

    // Initialize component
    useEffect(() => {
      if (!isMapReady && !skipLeafletCheck) return;
      
      let mounted = true;
      let retryAttempt = 0;
      let cleanupFunction = null;
      
      const initComponent = () => {
        if (!mounted) return;
        
        cleanupFunction = setupComponent();
        
        // Retry logic
        if (!cleanupFunction && retryAttempt < maxRetries) {
          retryAttempt++;
          setTimeout(initComponent, retryDelay * retryAttempt);
        }
      };
      
      initComponent();
      
      return () => {
        mounted = false;
        if (cleanupFunction) {
          cleanupFunction();
        }
        // Cleanup from refs
        if (internalRef.current?._cleanup) {
          internalRef.current._cleanup();
        }
        markersRef?.current?.delete(componentId.current);
        layersRef?.current?.delete(componentId.current);
      };
    }, [isMapReady, setupComponent]);

    // Update component when props change
    useEffect(() => {
      if (isComponentReady && isMapReady) {
        setupComponent();
      }
    }, [otherProps, isComponentReady, isMapReady, setupComponent]);

    // Forward ref
    React.useImperativeHandle(ref, () => ({
      isReady: () => isComponentReady && !componentError,
      getElement: () => internalRef.current,
      getId: () => componentId.current,
    }));

    // Map components don't render DOM elements
    return null;
  });
};

// Enhanced Marker component
const Marker = createMapComponent('Marker', ({ map, L, props, internalRef, markersRef, componentId }) => {
  const { coordinate, title, description, onPress, draggable = false } = props;
  
  if (!coordinate || typeof coordinate.latitude !== 'number' || typeof coordinate.longitude !== 'number') {
    return () => {};
  }

  // Create marker
  const marker = L.marker([coordinate.latitude, coordinate.longitude], {
    draggable,
    title: title || '',
  }).addTo(map);
  
  // Add popup if title or description exists
  if (title || description) {
    const popupContent = `
      ${title ? `<strong>${title}</strong>` : ''}
      ${description ? `<p>${description}</p>` : ''}
    `.trim();
    if (popupContent) {
      marker.bindPopup(popupContent);
    }
  }
  
  // Add click handler
  if (onPress) {
    marker.on('click', (e) => {
      onPress({
        nativeEvent: {
          coordinate: {
            latitude: e.latlng.lat,
            longitude: e.latlng.lng,
          },
          position: {
            x: e.originalEvent.clientX,
            y: e.originalEvent.clientY,
          },
        },
      });
    });
  }
  
  // Handle drag events
  if (draggable) {
    marker.on('dragend', (e) => {
      const target = e.target;
      const position = target.getLatLng();
      if (props.onDragEnd) {
        props.onDragEnd({
          nativeEvent: {
            coordinate: {
              latitude: position.lat,
              longitude: position.lng,
            },
          },
        });
      }
    });
  }
  
  internalRef.current = marker;
  markersRef.current.set(componentId, marker);
  
  return () => {
    if (map && marker) {
      map.removeLayer(marker);
    }
  };
});

// Enhanced Polyline component
const Polyline = createMapComponent('Polyline', ({ map, L, props, internalRef, layersRef, componentId }) => {
  const { coordinates, strokeColor = '#007AFF', strokeWidth = 4, lineCap = 'round', lineJoin = 'round' } = props;
  
  if (!coordinates || coordinates.length < 2) {
    return () => {};
  }

  const latLngs = coordinates.map(coord => [coord.latitude, coord.longitude]);
  
  const polyline = L.polyline(latLngs, {
    color: strokeColor,
    weight: strokeWidth,
    opacity: 0.7,
    lineCap,
    lineJoin,
    smoothFactor: 1,
  }).addTo(map);
  
  internalRef.current = polyline;
  layersRef.current.set(componentId, polyline);
  
  return () => {
    if (map && polyline) {
      map.removeLayer(polyline);
    }
  };
});

// Enhanced Circle component
const Circle = createMapComponent('Circle', ({ map, L, props, internalRef, layersRef, componentId }) => {
  const { 
    center, 
    radius = 1000, 
    fillColor = 'rgba(0, 122, 255, 0.1)', 
    strokeColor = 'rgba(0, 122, 255, 0.5)', 
    strokeWidth = 2,
    fillOpacity = 0.2,
    strokeOpacity = 0.5,
  } = props;
  
  if (!center || typeof center.latitude !== 'number' || typeof center.longitude !== 'number') {
    return () => {};
  }

  const circle = L.circle([center.latitude, center.longitude], {
    radius,
    fillColor,
    color: strokeColor,
    weight: strokeWidth,
    fillOpacity,
    opacity: strokeOpacity,
  }).addTo(map);
  
  internalRef.current = circle;
  layersRef.current.set(componentId, circle);
  
  return () => {
    if (map && circle) {
      map.removeLayer(circle);
    }
  };
});

// Enhanced UrlTile component
const UrlTile = createMapComponent('UrlTile', ({ map, L, props, internalRef, layersRef, componentId }) => {
  const { 
    urlTemplate, 
    maximumZ = 19, 
    minimumZ = 0,
    tileSize = 256,
    zIndex = 1,
  } = props;
  
  if (!urlTemplate) {
    return () => {};
  }

  const tileLayer = L.tileLayer(urlTemplate, {
    maxZoom: maximumZ,
    minZoom: minimumZ,
    tileSize,
    zIndex,
  }).addTo(map);
  
  internalRef.current = tileLayer;
  layersRef.current.set(componentId, tileLayer);
  
  return () => {
    if (map && tileLayer) {
      map.removeLayer(tileLayer);
    }
  };
});

// Callout component (stub for web)
const Callout = React.forwardRef(({ children, ...props }, ref) => {
  // Callout is not directly supported in Leaflet on web
  // Popups are used instead via Marker bindPopup
  return null;
});

Callout.displayName = 'Callout';

const PROVIDER_DEFAULT = 'default';

const styles = StyleSheet.create({
  container: {
    width: '100%',
    height: '100%',
    minHeight: 300,
    position: 'relative',
    overflow: 'hidden',
  },
  mapContainer: {
    width: '100%',
    height: '100%',
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
  },
  loadingOverlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 1000,
  },
  loadingText: {
    fontSize: 16,
    color: '#666',
    fontFamily: 'system-ui, -apple-system, sans-serif',
  },
  errorContainer: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#f8f9fa',
    border: '1px solid #dee2e6',
    borderRadius: 4,
  },
  errorText: {
    color: '#dc3545',
    fontSize: 14,
    textAlign: 'center',
    padding: 20,
  },
});

export {
  MapView as default,
  Marker,
  UrlTile,
  Polyline,
  Circle,
  Callout,
  PROVIDER_DEFAULT,
};