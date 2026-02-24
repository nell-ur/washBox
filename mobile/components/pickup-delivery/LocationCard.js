import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const COLORS = {
  background: '#0F1332',
  surface: '#171D45',
  primary: '#0EA5E9',
  pickup: '#10B981',
  driver: '#0EA5E9',
  user: '#8B5CF6',
  textPrimary: '#F1F5F9',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  border: 'rgba(255, 255, 255, 0.06)',
};

// delivery type removed — delivery is always the same address as pickup in WashBox
const TYPE_CONFIG = {
  pickup: { icon: 'location',  color: COLORS.pickup,  label: 'Pickup Location' },
  driver: { icon: 'car',       color: COLORS.driver,  label: 'Driver Location' },
  user:   { icon: 'person',    color: COLORS.user,    label: 'Your Location'   },
};

const LocationCard = ({ location, onClose }) => {
  const config = TYPE_CONFIG[location?.type] || TYPE_CONFIG.pickup;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={[styles.iconContainer, { backgroundColor: config.color + '20' }]}>
          <Ionicons name={config.icon} size={18} color={config.color} />
        </View>
        <View style={styles.titleContainer}>
          <Text style={styles.title}>
            {location?.title || config.label}
          </Text>
          {location?.description && (
            <Text style={styles.description} numberOfLines={2}>
              {location.description}
            </Text>
          )}
        </View>
        <TouchableOpacity onPress={onClose} style={styles.closeButton}>
          <Ionicons name="close" size={20} color={COLORS.textMuted} />
        </TouchableOpacity>
      </View>

      {location?.address && (
        <View style={styles.addressContainer}>
          <Ionicons name="location-outline" size={13} color={COLORS.textMuted} />
          <Text style={styles.addressText}>{location.address}</Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: COLORS.background,
    borderRadius: 16,
    padding: 16,
    margin: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  iconContainer: {
    width: 34,
    height: 34,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  titleContainer: {
    flex: 1,
  },
  title: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  description: {
    fontSize: 12,
    color: COLORS.textSecondary,
    lineHeight: 16,
  },
  closeButton: {
    padding: 4,
  },
  addressContainer: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  addressText: {
    flex: 1,
    fontSize: 12,
    color: COLORS.textSecondary,
    lineHeight: 18,
  },
});

export default LocationCard;