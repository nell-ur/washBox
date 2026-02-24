import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Linking,
  Animated,
  Platform,
  Dimensions,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';
import { LinearGradient } from 'expo-linear-gradient';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

const COLORS = {
  background: '#06081A',
  surface: '#0F1332',
  surfaceLight: '#171D45',
  surfaceElevated: '#1E2654',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primaryGlow: 'rgba(14, 165, 233, 0.12)',
  primarySoft: 'rgba(14, 165, 233, 0.08)',
  secondary: '#8B5CF6',
  secondaryGlow: 'rgba(139, 92, 246, 0.12)',
  accent: '#F59E0B',
  accentGlow: 'rgba(245, 158, 11, 0.12)',
  success: '#10B981',
  successGlow: 'rgba(16, 185, 129, 0.12)',
  danger: '#EF4444',
  dangerGlow: 'rgba(239, 68, 68, 0.12)',
  textPrimary: '#F1F5F9',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  border: '#1E293B',
  borderLight: 'rgba(255, 255, 255, 0.06)',
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
  gradientSecondary: ['#8B5CF6', '#7C3AED'],
  gradientSuccess: ['#10B981', '#059669'],
  gradientWarning: ['#F59E0B', '#D97706'],
  gradientDanger: ['#EF4444', '#DC2626'],
};

const STATUS_MAP = {
  received:         { gradient: ['#3B82F6', '#2563EB'], color: '#3B82F6', icon: 'receipt-outline',              label: 'Received',          description: 'Your laundry has been received and is being prepared' },
  processing:       { gradient: ['#8B5CF6', '#7C3AED'], color: '#8B5CF6', icon: 'sync-outline',                label: 'Processing',        description: 'Your laundry is being washed and cared for' },
  washing:          { gradient: ['#06B6D4', '#0891B2'], color: '#06B6D4', icon: 'water-outline',               label: 'Washing',           description: 'Your clothes are in the washing machine' },
  drying:           { gradient: ['#F59E0B', '#D97706'], color: '#F59E0B', icon: 'sunny-outline',               label: 'Drying',            description: 'Your clothes are being dried' },
  ironing:          { gradient: ['#EC4899', '#DB2777'], color: '#EC4899', icon: 'shirt-outline',               label: 'Ironing',           description: 'Your clothes are being pressed and ironed' },
  folding:          { gradient: ['#F97316', '#EA580C'], color: '#F97316', icon: 'layers-outline',              label: 'Folding',           description: 'Your clothes are being neatly folded' },
  ready:            { gradient: ['#F59E0B', '#D97706'], color: '#F59E0B', icon: 'bag-check-outline',           label: 'Ready for Pickup',  description: 'Your laundry is clean and ready to collect!' },
  ready_for_pickup: { gradient: ['#F59E0B', '#D97706'], color: '#F59E0B', icon: 'bag-check-outline',           label: 'Ready for Pickup',  description: 'Your laundry is clean and ready to collect!' },
  paid:             { gradient: ['#10B981', '#059669'], color: '#10B981', icon: 'card-outline',                label: 'Paid',              description: 'Payment confirmed — thank you!' },
  completed:        { gradient: ['#10B981', '#059669'], color: '#10B981', icon: 'checkmark-done-circle-outline',label: 'Completed',         description: 'Your laundry has been completed successfully' },
  cancelled:        { gradient: ['#EF4444', '#DC2626'], color: '#EF4444', icon: 'close-circle-outline',        label: 'Cancelled',         description: 'This laundry order has been cancelled' },
};

const getStatusConfig = (status) => STATUS_MAP[status?.toLowerCase()] || STATUS_MAP.received;

const TIMELINE_STAGES = [
  { key: 'received',   label: 'Received', icon: 'receipt-outline' },
  { key: 'processing', label: 'Processing', icon: 'sync-outline' },
  { key: 'ready',      label: 'Ready',    icon: 'bag-check-outline' },
  { key: 'paid',       label: 'Paid',     icon: 'card-outline' },
  { key: 'completed',  label: 'Done',     icon: 'checkmark-done-circle-outline' },
];

