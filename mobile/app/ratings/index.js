import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Modal,
  Animated,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

const COLORS = {
  background: '#0A0E27',
  cardDark: '#1C2340',
  cardLight: '#252D4C',
  primary: '#0EA5E9',
  primaryDark: '#0284C7',
  primaryLight: '#38BDF8',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  success: '#10B981',
  warning: '#F59E0B',
  danger: '#EF4444',
  purple: '#8B5CF6',
  border: '#1E293B',
  star: '#F59E0B',
  starEmpty: '#334155',
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
};

export default function RatingsScreen() {
  const params = useLocalSearchParams();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeTab, setActiveTab] = useState('my_ratings');
  const [ratings, setRatings] = useState([]);
  const [unratedLaundries, setUnratedLaundries] = useState([]);
  const [branches, setBranches] = useState([]);
  const [fadeAnim] = useState(new Animated.Value(0));

  // Rating Modal State
  const [ratingModalVisible, setRatingModalVisible] = useState(false);
  const [ratingType, setRatingType] = useState('laundry'); // 'laundry' or 'branch'
  const [selectedLaundry, setSelectedLaundry] = useState(null);
  const [selectedBranch, setSelectedBranch] = useState(null);
  const [selectedRating, setSelectedRating] = useState(0);
  const [ratingComment, setRatingComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  // Stats
  const [ratingStats, setRatingStats] = useState({
    averageRating: 0,
    totalRatings: 0,
    laundryRatings: 0,
    branchRatings: 0,
    distribution: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 },
  });

  useEffect(() => {
    // Check for tab parameter
    if (params.tab === 'rate_branch') {
      setActiveTab('rate_branch');
    }
    fetchData();
  }, [params.tab]);

  useEffect(() => {
    if (!loading) {
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 500,
        useNativeDriver: true,
      }).start();
    }
  }, [loading]);

  const fetchData = async () => {
    try {
      setLoading(true);
      await Promise.all([
        fetchMyRatings(),
        fetchUnratedLaundries(),
        fetchBranches(),
      ]);
    } catch (error) {
      console.error('Error fetching ratings data:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const fetchMyRatings = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/customer/ratings`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setRatings(data.data.ratings || []);
          if (data.data.stats) {
            setRatingStats(data.data.stats);
          }
        }
      } else if (response.status === 401) {
        await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);
        router.replace('/(auth)/login');
      }
    } catch (error) {
      console.error('Error fetching ratings:', error);
    }
  };

  const fetchUnratedLaundries = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;

      const response = await fetch(`${API_BASE_URL}/v1/customer/unrated-laundries`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setUnratedLaundries(data.data.laundries || []);
        }
      }
    } catch (error) {
      console.error('Error fetching unrated laundries:', error);
    }
  };

  const fetchBranches = async () => {
    try {
      console.log('Fetching branches...');
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) {
        console.log('No token found');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/customer/branches`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      console.log('Branches response status:', response.status);

      if (response.ok) {
        const data = await response.json();
        console.log('Branches data:', data);
        if (data.success) {
          // Get branches array from the response
          const branchesData = data.data?.branches || [];
          console.log('Branches loaded:', branchesData);
          setBranches(branchesData);
        }
      }
    } catch (error) {
      console.error('Error fetching branches:', error);
    }
  };

  const submitLaundryRating = async () => {
    if (selectedRating === 0) {
      Alert.alert('Rating Required', 'Please select a star rating.');
      return;
    }

    try {
      setSubmitting(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);

      const response = await fetch(`${API_BASE_URL}/v1/customer/ratings`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          laundry_id: selectedLaundry.id,
          rating: selectedRating,
          comment: ratingComment.trim() || null,
        }),
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Thank You!', 'Your rating has been submitted successfully.');
        closeRatingModal();
        await fetchData();
      } else {
        Alert.alert('Error', data.message || 'Failed to submit rating.');
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to submit rating. Please check your connection.');
    } finally {
      setSubmitting(false);
    }
  };

  const submitBranchRating = async () => {
    if (selectedRating === 0) {
      Alert.alert('Rating Required', 'Please select a star rating.');
      return;
    }

    if (!selectedBranch) {
      Alert.alert('Branch Required', 'Please select a branch.');
      return;
    }

    try {
      setSubmitting(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);

      const response = await fetch(`${API_BASE_URL}/v1/customer/branch-ratings`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          branch_id: selectedBranch.id,
          rating: selectedRating,
          comment: ratingComment.trim() || null,
        }),
      });

      const data = await response.json();

      if (data.success) {
        Alert.alert('Thank You!', 'Your branch rating has been submitted successfully.');
        closeRatingModal();
        // Only refresh ratings + branches — intentionally NOT calling fetchUnratedLaundries()
        // so the "To Rate" list stays intact after a branch rating is submitted.
        await Promise.all([fetchMyRatings(), fetchBranches()]);
      } else {
        Alert.alert('Error', data.message || 'Failed to submit branch rating.');
      }
    } catch (error) {
      Alert.alert('Error', 'Failed to submit rating. Please check your connection.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleSubmitRating = () => {
    if (ratingType === 'laundry') {
      submitLaundryRating();
    } else {
      submitBranchRating();
    }
  };

  const openLaundryRatingModal = (laundry) => {
    setRatingType('laundry');
    setSelectedLaundry(laundry);
    setSelectedBranch(null);
    setSelectedRating(0);
    setRatingComment('');
    setRatingModalVisible(true);
  };

  const openBranchRatingModal = () => {
    setRatingType('branch');
    setSelectedLaundry(null);
    setSelectedBranch(null);
    setSelectedRating(0);
    setRatingComment('');
    setRatingModalVisible(true);
    // Refresh branches when opening the modal
    fetchBranches();
  };

  const closeRatingModal = () => {
    setRatingModalVisible(false);
    setSelectedLaundry(null);
    setSelectedBranch(null);
    setSelectedRating(0);
    setRatingComment('');
    setRatingType('laundry');
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const formatPrice = (price) => {
    if (!price || isNaN(price)) return '₱0.00';
    return `₱${parseFloat(price).toFixed(2)}`;
  };

  // Resolves the correct order identifier regardless of which field the API returns
  const getOrderNumber = (laundry) => {
    const raw =
      laundry.tracking_number ||   // preferred
      laundry.laundry_number  ||   // some API versions
      laundry.order_number    ||   // fallback
      laundry.id;                   // last resort (numeric id)
    // Always prefix with "Order #" so it's clear what the number is
    const str = String(raw);
    return str.startsWith('#') || str.toLowerCase().startsWith('order')
      ? str
      : `Order #${str}`;
  };

  const getRatingLabel = (rating) => {
    const labels = {
      1: 'Poor',
      2: 'Fair',
      3: 'Good',
      4: 'Very Good',
      5: 'Excellent',
    };
    return labels[rating] || '';
  };

  const getRatingColor = (rating) => {
    if (rating >= 4) return COLORS.success;
    if (rating >= 3) return COLORS.warning;
    return COLORS.danger;
  };

  // Star Rating Component
  const StarRating = ({ rating, size = 20, interactive = false, onRate = null }) => (
    <View style={styles.starRow}>
      {[1, 2, 3, 4, 5].map((star) => (
        <TouchableOpacity
          key={star}
          disabled={!interactive}
          onPress={() => interactive && onRate && onRate(star)}
          activeOpacity={interactive ? 0.7 : 1}
          style={interactive ? styles.starTouchable : null}
        >
          <Ionicons
            name={star <= rating ? 'star' : 'star-outline'}
            size={size}
            color={star <= rating ? COLORS.star : COLORS.starEmpty}
          />
        </TouchableOpacity>
      ))}
    </View>
  );

  const tabs = [
    { id: 'my_ratings', label: 'My Ratings', count: ratings.length },
    { id: 'to_rate', label: 'To Rate', count: unratedLaundries.length },
    { id: 'rate_branch', label: 'Rate Branch', count: 0 },
  ];

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading ratings...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <View style={styles.headerCenter}>
          <Text style={styles.headerTitle}>Ratings</Text>
          <Text style={styles.headerSubtitle}>Share your feedback</Text>
        </View>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        style={styles.scrollView}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
          />
        }
      >
        <Animated.View style={{ opacity: fadeAnim }}>
          {/* Rating Summary Card */}
          <View style={styles.summaryCard}>
            <LinearGradient
              colors={['#1C2340', '#252D4C']}
              style={styles.summaryGradient}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <View style={styles.summaryStats}>
                <View style={styles.summaryMain}>
                  <Text style={styles.summaryScore}>
                    {(ratingStats.averageRating || 0).toFixed(1)}
                  </Text>
                  <StarRating rating={Math.round(ratingStats.averageRating || 0)} size={16} />
                  <Text style={styles.summaryTotal}>
                    {ratingStats.totalRatings} total ratings
                  </Text>
                </View>
                <View style={styles.summaryBreakdown}>
                  <View style={styles.summaryItem}>
                    <Ionicons name="shirt" size={16} color={COLORS.primary} />
                    <Text style={styles.summaryItemText}>
                      {ratingStats.laundryRatings} Laundry
                    </Text>
                  </View>
                  <View style={styles.summaryItem}>
                    <Ionicons name="business" size={16} color={COLORS.success} />
                    <Text style={styles.summaryItemText}>
                      {ratingStats.branchRatings} Branches
                    </Text>
                  </View>
                </View>
              </View>

              <View style={styles.summaryBars}>
                {[5, 4, 3, 2, 1].map((star) => {
                  const count = ratingStats.distribution?.[star] || 0;
                  const total = ratingStats.totalRatings || 1;
                  const percentage = (count / total) * 100;
                  return (
                    <View key={star} style={styles.barRow}>
                      <Text style={styles.barLabel}>{star}</Text>
                      <Ionicons name="star" size={12} color={COLORS.star} />
                      <View style={styles.barTrack}>
                        <View
                          style={[
                            styles.barFill,
                            { width: `${percentage}%`, backgroundColor: getRatingColor(star) },
                          ]}
                        />
                      </View>
                      <Text style={styles.barCount}>{count}</Text>
                    </View>
                  );
                })}
              </View>
            </LinearGradient>
          </View>

          {/* Tabs */}
          <View style={styles.tabsContainer}>
            {tabs.map((tab) => (
              <TouchableOpacity
                key={tab.id}
                style={[styles.tab, activeTab === tab.id && styles.tabActive]}
                onPress={() => setActiveTab(tab.id)}
              >
                <Text style={[styles.tabText, activeTab === tab.id && styles.tabTextActive]}>
                  {tab.label}
                </Text>
                {tab.count > 0 && (
                  <View style={[styles.tabBadge, activeTab === tab.id && styles.tabBadgeActive]}>
                    <Text style={[styles.tabBadgeText, activeTab === tab.id && styles.tabBadgeTextActive]}>
                      {tab.count}
                    </Text>
                  </View>
                )}
              </TouchableOpacity>
            ))}
          </View>

          {/* Content */}
          {activeTab === 'my_ratings' && (
            <View style={styles.listContainer}>
              {ratings.length === 0 ? (
                <View style={styles.emptyState}>
                  <Ionicons name="star-outline" size={64} color={COLORS.textMuted} />
                  <Text style={styles.emptyTitle}>No Ratings Yet</Text>
                  <Text style={styles.emptyText}>
                    Your ratings will appear here after you rate your laundries or branches
                  </Text>
                </View>
              ) : (
                ratings.map((rating) => (
                  <TouchableOpacity 
                    key={rating.id} 
                    style={styles.ratingCard}
                    activeOpacity={0.8}
                  >
                    <View style={styles.ratingCardTop}>
                      <View style={[
                        styles.ratingIconContainer,
                        { backgroundColor: rating.type === 'branch' ? COLORS.success + '20' : COLORS.primary + '20' }
                      ]}>
                        <Ionicons 
                          name={rating.type === 'branch' ? 'business' : 'shirt'} 
                          size={24} 
                          color={rating.type === 'branch' ? COLORS.success : COLORS.primary} 
                        />
                      </View>
                      <View style={styles.ratingCardInfo}>
                        <Text style={styles.ratingTrackingNumber}>
                          {rating.type === 'branch'
                            ? (rating.branch_name || 'Branch')
                            : (rating.tracking_number
                                ? `Order #${rating.tracking_number}`
                                : `Order #${rating.laundry_id || rating.id}`)}
                        </Text>
                        <Text style={styles.ratingServiceName}>
                          {rating.type === 'branch' ? 'Branch Rating' : rating.service_name}
                        </Text>
                        <View style={styles.ratingMetaRow}>
                          <Ionicons name="calendar-outline" size={12} color={COLORS.textMuted} />
                          <Text style={styles.ratingMetaText}>{formatDate(rating.created_at)}</Text>
                        </View>
                      </View>
                    </View>

                    <View style={styles.ratingStarSection}>
                      <View style={styles.ratingStarContainer}>
                        <StarRating rating={rating.rating} size={18} />
                        <View style={[styles.ratingLabelBadge, { backgroundColor: getRatingColor(rating.rating) + '20' }]}>
                          <Text style={[styles.ratingLabelText, { color: getRatingColor(rating.rating) }]}>
                            {getRatingLabel(rating.rating)}
                          </Text>
                        </View>
                      </View>
                    </View>

                    {rating.comment && (
                      <View style={styles.ratingCommentBox}>
                        <Ionicons name="chatbubble-outline" size={14} color={COLORS.textMuted} />
                        <Text style={styles.ratingComment}>{rating.comment}</Text>
                      </View>
                    )}
                  </TouchableOpacity>
                ))
              )}
            </View>
          )}

          {activeTab === 'to_rate' && (
            <View style={styles.listContainer}>
              {unratedLaundries.length === 0 ? (
                <View style={styles.emptyState}>
                  <Ionicons name="checkmark-done-circle-outline" size={64} color={COLORS.success} />
                  <Text style={styles.emptyTitle}>All Caught Up!</Text>
                  <Text style={styles.emptyText}>
                    You've rated all your completed laundries. Thank you for your feedback!
                  </Text>
                </View>
              ) : (
                unratedLaundries.map((laundry) => (
                  <View key={laundry.id} style={styles.unratedCard}>
                    <View style={styles.unratedInfo}>
                      <View style={styles.unratedIconContainer}>
                        <Ionicons name="shirt" size={24} color={COLORS.primary} />
                      </View>
                      <View style={styles.unratedDetails}>
                        <Text style={styles.unratedTracking}>
                          {getOrderNumber(laundry)}
                        </Text>
                        <Text style={styles.unratedService}>
                          {laundry.service_name} • {laundry.branch_name}
                        </Text>
                        <Text style={styles.unratedMeta}>
                          {formatDate(laundry.completed_at || laundry.created_at)} • {formatPrice(laundry.total_amount)}
                        </Text>
                      </View>
                    </View>
                    <TouchableOpacity
                      style={styles.rateButton}
                      onPress={() => openLaundryRatingModal(laundry)}
                      activeOpacity={0.8}
                    >
                      <LinearGradient
                        colors={COLORS.gradientPrimary}
                        style={styles.rateButtonGradient}
                        start={{ x: 0, y: 0 }}
                        end={{ x: 1, y: 0 }}
                      >
                        <Ionicons name="star" size={16} color="#FFF" />
                        <Text style={styles.rateButtonText}>Rate</Text>
                      </LinearGradient>
                    </TouchableOpacity>
                  </View>
                ))
              )}
            </View>
          )}

          {activeTab === 'rate_branch' && (
            <View style={styles.listContainer}>
              <View style={styles.rateBranchCard}>
                <LinearGradient
                  colors={['#1C2340', '#252D4C']}
                  style={styles.rateBranchGradient}
                >
                  <View style={styles.rateBranchIconContainer}>
                    <Ionicons name="business" size={48} color={COLORS.primary} />
                  </View>
                  <Text style={styles.rateBranchTitle}>Rate a Branch</Text>
                  <Text style={styles.rateBranchDescription}>
                    Share your feedback about our branches to help us improve our service quality
                  </Text>
                  
                  <TouchableOpacity
                    style={styles.startRatingButton}
                    onPress={openBranchRatingModal}
                    activeOpacity={0.8}
                  >
                    <LinearGradient
                      colors={COLORS.gradientPrimary}
                      style={styles.startRatingGradient}
                    >
                      <Ionicons name="star" size={20} color="#FFF" />
                      <Text style={styles.startRatingText}>Start Rating</Text>
                    </LinearGradient>
                  </TouchableOpacity>
                </LinearGradient>
              </View>

              {ratings.filter(r => r.type === 'branch').length > 0 && (
                <View style={styles.recentBranchRatings}>
                  <Text style={styles.recentBranchTitle}>Your Branch Ratings</Text>
                  {ratings
                    .filter(r => r.type === 'branch')
                    .slice(0, 3)
                    .map((rating) => (
                      <View key={rating.id} style={styles.recentBranchItem}>
                        <View style={styles.recentBranchInfo}>
                          <Ionicons name="business" size={20} color={COLORS.primary} />
                          <Text style={styles.recentBranchName}>
                            {rating.branch_name || 'Branch'}
                          </Text>
                        </View>
                        <View style={styles.recentBranchRating}>
                          <StarRating rating={rating.rating} size={14} />
                          <Text style={styles.recentBranchDate}>
                            {formatDate(rating.created_at)}
                          </Text>
                        </View>
                      </View>
                    ))}
                </View>
              )}
            </View>
          )}

          <View style={{ height: 40 }} />
        </Animated.View>
      </ScrollView>

      {/* Rating Modal */}
      <Modal
        visible={ratingModalVisible}
        transparent
        animationType="slide"
        onRequestClose={closeRatingModal}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHandle} />
            
            <ScrollView 
              showsVerticalScrollIndicator={false}
              contentContainerStyle={styles.modalScrollContent}
            >
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>
                  {ratingType === 'laundry' ? 'Rate Your Laundry' : 'Rate a Branch'}
                </Text>
                <TouchableOpacity onPress={closeRatingModal}>
                  <Ionicons name="close" size={24} color={COLORS.textSecondary} />
                </TouchableOpacity>
              </View>

              {/* Rating Type Toggle */}
              <View style={styles.ratingTypeToggle}>
                <TouchableOpacity
                  style={[
                    styles.ratingTypeButton,
                    ratingType === 'laundry' && styles.ratingTypeButtonActive,
                  ]}
                  onPress={() => setRatingType('laundry')}
                >
                  <Ionicons 
                    name="shirt" 
                    size={16} 
                    color={ratingType === 'laundry' ? COLORS.primary : COLORS.textMuted} 
                  />
                  <Text style={[
                    styles.ratingTypeText,
                    ratingType === 'laundry' && styles.ratingTypeTextActive,
                  ]}>
                    Rate Laundry
                  </Text>
                </TouchableOpacity>
                
                <TouchableOpacity
                  style={[
                    styles.ratingTypeButton,
                    ratingType === 'branch' && styles.ratingTypeButtonActive,
                  ]}
                  onPress={() => setRatingType('branch')}
                >
                  <Ionicons 
                    name="business" 
                    size={16} 
                    color={ratingType === 'branch' ? COLORS.primary : COLORS.textMuted} 
                  />
                  <Text style={[
                    styles.ratingTypeText,
                    ratingType === 'branch' && styles.ratingTypeTextActive,
                  ]}>
                    Rate Branch
                  </Text>
                </TouchableOpacity>
              </View>

              {/* Laundry Info */}
              {ratingType === 'laundry' && selectedLaundry && (
                <View style={styles.modalLaundryInfo}>
                  <View style={styles.modalLaundryIcon}>
                    <Ionicons name="shirt" size={28} color={COLORS.primary} />
                  </View>
                  <View>
                    <Text style={styles.modalLaundryTracking}>
                      {getOrderNumber(selectedLaundry)}
                    </Text>
                    <Text style={styles.modalLaundryService}>
                      {selectedLaundry.service_name} • {selectedLaundry.branch_name}
                    </Text>
                  </View>
                </View>
              )}

              {/* Branch Selector */}
              {ratingType === 'branch' && (
                <View style={styles.branchSelectorContainer}>
                  <Text style={styles.branchSelectorLabel}>Select Branch</Text>
                  {branches.length === 0 ? (
                    <View style={styles.noBranchesContainer}>
                      <ActivityIndicator size="small" color={COLORS.primary} />
                      <Text style={styles.noBranchesText}>Loading branches...</Text>
                      <TouchableOpacity 
                        onPress={fetchBranches}
                        style={{ marginTop: 10 }}
                      >
                        <Text style={{ color: COLORS.primary }}>Tap to retry</Text>
                      </TouchableOpacity>
                    </View>
                  ) : (
                    <ScrollView 
                      horizontal 
                      showsHorizontalScrollIndicator={false}
                      style={styles.branchList}
                    >
                      {branches.map((branch) => {
                        // Use the correct field names from the API response
                        const branchName = branch.name || branch.full_name || 'Branch';
                        const branchCode = branch.code || '';
                        const branchAddress = branch.address || '';
                        
                        return (
                          <TouchableOpacity
                            key={branch.id}
                            style={[
                              styles.branchCard,
                              selectedBranch?.id === branch.id && styles.branchCardSelected,
                            ]}
                            onPress={() => setSelectedBranch(branch)}
                            activeOpacity={0.7}
                          >
                            <LinearGradient
                              colors={selectedBranch?.id === branch.id 
                                ? COLORS.gradientPrimary 
                                : ['#1C2340', '#252D4C']}
                              style={styles.branchGradient}
                              start={{ x: 0, y: 0 }}
                              end={{ x: 1, y: 1 }}
                            >
                              <Ionicons 
                                name="business" 
                                size={28} 
                                color={selectedBranch?.id === branch.id ? '#FFF' : COLORS.primary} 
                              />
                              <Text style={[
                                styles.branchName,
                                selectedBranch?.id === branch.id && styles.branchNameSelected,
                              ]} numberOfLines={2}>
                                {branchName}
                              </Text>
                              {branchCode ? (
                                <Text style={[
                                  styles.branchCode,
                                  selectedBranch?.id === branch.id && styles.branchCodeSelected,
                                ]}>
                                  {branchCode}
                                </Text>
                              ) : (
                                <Text style={[
                                  styles.branchAddress,
                                  selectedBranch?.id === branch.id && styles.branchAddressSelected,
                                ]} numberOfLines={1}>
                                  {branchAddress}
                                </Text>
                              )}
                            </LinearGradient>
                          </TouchableOpacity>
                        );
                      })}
                    </ScrollView>
                  )}
                </View>
              )}

              {/* Star Rating */}
              <View style={styles.modalRatingSection}>
                <Text style={styles.modalRatingPrompt}>
                  {ratingType === 'laundry' 
                    ? 'How was the laundry service?' 
                    : 'How would you rate this branch?'}
                </Text>
                <StarRating
                  rating={selectedRating}
                  size={44}
                  interactive
                  onRate={setSelectedRating}
                />
                {selectedRating > 0 && (
                  <Text style={[styles.modalRatingLabel, { color: getRatingColor(selectedRating) }]}>
                    {getRatingLabel(selectedRating)}
                  </Text>
                )}
              </View>

              {/* Comment Input */}
              <View style={styles.modalCommentSection}>
                <Text style={styles.modalCommentLabel}>
                  {ratingType === 'laundry' 
                    ? 'Leave a comment about the service (optional)' 
                    : 'Leave a comment about the branch (optional)'}
                </Text>
                <TextInput
                  style={styles.modalCommentInput}
                  placeholder="Tell us about your experience..."
                  placeholderTextColor={COLORS.textMuted}
                  value={ratingComment}
                  onChangeText={setRatingComment}
                  multiline
                  numberOfLines={4}
                  textAlignVertical="top"
                  maxLength={500}
                />
                <Text style={styles.modalCharCount}>{ratingComment.length}/500</Text>
              </View>

              {/* Submit Button */}
              <TouchableOpacity
                style={[
                  styles.modalSubmitButton,
                  (selectedRating === 0 || (ratingType === 'branch' && !selectedBranch)) 
                    && styles.modalSubmitDisabled
                ]}
                onPress={handleSubmitRating}
                disabled={
                  submitting || 
                  selectedRating === 0 || 
                  (ratingType === 'branch' && !selectedBranch)
                }
                activeOpacity={0.8}
              >
                {submitting ? (
                  <ActivityIndicator color="#FFF" />
                ) : (
                  <>
                    <Ionicons name="checkmark-circle" size={22} color="#FFF" />
                    <Text style={styles.modalSubmitText}>Submit Rating</Text>
                  </>
                )}
              </TouchableOpacity>
              
              {/* Extra bottom padding */}
              <View style={{ height: Platform.OS === 'ios' ? 20 : 10 }} />
            </ScrollView>
          </View>
        </View>
      </Modal>
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
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 60 : 48,
    paddingBottom: 20,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerCenter: {
    flex: 1,
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  headerSubtitle: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginTop: 2,
  },
  scrollView: {
    flex: 1,
  },
  summaryCard: {
    margin: 20,
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  summaryGradient: {
    padding: 20,
  },
  summaryStats: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  summaryMain: {
    alignItems: 'center',
  },
  summaryScore: {
    fontSize: 36,
    fontWeight: '800',
    color: COLORS.textPrimary,
  },
  summaryTotal: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginTop: 4,
  },
  summaryBreakdown: {
    gap: 8,
  },
  summaryItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  summaryItemText: {
    fontSize: 13,
    color: COLORS.textSecondary,
  },
  summaryBars: {
    gap: 6,
  },
  barRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  barLabel: {
    fontSize: 12,
    color: COLORS.textSecondary,
    width: 12,
    textAlign: 'right',
  },
  barTrack: {
    flex: 1,
    height: 8,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 4,
    overflow: 'hidden',
  },
  barFill: {
    height: '100%',
    borderRadius: 4,
  },
  barCount: {
    fontSize: 12,
    color: COLORS.textSecondary,
    width: 30,
    textAlign: 'right',
  },
  tabsContainer: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    gap: 12,
    marginBottom: 20,
  },
  tab: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.cardDark,
    paddingVertical: 12,
    borderRadius: 14,
    gap: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  tabActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  tabText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  tabTextActive: {
    color: '#FFF',
  },
  tabBadge: {
    backgroundColor: COLORS.background,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    minWidth: 24,
    alignItems: 'center',
  },
  tabBadgeActive: {
    backgroundColor: 'rgba(255,255,255,0.25)',
  },
  tabBadgeText: {
    color: COLORS.textPrimary,
    fontSize: 12,
    fontWeight: 'bold',
  },
  tabBadgeTextActive: {
    color: '#FFF',
  },
  listContainer: {
    paddingHorizontal: 20,
  },
  ratingCard: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  ratingCardTop: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    marginBottom: 14,
  },
  ratingIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  ratingCardInfo: {
    flex: 1,
  },
  ratingTrackingNumber: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  ratingServiceName: {
    fontSize: 13,
    color: COLORS.textSecondary,
    marginBottom: 4,
  },
  ratingMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  ratingMetaText: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  ratingStarSection: {
    marginBottom: 4,
  },
  ratingStarContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  ratingLabelBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  ratingLabelText: {
    fontSize: 12,
    fontWeight: '700',
  },
  ratingCommentBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  ratingComment: {
    flex: 1,
    fontSize: 13,
    color: COLORS.textSecondary,
    lineHeight: 18,
  },
  unratedCard: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  unratedInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    marginBottom: 14,
  },
  unratedIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
    alignItems: 'center',
  },
  unratedDetails: {
    flex: 1,
  },
  unratedTracking: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  unratedService: {
    fontSize: 13,
    color: COLORS.textSecondary,
    marginBottom: 2,
  },
  unratedMeta: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  rateButton: {
    borderRadius: 12,
    overflow: 'hidden',
  },
  rateButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    gap: 8,
  },
  rateButtonText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#FFF',
  },
  rateBranchCard: {
    borderRadius: 20,
    overflow: 'hidden',
    marginBottom: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  rateBranchGradient: {
    padding: 24,
    alignItems: 'center',
  },
  rateBranchIconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  rateBranchTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: 8,
  },
  rateBranchDescription: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginBottom: 20,
    lineHeight: 20,
  },
  startRatingButton: {
    width: '100%',
    borderRadius: 14,
    overflow: 'hidden',
  },
  startRatingGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    gap: 10,
  },
  startRatingText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#FFF',
  },
  recentBranchRatings: {
    marginTop: 8,
  },
  recentBranchTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textSecondary,
    marginBottom: 12,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  recentBranchItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: COLORS.cardDark,
    padding: 14,
    borderRadius: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  recentBranchInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  recentBranchName: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textPrimary,
  },
  recentBranchRating: {
    alignItems: 'flex-end',
    gap: 4,
  },
  recentBranchDate: {
    fontSize: 10,
    color: COLORS.textMuted,
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
    paddingHorizontal: 40,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
    marginTop: 16,
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  starRow: {
    flexDirection: 'row',
    gap: 4,
  },
  starTouchable: {
    padding: 4,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.7)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: COLORS.cardDark,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    paddingBottom: Platform.OS === 'ios' ? 40 : 24,
    maxHeight: '90%',
  },
  modalScrollContent: {
    paddingBottom: 20,
  },
  modalHandle: {
    width: 40,
    height: 4,
    backgroundColor: COLORS.textMuted,
    borderRadius: 2,
    alignSelf: 'center',
    marginBottom: 20,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 24,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  ratingTypeToggle: {
    flexDirection: 'row',
    backgroundColor: COLORS.cardLight,
    borderRadius: 12,
    padding: 4,
    marginBottom: 20,
  },
  ratingTypeButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 10,
    gap: 8,
  },
  ratingTypeButtonActive: {
    backgroundColor: COLORS.primary + '20',
  },
  ratingTypeText: {
    fontSize: 13,
    color: COLORS.textMuted,
    fontWeight: '600',
  },
  ratingTypeTextActive: {
    color: COLORS.primary,
  },
  modalLaundryInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: COLORS.cardLight,
    borderRadius: 14,
    padding: 14,
    marginBottom: 28,
  },
  modalLaundryIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalLaundryTracking: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  modalLaundryService: {
    fontSize: 12,
    color: COLORS.textSecondary,
  },
  branchSelectorContainer: {
    marginBottom: 24,
  },
  branchSelectorLabel: {
    fontSize: 14,
    color: COLORS.textSecondary,
    fontWeight: '600',
    marginBottom: 12,
  },
  branchList: {
    flexDirection: 'row',
  },
  branchCard: {
    width: 140,
    marginRight: 12,
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  branchCardSelected: {
    borderColor: COLORS.primary,
  },
  branchGradient: {
    padding: 16,
    alignItems: 'center',
    gap: 8,
  },
  branchName: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    textAlign: 'center',
  },
  branchNameSelected: {
    color: '#FFF',
  },
  branchCode: {
    fontSize: 11,
    color: COLORS.textMuted,
  },
  branchCodeSelected: {
    color: 'rgba(255,255,255,0.8)',
  },
  branchAddress: {
    fontSize: 10,
    color: COLORS.textMuted,
    textAlign: 'center',
  },
  branchAddressSelected: {
    color: 'rgba(255,255,255,0.8)',
  },
  noBranchesContainer: {
    height: 160,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.cardLight,
    borderRadius: 16,
    marginTop: 8,
  },
  noBranchesText: {
    marginTop: 12,
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  modalRatingSection: {
    alignItems: 'center',
    marginBottom: 28,
  },
  modalRatingPrompt: {
    fontSize: 16,
    color: COLORS.textSecondary,
    marginBottom: 16,
    fontWeight: '600',
  },
  modalRatingLabel: {
    fontSize: 16,
    fontWeight: '700',
    marginTop: 12,
  },
  modalCommentSection: {
    marginBottom: 24,
  },
  modalCommentLabel: {
    fontSize: 14,
    color: COLORS.textSecondary,
    fontWeight: '600',
    marginBottom: 10,
  },
  modalCommentInput: {
    backgroundColor: COLORS.cardLight,
    borderRadius: 14,
    padding: 16,
    color: COLORS.textPrimary,
    fontSize: 14,
    minHeight: 100,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  modalCharCount: {
    fontSize: 11,
    color: COLORS.textMuted,
    textAlign: 'right',
    marginTop: 6,
  },
  modalSubmitButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primary,
    paddingVertical: 16,
    borderRadius: 16,
    gap: 10,
  },
  modalSubmitDisabled: {
    opacity: 0.5,
  },
  modalSubmitText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#FFF',
  },
});