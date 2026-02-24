import React, { useState, useEffect, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

const COLORS = {
  background: '#06081A',
  surface: '#0F1332',
  surfaceLight: '#171D45',
  surfaceElevated: '#1E2654',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primaryGlow: 'rgba(14, 165, 233, 0.15)',
  primarySoft: 'rgba(14, 165, 233, 0.08)',
  pickup: '#10B981',
  pickupGlow: 'rgba(16, 185, 129, 0.15)',
  warning: '#F59E0B',
  danger: '#EF4444',
  textPrimary: '#F1F5F9',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  border: '#1E293B',
  borderLight: 'rgba(255, 255, 255, 0.06)',
};

const STATUS_COLORS = {
  pending: '#F59E0B',
  confirmed: '#0EA5E9',
  picked_up: '#8B5CF6',
  processing: '#3B82F6',
  ready_for_delivery: '#10B981',
  out_for_delivery: '#10B981',
  delivered: '#10B981',
  en_route: '#F59E0B',
  cancelled: '#EF4444',
};

const STATUS_LABELS = {
  pending: 'Pending',
  confirmed: 'Confirmed',
  picked_up: 'Picked Up',
  processing: 'Processing',
  ready_for_delivery: 'Ready for Delivery',
  out_for_delivery: 'Out for Delivery',
  delivered: 'Delivered',
  en_route: 'En Route',
  cancelled: 'Cancelled',
};

export default function PickupsHistoryScreen() {
  const [pickups, setPickups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filter, setFilter] = useState('all');

  const fetchPickups = useCallback(async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) {
        router.replace('/(auth)/login');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/pickups`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          setPickups(data.data.pickups || data.data);
        }
      } else if (response.status === 401) {
        router.replace('/(auth)/login');
      }
    } catch (error) {
      console.error('Error fetching pickups:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchPickups();
  }, [fetchPickups]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchPickups();
  };

  // Explicit status lists — safer than exclusion-based filtering
  const ACTIVE_STATUSES = ['pending', 'confirmed', 'en_route'];
  const COMPLETED_STATUSES = ['picked_up', 'delivered', 'cancelled'];

  const filteredPickups = useMemo(() => {
    if (filter === 'active') return pickups.filter(p => ACTIVE_STATUSES.includes(p.status));
    if (filter === 'completed') return pickups.filter(p => COMPLETED_STATUSES.includes(p.status));
    return pickups;
  }, [pickups, filter]);

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short', day: 'numeric', year: 'numeric',
    });
  };

  const formatTime = (timeString) => {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    return `${displayHour}:${minutes} ${ampm}`;
  };

  const renderPickupItem = ({ item }) => {
    const statusColor = STATUS_COLORS[item.status] || COLORS.textMuted;
    const statusLabel = STATUS_LABELS[item.status] || item.status;
    const isActive = ['pending', 'confirmed', 'en_route'].includes(item.status);

    // Card is now a plain View — no tap navigation
    return (
      <View style={styles.pickupCard}>
        <LinearGradient
          colors={[COLORS.surface, COLORS.surfaceLight]}
          style={styles.cardGradient}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          {/* Header */}
          <View style={styles.cardHeader}>
            <View style={styles.idContainer}>
              <Ionicons name="receipt-outline" size={16} color={COLORS.primary} />
              <Text style={styles.pickupId}>#{item.id}</Text>
            </View>
            <View style={[styles.statusBadge, { backgroundColor: statusColor + '20' }]}>
              <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
              <Text style={[styles.statusText, { color: statusColor }]}>{statusLabel}</Text>
            </View>
          </View>

          {/* Address — single row, pickup = delivery */}
          <View style={styles.addressRow}>
            <View style={[styles.addressIconCircle, { backgroundColor: COLORS.pickupGlow }]}>
              <View style={[styles.addressDot, { backgroundColor: COLORS.pickup }]} />
            </View>
            <View style={styles.addressContent}>
              <Text style={styles.addressLabel}>ADDRESS</Text>
              <Text style={styles.addressText} numberOfLines={2}>
                {item.pickup_address || 'Address not specified'}
              </Text>
            </View>
          </View>

          {/* Return to same address note */}
          <View style={styles.returnNote}>
            <Ionicons name="refresh-outline" size={12} color={COLORS.primary} />
            <Text style={styles.returnNoteText}>Pickup & delivery to same address</Text>
          </View>

          {/* Details Grid */}
          <View style={styles.detailsGrid}>
            <View style={styles.detailItem}>
              <Ionicons name="calendar-outline" size={14} color={COLORS.textMuted} />
              <Text style={styles.detailText}>{formatDate(item.preferred_date)}</Text>
            </View>
            <View style={styles.detailItem}>
              <Ionicons name="time-outline" size={14} color={COLORS.textMuted} />
              <Text style={styles.detailText}>{formatTime(item.preferred_time)}</Text>
            </View>
            {item.estimated_weight && (
              <View style={styles.detailItem}>
                <Ionicons name="scale-outline" size={14} color={COLORS.textMuted} />
                <Text style={styles.detailText}>{item.estimated_weight} kg</Text>
              </View>
            )}
          </View>

          {/* Footer */}
          <View style={styles.cardFooter}>
            <View style={styles.branchInfo}>
              <Ionicons name="business-outline" size={12} color={COLORS.primary} />
              <Text style={styles.branchText}>
                {item.branch?.name || `Branch #${item.branch_id}`}
              </Text>
            </View>

            {/* Active indicator */}
            {isActive && (
              <View style={styles.activePill}>
                <View style={styles.activePulse} />
                <Text style={styles.activePillText}>In Progress</Text>
              </View>
            )}
          </View>
        </LinearGradient>
      </View>
    );
  };

  const renderEmptyState = () => (
    <View style={styles.emptyState}>
      <View style={styles.emptyIconContainer}>
        <Ionicons name="car-outline" size={48} color={COLORS.primary} />
      </View>
      <Text style={styles.emptyTitle}>No Pickups Yet</Text>
      <Text style={styles.emptyText}>
        Schedule your first pickup and we'll handle the rest
      </Text>
      <TouchableOpacity
        style={styles.emptyButton}
        onPress={() => router.push('/(tabs)/pickups')}
      >
        <LinearGradient
          colors={[COLORS.primary, COLORS.primaryDark]}
          style={styles.emptyButtonGradient}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
        >
          <Ionicons name="add" size={20} color="#FFF" />
          <Text style={styles.emptyButtonText}>Schedule Pickup</Text>
        </LinearGradient>
      </TouchableOpacity>
    </View>
  );

  if (loading && !refreshing) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading pickups...</Text>
      </View>
    );
  }

  const counts = {
    all: pickups.length,
    active: pickups.filter(p => ['pending', 'confirmed', 'en_route'].includes(p.status)).length,
    completed: pickups.filter(p => ['delivered', 'cancelled'].includes(p.status)).length,
  };

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerTop}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Pickup History</Text>
          <TouchableOpacity
            onPress={() => router.push('/(tabs)/pickups')}
            style={styles.newButton}
          >
            <Ionicons name="add" size={24} color={COLORS.primary} />
          </TouchableOpacity>
        </View>

        {/* Filter Tabs with counts */}
        <View style={styles.filterTabs}>
          {['all', 'active', 'completed'].map((tab) => (
            <TouchableOpacity
              key={tab}
              style={[styles.filterTab, filter === tab && styles.filterTabActive]}
              onPress={() => setFilter(tab)}
            >
              <Text style={[styles.filterText, filter === tab && styles.filterTextActive]}>
                {tab.charAt(0).toUpperCase() + tab.slice(1)}
              </Text>
              {counts[tab] > 0 && (
                <View style={[styles.filterCount, filter === tab && styles.filterCountActive]}>
                  <Text style={[styles.filterCountText, filter === tab && styles.filterCountTextActive]}>
                    {counts[tab]}
                  </Text>
                </View>
              )}
            </TouchableOpacity>
          ))}
        </View>
      </View>

      {/* List */}
      <FlatList
        data={filteredPickups}
        keyExtractor={(item) => item.id.toString()}
        renderItem={renderPickupItem}
        contentContainerStyle={styles.listContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
            colors={[COLORS.primary]}
          />
        }
        ListEmptyComponent={renderEmptyState}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  centerContent: { justifyContent: 'center', alignItems: 'center' },
  loadingText: { color: COLORS.textSecondary, marginTop: 12, fontSize: 14 },

  // Header
  header: {
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 60 : 48,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
  },
  headerTop: {
    flexDirection: 'row', alignItems: 'center',
    justifyContent: 'space-between', marginBottom: 16,
  },
  backButton: {
    width: 40, height: 40, borderRadius: 12,
    backgroundColor: COLORS.surfaceElevated,
    justifyContent: 'center', alignItems: 'center',
  },
  newButton: {
    width: 40, height: 40, borderRadius: 12,
    backgroundColor: COLORS.primarySoft,
    justifyContent: 'center', alignItems: 'center',
  },
  headerTitle: { fontSize: 20, fontWeight: '800', color: COLORS.textPrimary },

  // Filter Tabs
  filterTabs: { flexDirection: 'row', gap: 8 },
  filterTab: {
    flex: 1, paddingVertical: 8, borderRadius: 10,
    backgroundColor: COLORS.surfaceElevated,
    alignItems: 'center', flexDirection: 'row',
    justifyContent: 'center', gap: 6,
  },
  filterTabActive: { backgroundColor: COLORS.primary },
  filterText: { fontSize: 13, fontWeight: '600', color: COLORS.textMuted },
  filterTextActive: { color: '#FFF' },
  filterCount: {
    backgroundColor: COLORS.border,
    paddingHorizontal: 6, paddingVertical: 1,
    borderRadius: 8, minWidth: 18, alignItems: 'center',
  },
  filterCountActive: { backgroundColor: 'rgba(255,255,255,0.25)' },
  filterCountText: { fontSize: 10, fontWeight: '700', color: COLORS.textMuted },
  filterCountTextActive: { color: '#FFF' },

  // List
  listContent: { padding: 20, paddingBottom: 40 },

  // Card — now View, not TouchableOpacity
  pickupCard: {
    marginBottom: 16, borderRadius: 20,
    overflow: 'hidden', borderWidth: 1, borderColor: COLORS.borderLight,
  },
  cardGradient: { padding: 16 },
  cardHeader: {
    flexDirection: 'row', justifyContent: 'space-between',
    alignItems: 'center', marginBottom: 14,
  },
  idContainer: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  pickupId: { fontSize: 14, fontWeight: '700', color: COLORS.primary },
  statusBadge: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 10, paddingVertical: 4,
    borderRadius: 8, gap: 6,
  },
  statusDot: { width: 8, height: 8, borderRadius: 4 },
  statusText: { fontSize: 12, fontWeight: '600' },

  // Address
  addressRow: {
    flexDirection: 'row', alignItems: 'flex-start',
    gap: 12, marginBottom: 8,
  },
  addressIconCircle: {
    width: 30, height: 30, borderRadius: 15,
    justifyContent: 'center', alignItems: 'center',
    marginTop: 2,
  },
  addressDot: { width: 9, height: 9, borderRadius: 4.5 },
  addressContent: { flex: 1 },
  addressLabel: {
    fontSize: 10, fontWeight: '800', color: COLORS.textMuted,
    letterSpacing: 1.2, marginBottom: 3,
  },
  addressText: { fontSize: 13, fontWeight: '600', color: COLORS.textPrimary, lineHeight: 19 },

  // Return note
  returnNote: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 10, paddingVertical: 6,
    borderRadius: 8, marginBottom: 12, alignSelf: 'flex-start',
  },
  returnNoteText: { fontSize: 11, color: COLORS.textSecondary, fontWeight: '500' },

  // Details
  detailsGrid: {
    flexDirection: 'row', flexWrap: 'wrap', gap: 8,
    marginBottom: 12, paddingBottom: 12,
    borderBottomWidth: 1, borderBottomColor: COLORS.borderLight,
  },
  detailItem: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6,
  },
  detailText: { fontSize: 11, fontWeight: '500', color: COLORS.textSecondary },

  // Footer
  cardFooter: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
  },
  branchInfo: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  branchText: { fontSize: 11, color: COLORS.textMuted },
  activePill: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: COLORS.pickupGlow,
    paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8,
  },
  activePulse: {
    width: 6, height: 6, borderRadius: 3,
    backgroundColor: COLORS.pickup,
  },
  activePillText: { fontSize: 10, fontWeight: '700', color: COLORS.pickup },

  // Empty State
  emptyState: {
    alignItems: 'center', justifyContent: 'center',
    paddingVertical: 60, paddingHorizontal: 40,
  },
  emptyIconContainer: {
    width: 96, height: 96, borderRadius: 48,
    backgroundColor: COLORS.primarySoft,
    justifyContent: 'center', alignItems: 'center', marginBottom: 20,
  },
  emptyTitle: { fontSize: 20, fontWeight: '800', color: COLORS.textPrimary, marginBottom: 8 },
  emptyText: { fontSize: 14, color: COLORS.textMuted, textAlign: 'center', marginBottom: 24, lineHeight: 20 },
  emptyButton: { borderRadius: 16, overflow: 'hidden' },
  emptyButtonGradient: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    paddingHorizontal: 24, paddingVertical: 14,
  },
  emptyButtonText: { fontSize: 16, fontWeight: '700', color: '#FFF' },
});