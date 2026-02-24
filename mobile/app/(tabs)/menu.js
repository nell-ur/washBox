import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  RefreshControl,
  Animated,
  Dimensions,
  StatusBar,
  Platform,
} from 'react-native';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { API_BASE_URL, STORAGE_KEYS, ENDPOINTS } from '../../constants/config';
import { useAuth } from '../../context/AuthContext';
import TermsModal from '../../components/TermsModal';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

const COLORS = {
  background: '#0A0E27',
  backgroundLight: '#131937',
  cardDark: '#1C2340',
  cardLight: '#252D4C',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primaryLight: '#38BDF8',
  secondary: '#8B5CF6',
  accent: '#F59E0B',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  success: '#10B981',
  warning: '#F59E0B',
  danger: '#EF4444',
  purple: '#8B5CF6',
  pink: '#EC4899',
  cyan: '#06B6D4',
  border: '#1E293B',
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
  gradientSecondary: ['#8B5CF6', '#EC4899'],
  gradientSuccess: ['#10B981', '#059669'],
  gradientWarning: ['#F59E0B', '#EF4444'],
};

// ─── Services config ───────────────────────────────────────────────────────
const CATEGORY_ORDER = ['drop_off', 'self_service', 'addon'];

const CATEGORY_CONFIG = {
  drop_off: {
    label: 'Drop Off',
    icon: 'shirt-outline',
    gradient: ['#0EA5E9', '#3B82F6'],
  },
  self_service: {
    label: 'Self Service',
    icon: 'settings-outline',
    gradient: ['#10B981', '#059669'],
  },
  addon: {
    label: 'Add-ons',
    icon: 'add-circle-outline',
    gradient: ['#F59E0B', '#D97706'],
  },
};

const SERVICE_TYPE_LABELS = {
  regular_clothes: 'Regular Clothes',
  full_service:    'Full Service',
  special_item:    'Comforter / Blanket',
  self_service:    'Self Service',
  addon:           'Add-on',
};
// ──────────────────────────────────────────────────────────────────────────

