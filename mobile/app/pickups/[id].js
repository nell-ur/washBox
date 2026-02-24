import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

// ─── Design System ───
const COLORS = {
  background: '#06081A',
  surface: '#0F1332',
  surfaceLight: '#171D45',
  surfaceElevated: '#1E2654',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primarySoft: 'rgba(14, 165, 233, 0.08)',
  primaryGlow: 'rgba(14, 165, 233, 0.15)',
  pickup: '#10B981',
  pickupGlow: 'rgba(16, 185, 129, 0.15)',
  warning: '#F59E0B',
  warningGlow: 'rgba(245, 158, 11, 0.15)',
  danger: '#EF4444',
  dangerGlow: 'rgba(239, 68, 68, 0.15)',
  purple: '#8B5CF6',
  purpleGlow: 'rgba(139, 92, 246, 0.15)',
  textPrimary: '#F1F5F9',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  border: '#1E293B',
  borderLight: 'rgba(255, 255, 255, 0.06)',
};

const STATUS_CONFIG = {
  pending:              { color: '#F59E0B', glow: 'rgba(245,158,11,0.15)',   icon: 'time-outline',           label: 'Pending' },
  confirmed:            { color: '#0EA5E9', glow: 'rgba(14,165,233,0.15)',   icon: 'checkmark-circle-outline',label: 'Confirmed' },
  picked_up:            { color: '#8B5CF6', glow: 'rgba(139,92,246,0.15)',   icon: 'bag-handle-outline',      label: 'Picked Up' },
  processing:           { color: '#3B82F6', glow: 'rgba(59,130,246,0.15)',   icon: 'refresh-circle-outline',  label: 'Processing' },
  ready_for_delivery:   { color: '#10B981', glow: 'rgba(16,185,129,0.15)',   icon: 'checkmark-done-outline',  label: 'Ready for Delivery' },
  out_for_delivery:     { color: '#10B981', glow: 'rgba(16,185,129,0.15)',   icon: 'bicycle-outline',         label: 'Out for Delivery' },
  delivered:            { color: '#10B981', glow: 'rgba(16,185,129,0.15)',   icon: 'home-outline',            label: 'Delivered' },
  cancelled:            { color: '#EF4444', glow: 'rgba(239,68,68,0.15)',    icon: 'close-circle-outline',    label: 'Cancelled' },
};

// ─── Status Timeline Steps ───
const TIMELINE_STEPS = [
  'pending',
  'confirmed',
  'picked_up',
  'processing',
  'ready_for_delivery',
  'out_for_delivery',
  'delivered',
];

