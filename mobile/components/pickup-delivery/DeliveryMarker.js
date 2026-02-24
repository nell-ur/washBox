import React from 'react';
import { View, StyleSheet, Platform } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

// Web version - no-op
const DeliveryMarkerWeb = () => null;

// Native version
const DeliveryMarkerNative = ({ type = 'pickup', isActive = true, size = 40 }) => {
  const getMarkerConfig = () => {
    const configs = {
      pickup: {
        icon: 'location',
        color: isActive ? '#10B981' : '#64748B',
        bgColor: isActive ? 'rgba(16, 185, 129, 0.2)' : 'rgba(100, 116, 139, 0.2)',
      },
      // delivery type removed — delivery is always the same address as pickup in WashBox
      driver: {
        icon: 'car',
        color: isActive ? '#0EA5E9' : '#64748B',
        bgColor: isActive ? 'rgba(14, 165, 233, 0.2)' : 'rgba(100, 116, 139, 0.2)',
      },
      user: {
        icon: 'person',
        color: '#8B5CF6',
        bgColor: 'rgba(139, 92, 246, 0.2)',
      },
    };

    return configs[type] || configs.pickup;
  };

  const config = getMarkerConfig();

  return (
    <View style={[styles.container, { width: size, height: size }]}>
      <View style={[
        styles.outerCircle,
        {
          backgroundColor: config.bgColor,
          width: size,
          height: size,
          borderRadius: size / 2,
        },
      ]}>
        <View style={[
          styles.innerCircle,
          {
            width: size * 0.7,
            height: size * 0.7,
            borderRadius: (size * 0.7) / 2,
          },
        ]}>
          <Ionicons name={config.icon} size={size * 0.4} color={config.color} />
        </View>
      </View>
    </View>
  );
};

const DeliveryMarker = (props) => {
  if (Platform.OS === 'web') return <DeliveryMarkerWeb {...props} />;
  return <DeliveryMarkerNative {...props} />;
};

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  outerCircle: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  innerCircle: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#0F1332',
    borderWidth: 2,
    borderColor: 'rgba(255, 255, 255, 0.15)',
  },
});

export default DeliveryMarker;