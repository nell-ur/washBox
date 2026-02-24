import React, { useState, useEffect } from 'react';
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
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

const COLORS = {
  background: '#0A1128',
  cardDark: '#1A2847',
  cardBlue: '#0EA5E9',
  primary: '#0EA5E9',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  success: '#10B981',
  info: '#3B82F6',
  warning: '#F59E0B',
  danger: '#EF4444',
};

export default function LaundriesScreen() {
  const [activeTab, setActiveTab] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  
  // Data from API
  const [laundries, setLaundries] = useState([]);
  const [filteredLaundries, setFilteredLaundries] = useState([]);

  useEffect(() => {
    fetchLaundries();     
  }, []);

  // Filter laundries when tab or search changes
  useEffect(() => {
    filterLaundries();
  }, [activeTab, searchQuery, laundries]);

  const filterLaundries = () => {
    let filtered = [...laundries];

    // Filter by tab
    if (activeTab === 'active') {
      filtered = filtered.filter(laundry => 
        ['received', 'processing', 'ready', 'paid'].includes(laundry.status?.toLowerCase())
      );
    } else if (activeTab === 'completed') {
      filtered = filtered.filter(laundry => 
        laundry.status?.toLowerCase() === 'completed'
      );
    }

    // Filter by search query
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(laundry =>
        laundry.tracking_number?.toLowerCase().includes(query) ||
        laundry.service_name?.toLowerCase().includes(query) ||
        laundry.branch_name?.toLowerCase().includes(query)
      );
    }

    setFilteredLaundries(filtered);
  };

  const fetchLaundries = async () => {
    try {
      setLoading(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      
      if (!token) {
        router.replace('/(auth)/login');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/laundries`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.laundries) {
          setLaundries(data.data.laundries);
        }
      } else if (response.status === 401) {
        // Token expired
        await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);
        router.replace('/(auth)/login');
      }
    } catch (error) {
      console.error('Error fetching laundries:', error);
      Alert.alert('Error', 'Failed to load laundries. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchLaundries();
    setRefreshing(false);
  };

  const getStatusColor = (status) => {
    const statusColors = {
      'received': COLORS.info,
      'processing': COLORS.warning,
      'ready': COLORS.success,
      'paid': COLORS.success,
      'completed': COLORS.textSecondary,
      'cancelled': COLORS.danger,
    };
    return statusColors[status?.toLowerCase()] || COLORS.info;
  };

  const formatPrice = (price) => {
    return `₱${parseFloat(price).toFixed(2)}`;
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    // Reset time for comparison
    today.setHours(0, 0, 0, 0);
    yesterday.setHours(0, 0, 0, 0);
    date.setHours(0, 0, 0, 0);

    if (date.getTime() === today.getTime()) {
      return 'Today';
    } else if (date.getTime() === yesterday.getTime()) {
      return 'Yesterday';
    } else {
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
  };

  const getLaundryTime = (laundry) => {
    const status = laundry.status?.toLowerCase(); // Fixed: changed 'laundries.status' to 'laundry.status'
    
    if (status === 'completed') {
      return 'Completed';
    } else if (status === 'ready') {
      return 'Ready for pickup';
    } else if (status === 'processing') {
      return 'In progress';
    } else if (laundry.estimated_completion) {
      return `Est: ${new Date(laundry.estimated_completion).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    } else {
      return 'Processing';
    }
  };

  // Calculate counts for tabs
  const allCount = laundries.length;
  const activeCount = laundries.filter(laundry => 
    ['received', 'processing', 'ready', 'paid'].includes(laundry.status?.toLowerCase())
  ).length;
  const completedCount = laundries.filter(laundry => 
    laundry.status?.toLowerCase() === 'completed'
  ).length;

  const tabs = [
    { id: 'all', label: 'All', count: allCount },
    { id: 'active', label: 'Active', count: activeCount },
    { id: 'completed', label: 'Completed', count: completedCount },
  ];

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading laundries...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>My Laundries</Text>
        <TouchableOpacity 
          style={styles.filterButton}
          onPress={() => Alert.alert('Filter', 'Filter options coming soon!')}
        >
          <Ionicons name="filter-outline" size={24} color={COLORS.textPrimary} />
        </TouchableOpacity>
      </View>

      {/* Search */}
      <View style={styles.searchSection}>
        <View style={styles.searchContainer}>
          <Ionicons name="search-outline" size={20} color={COLORS.textSecondary} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search laundries..."
            placeholderTextColor={COLORS.textSecondary}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery.length > 0 && (
            <TouchableOpacity onPress={() => setSearchQuery('')}>
              <Ionicons name="close-circle" size={20} color={COLORS.textSecondary} />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* Tabs */}
      <View style={styles.tabsContainer}>
        {tabs.map((tab) => (
          <TouchableOpacity
            key={tab.id}
            style={[
              styles.tab,
              activeTab === tab.id && styles.tabActive,
            ]}
            onPress={() => setActiveTab(tab.id)}
          >
            <Text
              style={[
                styles.tabText,
                activeTab === tab.id && styles.tabTextActive,
              ]}
            >
              {tab.label}
            </Text>
            <View
              style={[
                styles.tabBadge,
                activeTab === tab.id && styles.tabBadgeActive,
              ]}
            >
              <Text
                style={[
                  styles.tabBadgeText,
                  activeTab === tab.id && styles.tabBadgeTextActive,
                ]}
              >
                {tab.count}
              </Text>
            </View>
          </TouchableOpacity>
        ))}
      </View>

      {/* Laundry List */}
      <ScrollView
        style={styles.laundriesList}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={COLORS.primary}
          />
        }
      >
        {filteredLaundries.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="receipt-outline" size={64} color={COLORS.textSecondary} />
            <Text style={styles.emptyStateTitle}>
              {searchQuery ? 'No matching laundries' : 'No laundries yet'}
            </Text>
            <Text style={styles.emptyStateText}>
              {searchQuery 
                ? 'Try a different search term'
                : 'Your laundries will appear here once you place them'
              }
            </Text>
            {!searchQuery && (
              <TouchableOpacity
                style={styles.emptyStateButton}
                onPress={() => router.push('/(tabs)/')}
              >
                <Text style={styles.emptyStateButtonText}>Browse Services</Text>
              </TouchableOpacity>
            )}
          </View>
        ) : (
          filteredLaundries.map((laundry) => (
            <TouchableOpacity
              key={laundry.id}
              style={styles.laundryCard}
              onPress={() => router.push(`/laundries/${laundry.tracking_number || laundry.id}`)}
            >
              <View style={styles.laundryIconContainer}>
                <Ionicons name="shirt" size={28} color={COLORS.primary} />
              </View>
              <View style={styles.laundryInfo}>
                <View style={styles.laundryHeader}>
                  <Text style={styles.laundryNumber}>
                    {laundry.tracking_number || `#${laundry.id}`}
                  </Text>
                  <View
                    style={[
                      styles.statusBadge,
                      { backgroundColor: getStatusColor(laundry.status) + '20' },
                    ]}
                  >
                    <Text
                      style={[
                        styles.statusText,
                        { color: getStatusColor(laundry.status) },
                      ]}
                    >
                      {laundry.status?.toUpperCase()}
                    </Text>
                  </View>
                </View>
                <Text style={styles.laundryService}>
                  {laundry.service_name || 'Laundry Service'} ({laundry.branch_name || 'Branch'})
                </Text>
                <View style={styles.laundryFooter}>
                  <Ionicons name="time-outline" size={14} color={COLORS.textSecondary} />
                  <Text style={styles.laundryTime}>{getLaundryTime(laundry)}</Text>
                  <Text style={styles.laundryPrice}>
                    {formatPrice(laundry.total_amount || 0)}
                  </Text>
                </View>
                <View style={styles.laundryDateContainer}>
                  <Ionicons name="calendar-outline" size={12} color={COLORS.textSecondary} />
                  <Text style={styles.laundryDate}>
                    {formatDate(laundry.created_at)}
                  </Text>
                </View>
              </View>
              <Ionicons name="chevron-forward" size={20} color={COLORS.textSecondary} />
            </TouchableOpacity>
          ))
        )}

        <View style={{ height: 100 }} />
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
    marginTop: 12,
    fontSize: 14,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 20,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
  },
  filterButton: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  searchSection: {
    paddingHorizontal: 20,
    marginBottom: 20,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.cardDark,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    gap: 12,
  },
  searchInput: {
    flex: 1,
    color: COLORS.textPrimary,
    fontSize: 14,
  },
  tabsContainer: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    gap: 12,
    marginBottom: 20,
  },
  tab: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.cardDark,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
    gap: 8,
  },
  tabActive: {
    backgroundColor: COLORS.primary,
  },
  tabText: {
    color: COLORS.textSecondary,
    fontSize: 14,
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
    backgroundColor: 'rgba(255,255,255,0.2)',
  },
  tabBadgeText: {
    color: COLORS.textPrimary,
    fontSize: 12,
    fontWeight: 'bold',
  },
  tabBadgeTextActive: {
    color: '#FFF',
  },
  laundriesList: {
    flex: 1,
    paddingHorizontal: 20,
  },
  laundryCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.cardDark,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    gap: 12,
  },
  laundryIconContainer: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
    alignItems: 'center',
  },
  laundryInfo: {
    flex: 1,
  },
  laundryHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  laundryNumber: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  statusText: {
    fontSize: 11,
    fontWeight: 'bold',
  },
  laundryService: {
    fontSize: 13,
    color: COLORS.textSecondary,
    marginBottom: 8,
  },
  laundryFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 6,
  },
  laundryTime: {
    fontSize: 12,
    color: COLORS.textSecondary,
    flex: 1,
  },
  laundryPrice: {
    fontSize: 14,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
  },
  laundryDateContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  laundryDate: {
    fontSize: 11,
    color: COLORS.textSecondary,
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
    paddingHorizontal: 40,
  },
  emptyStateTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.textPrimary,
    marginTop: 16,
    marginBottom: 8,
  },
  emptyStateText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 20,
  },
  emptyStateButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 12,
  },
  emptyStateButtonText: {
    color: '#FFF',
    fontSize: 14,
    fontWeight: 'bold',
  },
});