export default function MenuScreen() {
  const { logout } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const [customer, setCustomer] = useState(null);
  const [branch, setBranch]     = useState(null);
  const [showTermsModal, setShowTermsModal] = useState(false);
  const [stats, setStats] = useState({
    totalLaundries: 0,
    totalSpent: 0,
    rating: 0,
    completionRate: 0,
    pendingLaundries: 0,
    activePickups: 0,
  });

  // Services state
  const [services, setServices] = useState({});          // { drop_off: [...], ... }
  const [servicesLoading, setServicesLoading] = useState(false);
  const [expandedCategory, setExpandedCategory] = useState(null);

  // Animations
  const [fadeAnim] = useState(new Animated.Value(0));
  const [slideAnim] = useState(new Animated.Value(30));

  useEffect(() => {
    fetchMenuData();
  }, []);

  useEffect(() => {
    if (!loading) {
      Animated.parallel([
        Animated.timing(fadeAnim, {
          toValue: 1,
          duration: 500,
          useNativeDriver: true,
        }),
        Animated.spring(slideAnim, {
          toValue: 0,
          tension: 50,
          friction: 8,
          useNativeDriver: true,
        }),
      ]).start();
    }
  }, [loading]);

  const fetchMenuData = async () => {
    try {
      setLoading(true);
      await Promise.all([
        fetchCustomerProfile(),
        fetchCustomerStats(),
        fetchServices(),
      ]);
    } catch (error) {
      console.error('Error fetching menu data:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchMenuData();
    setRefreshing(false);
  };

  const fetchCustomerProfile = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/user`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.customer) {
          setCustomer(data.data.customer);
          await AsyncStorage.setItem(STORAGE_KEYS.CUSTOMER, JSON.stringify(data.data.customer));
          // Fetch the branch the customer registered at
          const branchId = data.data.customer.branch_id;
          if (branchId) await fetchBranch(branchId, token);
        }
      } else if (response.status === 401) {
        await logout();
      }
    } catch (error) {
      const cachedCustomer = await AsyncStorage.getItem(STORAGE_KEYS.CUSTOMER);
      if (cachedCustomer) setCustomer(JSON.parse(cachedCustomer));
    }
  };

  const fetchBranch = async (branchId, token) => {
    try {
      const response = await fetch(`${API_BASE_URL}/v1/branches/${branchId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });
      if (response.ok) {
        const data = await response.json();
        const b = data.data?.branch ?? data.data ?? data.branch ?? null;
        if (b) {
          setBranch(b);
          await AsyncStorage.setItem('cached_branch', JSON.stringify(b));
        }
      } else {
        const cached = await AsyncStorage.getItem('cached_branch');
        if (cached) setBranch(JSON.parse(cached));
      }
    } catch (error) {
      console.log('Branch fetch error:', error);
      const cached = await AsyncStorage.getItem('cached_branch');
      if (cached) setBranch(JSON.parse(cached));
    }
  };

  const fetchCustomerStats = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/customer/stats`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.stats) {
          setStats(data.data.stats);
        }
      }
    } catch (error) {
      console.log('Stats fetch error:', error);
    }
  };

  // ─── Fetch + group services by category ─────────────────────────────────
  const fetchServices = async () => {
    try {
      setServicesLoading(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);

      // Use ENDPOINTS constant; strip any accidental trailing slash from base
      const baseUrl = API_BASE_URL.replace(/\/+$/, '');
      const url     = `${baseUrl}${ENDPOINTS.SERVICES}`;

      console.log('[fetchServices] GET', url);

      const response = await fetch(url, {
        headers: {
          'Authorization': token ? `Bearer ${token}` : '',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });

      console.log('[fetchServices] status', response.status, response.headers.get('content-type'));

      // Guard: make sure we actually got JSON before parsing
      const contentType = response.headers.get('content-type') ?? '';
      if (!contentType.includes('application/json')) {
        const raw = await response.text();
        console.warn('[fetchServices] Non-JSON response (first 300 chars):', raw.substring(0, 300));
        // Fall back to cache
        const cached = await AsyncStorage.getItem('cached_services');
        if (cached) setServices(JSON.parse(cached));
        return;
      }

      if (response.ok) {
        const data = await response.json();
        const list = data.data?.services ?? data.data ?? data.services ?? [];

        // Group by category field
        const grouped = {};
        list.forEach(svc => {
          const cat = svc.category || 'drop_off';
          if (!grouped[cat]) grouped[cat] = [];
          grouped[cat].push(svc);
        });

        console.log('[fetchServices] loaded', list.length, 'services in', Object.keys(grouped), 'categories');
        setServices(grouped);
        await AsyncStorage.setItem('cached_services', JSON.stringify(grouped));
      } else {
        const errBody = await response.json().catch(() => ({}));
        console.warn('[fetchServices] Error response:', response.status, errBody);
        const cached = await AsyncStorage.getItem('cached_services');
        if (cached) setServices(JSON.parse(cached));
      }
    } catch (error) {
      console.log('Services fetch error:', error);
      const cached = await AsyncStorage.getItem('cached_services');
      if (cached) setServices(JSON.parse(cached));
    } finally {
      setServicesLoading(false);
    }
  };
  // ──────────────────────────────────────────────────────────────────────────

  const performLogout = async () => {
    try {
      setLoggingOut(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);

      if (token) {
        await fetch(`${API_BASE_URL}/v1/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });
      }
      await logout();
    } catch (error) {
      console.error('Logout error:', error);
      await logout();
    } finally {
      setLoggingOut(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(
      'Log Out',
      'Are you sure you want to log out?',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Log Out', style: 'destructive', onPress: performLogout },
      ]
    );
  };

  const getInitials = (name) => {
    if (!name) return '??';
    return name.split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  };

  const formatCurrency = (amount) => {
    return `₱${parseFloat(amount || 0).toLocaleString('en-PH', { 
      minimumFractionDigits: 0,
      maximumFractionDigits: 0 
    })}`;
  };

  const formatServicePrice = (service) => {
    const price = service.price_per_load ?? service.price ?? 0;
    const isSpecial = service.service_type === 'special_item';
    return `₱${parseFloat(price).toLocaleString('en-PH', { minimumFractionDigits: 0 })}/${isSpecial ? 'piece' : 'load'}`;
  };

  // Menu items - ADDED Rate Branch item
  const primaryMenuItems = [
    {
      icon: 'person-outline',
      label: 'Edit Profile',
      subtitle: 'Update your information',
      color: COLORS.primary,
      action: () => router.push('/profile/edit'),
    },
    // NEW: Rate Branch menu item
    {
      icon: 'business-outline',
      label: 'Rate a Branch',
      subtitle: 'Share your feedback about our branches',
      color: COLORS.success,
      action: () => router.push('/ratings?tab=rate_branch'),
    },
    {
      icon: 'star-outline',
      label: 'My Ratings',
      subtitle: 'View and manage your ratings',
      color: COLORS.warning,
      action: () => router.push('/ratings'),
    },
    {
      icon: 'notifications-outline',
      label: 'Notifications',
      subtitle: 'Push notifications & alerts',
      color: COLORS.purple,
      action: () => router.push('/notifications'),
    },
  ];

  const secondaryMenuItems = [
    {
      icon: 'pricetag-outline',
      label: 'Promotions',
      subtitle: 'Deals and discounts',
      color: COLORS.success,
      action: () => router.push('/promotions'),
    },
    {
      icon: 'card-outline',
      label: 'Payment Methods',
      subtitle: 'Manage payment options',
      color: COLORS.cyan,
      action: () => Alert.alert('Coming Soon', 'Payment methods will be available soon!'),
    },
    {
      icon: 'location-outline',
      label: 'Saved Addresses',
      subtitle: 'Manage delivery locations',
      color: COLORS.primaryLight,
      action: () => Alert.alert('Coming Soon', 'Saved addresses will be available soon!'),
    },
  ];

  const supportMenuItems = [
    {
      icon: 'shield-checkmark-outline',
      label: 'Privacy & Security',
      subtitle: 'Password & account security',
      color: COLORS.secondary,
      action: () => Alert.alert('Coming Soon', 'Privacy settings will be available soon!'),
    },
    {
      icon: 'help-circle-outline',
      label: 'Help Center',
      subtitle: 'FAQs and support',
      color: COLORS.primary,
      action: () => {
        const phone = branch?.phone ?? '(035) 123-4567';
        const email = branch?.email ?? 'support@washbox.com';
        const name  = branch?.name  ?? 'WashBox';
        Alert.alert(
          `${name} Support`,
          `📞 ${phone}\n✉️ ${email}`,
          [{ text: 'OK' }]
        );
      },
    },
    {
      icon: 'document-text-outline',
      label: 'Terms & Privacy',
      subtitle: 'Legal information',
      color: COLORS.textSecondary,
      action: () => setShowTermsModal(true),
    },
  ];

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading...</Text>
      </View>
    );
  }

  // ─── Renderers ────────────────────────────────────────────────────────────

  const renderMenuItem = (item, index, isLast) => (
    <React.Fragment key={index}>
      <TouchableOpacity
        style={styles.menuItem}
        onPress={item.action}
        activeOpacity={0.7}
      >
        <View style={[styles.menuIconContainer, { backgroundColor: item.color + '20' }]}>
          <Ionicons name={item.icon} size={22} color={item.color} />
        </View>
        <View style={styles.menuContent}>
          <Text style={styles.menuLabel}>{item.label}</Text>
          <Text style={styles.menuSubtitle}>{item.subtitle}</Text>
        </View>
        <Ionicons name="chevron-forward" size={20} color={COLORS.textMuted} />
      </TouchableOpacity>
      {!isLast && <View style={styles.menuDivider} />}
    </React.Fragment>
  );

  // Single service row inside an expanded category
  const renderServiceRow = (service, index, isLast) => {
    const isSpecial = service.service_type === 'special_item';
    const typeLabel = SERVICE_TYPE_LABELS[service.service_type] || service.service_type;
    const maxWeight = service.max_weight ? ` · up to ${service.max_weight} kg` : '';

    return (
      <React.Fragment key={service.id ?? index}>
        <View style={styles.serviceRow}>
          <View style={[
            styles.serviceTypeDot,
            { backgroundColor: isSpecial ? COLORS.warning + '25' : COLORS.primary + '20' },
          ]}>
            <Ionicons
              name={isSpecial ? 'layers-outline' : 'checkmark-outline'}
              size={14}
              color={isSpecial ? COLORS.warning : COLORS.primary}
            />
          </View>
          <View style={styles.serviceRowInfo}>
            <Text style={styles.serviceRowName}>{service.name}</Text>
            <Text style={styles.serviceRowMeta}>{typeLabel}{maxWeight}</Text>
          </View>
          <Text style={styles.serviceRowPrice}>{formatServicePrice(service)}</Text>
        </View>
        {!isLast && <View style={styles.serviceRowDivider} />}
      </React.Fragment>
    );
  };

  // Collapsible category card
  const renderCategoryCard = (categoryKey, catServices) => {
    const config = CATEGORY_CONFIG[categoryKey] ?? CATEGORY_CONFIG.drop_off;
    const isOpen = expandedCategory === categoryKey;
    const count  = catServices?.length ?? 0;

    return (
      <View key={categoryKey} style={styles.categoryCard}>
        <TouchableOpacity
          style={styles.categoryHeader}
          activeOpacity={0.75}
          onPress={() => setExpandedCategory(isOpen ? null : categoryKey)}
        >
          <LinearGradient
            colors={config.gradient}
            style={styles.categoryIcon}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <Ionicons name={config.icon} size={20} color="#fff" />
          </LinearGradient>
          <View style={styles.categoryMeta}>
            <Text style={styles.categoryLabel}>{config.label}</Text>
            <Text style={styles.categoryCount}>
              {count} service{count !== 1 ? 's' : ''}
            </Text>
          </View>
          <Ionicons
            name={isOpen ? 'chevron-up' : 'chevron-down'}
            size={18}
            color={COLORS.textMuted}
          />
        </TouchableOpacity>

        {isOpen && count > 0 && (
          <View style={styles.serviceList}>
            <View style={styles.serviceListDivider} />
            {catServices.map((svc, idx) =>
              renderServiceRow(svc, idx, idx === catServices.length - 1)
            )}
          </View>
        )}

        {isOpen && count === 0 && (
          <View style={styles.serviceListEmpty}>
            <Text style={styles.serviceListEmptyText}>No services in this category</Text>
          </View>
        )}
      </View>
    );
  };

  const hasServices = Object.keys(services).length > 0;

  // ─── Main render ──────────────────────────────────────────────────────────
  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />

      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Menu</Text>
        <TouchableOpacity
          style={styles.headerIconButton}
          onPress={() => router.push('/notifications')}
        >
          <Ionicons name="settings-outline" size={22} color={COLORS.textPrimary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        style={styles.scrollView}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
            colors={[COLORS.primary]}
          />
        }
        contentContainerStyle={styles.scrollContent}
      >
        <Animated.View style={{ 
          opacity: fadeAnim,
          transform: [{ translateY: slideAnim }]
        }}>

          {/* Profile Card */}
          <TouchableOpacity
            style={styles.profileCard}
            activeOpacity={0.8}
            onPress={() => router.push('/profile/edit')}
          >
            <View style={styles.profileRow}>
              <LinearGradient
                colors={COLORS.gradientPrimary}
                style={styles.avatar}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
              >
                <Text style={styles.avatarText}>{getInitials(customer?.name)}</Text>
              </LinearGradient>
              <View style={styles.profileInfo}>
                <Text style={styles.profileName}>{customer?.name || 'User'}</Text>
                <Text style={styles.profileEmail}>{customer?.email || 'View your profile'}</Text>
              </View>
              <View style={styles.profileNotificationBadge}>
                <Ionicons name="chevron-forward" size={20} color={COLORS.textMuted} />
              </View>
            </View>

            <View style={styles.miniStatsRow}>
              <View style={styles.miniStat}>
                <Text style={styles.miniStatValue}>{stats.totalLaundries}</Text>
                <Text style={styles.miniStatLabel}>Laundries</Text>
              </View>
              <View style={styles.miniStatDivider} />
              <View style={styles.miniStat}>
                <Text style={styles.miniStatValue}>{formatCurrency(stats.totalSpent)}</Text>
                <Text style={styles.miniStatLabel}>Spent</Text>
              </View>
              <View style={styles.miniStatDivider} />
              <View style={styles.miniStat}>
                <View style={styles.miniRating}>
                  <Ionicons name="star" size={14} color={COLORS.warning} />
                  <Text style={styles.miniStatValue}>{(stats.rating || 0).toFixed(1)}</Text>
                </View>
                <Text style={styles.miniStatLabel}>Rating</Text>
              </View>
            </View>
          </TouchableOpacity>

          {/* Primary Menu */}
          <View style={styles.menuSection}>
            <View style={styles.menuCard}>
              {primaryMenuItems.map((item, index) => 
                renderMenuItem(item, index, index === primaryMenuItems.length - 1)
              )}
            </View>
          </View>

          {/* ══════ SERVICES SECTION ══════ */}
          <View style={styles.menuSection}>
            <View style={styles.sectionTitleRow}>
              <Text style={styles.sectionTitle}>Our Services</Text>
              {servicesLoading && (
                <ActivityIndicator
                  size="small"
                  color={COLORS.primary}
                  style={{ marginLeft: 8 }}
                />
              )}
            </View>

            {hasServices ? (
              <View style={styles.servicesContainer}>
                {/* Render in defined order, then any extras */}
                {[
                  ...CATEGORY_ORDER,
                  ...Object.keys(services).filter(k => !CATEGORY_ORDER.includes(k)),
                ]
                  .filter(cat => services[cat]?.length > 0)
                  .map(cat => renderCategoryCard(cat, services[cat]))}
              </View>
            ) : servicesLoading ? (
              <View style={styles.servicesPlaceholder}>
                <ActivityIndicator color={COLORS.primary} />
                <Text style={styles.servicesPlaceholderText}>Loading services...</Text>
              </View>
            ) : (
              <View style={styles.servicesPlaceholder}>
                <Ionicons name="water-outline" size={32} color={COLORS.textMuted} />
                <Text style={styles.servicesPlaceholderText}>No services available</Text>
              </View>
            )}
          </View>
          {/* ══════════════════════════════ */}

          {/* Secondary Menu */}
          <View style={styles.menuSection}>
            <Text style={styles.sectionTitle}>More</Text>
            <View style={styles.menuCard}>
              {secondaryMenuItems.map((item, index) => 
                renderMenuItem(item, index, index === secondaryMenuItems.length - 1)
              )}
            </View>
          </View>

          {/* Support Section */}
          <View style={styles.menuSection}>
            <Text style={styles.sectionTitle}>Support & Legal</Text>
            <View style={styles.menuCard}>
              {supportMenuItems.map((item, index) => 
                renderMenuItem(item, index, index === supportMenuItems.length - 1)
              )}
            </View>
          </View>

          {/* Logout */}
          <View style={styles.logoutSection}>
            <TouchableOpacity
              style={styles.logoutButton}
              onPress={handleLogout}
              disabled={loggingOut}
              activeOpacity={0.8}
            >
              <LinearGradient
                colors={['rgba(239, 68, 68, 0.1)', 'rgba(239, 68, 68, 0.05)']}
                style={styles.logoutGradient}
              >
                {loggingOut ? (
                  <ActivityIndicator color={COLORS.danger} size="small" />
                ) : (
                  <>
                    <Ionicons name="log-out-outline" size={22} color={COLORS.danger} />
                    <Text style={styles.logoutText}>Log Out</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </View>

          {/* App Info */}
          <View style={styles.appInfoSection}>
            <Text style={styles.appInfoTitle}>WashBox v2.1.0</Text>
            <Text style={styles.appInfoText}>© {new Date().getFullYear()} WashBox Laundry Services</Text>
          </View>

          <View style={{ height: 40 }} />
        </Animated.View>
      </ScrollView>

      <TermsModal
        visible={showTermsModal}
        onClose={() => setShowTermsModal(false)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: 16,
    fontSize: 14,
    fontWeight: '600',
  },

  // Header
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 60 : 48,
    paddingBottom: 16,
  },
  headerTitle: {
    fontSize: 32,
    fontWeight: '800',
    color: COLORS.textPrimary,
    letterSpacing: -0.5,
  },
  headerIconButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },

  scrollView: { flex: 1 },
  scrollContent: { paddingHorizontal: 20 },

  // Profile Card
  profileCard: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 20,
    padding: 20,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  avatar: {
    width: 60,
    height: 60,
    borderRadius: 30,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.textPrimary,
    letterSpacing: 1,
  },
  profileInfo: {
    flex: 1,
    marginLeft: 16,
  },
  profileName: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 4,
  },
  profileEmail: {
    fontSize: 13,
    color: COLORS.textSecondary,
  },
  profileNotificationBadge: {
    marginLeft: 8,
  },

  miniStatsRow: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 14,
    padding: 14,
    alignItems: 'center',
  },
  miniStat: {
    flex: 1,
    alignItems: 'center',
  },
  miniStatDivider: {
    width: 1,
    height: 30,
    backgroundColor: 'rgba(255,255,255,0.1)',
  },
  miniStatValue: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  miniStatLabel: {
    fontSize: 10,
    color: COLORS.textSecondary,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  miniRating: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },

  // Sections
  menuSection: { marginBottom: 20 },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
    marginLeft: 4,
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },

  // Generic menu card (existing items)
  menuCard: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    gap: 14,
  },
  menuIconContainer: {
    width: 44,
    height: 44,
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
  },
  menuContent: { flex: 1 },
  menuLabel: {
    fontSize: 15,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  menuSubtitle: {
    fontSize: 12,
    color: COLORS.textSecondary,
  },
  menuDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginLeft: 74,
  },

  // ─── Services ───────────────────────────────────────────────────────────
  servicesContainer: { gap: 10 },

  // Category card
  categoryCard: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },
  categoryHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    gap: 14,
  },
  categoryIcon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
  },
  categoryMeta: { flex: 1 },
  categoryLabel: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  categoryCount: {
    fontSize: 12,
    color: COLORS.textSecondary,
  },

  // Expanded service list
  serviceList: {
    paddingHorizontal: 16,
    paddingBottom: 8,
  },
  serviceListDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginBottom: 4,
  },
  serviceListEmpty: {
    paddingHorizontal: 16,
    paddingBottom: 14,
    alignItems: 'center',
  },
  serviceListEmptyText: {
    fontSize: 12,
    color: COLORS.textMuted,
    fontStyle: 'italic',
  },

  // Individual service row
  serviceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    gap: 12,
  },
  serviceTypeDot: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  serviceRowInfo: { flex: 1 },
  serviceRowName: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  serviceRowMeta: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  serviceRowPrice: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.primary,
  },
  serviceRowDivider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginLeft: 44,
  },

  // Services placeholder (loading / empty)
  servicesPlaceholder: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 32,
    alignItems: 'center',
    gap: 10,
  },
  servicesPlaceholderText: {
    fontSize: 13,
    color: COLORS.textMuted,
  },

  // Logout
  logoutSection: { marginBottom: 20 },
  logoutButton: {
    borderRadius: 16,
    overflow: 'hidden',
  },
  logoutGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 18,
    gap: 12,
  },
  logoutText: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.danger,
  },

  // App Info
  appInfoSection: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  appInfoTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textMuted,
    marginBottom: 4,
  },
  appInfoText: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
});