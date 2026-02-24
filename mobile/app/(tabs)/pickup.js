import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  Alert,
  ActivityIndicator,
  Platform,
  Modal,
  Dimensions,
  Animated,
  KeyboardAvoidingView,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

// Import map components
import PickupDeliveryMap from '../../components/pickup-delivery/PickupDelivery';
import LocationSearch from '../../components/pickup-delivery/LocationSearch';
import { LocationService } from '../../services/locationService';
import { useLocationStore } from '../../store/locationStore';

const { width, height } = Dimensions.get('window');

// ─────────────────────────────────────────────
// DESIGN SYSTEM
// ─────────────────────────────────────────────
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
  textInverse: '#0F172A',
  border: '#1E293B',
  borderLight: 'rgba(255, 255, 255, 0.06)',
  gradientPickup: ['#10B981', '#059669'],
  gradientPrimary: ['#0EA5E9', '#3B82F6'],
  gradientSurface: ['#0F1332', '#171D45'],
};

// ─────────────────────────────────────────────
// STEP INDICATOR
// ─────────────────────────────────────────────
const StepIndicator = ({ currentStep, steps }) => (
  <View style={stepStyles.container}>
    {steps.map((step, index) => {
      const isActive = index === currentStep;
      const isCompleted = index < currentStep;
      const isLast = index === steps.length - 1;
      return (
        <View key={index} style={stepStyles.stepRow}>
          <View style={[
            stepStyles.dot,
            isActive && stepStyles.dotActive,
            isCompleted && stepStyles.dotCompleted,
          ]}>
            {isCompleted ? (
              <Ionicons name="checkmark" size={12} color="#FFF" />
            ) : (
              <Text style={[
                stepStyles.dotNumber,
                (isActive || isCompleted) && stepStyles.dotNumberActive,
              ]}>
                {index + 1}
              </Text>
            )}
            {isActive && <View style={stepStyles.dotPulse} />}
          </View>
          <Text style={[
            stepStyles.label,
            isActive && stepStyles.labelActive,
            isCompleted && stepStyles.labelCompleted,
          ]}>
            {step}
          </Text>
          {!isLast && (
            <View style={[stepStyles.connector, isCompleted && stepStyles.connectorCompleted]} />
          )}
        </View>
      );
    })}
  </View>
);

const stepStyles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingVertical: 16,
  },
  stepRow: { flexDirection: 'row', alignItems: 'center' },
  dot: {
    width: 26, height: 26, borderRadius: 13,
    backgroundColor: COLORS.surfaceElevated,
    justifyContent: 'center', alignItems: 'center',
    borderWidth: 2, borderColor: COLORS.border,
  },
  dotActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  dotCompleted: { backgroundColor: COLORS.pickup, borderColor: COLORS.pickup },
  dotPulse: {
    position: 'absolute', width: 38, height: 38, borderRadius: 19,
    backgroundColor: 'rgba(14, 165, 233, 0.2)',
  },
  dotNumber: { fontSize: 11, fontWeight: '700', color: COLORS.textMuted },
  dotNumberActive: { color: '#FFF' },
  label: { fontSize: 11, fontWeight: '600', color: COLORS.textMuted, marginLeft: 6, letterSpacing: 0.3 },
  labelActive: { color: COLORS.primary },
  labelCompleted: { color: COLORS.pickup },
  connector: { width: 20, height: 2, backgroundColor: COLORS.border, marginHorizontal: 6, borderRadius: 1 },
  connectorCompleted: { backgroundColor: COLORS.pickup },
});

