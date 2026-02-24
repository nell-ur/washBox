import React, { useState, useEffect, useCallback, useRef } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Dimensions,
  StatusBar,
  Platform,
  Animated,
  Image,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// ─────────────────────────────────────────────
// DESIGN SYSTEM
// ─────────────────────────────────────────────
const COLORS = {
  // Base
  background: '#06081A',
  surface: '#0F1332',
  surfaceLight: '#171D45',
  surfaceElevated: '#1E2654',

  // Brand
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primaryGlow: 'rgba(14, 165, 233, 0.12)',
  primarySoft: 'rgba(14, 165, 233, 0.08)',

  // Accents
  secondary: '#8B5CF6',
  secondaryGlow: 'rgba(139, 92, 246, 0.12)',
  accent: '#F59E0B',
  accentGlow: 'rgba(245, 158, 11, 0.12)',
  success: '#10B981',
  successGlow: 'rgba(16, 185, 129, 0.12)',
  warning: '#F59E0B',
  danger: '#EF4444',
  dangerGlow: 'rgba(239, 68, 68, 0.12)',
  pink: '#EC4899',

  // Text
  textPrimary: '#F1F5F9',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',

  // Borders
  border: '#1E293B',
  borderLight: 'rgba(255, 255, 255, 0.06)',

  // Gradients
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
  gradientSecondary: ['#8B5CF6', '#7C3AED'],
  gradientAccent: ['#F59E0B', '#F97316'],
  gradientSuccess: ['#10B981', '#059669'],
  gradientSurface: ['#0F1332', '#171D45'],
};

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
const getGreeting = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Good Morning';
  if (hour < 17) return 'Good Afternoon';
  return 'Good Evening';
};

const getStatusColor = (status) => {
  const s = status?.toLowerCase();
  const map = {
    received: COLORS.primary,
    processing: COLORS.secondary,
    washing: COLORS.secondary,
    drying: COLORS.secondary,
    ironing: COLORS.accent,
    folding: COLORS.accent,
    ready: COLORS.success,
    ready_for_pickup: COLORS.success,
    completed: COLORS.success,
    cancelled: COLORS.danger,
    paid: COLORS.success,
  };
  return map[s] || COLORS.textMuted;
};

const getStatusIcon = (status) => {
  const s = status?.toLowerCase();
  const map = {
    received: 'receipt-outline',
    processing: 'sync-outline',
    washing: 'water-outline',
    drying: 'sunny-outline',
    ironing: 'shirt-outline',
    folding: 'layers-outline',
    ready: 'checkmark-circle-outline',
    ready_for_pickup: 'bag-check-outline',
    completed: 'checkmark-done-outline',
    cancelled: 'close-circle-outline',
    paid: 'card-outline',
  };
  return map[s] || 'ellipse-outline';
};

