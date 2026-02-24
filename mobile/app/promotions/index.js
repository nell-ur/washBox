import React, { useState, useEffect } from 'react';
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
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { API_BASE_URL } from '../../constants/config';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

const COLORS = {
  background: '#0A0E27',
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
  border: '#1E293B',
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
  gradientSecondary: ['#8B5CF6', '#EC4899'],
  gradientWarning: ['#F59E0B', '#EF4444'],
};

export default function PromotionsScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [promotions, setPromotions] = useState([]);
  const [filter, setFilter] = useState('all'); // all, active, featured
  const [fadeAnim] = useState(new Animated.Value(0));

  useEffect(() => {
    fetchPromotions();
  }, []);

  useEffect(() => {
    if (!loading) {
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }).start();
    }
  }, [loading]);

  const fetchPromotions = async () => {
    try {
      setLoading(true);
      
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
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchPromotions();
    setRefreshing(false);
  };

  const getFilteredPromotions = () => {
    switch (filter) {
      case 'active':
        return promotions.filter(p => p.is_active);
      case 'featured':
        return promotions.filter(p => p.is_featured);
      default:
        return promotions;
    }
  };

  const filteredPromotions = getFilteredPromotions();

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading promotions...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />

      {/* Header */}
      <LinearGradient
        colors={['rgba(14, 165, 233, 0.15)', 'rgba(59, 130, 246, 0.05)']}
        style={styles.header}
      >
        <View style={styles.headerContent}>
          <TouchableOpacity 
            style={styles.backButton}
            onPress={() => router.back()}
          >
            <Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} />
          </TouchableOpacity>
          
          <View style={styles.headerTextContainer}>
            <Text style={styles.headerTitle}>Special Offers</Text>
            <Text style={styles.headerSubtitle}>
              {filteredPromotions.length} {filteredPromotions.length === 1 ? 'offer' : 'offers'} available
            </Text>
          </View>

          <View style={styles.headerSpacer} />
        </View>
      </LinearGradient>

      <ScrollView
        style={styles.content}
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
        <Animated.View style={{ opacity: fadeAnim }}>
          {/* Filters */}
          <View style={styles.filtersContainer}>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.filtersScroll}
            >
              <TouchableOpacity
                style={[styles.filterChip, filter === 'all' && styles.filterChipActive]}
                onPress={() => setFilter('all')}
              >
                <Text style={[styles.filterText, filter === 'all' && styles.filterTextActive]}>
                  All Offers
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.filterChip, filter === 'active' && styles.filterChipActive]}
                onPress={() => setFilter('active')}
              >
                <Text style={[styles.filterText, filter === 'active' && styles.filterTextActive]}>
                  Active
                </Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.filterChip, filter === 'featured' && styles.filterChipActive]}
                onPress={() => setFilter('featured')}
              >
                <Ionicons 
                  name="star" 
                  size={14} 
                  color={filter === 'featured' ? COLORS.textPrimary : COLORS.textSecondary} 
                  style={{ marginRight: 4 }}
                />
                <Text style={[styles.filterText, filter === 'featured' && styles.filterTextActive]}>
                  Featured
                </Text>
              </TouchableOpacity>
            </ScrollView>
          </View>

          {/* Promotions Grid */}
          {filteredPromotions.length > 0 ? (
            <View style={styles.promotionsGrid}>
              {filteredPromotions.map((promo, index) => (
                <TouchableOpacity
                  key={promo.id}
                  style={styles.promoCard}
                  activeOpacity={0.9}
                  onPress={() => {
                    // Navigate to promotion details if needed
                    // router.push(`/promotions/${promo.id}`);
                  }}
                >
                  <LinearGradient
                    colors={index % 2 === 0 ? COLORS.gradientPrimary : COLORS.gradientSecondary}
                    style={styles.promoGradient}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                  >
                    {/* Badges */}
                    <View style={styles.promoBadges}>
                      {promo.is_featured && (
                        <View style={styles.featuredBadge}>
                          <Ionicons name="star" size={10} color={COLORS.warning} />
                          <Text style={styles.featuredBadgeText}>FEATURED</Text>
                        </View>
                      )}
                      {promo.is_active && (
                        <View style={styles.activeBadge}>
                          <View style={styles.activeDot} />
                          <Text style={styles.activeBadgeText}>ACTIVE</Text>
                        </View>
                      )}
                    </View>

                    {/* Content */}
                    <View style={styles.promoContent}>
                      <Text style={styles.promoTitle} numberOfLines={2}>
                        {promo.poster_title || promo.name}
                      </Text>
                      
                      <Text style={styles.promoSubtitle} numberOfLines={2}>
                        {promo.poster_subtitle || promo.description}
                      </Text>

                      {/* Price */}
                      {promo.display_price && (
                        <View style={styles.promoPriceContainer}>
                          <Text style={styles.promoPrice}>₱{promo.display_price}</Text>
                          {promo.original_price && (
                            <Text style={styles.promoOriginalPrice}>
                              ₱{promo.original_price}
                            </Text>
                          )}
                        </View>
                      )}

                      {/* Features */}
                      {promo.poster_features && promo.poster_features.length > 0 && (
                        <View style={styles.promoFeatures}>
                          {promo.poster_features.slice(0, 2).map((feature, idx) => (
                            <View key={idx} style={styles.featureRow}>
                              <Ionicons 
                                name="checkmark-circle" 
                                size={12} 
                                color="rgba(255,255,255,0.9)" 
                              />
                              <Text style={styles.featureText} numberOfLines={1}>
                                {feature}
                              </Text>
                            </View>
                          ))}
                        </View>
                      )}

                      {/* Valid Until */}
                      {promo.valid_until && (
                        <View style={styles.validityContainer}>
                          <Ionicons name="time-outline" size={12} color="rgba(255,255,255,0.7)" />
                          <Text style={styles.validityText}>
                            Valid until {new Date(promo.valid_until).toLocaleDateString()}
                          </Text>
                        </View>
                      )}
                    </View>

                    {/* Arrow */}
                    <View style={styles.promoArrow}>
                      <Ionicons name="arrow-forward" size={18} color="rgba(255,255,255,0.8)" />
                    </View>
                  </LinearGradient>
                </TouchableOpacity>
              ))}
            </View>
          ) : (
            // Empty State
            <View style={styles.emptyStateContainer}>
              <LinearGradient
                colors={['rgba(14, 165, 233, 0.1)', 'rgba(59, 130, 246, 0.05)']}
                style={styles.emptyStateCard}
              >
                <View style={styles.emptyStateIcon}>
                  <Ionicons name="gift-outline" size={48} color={COLORS.primary} />
                </View>
                <Text style={styles.emptyStateTitle}>No Offers Found</Text>
                <Text style={styles.emptyStateText}>
                  {filter === 'all' 
                    ? 'Check back later for exciting promotions!' 
                    : `No ${filter} offers available at the moment.`}
                </Text>
                {filter !== 'all' && (
                  <TouchableOpacity
                    style={styles.emptyStateButton}
                    onPress={() => setFilter('all')}
                  >
                    <Text style={styles.emptyStateButtonText}>View All Offers</Text>
                  </TouchableOpacity>
                )}
              </LinearGradient>
            </View>
          )}

          {/* Bottom Spacing */}
          <View style={{ height: 40 }} />
        </Animated.View>
      </ScrollView>
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
    paddingTop: Platform.OS === 'ios' ? 60 : 50,
    paddingBottom: 20,
    paddingHorizontal: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  backButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTextContainer: {
    flex: 1,
    marginLeft: 16,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  headerSubtitle: {
    fontSize: 13,
    color: COLORS.textSecondary,
    fontWeight: '500',
  },
  headerSpacer: {
    width: 44,
  },

  // Content
  content: {
    flex: 1,
  },
  scrollContent: {
    paddingTop: 20,
  },

  // Filters
  filtersContainer: {
    marginBottom: 24,
  },
  filtersScroll: {
    paddingHorizontal: 20,
    gap: 12,
  },
  filterChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: COLORS.cardDark,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  filterChipActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  filterText: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textSecondary,
  },
  filterTextActive: {
    color: COLORS.textPrimary,
  },

  // Promotions Grid
  promotionsGrid: {
    paddingHorizontal: 20,
    gap: 16,
  },
  promoCard: {
    borderRadius: 20,
    overflow: 'hidden',
    marginBottom: 4,
    elevation: 6,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
  },
  promoGradient: {
    padding: 20,
    minHeight: 200,
  },
  promoBadges: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 16,
  },
  featuredBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(245, 158, 11, 0.2)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 12,
    gap: 4,
  },
  featuredBadgeText: {
    color: COLORS.warning,
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  activeBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(16, 185, 129, 0.2)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 12,
    gap: 4,
  },
  activeDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: COLORS.success,
  },
  activeBadgeText: {
    color: COLORS.success,
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  promoContent: {
    flex: 1,
  },
  promoTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 8,
    lineHeight: 30,
  },
  promoSubtitle: {
    fontSize: 14,
    color: 'rgba(255, 255, 255, 0.9)',
    marginBottom: 16,
    lineHeight: 20,
  },
  promoPriceContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 12,
  },
  promoPrice: {
    fontSize: 32,
    fontWeight: '800',
    color: COLORS.textPrimary,
  },
  promoOriginalPrice: {
    fontSize: 18,
    color: 'rgba(255, 255, 255, 0.6)',
    textDecorationLine: 'line-through',
  },
  promoFeatures: {
    gap: 8,
    marginBottom: 12,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  featureText: {
    flex: 1,
    color: 'rgba(255, 255, 255, 0.9)',
    fontSize: 12,
    fontWeight: '500',
  },
  validityContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 8,
  },
  validityText: {
    fontSize: 11,
    color: 'rgba(255, 255, 255, 0.7)',
    fontWeight: '500',
  },
  promoArrow: {
    alignSelf: 'flex-end',
    marginTop: 12,
  },

  // Empty State
  emptyStateContainer: {
    paddingHorizontal: 20,
  },
  emptyStateCard: {
    borderRadius: 20,
    padding: 40,
    alignItems: 'center',
  },
  emptyStateIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: 'rgba(14, 165, 233, 0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  emptyStateTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 8,
  },
  emptyStateText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: 24,
  },
  emptyStateButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  emptyStateButtonText: {
    color: COLORS.textPrimary,
    fontSize: 14,
    fontWeight: '700',
  },
});