const STAGE_ORDER = ['received','processing','washing','drying','ironing','folding','ready','ready_for_pickup','paid','completed'];

const getStageIndex = (status) => {
  const idx = STAGE_ORDER.indexOf(status?.toLowerCase());
  if (idx <= 1) return idx;
  if (idx <= 5) return 1;
  if (idx <= 7) return 2;
  if (idx === 8) return 3;
  if (idx === 9) return 4;
  return 0;
};

const HorizontalTimeline = ({ currentStatus }) => {
  const activeIdx = getStageIndex(currentStatus);
  const isCancelled = currentStatus?.toLowerCase() === 'cancelled';

  return (
    <View style={tlStyles.container}>
      {TIMELINE_STAGES.map((stage, index) => {
        const isCompleted = !isCancelled && index < activeIdx;
        const isCurrent   = !isCancelled && index === activeIdx;
        const isLast      = index === TIMELINE_STAGES.length - 1;

        return (
          <View key={stage.key} style={tlStyles.stepWrapper}>
            <View style={[
              tlStyles.dot,
              isCompleted && tlStyles.dotCompleted,
              isCurrent   && tlStyles.dotCurrent,
              isCancelled && tlStyles.dotCancelled,
            ]}>
              {isCompleted
                ? <Ionicons name="checkmark" size={14} color="#FFF" />
                : <Ionicons name={stage.icon} size={14} color={isCurrent ? '#FFF' : COLORS.textMuted} />
              }
              {isCurrent && <View style={tlStyles.dotPulse} />}
            </View>
            <Text style={[
              tlStyles.label,
              isCompleted && tlStyles.labelCompleted,
              isCurrent   && tlStyles.labelCurrent,
            ]} numberOfLines={1}>{stage.label}</Text>
            {!isLast && (
              <View style={[tlStyles.connector, isCompleted && tlStyles.connectorCompleted]} />
            )}
          </View>
        );
      })}
    </View>
  );
};