// ─────────────────────────────────────────────
// PICKUP LOCATION CARD (simplified — no delivery field)
// ─────────────────────────────────────────────
const PickupCard = ({ pickupAddress, pickupCoords, onPickupPress }) => (
  <View style={routeStyles.card}>
    {/* Pickup row */}
    <TouchableOpacity style={routeStyles.locationRow} onPress={onPickupPress} activeOpacity={0.7}>
      <View style={routeStyles.iconColumn}>
        <View style={[routeStyles.iconCircle, { backgroundColor: COLORS.pickupGlow }]}>
          <View style={[routeStyles.iconDot, { backgroundColor: COLORS.pickup }]} />
        </View>
      </View>
      <View style={routeStyles.locationContent}>
        <Text style={routeStyles.locationLabel}>PICKUP FROM</Text>
        {pickupAddress ? (
          <Text style={routeStyles.locationAddress} numberOfLines={2}>{pickupAddress}</Text>
        ) : (
          <Text style={routeStyles.locationPlaceholder}>Tap to set your pickup location</Text>
        )}
        {pickupCoords && (
          <View style={routeStyles.coordRow}>
            <Ionicons name="navigate-outline" size={10} color={COLORS.pickup} />
            <Text style={[routeStyles.coordText, { color: COLORS.pickup }]}>
              {pickupCoords.latitude.toFixed(4)}, {pickupCoords.longitude.toFixed(4)}
            </Text>
          </View>
        )}
      </View>
      <View style={[routeStyles.actionChip, { borderColor: COLORS.pickup + '40' }]}>
        <Ionicons name={pickupAddress ? 'pencil' : 'add'} size={14} color={COLORS.pickup} />
      </View>
    </TouchableOpacity>

    {/* Delivery info banner */}
    <View style={routeStyles.deliveryBanner}>
      <Ionicons name="refresh-outline" size={14} color={COLORS.primary} />
      <Text style={routeStyles.deliveryBannerText}>
        Your laundry will be delivered back to the same address
      </Text>
    </View>
  </View>
);

const routeStyles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.surface,
    borderRadius: 20,
    padding: 20,
    marginHorizontal: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    gap: 16,
  },
  locationRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 14 },
  iconColumn: { alignItems: 'center', width: 32 },
  iconCircle: { width: 32, height: 32, borderRadius: 16, justifyContent: 'center', alignItems: 'center' },
  iconDot: { width: 10, height: 10, borderRadius: 5 },
  locationContent: { flex: 1, paddingBottom: 6 },
  locationLabel: {
    fontSize: 10, fontWeight: '800', color: COLORS.textMuted,
    letterSpacing: 1.2, marginBottom: 4,
  },
  locationAddress: { fontSize: 14, fontWeight: '600', color: COLORS.textPrimary, lineHeight: 20 },
  locationPlaceholder: { fontSize: 14, color: COLORS.textMuted, fontStyle: 'italic' },
  coordRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4 },
  coordText: {
    fontSize: 10,
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
    fontWeight: '600',
  },
  actionChip: {
    width: 32, height: 32, borderRadius: 10, borderWidth: 1.5,
    justifyContent: 'center', alignItems: 'center', marginTop: 2,
  },
  deliveryBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
  },
  deliveryBannerText: {
    fontSize: 12,
    fontWeight: '500',
    color: COLORS.textSecondary,
    flex: 1,
    lineHeight: 17,
  },
});

// ─────────────────────────────────────────────
// SUMMARY ROW
// ─────────────────────────────────────────────
const SummaryRow = ({ icon, label, value, accent, numberOfLines = 0 }) => (
  <View style={summaryRowStyles.row}>
    <View style={summaryRowStyles.iconWrap}>
      <Ionicons name={icon} size={14} color={accent ? COLORS.primary : COLORS.textMuted} />
    </View>
    <Text style={summaryRowStyles.label}>{label}</Text>
    <Text
      style={[summaryRowStyles.value, accent && { color: COLORS.primary, fontWeight: '700' }]}
      numberOfLines={numberOfLines}
    >
      {value}
    </Text>
  </View>
);

const summaryRowStyles = StyleSheet.create({
  row: {
    flexDirection: 'row', alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1, borderBottomColor: COLORS.borderLight,
    gap: 10,
  },
  iconWrap: {
    width: 28, height: 28, borderRadius: 8,
    backgroundColor: COLORS.primarySoft,
    justifyContent: 'center', alignItems: 'center',
  },
  label: { fontSize: 12, fontWeight: '600', color: COLORS.textMuted, width: 60 },
  value: { flex: 1, fontSize: 13, fontWeight: '500', color: COLORS.textPrimary, lineHeight: 18 },
});

