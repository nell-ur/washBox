import React from 'react';
import MapView, { 
  PROVIDER_DEFAULT, 
  UrlTile, 
  Marker, 
  Polyline,
  Circle,
  Callout 
} from 'react-native-maps';
import { StyleSheet, View, Text } from 'react-native';
import { MAP_CONFIG } from '../../constants/mapConstants';
import DeliveryMarker from '../pickup-delivery/DeliveryMarker';

const OSMMap = ({ 
  initialRegion, 
  markers = [], 
  polylines = [],
  circles = [],
  onRegionChange,
  onMarkerPress,
  style,
  showAttribution = true,
  zoomEnabled = true,
  scrollEnabled = true
}) => {
  const renderMarkers = () => {
    return markers.map((marker, index) => (
      <Marker
        key={`marker-${index}`}
        coordinate={marker.coordinate}
        title={marker.title}
        description={marker.description}
        onPress={() => onMarkerPress?.(marker)}
        pinColor={marker.color}
        tracksViewChanges={false}
      >
        {marker.customIcon ? (
          <DeliveryMarker 
            type={marker.type} 
            isActive={marker.isActive}
          />
        ) : null}
        {marker.callout && (
          <Callout>
            <View style={styles.callout}>
              <Text style={styles.calloutTitle}>{marker.title}</Text>
              {marker.description && (
                <Text style={styles.calloutDescription}>
                  {marker.description}
                </Text>
              )}
            </View>
          </Callout>
        )}
      </Marker>
    ));
  };

  return (
    <View style={[styles.container, style]}>
      <MapView
        provider={PROVIDER_DEFAULT}
        style={styles.map}
        initialRegion={initialRegion}
        region={initialRegion}
        onRegionChangeComplete={onRegionChange}
        zoomEnabled={zoomEnabled}
        scrollEnabled={scrollEnabled}
        showsUserLocation={true}
        showsMyLocationButton={true}
        showsCompass={true}
        showsScale={true}
      >
        {/* OSM Tile Layer */}
        <UrlTile
          urlTemplate={MAP_CONFIG.tileUrl}
          maximumZ={19}
          flipY={false}
        />
        
        {/* Polylines for routes */}
        {polylines.map((polyline, index) => (
          <Polyline
            key={`polyline-${index}`}
            coordinates={polyline.coordinates}
            strokeColor={polyline.color || '#007AFF'}
            strokeWidth={polyline.width || 4}
            lineDashPattern={polyline.dashed ? [10, 5] : null}
          />
        ))}
        
        {/* Circles for geofencing */}
        {circles.map((circle, index) => (
          <Circle
            key={`circle-${index}`}
            center={circle.center}
            radius={circle.radius}
            fillColor={circle.fillColor || 'rgba(0, 122, 255, 0.1)'}
            strokeColor={circle.strokeColor || 'rgba(0, 122, 255, 0.5)'}
            strokeWidth={circle.strokeWidth || 2}
          />
        ))}
        
        {/* Markers */}
        {renderMarkers()}
      </MapView>
      
      {showAttribution && (
        <View style={styles.attribution}>
          <Text style={styles.attributionText}>
            {MAP_CONFIG.attributionText}
          </Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  map: {
    width: '100%',
    height: '100%',
  },
  attribution: {
    position: 'absolute',
    bottom: 10,
    right: 10,
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  attributionText: {
    fontSize: 10,
    color: '#666',
  },
  callout: {
    padding: 8,
    maxWidth: 200,
  },
  calloutTitle: {
    fontWeight: 'bold',
    fontSize: 14,
  },
  calloutDescription: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
});

export default OSMMap;