const tlStyles = StyleSheet.create({
  container:          { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'center', paddingHorizontal: 8, paddingVertical: 4 },
  stepWrapper:        { alignItems: 'center', flexDirection: 'column', position: 'relative', flex: 1 },
  dot:                { width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.surfaceElevated, borderWidth: 2, borderColor: COLORS.border, justifyContent: 'center', alignItems: 'center', zIndex: 2 },
  dotCompleted:       { backgroundColor: COLORS.success, borderColor: COLORS.success },
  dotCurrent:         { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  dotCancelled:       { backgroundColor: COLORS.dangerGlow, borderColor: COLORS.danger },
  dotPulse:           { position: 'absolute', width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(14,165,233,0.18)', zIndex: -1 },
  label:              { fontSize: 9, fontWeight: '700', color: COLORS.textMuted, marginTop: 6, textAlign: 'center', letterSpacing: 0.2 },
  labelCompleted:     { color: COLORS.success },
  labelCurrent:       { color: COLORS.primary },
  connector:          { position: 'absolute', top: 15, left: '60%', right: '-40%', height: 2, backgroundColor: COLORS.border, zIndex: 1 },
  connectorCompleted: { backgroundColor: COLORS.success },
});

const DetailRow = ({ icon, label, value, color, mono }) => (
  <View style={drStyles.row}>
    <View style={[drStyles.iconWrap, { backgroundColor: (color || COLORS.primary) + '14' }]}>
      <Ionicons name={icon} size={16} color={color || COLORS.primary} />
    </View>
    <Text style={drStyles.label}>{label}</Text>
    <Text style={[drStyles.value, mono && drStyles.mono]} numberOfLines={1}>{value || '—'}</Text>
  </View>
);

const drStyles = StyleSheet.create({
  row:      { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, gap: 12, borderBottomWidth: 1, borderBottomColor: COLORS.borderLight },
  iconWrap: { width: 32, height: 32, borderRadius: 10, justifyContent: 'center', alignItems: 'center' },
  label:    { fontSize: 13, fontWeight: '500', color: COLORS.textMuted, width: 80 },
  value:    { flex: 1, fontSize: 14, fontWeight: '600', color: COLORS.textPrimary, textAlign: 'right' },
  mono:     { fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace', letterSpacing: 0.5 },
});

const PricingRow = ({ label, sublabel, value, icon, discount, bold, large }) => (
  <View style={[prStyles.row, large && prStyles.rowLarge]}>
    <View style={prStyles.labelSide}>
      {icon && <Ionicons name={icon} size={14} color={discount ? COLORS.success : COLORS.textMuted} style={{ marginRight: 6 }} />}
      <View>
        <Text style={[prStyles.label, bold && prStyles.labelBold, large && prStyles.labelLarge]}>{label}</Text>
        {sublabel && <Text style={prStyles.sublabel}>{sublabel}</Text>}
      </View>
    </View>
    <Text style={[prStyles.value, discount && prStyles.valueDiscount, bold && prStyles.valueBold, large && prStyles.valueLarge]}>
      {discount ? '-' : ''}{value}
    </Text>
  </View>
);

const prStyles = StyleSheet.create({
  row:           { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 8 },
  rowLarge:      { paddingVertical: 12 },
  labelSide:     { flexDirection: 'row', alignItems: 'center', flex: 1 },
  label:         { fontSize: 13, color: COLORS.textSecondary },
  labelBold:     { fontWeight: '700', color: COLORS.textPrimary },
  labelLarge:    { fontSize: 16 },
  sublabel:      { fontSize: 11, color: COLORS.textMuted, marginTop: 1 },
  value:         { fontSize: 14, fontWeight: '600', color: COLORS.textPrimary },
  valueDiscount: { color: COLORS.success },
  valueBold:     { fontWeight: '800' },
  valueLarge:    { fontSize: 22, color: COLORS.primary, letterSpacing: -0.3 },
});

export default function LaundryDetailsScreen() {
  const { id } = useLocalSearchParams();
  const [loading, setLoading]     = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [laundry, setLaundry]     = useState(null);
  const [fadeAnim]  = useState(new Animated.Value(0));
  const [slideAnim] = useState(new Animated.Value(30));

  useEffect(() => { fetchLaundryDetails(); }, [id]);

  useEffect(() => {
    if (laundry) {
      Animated.parallel([
        Animated.timing(fadeAnim,  { toValue: 1, duration: 500, useNativeDriver: true }),
        Animated.spring(slideAnim, { toValue: 0, useNativeDriver: true, tension: 60, friction: 12 }),
      ]).start();
    }
  }, [laundry]);

  const fetchLaundryDetails = async () => {
    try {
      setLoading(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) { router.replace('/(auth)/login'); return; }

      const response = await fetch(`${API_BASE_URL}/v1/laundries/${id}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
          setLaundry(data.data.laundry || data.data);
        }
      } else if (response.status === 401) {
        await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);
        router.replace('/(auth)/login');
      } else {
        Alert.alert('Error', 'Laundry not found');
        router.back();
      }
    } catch (error) {
      console.error('Error fetching laundry details:', error);
      Alert.alert('Error', 'Failed to load laundry details');
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchLaundryDetails();
    setRefreshing(false);
  };

  const formatPrice    = (p)  => `₱${parseFloat(p || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  const formatDate     = (d)  => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
  const formatDateTime = (d)  => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
  const handleCall     = (ph) => Linking.openURL(`tel:${ph}`);

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <View style={styles.loadingWrap}>
          <Ionicons name="shirt-outline" size={32} color={COLORS.primary} />
        </View>
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 20 }} />
        <Text style={styles.loadingText}>Loading details...</Text>
      </View>
    );
  }

  if (!laundry) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <View style={styles.errorIcon}>
          <Ionicons name="alert-circle-outline" size={56} color={COLORS.danger} />
        </View>
        <Text style={styles.errorTitle}>Laundry Not Found</Text>
        <Text style={styles.errorText}>We could not find this order. Check the tracking number and try again.</Text>
        <TouchableOpacity style={styles.errorBtn} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={18} color="#FFF" />
          <Text style={styles.errorBtnText}>Go Back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const statusCfg      = getStatusConfig(laundry.status);
  const isCancelled    = laundry.status?.toLowerCase() === 'cancelled';
  const isPaid         = laundry.payment_status === 'paid';
  const hasPickupFee   = parseFloat(laundry.pickup_fee) > 0;
  const hasDeliveryFee = parseFloat(laundry.delivery_fee) > 0;
  const hasDiscount    = parseFloat(laundry.discount_amount) > 0;
  const showCallButton = ['ready','ready_for_pickup','paid'].includes(laundry.status?.toLowerCase()) && laundry.branch_phone;

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Ionicons name="chevron-back" size={22} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <View style={styles.headerCenter}>
          <Text style={styles.headerTitle}>Order Details</Text>
        </View>
        <TouchableOpacity style={styles.refreshBtn} onPress={onRefresh}>
          <Ionicons name="refresh-outline" size={20} color={COLORS.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
        contentContainerStyle={{ paddingBottom: showCallButton ? 110 : 40 }}
      >
        <Animated.View style={{ opacity: fadeAnim, transform: [{ translateY: slideAnim }] }}>

          {/* Status Hero */}
          <View style={styles.heroCard}>
            <LinearGradient colors={statusCfg.gradient} style={styles.heroGradient} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
              <View style={styles.heroCircle1} />
              <View style={styles.heroCircle2} />
              <View style={styles.heroRow}>
                <View style={styles.heroIconWrap}>
                  <Ionicons name={statusCfg.icon} size={32} color="#FFF" />
                </View>
                <View style={styles.heroTextWrap}>
                  <Text style={styles.heroLabel}>{statusCfg.label}</Text>
                  <Text style={styles.heroDesc}>{statusCfg.description}</Text>
                </View>
              </View>
              <View style={styles.trackingPill}>
                <Ionicons name="barcode-outline" size={14} color="rgba(255,255,255,0.85)" />
                <Text style={styles.trackingText}>{laundry.tracking_number}</Text>
              </View>
            </LinearGradient>
          </View>

          {/* Timeline */}
          {!isCancelled && (
            <View style={styles.card}>
              <View style={styles.cardHead}>
                <Ionicons name="git-commit-outline" size={18} color={COLORS.primary} />
                <Text style={styles.cardTitle}>Progress</Text>
              </View>
              <HorizontalTimeline currentStatus={laundry.status} />
            </View>
          )}

          {isCancelled && (
            <View style={styles.cancelledBanner}>
              <Ionicons name="close-circle" size={20} color={COLORS.danger} />
              <Text style={styles.cancelledText}>This order has been cancelled</Text>
            </View>
          )}

          {/* Order Info */}
          <View style={styles.card}>
            <View style={styles.cardHead}>
              <Ionicons name="information-circle-outline" size={18} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Order Information</Text>
            </View>
            <DetailRow icon="pricetag-outline" label="Service"  value={laundry.service_name} />
            <DetailRow icon="scale-outline"    label="Weight"   value={`${parseFloat(laundry.weight || 0).toFixed(2)} kg`} />
            <DetailRow icon="business-outline" label="Branch"   value={laundry.branch_name} />
            <DetailRow icon="calendar-outline" label="Placed"   value={formatDate(laundry.created_at)} />
            {laundry.estimated_completion && (
              <DetailRow icon="time-outline" label="Est. Done" value={formatDate(laundry.estimated_completion)} color={COLORS.accent} />
            )}
            <DetailRow icon="sync-outline" label="Updated" value={formatDateTime(laundry.updated_at)} />
          </View>

          {/* Pricing */}
          <View style={styles.card}>
            <View style={styles.cardHead}>
              <Ionicons name="receipt-outline" size={18} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Pricing</Text>
              <View style={[styles.payBadge, { backgroundColor: isPaid ? COLORS.successGlow : COLORS.accentGlow }]}>
                <View style={[styles.payDot, { backgroundColor: isPaid ? COLORS.success : COLORS.accent }]} />
                <Text style={[styles.payText, { color: isPaid ? COLORS.success : COLORS.accent }]}>
                  {isPaid ? 'Paid' : 'Unpaid'}
                </Text>
              </View>
            </View>
            <View style={styles.receiptBody}>
              <PricingRow
                label={laundry.service_name || 'Service'}
                sublabel={`${parseFloat(laundry.weight || 0).toFixed(2)} kg × ₱${parseFloat(laundry.price_per_kg || 0).toFixed(2)}/kg`}
                value={formatPrice(laundry.subtotal)}
              />
              {hasPickupFee   && <PricingRow label="Pickup Fee"   icon="car-outline"     value={formatPrice(laundry.pickup_fee)} />}
              {hasDeliveryFee && <PricingRow label="Delivery Fee" icon="bicycle-outline"  value={formatPrice(laundry.delivery_fee)} />}
              {hasDiscount    && <PricingRow label="Discount"     icon="pricetag-outline" value={formatPrice(laundry.discount_amount)} discount />}
              <View style={styles.receiptDivider}>
                {[...Array(Math.floor((SCREEN_WIDTH - 80) / 8))].map((_, i) => (
                  <View key={i} style={styles.receiptDash} />
                ))}
              </View>
              <PricingRow label="Total" value={formatPrice(laundry.total_amount)} bold large />
            </View>
          </View>

          {/* Branch */}
          {(laundry.branch_name || laundry.branch_address) && (
            <View style={styles.card}>
              <View style={styles.cardHead}>
                <Ionicons name="location-outline" size={18} color={COLORS.primary} />
                <Text style={styles.cardTitle}>Branch</Text>
              </View>
              <View style={styles.branchRow}>
                <View style={styles.branchIcon}>
                  <Ionicons name="business" size={24} color={COLORS.primary} />
                </View>
                <View style={styles.branchInfo}>
                  <Text style={styles.branchName}>{laundry.branch_name}</Text>
                  {laundry.branch_address && <Text style={styles.branchAddr}>{laundry.branch_address}</Text>}
                </View>
              </View>
              {laundry.branch_phone && (
                <TouchableOpacity style={styles.callRow} onPress={() => handleCall(laundry.branch_phone)} activeOpacity={0.7}>
                  <View style={styles.callIconWrap}>
                    <Ionicons name="call" size={16} color={COLORS.success} />
                  </View>
                  <Text style={styles.callPhone}>{laundry.branch_phone}</Text>
                  <View style={styles.callChip}>
                    <Text style={styles.callChipText}>Call</Text>
                  </View>
                </TouchableOpacity>
              )}
            </View>
          )}

          {/* Notes */}
          {laundry.notes && (
            <View style={styles.card}>
              <View style={styles.cardHead}>
                <Ionicons name="document-text-outline" size={18} color={COLORS.primary} />
                <Text style={styles.cardTitle}>Notes</Text>
              </View>
              <View style={styles.notesBox}>
                <Text style={styles.notesText}>{laundry.notes}</Text>
              </View>
            </View>
          )}

          {/* Timestamps */}
          <View style={styles.timestamps}>
            <View style={styles.tsRow}>
              <Text style={styles.tsLabel}>Created</Text>
              <Text style={styles.tsValue}>{formatDateTime(laundry.created_at)}</Text>
            </View>
            <View style={styles.tsDot} />
            <View style={styles.tsRow}>
              <Text style={styles.tsLabel}>Updated</Text>
              <Text style={styles.tsValue}>{formatDateTime(laundry.updated_at)}</Text>
            </View>
          </View>

        </Animated.View>
      </ScrollView>

      {/* Bottom Call Bar */}
      {showCallButton && (
        <View style={styles.bottomBar}>
          <TouchableOpacity style={styles.bottomBtn} onPress={() => handleCall(laundry.branch_phone)} activeOpacity={0.85}>
            <LinearGradient colors={COLORS.gradientPrimary} style={styles.bottomGradient} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}>
              <Ionicons name="call" size={20} color="#FFF" />
              <Text style={styles.bottomBtnText}>Call to Confirm Pickup</Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container:     { flex: 1, backgroundColor: COLORS.background },
  centerContent: { justifyContent: 'center', alignItems: 'center', padding: 32 },
  loadingWrap:   { width: 72, height: 72, borderRadius: 24, backgroundColor: COLORS.primaryGlow, justifyContent: 'center', alignItems: 'center' },
  loadingText:   { color: COLORS.textSecondary, marginTop: 12, fontSize: 14, fontWeight: '500' },
  errorIcon:     { width: 88, height: 88, borderRadius: 28, backgroundColor: COLORS.dangerGlow, justifyContent: 'center', alignItems: 'center', marginBottom: 20 },
  errorTitle:    { fontSize: 22, fontWeight: '800', color: COLORS.textPrimary, marginBottom: 8 },
  errorText:     { fontSize: 14, color: COLORS.textMuted, textAlign: 'center', lineHeight: 20, marginBottom: 28 },
  errorBtn:      { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: COLORS.primary, paddingHorizontal: 28, paddingVertical: 14, borderRadius: 14 },
  errorBtnText:  { color: '#FFF', fontSize: 15, fontWeight: '700' },
  header:        { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingTop: Platform.OS === 'ios' ? 58 : 44, paddingBottom: 12, gap: 8 },
  backBtn:       { width: 40, height: 40, borderRadius: 13, backgroundColor: COLORS.surface, justifyContent: 'center', alignItems: 'center', borderWidth: 1, borderColor: COLORS.borderLight },
  headerCenter:  { flex: 1, alignItems: 'center' },
  headerTitle:   { fontSize: 17, fontWeight: '700', color: COLORS.textPrimary },
  refreshBtn:    { width: 40, height: 40, borderRadius: 13, backgroundColor: COLORS.primarySoft, justifyContent: 'center', alignItems: 'center' },
  heroCard:      { marginHorizontal: 16, marginTop: 8, marginBottom: 16, borderRadius: 22, overflow: 'hidden', elevation: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.25, shadowRadius: 12 },
  heroGradient:  { padding: 22, position: 'relative', overflow: 'hidden' },
  heroCircle1:   { position: 'absolute', width: 120, height: 120, borderRadius: 60, backgroundColor: 'rgba(255,255,255,0.06)', top: -30, right: -20 },
  heroCircle2:   { position: 'absolute', width: 70, height: 70, borderRadius: 35, backgroundColor: 'rgba(255,255,255,0.04)', bottom: -15, left: -10 },
  heroRow:       { flexDirection: 'row', alignItems: 'center', gap: 16, marginBottom: 16 },
  heroIconWrap:  { width: 56, height: 56, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.18)', justifyContent: 'center', alignItems: 'center' },
  heroTextWrap:  { flex: 1 },
  heroLabel:     { fontSize: 22, fontWeight: '800', color: '#FFF', letterSpacing: -0.3 },
  heroDesc:      { fontSize: 13, color: 'rgba(255,255,255,0.8)', marginTop: 3, lineHeight: 18 },
  trackingPill:  { flexDirection: 'row', alignItems: 'center', alignSelf: 'flex-start', gap: 6, backgroundColor: 'rgba(255,255,255,0.15)', paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20 },
  trackingText:  { fontSize: 13, fontWeight: '700', color: '#FFF', fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace', letterSpacing: 0.8 },
  card:          { backgroundColor: COLORS.surface, marginHorizontal: 16, marginBottom: 12, borderRadius: 18, padding: 18, borderWidth: 1, borderColor: COLORS.borderLight },
  cardHead:      { flexDirection: 'row', alignItems: 'center', marginBottom: 14, gap: 8 },
  cardTitle:     { fontSize: 15, fontWeight: '700', color: COLORS.textPrimary, flex: 1 },
  cancelledBanner: { flexDirection: 'row', alignItems: 'center', gap: 10, marginHorizontal: 16, marginBottom: 12, backgroundColor: COLORS.dangerGlow, paddingHorizontal: 18, paddingVertical: 14, borderRadius: 14, borderWidth: 1, borderColor: COLORS.danger + '30' },
  cancelledText: { fontSize: 14, fontWeight: '600', color: COLORS.danger },
  payBadge:      { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 10 },
  payDot:        { width: 6, height: 6, borderRadius: 3 },
  payText:       { fontSize: 11, fontWeight: '700', letterSpacing: 0.3 },
  receiptBody:   { paddingTop: 2 },
  receiptDivider:{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', marginVertical: 10, gap: 4 },
  receiptDash:   { width: 4, height: 1.5, backgroundColor: COLORS.border, borderRadius: 1 },
  branchRow:     { flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 14 },
  branchIcon:    { width: 50, height: 50, borderRadius: 16, backgroundColor: COLORS.primaryGlow, justifyContent: 'center', alignItems: 'center' },
  branchInfo:    { flex: 1 },
  branchName:    { fontSize: 16, fontWeight: '700', color: COLORS.textPrimary, marginBottom: 3 },
  branchAddr:    { fontSize: 13, color: COLORS.textMuted, lineHeight: 18 },
  callRow:       { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: COLORS.surfaceElevated, paddingHorizontal: 14, paddingVertical: 12, borderRadius: 14 },
  callIconWrap:  { width: 32, height: 32, borderRadius: 10, backgroundColor: COLORS.successGlow, justifyContent: 'center', alignItems: 'center' },
  callPhone:     { flex: 1, fontSize: 14, fontWeight: '600', color: COLORS.textPrimary },
  callChip:      { backgroundColor: COLORS.successGlow, paddingHorizontal: 14, paddingVertical: 6, borderRadius: 8 },
  callChipText:  { fontSize: 12, fontWeight: '700', color: COLORS.success },
  notesBox:      { backgroundColor: COLORS.surfaceElevated, borderRadius: 14, padding: 16 },
  notesText:     { fontSize: 13, color: COLORS.textSecondary, lineHeight: 20 },
  timestamps:    { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 16, paddingTop: 8, paddingBottom: 4, gap: 12 },
  tsRow:         { alignItems: 'center' },
  tsLabel:       { fontSize: 10, fontWeight: '700', color: COLORS.textMuted, letterSpacing: 0.5, textTransform: 'uppercase', marginBottom: 2 },
  tsValue:       { fontSize: 11, color: COLORS.textSecondary, fontWeight: '500' },
  tsDot:         { width: 4, height: 4, borderRadius: 2, backgroundColor: COLORS.border },
  bottomBar:     { position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: COLORS.background, paddingHorizontal: 16, paddingTop: 12, paddingBottom: Platform.OS === 'ios' ? 34 : 20, borderTopWidth: 1, borderTopColor: COLORS.borderLight },
  bottomBtn:     { borderRadius: 16, overflow: 'hidden' },
  bottomGradient:{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10, paddingVertical: 16 },
  bottomBtnText: { fontSize: 16, fontWeight: '700', color: '#FFF' },
});