export default function PickupDetailScreen() {
  const { id } = useLocalSearchParams();
  const [pickup, setPickup] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [cancelling, setCancelling] = useState(false);

  const fetchPickup = useCallback(async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) {
        router.replace('/(auth)/login');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/pickups/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          setPickup(data.data);
        }
      } else if (response.status === 401) {
        router.replace('/(auth)/login');
      } else if (response.status === 404) {
        router.back();
      }
    } catch (error) {
      console.error('Error fetching pickup:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [id]);

  useEffect(() => {
    fetchPickup();
  }, [fetchPickup]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchPickup();
  };

  const handleCancel = async () => {
    if (cancelling) return;
    setCancelling(true);
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      const response = await fetch(`${API_BASE_URL}/v1/pickups/${id}/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        await fetchPickup();
      }
    } catch (error) {
      console.error('Error cancelling pickup:', error);
    } finally {
      setCancelling(false);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
    });
  };

  const formatTime = (timeString) => {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    return `${displayHour}:${minutes} ${ampm}`;
  };

  const formatCreatedAt = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short', day: 'numeric', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  };

  // ─── Loading ───
  if (loading) {
    return (
      <View style={[styles.container, styles.centered]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading pickup details...</Text>
      </View>
    );
  }

  if (!pickup) {
    return (
      <View style={[styles.container, styles.centered]}>
        <Ionicons name="alert-circle-outline" size={48} color={COLORS.danger} />
        <Text style={styles.errorText}>Pickup not found</Text>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backBtnText}>Go Back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const statusCfg = STATUS_CONFIG[pickup.status] || STATUS_CONFIG.pending;
  const isActive = !['delivered', 'cancelled'].includes(pickup.status);
  const isCancellable = ['pending', 'confirmed'].includes(pickup.status);

  // Which step index is active in the timeline
  const currentStepIdx = TIMELINE_STEPS.indexOf(pickup.status);

  return (
    <View style={styles.container}>
      {/* ─── Header ─── */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerBack} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={22} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <View>
          <Text style={styles.headerTitle}>Pickup #{pickup.id}</Text>
          <Text style={styles.headerSub}>{formatCreatedAt(pickup.created_at)}</Text>
        </View>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
            colors={[COLORS.primary]}
          />
        }
      >
        {/* ─── Status Banner ─── */}
        <View style={[styles.statusBanner, { backgroundColor: statusCfg.glow, borderColor: statusCfg.color + '30' }]}>
          <View style={[styles.statusIconCircle, { backgroundColor: statusCfg.color + '20' }]}>
            <Ionicons name={statusCfg.icon} size={28} color={statusCfg.color} />
          </View>
          <View style={styles.statusBannerText}>
            <Text style={styles.statusBannerLabel}>Current Status</Text>
            <Text style={[styles.statusBannerValue, { color: statusCfg.color }]}>
              {statusCfg.label}
            </Text>
          </View>
          {isActive && (
            <View style={[styles.activePill, { backgroundColor: COLORS.pickup + '20' }]}>
              <View style={[styles.activeDot, { backgroundColor: COLORS.pickup }]} />
              <Text style={[styles.activePillText, { color: COLORS.pickup }]}>Active</Text>
            </View>
          )}
        </View>

        {/* ─── Timeline ─── */}
        {pickup.status !== 'cancelled' && (
          <View style={styles.card}>
            <Text style={styles.sectionTitle}>Progress</Text>
            {TIMELINE_STEPS.map((step, idx) => {
              const stepCfg = STATUS_CONFIG[step];
              const isDone = idx < currentStepIdx;
              const isCurrent = idx === currentStepIdx;
              const isUpcoming = idx > currentStepIdx;

              return (
                <View key={step} style={styles.timelineRow}>
                  {/* Line */}
                  <View style={styles.timelineLeft}>
                    <View style={[
                      styles.timelineDot,
                      isDone && { backgroundColor: COLORS.pickup },
                      isCurrent && { backgroundColor: stepCfg.color, borderWidth: 3, borderColor: stepCfg.color + '40' },
                      isUpcoming && { backgroundColor: COLORS.surfaceElevated, borderWidth: 1, borderColor: COLORS.borderLight },
                    ]} />
                    {idx < TIMELINE_STEPS.length - 1 && (
                      <View style={[
                        styles.timelineLine,
                        { backgroundColor: isDone ? COLORS.pickup + '60' : COLORS.borderLight },
                      ]} />
                    )}
                  </View>
                  <View style={styles.timelineContent}>
                    <Text style={[
                      styles.timelineLabel,
                      isCurrent && { color: stepCfg.color, fontWeight: '700' },
                      isDone && { color: COLORS.textSecondary },
                      isUpcoming && { color: COLORS.textMuted },
                    ]}>
                      {stepCfg.label}
                    </Text>
                    {isCurrent && (
                      <Text style={[styles.timelineSub, { color: stepCfg.color }]}>Current step</Text>
                    )}
                    {isDone && (
                      <Ionicons name="checkmark" size={14} color={COLORS.pickup} style={styles.timelineCheck} />
                    )}
                  </View>
                </View>
              );
            })}
          </View>
        )}

        {/* ─── Address ─── */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Address</Text>
          <View style={styles.addressRow}>
            <View style={[styles.addressIcon, { backgroundColor: COLORS.pickupGlow }]}>
              <Ionicons name="location" size={18} color={COLORS.pickup} />
            </View>
            <View style={styles.addressContent}>
              <Text style={styles.addressText}>{pickup.pickup_address || 'Not specified'}</Text>
              <View style={styles.returnNote}>
                <Ionicons name="refresh-outline" size={11} color={COLORS.primary} />
                <Text style={styles.returnNoteText}>Pickup & delivery to same address</Text>
              </View>
            </View>
          </View>
        </View>

        {/* ─── Schedule ─── */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Schedule</Text>
          <View style={styles.infoRow}>
            <View style={[styles.infoIcon, { backgroundColor: COLORS.primarySoft }]}>
              <Ionicons name="calendar-outline" size={16} color={COLORS.primary} />
            </View>
            <View>
              <Text style={styles.infoLabel}>Preferred Date</Text>
              <Text style={styles.infoValue}>{formatDate(pickup.preferred_date)}</Text>
            </View>
          </View>
          <View style={styles.infoRow}>
            <View style={[styles.infoIcon, { backgroundColor: COLORS.primarySoft }]}>
              <Ionicons name="time-outline" size={16} color={COLORS.primary} />
            </View>
            <View>
              <Text style={styles.infoLabel}>Preferred Time</Text>
              <Text style={styles.infoValue}>{formatTime(pickup.preferred_time)}</Text>
            </View>
          </View>
        </View>

        {/* ─── Contact & Branch ─── */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Details</Text>
          {pickup.phone_number && (
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: COLORS.primarySoft }]}>
                <Ionicons name="call-outline" size={16} color={COLORS.primary} />
              </View>
              <View>
                <Text style={styles.infoLabel}>Phone Number</Text>
                <Text style={styles.infoValue}>{pickup.phone_number}</Text>
              </View>
            </View>
          )}
          {pickup.branch && (
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: COLORS.primarySoft }]}>
                <Ionicons name="business-outline" size={16} color={COLORS.primary} />
              </View>
              <View>
                <Text style={styles.infoLabel}>Branch</Text>
                <Text style={styles.infoValue}>{pickup.branch.name}</Text>
                {pickup.branch.address && (
                  <Text style={styles.infoSub}>{pickup.branch.address}</Text>
                )}
              </View>
            </View>
          )}
          {pickup.estimated_weight && (
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: COLORS.primarySoft }]}>
                <Ionicons name="scale-outline" size={16} color={COLORS.primary} />
              </View>
              <View>
                <Text style={styles.infoLabel}>Estimated Weight</Text>
                <Text style={styles.infoValue}>{pickup.estimated_weight} kg</Text>
              </View>
            </View>
          )}
          {pickup.notes && (
            <View style={styles.infoRow}>
              <View style={[styles.infoIcon, { backgroundColor: COLORS.primarySoft }]}>
                <Ionicons name="document-text-outline" size={16} color={COLORS.primary} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.infoLabel}>Notes</Text>
                <Text style={styles.infoValue}>{pickup.notes}</Text>
              </View>
            </View>
          )}
        </View>

        {/* ─── Cancel Button ─── */}
        {isCancellable && (
          <TouchableOpacity
            style={[styles.cancelBtn, cancelling && styles.cancelBtnDisabled]}
            onPress={handleCancel}
            disabled={cancelling}
            activeOpacity={0.8}
          >
            {cancelling ? (
              <ActivityIndicator size="small" color={COLORS.danger} />
            ) : (
              <>
                <Ionicons name="close-circle-outline" size={18} color={COLORS.danger} />
                <Text style={styles.cancelBtnText}>Cancel Pickup</Text>
              </>
            )}
          </TouchableOpacity>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  centered: { justifyContent: 'center', alignItems: 'center' },
  loadingText: { color: COLORS.textSecondary, marginTop: 12, fontSize: 14 },
  errorText: { color: COLORS.textSecondary, marginTop: 12, fontSize: 16, fontWeight: '600' },
  backBtn: {
    marginTop: 20, paddingHorizontal: 24, paddingVertical: 10,
    backgroundColor: COLORS.surfaceElevated, borderRadius: 12,
  },
  backBtnText: { color: COLORS.primary, fontWeight: '600' },

  // Header
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 60 : 48,
    paddingBottom: 16,
    borderBottomWidth: 1, borderBottomColor: COLORS.borderLight,
  },
  headerBack: {
    width: 40, height: 40, borderRadius: 12,
    backgroundColor: COLORS.surfaceElevated,
    justifyContent: 'center', alignItems: 'center',
  },
  headerTitle: { fontSize: 18, fontWeight: '800', color: COLORS.textPrimary, textAlign: 'center' },
  headerSub: { fontSize: 11, color: COLORS.textMuted, textAlign: 'center', marginTop: 2 },

  scroll: { flex: 1 },
  scrollContent: { padding: 20 },

  // Status Banner
  statusBanner: {
    flexDirection: 'row', alignItems: 'center',
    padding: 16, borderRadius: 16, borderWidth: 1,
    marginBottom: 16, gap: 14,
  },
  statusIconCircle: {
    width: 52, height: 52, borderRadius: 16,
    justifyContent: 'center', alignItems: 'center',
  },
  statusBannerText: { flex: 1 },
  statusBannerLabel: { fontSize: 11, color: COLORS.textMuted, fontWeight: '600', marginBottom: 3 },
  statusBannerValue: { fontSize: 17, fontWeight: '800' },
  activePill: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8,
  },
  activeDot: { width: 6, height: 6, borderRadius: 3 },
  activePillText: { fontSize: 10, fontWeight: '700' },

  // Card
  card: {
    backgroundColor: COLORS.surface, borderRadius: 16,
    padding: 16, marginBottom: 16,
    borderWidth: 1, borderColor: COLORS.borderLight,
  },
  sectionTitle: {
    fontSize: 13, fontWeight: '700', color: COLORS.textMuted,
    letterSpacing: 0.5, marginBottom: 14,
  },

  // Timeline
  timelineRow: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 4 },
  timelineLeft: { width: 28, alignItems: 'center' },
  timelineDot: { width: 14, height: 14, borderRadius: 7, backgroundColor: COLORS.textMuted },
  timelineLine: { width: 2, flex: 1, minHeight: 24, marginTop: 4, backgroundColor: COLORS.borderLight },
  timelineContent: {
    flex: 1, paddingLeft: 12, paddingBottom: 20,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
  },
  timelineLabel: { fontSize: 13, fontWeight: '500', color: COLORS.textMuted },
  timelineSub: { fontSize: 10, fontWeight: '600', marginTop: 2 },
  timelineCheck: { marginLeft: 8 },

  // Address
  addressRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12 },
  addressIcon: {
    width: 36, height: 36, borderRadius: 10,
    justifyContent: 'center', alignItems: 'center', marginTop: 2,
  },
  addressContent: { flex: 1 },
  addressText: { fontSize: 14, fontWeight: '600', color: COLORS.textPrimary, lineHeight: 20 },
  returnNote: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    marginTop: 8, backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 8, paddingVertical: 5,
    borderRadius: 6, alignSelf: 'flex-start',
  },
  returnNoteText: { fontSize: 10, color: COLORS.textSecondary, fontWeight: '500' },

  // Info Rows
  infoRow: {
    flexDirection: 'row', alignItems: 'flex-start', gap: 12, marginBottom: 14,
  },
  infoIcon: {
    width: 34, height: 34, borderRadius: 10,
    justifyContent: 'center', alignItems: 'center', marginTop: 2,
  },
  infoLabel: { fontSize: 11, color: COLORS.textMuted, fontWeight: '600', marginBottom: 3 },
  infoValue: { fontSize: 14, fontWeight: '600', color: COLORS.textPrimary },
  infoSub: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },

  // Cancel
  cancelBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 8, paddingVertical: 14, borderRadius: 14,
    borderWidth: 1.5, borderColor: COLORS.danger + '50',
    backgroundColor: COLORS.dangerGlow, marginBottom: 12,
  },
  cancelBtnDisabled: { opacity: 0.5 },
  cancelBtnText: { fontSize: 15, fontWeight: '700', color: COLORS.danger },
});