// ─────────────────────────────────────────────
// MAIN SCREEN
// ─────────────────────────────────────────────
export default function PickupRequestScreen() {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);

  const [fadeAnim] = useState(new Animated.Value(0));
  const [slideAnim] = useState(new Animated.Value(30));

  // Map & Location
  const [showMapModal, setShowMapModal] = useState(false);
  const [isLoadingLocation, setIsLoadingLocation] = useState(false);
  const [mapRegion, setMapRegion] = useState(null);

  // Form
  const [branches, setBranches] = useState([]);
  const [selectedBranch, setSelectedBranch] = useState('');
  const [pickupAddress, setPickupAddress] = useState('');
  const [pickupCoordinates, setPickupCoordinates] = useState(null);
  const [pickupDate, setPickupDate] = useState(new Date());
  const [pickupTime, setPickupTime] = useState('09:00');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [notes, setNotes] = useState('');

  const [showDatePicker, setShowDatePicker] = useState(false);
  const [showTimePicker, setShowTimePicker] = useState(false);

  const locationStore = useLocationStore();
  const scrollRef = useRef(null);

  const STEPS = ['Location', 'Details', 'Confirm'];

  // ─── Memoized values ───
  const formComplete = useMemo(() =>
    !!(pickupAddress && selectedBranch && phoneNumber),
    [pickupAddress, selectedBranch, phoneNumber]
  );

  const timePickerValue = useMemo(() => {
    const [h, m] = (pickupTime || '09:00').split(':');
    const d = new Date();
    d.setHours(parseInt(h) || 9, parseInt(m) || 0, 0);
    return d;
  }, [pickupTime]);

  useEffect(() => { fetchInitialData(); }, []);

  useEffect(() => {
    if (!loading) {
      Animated.parallel([
        Animated.timing(fadeAnim, { toValue: 1, duration: 500, useNativeDriver: true }),
        Animated.spring(slideAnim, { toValue: 0, useNativeDriver: true, tension: 60, friction: 12 }),
      ]).start();
    }
  }, [loading]);

  // Step indicator — driven by form completion, not auto-advance
  useEffect(() => {
    if (formComplete) {
      setCurrentStep(2);
    } else if (pickupAddress) {
      setCurrentStep(1);
    } else {
      setCurrentStep(0);
    }
  }, [pickupAddress, formComplete]);

  // ─── API ───

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      await initializeLocation();
      await Promise.all([fetchBranches(), fetchCustomerData()]);
    } catch (error) {
      console.error('Error fetching initial data:', error);
    } finally {
      setLoading(false);
    }
  };

  const initializeLocation = async () => {
    try {
      setIsLoadingLocation(true);
      const location = await LocationService.getCurrentLocation();
      setMapRegion({ ...location, latitudeDelta: 0.05, longitudeDelta: 0.05 });
      locationStore.setUserLocation(location);
    } catch {
      // Default to Dumaguete City
      setMapRegion({ latitude: 9.3068, longitude: 123.3054, latitudeDelta: 0.1, longitudeDelta: 0.1 });
    } finally {
      setIsLoadingLocation(false);
    }
  };

  const fetchBranches = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/v1/branches`, {
        headers: { 'Accept': 'application/json' },
      });
      if (response.ok) {
        const data = await response.json();
        let arr = [];
        if (data.success && data.data?.branches) arr = data.data.branches;
        else if (Array.isArray(data.data)) arr = data.data;
        else if (Array.isArray(data)) arr = data;
        setBranches(arr);
        if (arr.length > 0) setSelectedBranch(arr[0].id.toString());
      }
    } catch (error) {
      console.error('Error fetching branches:', error);
      setBranches([]);
    }
  };

  const fetchCustomerData = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) return;
      const response = await fetch(`${API_BASE_URL}/v1/user`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
      });
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data?.customer) {
          const customer = data.data.customer;
          if (customer.address) setPickupAddress(customer.address);
          if (customer.phone) setPhoneNumber(customer.phone);
        }
      }
    } catch (error) {
      console.error('Error fetching customer data:', error);
    }
  };

  // ─── Location ───

  const handleLocationSelect = async (location) => {
    try {
      if (!location?.coordinate) return;
      const address = await LocationService.getAddressFromCoordinate(location.coordinate);
      setPickupAddress(address);
      setPickupCoordinates(location.coordinate);
      setShowMapModal(false);
    } catch {
      Alert.alert('Error', 'Could not get address for selected location');
    }
  };

  const handleUseCurrentLocation = async () => {
    try {
      setIsLoadingLocation(true);
      const location = await LocationService.getCurrentLocation();
      const address = await LocationService.getAddressFromCoordinate(location);
      setPickupAddress(address);
      setPickupCoordinates(location);
      setShowMapModal(false);
    } catch {
      Alert.alert('Error', 'Unable to get your current location');
    } finally {
      setIsLoadingLocation(false);
    }
  };

  // ─── Date/Time ───

  const handleDateChange = (event, selected) => {
    setShowDatePicker(false);
    if (selected) setPickupDate(selected);
  };

  const handleTimeChange = (event, selected) => {
    setShowTimePicker(false);
    if (selected) {
      const hours = selected.getHours().toString().padStart(2, '0');
      const minutes = selected.getMinutes().toString().padStart(2, '0');
      setPickupTime(`${hours}:${minutes}`);
    }
  };

  // ─── Validation ───

  const validateForm = () => {
    if (!selectedBranch) {
      Alert.alert('Missing Info', 'Please select a branch');
      return false;
    }
    if (!pickupAddress.trim()) {
      Alert.alert('Missing Info', 'Please set your pickup location');
      return false;
    }
    if (!phoneNumber.trim()) {
      Alert.alert('Missing Info', 'Please enter your phone number');
      return false;
    }
    // PH phone number validation
    const phoneRegex = /^(09|\+639)\d{9}$/;
    if (!phoneRegex.test(phoneNumber.replace(/[-\s]/g, ''))) {
      Alert.alert('Invalid Phone', 'Please enter a valid PH number (09XX-XXX-XXXX)');
      return false;
    }
    // Business hours: 7am – 8pm
    const [h] = pickupTime.split(':');
    const hour = parseInt(h);
    if (hour < 7 || hour >= 20) {
      Alert.alert('Invalid Time', 'Pickup is only available between 7:00 AM and 8:00 PM');
      return false;
    }
    return true;
  };

  // ─── Submit ───

  const handleSubmit = async () => {
    if (!validateForm()) return;
    try {
      setSubmitting(true);
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      if (!token) {
        Alert.alert('Error', 'Please login to continue');
        router.replace('/(auth)/login');
        return;
      }

      const response = await fetch(`${API_BASE_URL}/v1/pickups`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          branch_id: parseInt(selectedBranch),
          pickup_address: pickupAddress,
          delivery_address: pickupAddress,         // same as pickup
          latitude: pickupCoordinates?.latitude || 0,
          longitude: pickupCoordinates?.longitude || 0,
          delivery_latitude: pickupCoordinates?.latitude || 0,   // same
          delivery_longitude: pickupCoordinates?.longitude || 0, // same
          preferred_date: pickupDate.toISOString().split('T')[0],
          preferred_time: pickupTime,
          phone_number: phoneNumber,
          notes: notes,
          estimated_weight: null,
          service_id: null,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        Alert.alert(
          '🎉 Pickup Scheduled!',
          "We'll confirm your request shortly and deliver your laundry back to the same address.",
          [{
            text: 'View My Laundries',
            onPress: () => {
              setPickupAddress('');
              setPickupCoordinates(null);
              setNotes('');
              setCurrentStep(0);
              router.push('/(tabs)/laundries');
            },
          },
          {
            text: 'Done',
            style: 'cancel',
            onPress: () => {
              setPickupAddress('');
              setPickupCoordinates(null);
              setNotes('');
              setCurrentStep(0);
              router.push('/(tabs)/');
            },
          }]
        );
      } else {
        Alert.alert('Error', data.message || 'Failed to submit pickup request');
      }
    } catch (error) {
      console.error('Error submitting pickup request:', error);
      Alert.alert('Error', 'Failed to submit request. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  // ─── Helpers ───

  const getSelectedBranchName = () => {
    const branch = branches.find(b => b.id.toString() === selectedBranch);
    return branch ? branch.name : 'Select Branch';
  };

  const formatPickupDate = () => pickupDate.toLocaleDateString('en-US', {
    weekday: 'short', month: 'short', day: 'numeric',
  });

  const formatPickupTime = () => {
    const [h, m] = pickupTime.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    return `${displayHour}:${m} ${ampm}`;
  };

  // ─── Loading ───

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <View style={styles.loadingContainer}>
          <View style={styles.loadingIconWrap}>
            <Ionicons name="car-outline" size={32} color={COLORS.primary} />
          </View>
          <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 20 }} />
          <Text style={styles.loadingText}>Preparing your pickup...</Text>
        </View>
      </View>
    );
  }

  // ─── Render ───

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerTop}>
          <Text style={styles.headerTitle}>Schedule Pickup</Text>
          <TouchableOpacity
            style={styles.historyButton}
            onPress={() => router.push('/pickups')}
          >
            <Ionicons name="time-outline" size={18} color={COLORS.primary} />
            <Text style={styles.historyText}>History</Text>
          </TouchableOpacity>
        </View>
        <Text style={styles.headerSubtitle}>
          We'll pick up and deliver back to your address — free of charge
        </Text>
      </View>

      {/* Steps */}
      <StepIndicator currentStep={currentStep} steps={STEPS} />

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView
          ref={scrollRef}
          style={styles.scrollView}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ paddingBottom: 120 }}
          keyboardShouldPersistTaps="handled"
        >
          <Animated.View style={{ opacity: fadeAnim, transform: [{ translateY: slideAnim }] }}>

            {/* ─── SECTION 1: Location ─── */}
            <View style={styles.sectionHeader}>
              <View style={styles.sectionBadge}>
                <Ionicons name="location" size={14} color={COLORS.primary} />
              </View>
              <Text style={styles.sectionTitle}>Your Location</Text>
              {pickupAddress && (
                <View style={styles.sectionCheck}>
                  <Ionicons name="checkmark-circle" size={16} color={COLORS.pickup} />
                </View>
              )}
            </View>

            <PickupCard
              pickupAddress={pickupAddress}
              pickupCoords={pickupCoordinates}
              onPickupPress={() => setShowMapModal(true)}
            />

            {/* Quick action */}
            {!pickupAddress && (
              <View style={styles.quickActions}>
                <TouchableOpacity
                  style={styles.quickActionButton}
                  onPress={handleUseCurrentLocation}
                  disabled={isLoadingLocation}
                >
                  {isLoadingLocation ? (
                    <ActivityIndicator size="small" color={COLORS.pickup} />
                  ) : (
                    <>
                      <Ionicons name="locate" size={16} color={COLORS.pickup} />
                      <Text style={styles.quickActionText}>Use my current location</Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
            )}

            {/* ─── SECTION 2: Schedule & Details ─── */}
            <View style={[styles.sectionHeader, { marginTop: 28 }]}>
              <View style={styles.sectionBadge}>
                <Ionicons name="calendar" size={14} color={COLORS.primary} />
              </View>
              <Text style={styles.sectionTitle}>Schedule & Details</Text>
              {selectedBranch && phoneNumber && (
                <View style={styles.sectionCheck}>
                  <Ionicons name="checkmark-circle" size={16} color={COLORS.pickup} />
                </View>
              )}
            </View>

            <View style={styles.formCard}>
              {/* Branch */}
              <View style={styles.fieldGroup}>
                <Text style={styles.fieldLabel}>Branch</Text>
                <View style={styles.pickerWrapper}>
                  <Ionicons name="business-outline" size={18} color={COLORS.textMuted} style={styles.fieldIcon} />
                  <Picker
                    selectedValue={selectedBranch}
                    onValueChange={(value) => setSelectedBranch(value)}
                    style={styles.picker}
                    dropdownIconColor={COLORS.primary}
                  >
                    {branches.length > 0 ? (
                      branches.map((branch) => (
                        <Picker.Item
                          key={branch.id}
                          label={`${branch.name}${branch.city ? ' — ' + branch.city : ''}`}
                          value={branch.id.toString()}
                          color={'#FFF'}
                        />
                      ))
                    ) : (
                      <Picker.Item label="No branches available" value="" color="#999" />
                    )}
                  </Picker>
                </View>
              </View>

              {/* Date & Time */}
              <View style={styles.dateTimeRow}>
                <TouchableOpacity
                  style={[styles.dateTimeButton, { flex: 1 }]}
                  onPress={() => setShowDatePicker(true)}
                >
                  <Ionicons name="calendar-outline" size={18} color={COLORS.primary} />
                  <View>
                    <Text style={styles.dateTimeLabel}>DATE</Text>
                    <Text style={styles.dateTimeValue}>{formatPickupDate()}</Text>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.dateTimeButton, { flex: 0.7 }]}
                  onPress={() => setShowTimePicker(true)}
                >
                  <Ionicons name="time-outline" size={18} color={COLORS.primary} />
                  <View>
                    <Text style={styles.dateTimeLabel}>TIME</Text>
                    <Text style={styles.dateTimeValue}>{formatPickupTime()}</Text>
                  </View>
                </TouchableOpacity>
              </View>

              {/* Business hours hint */}
              <View style={styles.hoursHint}>
                <Ionicons name="information-circle-outline" size={13} color={COLORS.textMuted} />
                <Text style={styles.hoursHintText}>Available 7:00 AM – 8:00 PM</Text>
              </View>

              {/* Phone */}
              <View style={[styles.fieldGroup, { marginTop: 8 }]}>
                <Text style={styles.fieldLabel}>Phone Number</Text>
                <View style={styles.inputWrapper}>
                  <Ionicons name="call-outline" size={18} color={COLORS.textMuted} style={styles.fieldIcon} />
                  <TextInput
                    style={styles.textField}
                    placeholder="09XX-XXX-XXXX"
                    placeholderTextColor={COLORS.textMuted}
                    value={phoneNumber}
                    onChangeText={setPhoneNumber}
                    keyboardType="phone-pad"
                    maxLength={13}
                  />
                </View>
              </View>

              {/* Notes */}
              <View style={styles.fieldGroup}>
                <Text style={styles.fieldLabel}>
                  Notes <Text style={styles.optionalTag}>Optional</Text>
                </Text>
                <View style={[styles.inputWrapper, { alignItems: 'flex-start' }]}>
                  <Ionicons
                    name="chatbox-ellipses-outline" size={18}
                    color={COLORS.textMuted}
                    style={[styles.fieldIcon, { marginTop: 14 }]}
                  />
                  <TextInput
                    style={[styles.textField, { minHeight: 72, textAlignVertical: 'top', paddingTop: 14 }]}
                    placeholder="Gate code, landmarks, special instructions..."
                    placeholderTextColor={COLORS.textMuted}
                    value={notes}
                    onChangeText={setNotes}
                    multiline
                    numberOfLines={3}
                  />
                </View>
              </View>
            </View>

            {/* ─── SECTION 3: Summary ─── */}
            {formComplete && (
              <>
                <View style={[styles.sectionHeader, { marginTop: 28 }]}>
                  <View style={styles.sectionBadge}>
                    <Ionicons name="receipt" size={14} color={COLORS.primary} />
                  </View>
                  <Text style={styles.sectionTitle}>Summary</Text>
                </View>

                <View style={styles.summaryCard}>
                  <LinearGradient
                    colors={['#111640', '#171D45']}
                    style={styles.summaryGradient}
                    start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}
                  >
                    <SummaryRow icon="business" label="Branch" value={getSelectedBranchName()} />
                    <SummaryRow icon="location" label="Address" value={pickupAddress} numberOfLines={2} />
                    <SummaryRow icon="refresh" label="Delivery" value="Back to same address" />
                    <SummaryRow icon="calendar" label="Schedule" value={`${formatPickupDate()} at ${formatPickupTime()}`} />
                    <SummaryRow icon="call" label="Phone" value={phoneNumber} />
                    {notes ? <SummaryRow icon="chatbox-ellipses" label="Notes" value={notes} numberOfLines={2} /> : null}

                    <View style={styles.freeBanner}>
                      <LinearGradient
                        colors={COLORS.gradientPickup}
                        style={styles.freeBannerGradient}
                        start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}
                      >
                        <Ionicons name="pricetag" size={16} color="#FFF" />
                        <Text style={styles.freeBannerText}>FREE Pickup & Delivery</Text>
                      </LinearGradient>
                    </View>
                  </LinearGradient>
                </View>
              </>
            )}

          </Animated.View>
        </ScrollView>
      </KeyboardAvoidingView>

      {/* ─── Bottom Submit Bar ─── */}
      <View style={styles.bottomBar}>
        <TouchableOpacity
          style={[styles.submitButton, (!formComplete || submitting) && styles.submitDisabled]}
          onPress={handleSubmit}
          disabled={!formComplete || submitting}
          activeOpacity={0.85}
        >
          <LinearGradient
            colors={formComplete ? COLORS.gradientPrimary : [COLORS.surfaceElevated, COLORS.surfaceElevated]}
            style={styles.submitGradient}
            start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}
          >
            {submitting ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <>
                <Ionicons name="paper-plane" size={20} color={formComplete ? '#FFF' : COLORS.textMuted} />
                <Text style={[styles.submitText, !formComplete && { color: COLORS.textMuted }]}>
                  Request Pickup
                </Text>
              </>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </View>

      {/* ─── Map Modal ─── */}
      <Modal
        visible={showMapModal}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setShowMapModal(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={() => setShowMapModal(false)} style={styles.modalCloseBtn}>
              <Ionicons name="close" size={22} color={COLORS.textPrimary} />
            </TouchableOpacity>
            <View style={styles.modalTitleWrap}>
              <View style={[styles.modalTitleDot, { backgroundColor: COLORS.pickup }]} />
              <Text style={styles.modalTitle}>Set Pickup Location</Text>
            </View>
            <TouchableOpacity
              style={styles.currentLocBtn}
              onPress={handleUseCurrentLocation}
              disabled={isLoadingLocation}
            >
              {isLoadingLocation ? (
                <ActivityIndicator size="small" color={COLORS.primary} />
              ) : (
                <>
                  <Ionicons name="locate" size={16} color={COLORS.primary} />
                  <Text style={styles.currentLocText}>Current</Text>
                </>
              )}
            </TouchableOpacity>
          </View>

          <View style={styles.modalSearchWrap}>
            <LocationSearch
              placeholder="Search your pickup address..."
              onLocationSelect={handleLocationSelect}
              currentLocationButton={false}
            />
          </View>

          {mapRegion && (
            <PickupDeliveryMap
              pickupLocation={pickupCoordinates}
              deliveryLocation={null}
              onLocationSelect={(marker) => {
                if (marker.coordinate) {
                  handleLocationSelect({ coordinate: marker.coordinate, name: marker.title });
                }
              }}
              style={styles.map}
            />
          )}

          <View style={styles.modalFooter}>
            <TouchableOpacity
              style={styles.confirmBtn}
              onPress={() => {
                if (pickupCoordinates) {
                  setShowMapModal(false);
                } else {
                  Alert.alert('No Location', 'Tap the map or search to select a location');
                }
              }}
              activeOpacity={0.85}
            >
              <LinearGradient
                colors={COLORS.gradientPickup}
                style={styles.confirmGradient}
                start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}
              >
                <Ionicons name="checkmark-circle" size={20} color="#FFF" />
                <Text style={styles.confirmText}>Confirm Pickup Location</Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Date Picker */}
      {showDatePicker && (
        <DateTimePicker
          value={pickupDate}
          mode="date"
          display={Platform.OS === 'ios' ? 'spinner' : 'default'}
          onChange={handleDateChange}
          minimumDate={new Date()}
        />
      )}

      {/* Time Picker */}
      {showTimePicker && (
        <DateTimePicker
          value={timePickerValue}
          mode="time"
          display={Platform.OS === 'ios' ? 'spinner' : 'default'}
          onChange={handleTimeChange}
          minimumDate={new Date(new Date().setHours(7, 0, 0))}
          maximumDate={new Date(new Date().setHours(20, 0, 0))}
        />
      )}
    </View>
  );
}

// ─────────────────────────────────────────────
// MAIN STYLES
// ─────────────────────────────────────────────
const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  centerContent: { justifyContent: 'center', alignItems: 'center' },

  loadingContainer: { alignItems: 'center' },
  loadingIconWrap: {
    width: 72, height: 72, borderRadius: 36,
    backgroundColor: COLORS.primaryGlow,
    justifyContent: 'center', alignItems: 'center',
  },
  loadingText: { color: COLORS.textSecondary, marginTop: 12, fontSize: 14, fontWeight: '500' },

  header: {
    paddingHorizontal: 24,
    paddingTop: Platform.OS === 'ios' ? 60 : 48,
    paddingBottom: 8,
  },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  headerTitle: { fontSize: 26, fontWeight: '800', color: COLORS.textPrimary, letterSpacing: -0.5 },
  headerSubtitle: { fontSize: 13, color: COLORS.textMuted, marginTop: 2 },
  historyButton: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 12, paddingVertical: 7, borderRadius: 10,
  },
  historyText: { fontSize: 13, fontWeight: '600', color: COLORS.primary },

  scrollView: { flex: 1 },

  sectionHeader: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 24, marginBottom: 12, gap: 8,
  },
  sectionBadge: {
    width: 28, height: 28, borderRadius: 8,
    backgroundColor: COLORS.primarySoft,
    justifyContent: 'center', alignItems: 'center',
  },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.textPrimary, flex: 1 },
  sectionCheck: { marginLeft: 'auto' },

  quickActions: { paddingHorizontal: 24, marginTop: 12 },
  quickActionButton: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    backgroundColor: COLORS.pickupGlow,
    paddingHorizontal: 16, paddingVertical: 10,
    borderRadius: 12, borderWidth: 1, borderColor: COLORS.pickup + '20',
  },
  quickActionText: { fontSize: 13, fontWeight: '500', color: COLORS.pickup },

  formCard: {
    backgroundColor: COLORS.surface,
    marginHorizontal: 20, borderRadius: 20, padding: 20,
    borderWidth: 1, borderColor: COLORS.borderLight,
  },
  fieldGroup: { marginBottom: 18 },
  fieldLabel: { fontSize: 12, fontWeight: '700', color: COLORS.textSecondary, marginBottom: 8, letterSpacing: 0.3 },
  optionalTag: { fontSize: 10, fontWeight: '500', color: COLORS.textMuted, fontStyle: 'italic' },
  pickerWrapper: {
    backgroundColor: COLORS.surfaceElevated, borderRadius: 14,
    overflow: 'hidden', flexDirection: 'row', alignItems: 'center',
  },
  fieldIcon: { marginLeft: 14 },
  picker: { flex: 1, color: COLORS.textPrimary, backgroundColor: 'transparent' },
  inputWrapper: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.surfaceElevated, borderRadius: 14 },
  textField: { flex: 1, padding: 14, color: COLORS.textPrimary, fontSize: 14 },

  dateTimeRow: { flexDirection: 'row', gap: 12, marginBottom: 4 },
  dateTimeButton: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    backgroundColor: COLORS.surfaceElevated, borderRadius: 14, padding: 14,
  },
  dateTimeLabel: { fontSize: 10, fontWeight: '700', color: COLORS.textMuted, letterSpacing: 0.5 },
  dateTimeValue: { fontSize: 14, fontWeight: '600', color: COLORS.textPrimary, marginTop: 1 },

  hoursHint: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    marginBottom: 16, paddingLeft: 2,
  },
  hoursHintText: { fontSize: 11, color: COLORS.textMuted },

  summaryCard: {
    marginHorizontal: 20, borderRadius: 20,
    overflow: 'hidden', borderWidth: 1, borderColor: COLORS.borderLight,
  },
  summaryGradient: { padding: 20 },
  freeBanner: { marginTop: 16, borderRadius: 12, overflow: 'hidden' },
  freeBannerGradient: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 8, paddingVertical: 12,
  },
  freeBannerText: { fontSize: 14, fontWeight: '800', color: '#FFF', letterSpacing: 0.3 },

  bottomBar: {
    position: 'absolute', bottom: 0, left: 0, right: 0,
    backgroundColor: COLORS.background,
    paddingHorizontal: 20, paddingTop: 12,
    paddingBottom: Platform.OS === 'ios' ? 34 : 20,
    borderTopWidth: 1, borderTopColor: COLORS.borderLight,
  },
  submitButton: {},
  submitDisabled: { opacity: 0.7 },
  submitGradient: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 10, paddingVertical: 16, borderRadius: 16,
  },
  submitText: { fontSize: 16, fontWeight: '800', color: '#FFF', letterSpacing: 0.3 },

  modalContainer: { flex: 1, backgroundColor: COLORS.background },
  modalHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 56 : 44,
    paddingBottom: 14,
    borderBottomWidth: 1, borderBottomColor: COLORS.borderLight,
  },
  modalCloseBtn: {
    width: 38, height: 38, borderRadius: 12,
    backgroundColor: COLORS.surfaceElevated,
    justifyContent: 'center', alignItems: 'center',
  },
  modalTitleWrap: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  modalTitleDot: { width: 10, height: 10, borderRadius: 5 },
  modalTitle: { fontSize: 17, fontWeight: '700', color: COLORS.textPrimary },
  currentLocBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: COLORS.primarySoft,
    paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10,
  },
  currentLocText: { color: COLORS.primary, fontSize: 13, fontWeight: '600' },
  modalSearchWrap: { paddingHorizontal: 20, paddingVertical: 14, backgroundColor: COLORS.background },
  map: { flex: 1 },
  modalFooter: {
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 34 : 20,
    backgroundColor: COLORS.background,
    borderTopWidth: 1, borderTopColor: COLORS.borderLight,
  },
  confirmBtn: { borderRadius: 16, overflow: 'hidden' },
  confirmGradient: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 8, paddingVertical: 16,
  },
  confirmText: { color: '#FFF', fontSize: 16, fontWeight: '700' },
});