const formatStatus = (status) => {
  if (!status) return 'Unknown';
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

// ─────────────────────────────────────────────
// MAIN COMPONENT
// ─────────────────────────────────────────────
export default function HomeScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Data
  const [customer, setCustomer] = useState(null);
  const [laundries, setLaundries] = useState([]);
  const [promotions, setPromotions] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [stats, setStats] = useState({
    totalLaundries: 0,
    totalSpent: 0,
    pendingLaundries: 0,
    activePickups: 0,
  });

  // Animations
  const [fadeAnim] = useState(new Animated.Value(0));
  const [slideAnim] = useState(new Animated.Value(40));
  const promoScrollRef = useRef(null);

  useEffect(() => {
    fetchAllData();
  }, []);

  useEffect(() => {
    if (!loading) {
      Animated.parallel([
        Animated.timing(fadeAnim, { toValue: 1, duration: 500, useNativeDriver: true }),
        Animated.spring(slideAnim, { toValue: 0, useNativeDriver: true, tension: 60, friction: 12 }),
      ]).start();
    }
  }, [loading]);

  // ─── Data Fetching ───

  const fetchAllData = async () => {
    try {
      setLoading(true);
      await Promise.all([
        fetchCustomer(),
        fetchLaundries(),
        fetchPromotions(),
        fetchNotificationCount(),
        fetchStats(),
      ]);
    } catch (error) {
      console.error('Error loading home data:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchAllData();
    setRefreshing(false);
  }, []);

  const fetchCustomer = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/user`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.customer) {
          setCustomer(data.data.customer);
          await AsyncStorage.setItem(STORAGE_KEYS.CUSTOMER, JSON.stringify(data.data.customer));
        }
      } else if (response.status === 401) {
        await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);
        router.replace('/(auth)/login');
      }
    } catch (error) {
      // Try cached
      const cached = await AsyncStorage.getItem(STORAGE_KEYS.CUSTOMER);
      if (cached) setCustomer(JSON.parse(cached));
    }
  };

  const fetchLaundries = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/laundries`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.laundries) {
          setLaundries(data.data.laundries);
        }
      }
    } catch (error) {
      console.error('Error fetching laundries:', error);
    }
  };

  const fetchPromotions = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/v1/promotions`, {
        headers: { 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.promotions) {
          setPromotions(data.data.promotions);
        }
      }
    } catch (error) {
      console.error('Error fetching promotions:', error);
    }
  };

  const fetchNotificationCount = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/notifications?unread_only=true`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.notifications) {
          setUnreadCount(data.data.notifications.length);
        }
      }
    } catch (error) {
      console.error('Error fetching notifications:', error);
    }
  };

  const fetchStats = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/customer/stats`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.stats) {
          setStats(data.data.stats);
        }
      }
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  // ─── Derived Data ───

  const activeLaundries = laundries.filter(l =>
    !['completed', 'cancelled'].includes(l.status?.toLowerCase())
  ).slice(0, 5);

  const featuredPromos = promotions.filter(p => p.is_active).slice(0, 5);

  const firstName = customer?.name?.split(' ')[0] || 'there';

  // ─── Loading State ───

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />
        <Image
          source={require('../../assets/images/logo.png')}
          style={styles.loadingLogo}
          resizeMode="contain"
        />
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 24 }} />
        <Text style={styles.loadingText}>Loading WashBox...</Text>
      </View>
    );
  }

  // ─── Render ───

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
            colors={[COLORS.primary]}
            progressBackgroundColor={COLORS.surface}
          />
        }
        contentContainerStyle={{ paddingBottom: 100 }}
      >
        <Animated.View style={{ opacity: fadeAnim, transform: [{ translateY: slideAnim }] }}>

          {/* ═══════════════════════════════════════
              HEADER
          ═══════════════════════════════════════ */}
          <View style={styles.header}>
            <View style={styles.headerLeft}>
              <Image
                source={require('../../assets/images/logo.png')}
                style={styles.logo}
                resizeMode="contain"
              />
              <View style={styles.headerGreeting}>
                <Text style={styles.greetingLabel}>{getGreeting()}</Text>
                <Text style={styles.greetingName}>{firstName}</Text>
              </View>
            </View>

            <TouchableOpacity
              style={styles.notifButton}
              onPress={() => router.push('/notifications')}
              activeOpacity={0.7}
            >
              <Ionicons name="notifications-outline" size={22} color={COLORS.textPrimary} />
              {unreadCount > 0 && (
                <View style={styles.notifBadge}>
                  <Text style={styles.notifBadgeText}>
                    {unreadCount > 9 ? '9+' : unreadCount}
                  </Text>
                </View>
              )}
            </TouchableOpacity>
          </View>


          {/* ═══════════════════════════════════════
              QUICK ACTIONS
          ═══════════════════════════════════════ */}
          <View style={styles.quickActionsSection}>
            <View style={styles.quickActionsGrid}>
              <QuickAction
                icon="car-outline"
                label="Schedule Pickup"
                colors={COLORS.gradientPrimary}
                glowColor={COLORS.primaryGlow}
                onPress={() => router.push('/(tabs)/pickup')}
              />
              <QuickAction
                icon="shirt-outline"
                label="My Laundry"
                colors={COLORS.gradientSecondary}
                glowColor={COLORS.secondaryGlow}
                onPress={() => router.push('/(tabs)/laundry')}
              />
              <QuickAction
                icon="star-outline"
                label="Rate Service"
                colors={COLORS.gradientAccent}
                glowColor={COLORS.accentGlow}
                onPress={() => router.push('/ratings')}
              />
              <QuickAction
                icon="pricetag-outline"
                label="Promotions"
                colors={['#EC4899', '#F43F5E']}
                glowColor="rgba(236, 72, 153, 0.12)"
                onPress={() => router.push('/promotions')}
                badge={featuredPromos.length > 0 ? featuredPromos.length : null}
              />
            </View>
          </View>


          {/* ═══════════════════════════════════════
              ACTIVE LAUNDRY
          ═══════════════════════════════════════ */}
          {activeLaundries.length > 0 && (
            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <View style={styles.sectionTitleRow}>
                  <View style={[styles.sectionDot, { backgroundColor: COLORS.primary }]} />
                  <Text style={styles.sectionTitle}>Active Laundry</Text>
                </View>
                <TouchableOpacity
                  style={styles.seeAllButton}
                  onPress={() => router.push('/(tabs)/laundry')}
                >
                  <Text style={styles.seeAllText}>See All</Text>
                  <Ionicons name="chevron-forward" size={14} color={COLORS.primary} />
                </TouchableOpacity>
              </View>

              <View style={styles.laundryList}>
                {activeLaundries.map((laundry, index) => (
                  <LaundryCard key={laundry.id || index} laundry={laundry} />
                ))}
              </View>
            </View>
          )}

          {/* No active — show CTA */}
          {activeLaundries.length === 0 && (
            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <View style={styles.sectionTitleRow}>
                  <View style={[styles.sectionDot, { backgroundColor: COLORS.primary }]} />
                  <Text style={styles.sectionTitle}>Active Laundry</Text>
                </View>
              </View>
              <TouchableOpacity
                style={styles.emptyCta}
                onPress={() => router.push('/(tabs)/pickup')}
                activeOpacity={0.8}
              >
                <LinearGradient
                  colors={['#111640', '#171D45']}
                  style={styles.emptyCtaGradient}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                >
                  <View style={styles.emptyCtaIcon}>
                    <Ionicons name="basket-outline" size={28} color={COLORS.primary} />
                  </View>
                  <Text style={styles.emptyCtaTitle}>No active laundry</Text>
                  <Text style={styles.emptyCtaText}>
                    Schedule a pickup and we'll take care of the rest
                  </Text>
                  <View style={styles.emptyCtaButton}>
                    <Text style={styles.emptyCtaButtonText}>Schedule Pickup</Text>
                    <Ionicons name="arrow-forward" size={16} color={COLORS.primary} />
                  </View>
                </LinearGradient>
              </TouchableOpacity>
            </View>
          )}


          {/* ═══════════════════════════════════════
              PROMOTIONS
          ═══════════════════════════════════════ */}
          {featuredPromos.length > 0 && (
            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <View style={styles.sectionTitleRow}>
                  <View style={[styles.sectionDot, { backgroundColor: COLORS.pink }]} />
                  <Text style={styles.sectionTitle}>Special Offers</Text>
                </View>
                <TouchableOpacity
                  style={styles.seeAllButton}
                  onPress={() => router.push('/promotions')}
                >
                  <Text style={styles.seeAllText}>See All</Text>
                  <Ionicons name="chevron-forward" size={14} color={COLORS.primary} />
                </TouchableOpacity>
              </View>

              <ScrollView
                ref={promoScrollRef}
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.promoScroll}
                decelerationRate="fast"
                snapToInterval={SCREEN_WIDTH * 0.78 + 12}
                snapToAlignment="start"
              >
                {featuredPromos.map((promo, index) => (
                  <PromoCard key={promo.id || index} promo={promo} index={index} />
                ))}
              </ScrollView>
            </View>
          )}


          {/* ═══════════════════════════════════════
              STATS OVERVIEW
          ═══════════════════════════════════════ */}
          <View style={styles.section}>
            <View style={styles.sectionHeader}>
              <View style={styles.sectionTitleRow}>
                <View style={[styles.sectionDot, { backgroundColor: COLORS.success }]} />
                <Text style={styles.sectionTitle}>Your Overview</Text>
              </View>
            </View>

            <View style={styles.statsGrid}>
              <StatCard
                icon="shirt"
                label="Total Laundries"
                value={stats.totalLaundries || laundries.length}
                color={COLORS.primary}
                bgColor={COLORS.primaryGlow}
              />
              <StatCard
                icon="wallet"
                label="Total Spent"
                value={`₱${Number(stats.totalSpent || 0).toLocaleString()}`}
                color={COLORS.success}
                bgColor={COLORS.successGlow}
              />
              <StatCard
                icon="hourglass"
                label="In Progress"
                value={stats.pendingLaundries || activeLaundries.length}
                color={COLORS.secondary}
                bgColor={COLORS.secondaryGlow}
              />
              <StatCard
                icon="car"
                label="Active Pickups"
                value={stats.activePickups || 0}
                color={COLORS.accent}
                bgColor={COLORS.accentGlow}
              />
            </View>
          </View>

        </Animated.View>
      </ScrollView>
    </View>
  );
}


// ─────────────────────────────────────────────
// QUICK ACTION BUTTON
// ─────────────────────────────────────────────
const QuickAction = ({ icon, label, colors, glowColor, onPress, badge }) => (
  <TouchableOpacity style={styles.quickAction} onPress={onPress} activeOpacity={0.75}>
    <View style={[styles.quickActionIconWrap, { backgroundColor: glowColor }]}>
      <LinearGradient
        colors={colors}
        style={styles.quickActionIcon}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      >
        <Ionicons name={icon} size={22} color="#FFF" />
      </LinearGradient>
      {badge && (
        <View style={styles.quickActionBadge}>
          <Text style={styles.quickActionBadgeText}>{badge}</Text>
        </View>
      )}
    </View>
    <Text style={styles.quickActionLabel} numberOfLines={1}>{label}</Text>
  </TouchableOpacity>
);


// ─────────────────────────────────────────────
// LAUNDRY CARD
// ─────────────────────────────────────────────
const LaundryCard = ({ laundry }) => {
  const statusColor = getStatusColor(laundry.status);

  return (
    <TouchableOpacity
      style={styles.laundryCard}
      onPress={() => router.push(`/laundries/${laundry.tracking_number || laundry.id}`)}
      activeOpacity={0.7}
    >
      {/* Status indicator line */}
      <View style={[styles.laundryStatusLine, { backgroundColor: statusColor }]} />

      <View style={styles.laundryContent}>
        {/* Top row */}
        <View style={styles.laundryTopRow}>
          <View style={[styles.laundryIconWrap, { backgroundColor: statusColor + '18' }]}>
            <Ionicons name={getStatusIcon(laundry.status)} size={18} color={statusColor} />
          </View>
          <View style={styles.laundryInfo}>
            <Text style={styles.laundryTracker}>
              {laundry.tracking_number || `#${laundry.id}`}
            </Text>
            <Text style={styles.laundryService} numberOfLines={1}>
              {laundry.service_name || 'Laundry Service'}
            </Text>
          </View>
          <View style={[styles.statusChip, { backgroundColor: statusColor + '18' }]}>
            <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
            <Text style={[styles.statusText, { color: statusColor }]}>
              {formatStatus(laundry.status)}
            </Text>
          </View>
        </View>

        {/* Bottom row */}
        <View style={styles.laundryBottomRow}>
          {laundry.branch_name && (
            <View style={styles.laundryMeta}>
              <Ionicons name="business-outline" size={12} color={COLORS.textMuted} />
              <Text style={styles.laundryMetaText}>{laundry.branch_name}</Text>
            </View>
          )}
          {laundry.total_amount > 0 && (
            <Text style={styles.laundryAmount}>
              ₱{Number(laundry.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}
            </Text>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );
};


// ─────────────────────────────────────────────
// PROMOTION CARD
// ─────────────────────────────────────────────
const PROMO_GRADIENTS = [
  ['#0EA5E9', '#3B82F6'],
  ['#8B5CF6', '#7C3AED'],
  ['#EC4899', '#F43F5E'],
  ['#F59E0B', '#F97316'],
  ['#10B981', '#059669'],
];

const PromoCard = ({ promo, index }) => {
  const colors = PROMO_GRADIENTS[index % PROMO_GRADIENTS.length];

  return (
    <TouchableOpacity
      style={styles.promoCard}
      activeOpacity={0.85}
      onPress={() => router.push('/promotions')}
    >
      <LinearGradient
        colors={colors}
        style={styles.promoGradient}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      >
        {/* Decorative circles */}
        <View style={[styles.promoCircle, styles.promoCircle1]} />
        <View style={[styles.promoCircle, styles.promoCircle2]} />

        {/* Badges */}
        <View style={styles.promoBadges}>
          {promo.is_featured && (
            <View style={styles.promoBadge}>
              <Ionicons name="star" size={10} color="#FFC107" />
              <Text style={styles.promoBadgeText}>FEATURED</Text>
            </View>
          )}
          {promo.is_active && (
            <View style={[styles.promoBadge, { backgroundColor: 'rgba(16, 185, 129, 0.25)' }]}>
              <View style={{ width: 5, height: 5, borderRadius: 2.5, backgroundColor: '#10B981' }} />
              <Text style={[styles.promoBadgeText, { color: '#6EE7B7' }]}>ACTIVE</Text>
            </View>
          )}
        </View>

        {/* Content */}
        <Text style={styles.promoTitle} numberOfLines={2}>
          {promo.poster_title || promo.name}
        </Text>
        <Text style={styles.promoSubtitle} numberOfLines={2}>
          {promo.poster_subtitle || promo.description}
        </Text>

        {/* Price */}
        {promo.display_price && (
          <View style={styles.promoPriceRow}>
            <Text style={styles.promoPrice}>₱{promo.display_price}</Text>
            {promo.original_price && (
              <Text style={styles.promoOriginal}>₱{promo.original_price}</Text>
            )}
          </View>
        )}

        {/* Features */}
        {promo.poster_features?.length > 0 && (
          <View style={styles.promoFeatures}>
            {promo.poster_features.slice(0, 2).map((f, i) => (
              <View key={i} style={styles.promoFeatureRow}>
                <Ionicons name="checkmark-circle" size={12} color="rgba(255,255,255,0.85)" />
                <Text style={styles.promoFeatureText} numberOfLines={1}>{f}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Valid until */}
        {promo.valid_until && (
          <View style={styles.promoValidity}>
            <Ionicons name="time-outline" size={11} color="rgba(255,255,255,0.6)" />
            <Text style={styles.promoValidityText}>
              Valid until {new Date(promo.valid_until).toLocaleDateString()}
            </Text>
          </View>
        )}
      </LinearGradient>
    </TouchableOpacity>
  );
};


// ─────────────────────────────────────────────
// STAT CARD
// ─────────────────────────────────────────────
const StatCard = ({ icon, label, value, color, bgColor }) => (
  <View style={styles.statCard}>
    <View style={[styles.statIconWrap, { backgroundColor: bgColor }]}>
      <Ionicons name={icon} size={18} color={color} />
    </View>
    <Text style={styles.statValue}>{value}</Text>
    <Text style={styles.statLabel}>{label}</Text>
  </View>
);


// ─────────────────────────────────────────────
// STYLES
// ─────────────────────────────────────────────
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },

  // Loading
  loadingLogo: {
    width: 80,
    height: 80,
    borderRadius: 20,
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: 12,
    fontSize: 14,
    fontWeight: '500',
  },

  // ─── Header ───
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 62 : 48,
    paddingBottom: 12,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    flex: 1,
  },
  logo: {
    width: 44,
    height: 44,
    borderRadius: 14,
  },
  headerGreeting: {
    flex: 1,
  },
  greetingLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: COLORS.textMuted,
    letterSpacing: 0.5,
    textTransform: 'uppercase',
  },
  greetingName: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    letterSpacing: -0.3,
    marginTop: 1,
  },
  notifButton: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: COLORS.surface,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },
  notifBadge: {
    position: 'absolute',
    top: 6,
    right: 6,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: COLORS.danger,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 4,
  },
  notifBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#FFF',
  },

  // ─── Quick Actions ───
  quickActionsSection: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 8,
  },
  quickActionsGrid: {
    flexDirection: 'row',
    gap: 12,
  },
  quickAction: {
    flex: 1,
    alignItems: 'center',
    gap: 8,
  },
  quickActionIconWrap: {
    width: 56,
    height: 56,
    borderRadius: 18,
    justifyContent: 'center',
    alignItems: 'center',
    position: 'relative',
  },
  quickActionIcon: {
    width: 46,
    height: 46,
    borderRadius: 15,
    justifyContent: 'center',
    alignItems: 'center',
  },
  quickActionBadge: {
    position: 'absolute',
    top: -2,
    right: -2,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: COLORS.danger,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: COLORS.background,
  },
  quickActionBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#FFF',
  },
  quickActionLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: COLORS.textSecondary,
    textAlign: 'center',
  },

  // ─── Sections ───
  section: {
    marginTop: 28,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    marginBottom: 14,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  sectionDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: COLORS.textPrimary,
    letterSpacing: -0.2,
  },
  seeAllButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
  },
  seeAllText: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.primary,
  },

  // ─── Laundry Cards ───
  laundryList: {
    paddingHorizontal: 20,
    gap: 10,
  },
  laundryCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    flexDirection: 'row',
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },
  laundryStatusLine: {
    width: 4,
  },
  laundryContent: {
    flex: 1,
    padding: 14,
    gap: 10,
  },
  laundryTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  laundryIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  laundryInfo: {
    flex: 1,
  },
  laundryTracker: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.textPrimary,
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
    letterSpacing: 0.3,
  },
  laundryService: {
    fontSize: 12,
    color: COLORS.textMuted,
    marginTop: 2,
  },
  statusChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 10,
  },
  statusDot: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
  },
  statusText: {
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  laundryBottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingLeft: 50,
  },
  laundryMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  laundryMetaText: {
    fontSize: 11,
    color: COLORS.textMuted,
    fontWeight: '500',
  },
  laundryAmount: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },

  // ─── Empty CTA ───
  emptyCta: {
    marginHorizontal: 20,
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },
  emptyCtaGradient: {
    padding: 28,
    alignItems: 'center',
  },
  emptyCtaIcon: {
    width: 60,
    height: 60,
    borderRadius: 20,
    backgroundColor: COLORS.primaryGlow,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  emptyCtaTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 6,
  },
  emptyCtaText: {
    fontSize: 13,
    color: COLORS.textMuted,
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 18,
  },
  emptyCtaButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: COLORS.primaryGlow,
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.primary + '30',
  },
  emptyCtaButtonText: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.primary,
  },

  // ─── Promo Cards ───
  promoScroll: {
    paddingHorizontal: 20,
    gap: 12,
  },
  promoCard: {
    width: SCREEN_WIDTH * 0.78,
    borderRadius: 20,
    overflow: 'hidden',
    elevation: 6,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
  },
  promoGradient: {
    padding: 20,
    minHeight: 190,
    position: 'relative',
    overflow: 'hidden',
  },
  promoCircle: {
    position: 'absolute',
    borderRadius: 999,
    backgroundColor: 'rgba(255, 255, 255, 0.06)',
  },
  promoCircle1: {
    width: 140,
    height: 140,
    top: -40,
    right: -30,
  },
  promoCircle2: {
    width: 80,
    height: 80,
    bottom: -20,
    left: -20,
  },
  promoBadges: {
    flexDirection: 'row',
    gap: 6,
    marginBottom: 14,
  },
  promoBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(255, 193, 7, 0.2)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  promoBadgeText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#FFC107',
    letterSpacing: 0.5,
  },
  promoTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#FFF',
    marginBottom: 6,
    lineHeight: 26,
  },
  promoSubtitle: {
    fontSize: 13,
    color: 'rgba(255, 255, 255, 0.85)',
    lineHeight: 18,
    marginBottom: 12,
  },
  promoPriceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 10,
  },
  promoPrice: {
    fontSize: 28,
    fontWeight: '800',
    color: '#FFF',
  },
  promoOriginal: {
    fontSize: 16,
    color: 'rgba(255, 255, 255, 0.5)',
    textDecorationLine: 'line-through',
  },
  promoFeatures: {
    gap: 6,
    marginBottom: 8,
  },
  promoFeatureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  promoFeatureText: {
    flex: 1,
    fontSize: 11,
    fontWeight: '500',
    color: 'rgba(255, 255, 255, 0.85)',
  },
  promoValidity: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: 6,
  },
  promoValidityText: {
    fontSize: 10,
    color: 'rgba(255, 255, 255, 0.55)',
    fontWeight: '500',
  },

  // ─── Stats Grid ───
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: 20,
    gap: 10,
  },
  statCard: {
    width: (SCREEN_WIDTH - 50) / 2,
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    gap: 6,
  },
  statIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 4,
  },
  statValue: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    letterSpacing: -0.3,
  },
  statLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: COLORS.textMuted,
    letterSpacing: 0.2,
  },
});