import React, { useState, useEffect } from 'react';
import {
  View,
  TextInput,
  FlatList,
  TouchableOpacity,
  Text,
  StyleSheet,
  ActivityIndicator,
  Animated,
  Keyboard,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LocationService } from '../../services/locationService';

// ─── Design System ───
const COLORS = {
  background: '#06081A',
  surface: '#0F1332',
  surfaceLight: '#171D45',
  surfaceElevated: '#1E2654',
  primary: '#0EA5E9',
  primaryGlow: 'rgba(14, 165, 233, 0.12)',
  success: '#10B981',
  successGlow: 'rgba(16, 185, 129, 0.12)',
  accent: '#F59E0B',
  accentGlow: 'rgba(245, 158, 11, 0.12)',
  secondary: '#8B5CF6',
  secondaryGlow: 'rgba(139, 92, 246, 0.12)',
  danger: '#EF4444',
  textPrimary: '#F1F5F9',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  borderLight: 'rgba(255, 255, 255, 0.06)',
  border: '#1E293B',
};

const LocationSearch = ({
  placeholder = 'Search address...',
  onLocationSelect,
  initialValue = '',
  currentLocationButton = true,
  showManualEntry = true,
}) => {
  // ─── Search State ───
  const [query, setQuery] = useState(initialValue);
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showResults, setShowResults] = useState(false);
  const [noResults, setNoResults] = useState(false);   // FIX: separate flag for empty results

  // ─── Manual Entry State ───
  const [mode, setMode] = useState('search'); // 'search' | 'manual'
  const [manualFields, setManualFields] = useState({
    houseNumber: '',
    street: '',
    barangay: '',
    city: 'Dumaguete City',
    landmark: '',
  });
  const [manualLoading, setManualLoading] = useState(false);
  const [manualPreview, setManualPreview] = useState('');

  // ─── Animation ───
  const [formHeight] = useState(new Animated.Value(0));

  // Abort controller + stale result guard
  const abortControllerRef = React.useRef(null);
  const searchIdRef = React.useRef(0);

  // ─── Debounced Search ───
  useEffect(() => {
    if (mode !== 'search') return;

    const timer = setTimeout(() => {
      if (query.trim().length >= 2) {
        if (abortControllerRef.current) {
          abortControllerRef.current.abort();
        }

        const controller = new AbortController();
        abortControllerRef.current = controller;
        const currentSearchId = ++searchIdRef.current;

        performSearch(query, controller.signal, currentSearchId);
      } else {
        if (abortControllerRef.current) {
          abortControllerRef.current.abort();
          abortControllerRef.current = null;
        }
        setResults([]);
        setShowResults(false);
        setNoResults(false);
      }
    }, 400);

    return () => clearTimeout(timer);
  }, [query, mode]);

  // ─── Update manual preview ───
  useEffect(() => {
    const parts = [
      manualFields.houseNumber,
      manualFields.street,
      manualFields.barangay,
      manualFields.city,
    ].filter(Boolean);
    setManualPreview(parts.join(', '));
  }, [manualFields]);

  // ─── Search ───
  const performSearch = async (searchQuery, signal, searchId) => {
    if (!searchQuery.trim()) return;

    setLoading(true);
    setNoResults(false);

    try {
      const searchResults = await LocationService.searchLocations(searchQuery, {
        limit: 8,
        signal,
      });

      if (searchId !== searchIdRef.current) return;

      if (searchResults && searchResults.length > 0) {
        setResults(searchResults);
        setShowResults(true);
        setNoResults(false);
      } else {
        setResults([]);
        setShowResults(false);
        setNoResults(true);   // FIX: flag empty results separately so the hint shows
      }
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Search error:', error);
    } finally {
      if (searchId === searchIdRef.current) setLoading(false);
    }
  };

  // ─── Selection ───
  const handleSelectLocation = (location) => {
    setQuery(location.displayName || location.name);
    setShowResults(false);
    setNoResults(false);
    Keyboard.dismiss();
    onLocationSelect?.(location);
  };

  // FIX: just selects current location directly — no longer adds to results list
  const handleUseCurrentLocation = async () => {
    setLoading(true);
    try {
      const location = await LocationService.getCurrentLocation();
      const address = await LocationService.getAddressFromCoordinate(location);

      const selectedLocation = {
        id: 'current',
        coordinate: location,
        name: address,
        displayName: address,
        address: { road: address },
        type: 'current',
      };

      setQuery(address);
      setShowResults(false);
      setNoResults(false);
      Keyboard.dismiss();
      onLocationSelect?.(selectedLocation);
    } catch (error) {
      console.error('Error getting current location:', error);
    } finally {
      setLoading(false);
    }
  };

  // ─── Manual Entry ───
  const toggleMode = () => {
    const next = mode === 'search' ? 'manual' : 'search';
    setMode(next);
    setShowResults(false);
    setNoResults(false);

    Animated.spring(formHeight, {
      toValue: next === 'manual' ? 1 : 0,
      useNativeDriver: false,
      tension: 80,
      friction: 12,
    }).start();
  };

  const updateManualField = (field, value) => {
    setManualFields((prev) => ({ ...prev, [field]: value }));
  };

  const handleManualSubmit = async () => {
    if (!manualFields.street.trim()) return;

    setManualLoading(true);
    Keyboard.dismiss();

    try {
      const location = await LocationService.createManualLocation({
        houseNumber: manualFields.houseNumber.trim(),
        street: manualFields.street.trim(),
        barangay: manualFields.barangay.trim(),
        city: manualFields.city.trim() || 'Dumaguete City',
        province: 'Negros Oriental',
        landmark: manualFields.landmark.trim(),
      });

      setQuery(location.displayName);
      setMode('search');
      Animated.spring(formHeight, {
        toValue: 0,
        useNativeDriver: false,
        tension: 80,
        friction: 12,
      }).start();

      onLocationSelect?.(location);
    } catch (error) {
      console.error('Manual location error:', error);
    } finally {
      setManualLoading(false);
    }
  };

  // ─── Render: Result Item ───
  // FIX: use stable key (item.id + index) instead of Math.random()
  const renderResultItem = ({ item, index }) => (
    <TouchableOpacity
      style={styles.resultItem}
      onPress={() => handleSelectLocation(item)}
      activeOpacity={0.7}
    >
      <View style={[
        styles.resultIconWrap,
        item.type === 'current' && { backgroundColor: COLORS.primaryGlow },
        item.isManual && { backgroundColor: COLORS.accentGlow },
      ]}>
        <Ionicons
          name={
            item.type === 'current'
              ? 'navigate'
              : item.isManual
              ? 'create-outline'
              : 'location-outline'
          }
          size={16}
          color={
            item.type === 'current'
              ? COLORS.primary
              : item.isManual
              ? COLORS.accent
              : COLORS.textMuted
          }
        />
      </View>
      <View style={styles.resultTextContainer}>
        <Text style={styles.resultTitle} numberOfLines={1}>
          {item.address?.road
            ? `${item.address.house_number ? item.address.house_number + ' ' : ''}${item.address.road}`
            : item.name?.split(',')[0]}
        </Text>
        <Text style={styles.resultSubtitle} numberOfLines={2}>
          {item.displayName || item.name}
        </Text>
      </View>
      <Ionicons name="chevron-forward" size={14} color={COLORS.textMuted} />
    </TouchableOpacity>
  );

  // ─── Manual form animated height ───
  // FIX: increased to 420 to avoid clipping on smaller screens
  const formMaxHeight = formHeight.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 420],
  });

  const formOpacity = formHeight.interpolate({
    inputRange: [0, 0.5, 1],
    outputRange: [0, 0.3, 1],
  });

  return (
    <View style={styles.container}>
      {/* ═══ Search Bar ═══ */}
      <View style={styles.searchContainer}>
        <Ionicons name="search" size={18} color={COLORS.textMuted} style={styles.searchIcon} />
        <TextInput
          style={styles.input}
          placeholder={placeholder}
          value={query}
          onChangeText={(text) => {
            setQuery(text);
            // Reset no-results hint when user starts typing again
            if (noResults) setNoResults(false);
          }}
          onFocus={() => {
            if (mode === 'search' && query.length >= 2 && results.length > 0) {
              setShowResults(true);
            }
          }}
          placeholderTextColor={COLORS.textMuted}
          selectionColor={COLORS.primary}
          // FIX: always editable — in manual mode the input is just used as a display
          editable={true}
        />
        {loading && <ActivityIndicator size="small" color={COLORS.primary} style={styles.loading} />}
        {query.length > 0 && !loading && (
          <TouchableOpacity
            onPress={() => {
              setQuery('');
              setResults([]);
              setShowResults(false);
              setNoResults(false);
            }}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <Ionicons name="close-circle" size={18} color={COLORS.textMuted} />
          </TouchableOpacity>
        )}
      </View>

      {/* ═══ Action Row: Current Location + Manual Entry Toggle ═══ */}
      <View style={styles.actionRow}>
        {currentLocationButton && (
          <TouchableOpacity
            style={styles.actionBtn}
            onPress={handleUseCurrentLocation}
            disabled={loading}
            activeOpacity={0.7}
          >
            <View style={[styles.actionIcon, { backgroundColor: COLORS.primaryGlow }]}>
              <Ionicons name="navigate" size={13} color={COLORS.primary} />
            </View>
            <Text style={[styles.actionText, { color: COLORS.primary }]}>Current Location</Text>
          </TouchableOpacity>
        )}

        {showManualEntry && (
          <TouchableOpacity
            style={[styles.actionBtn, mode === 'manual' && styles.actionBtnActive]}
            onPress={toggleMode}
            activeOpacity={0.7}
          >
            <View style={[
              styles.actionIcon,
              { backgroundColor: mode === 'manual' ? COLORS.accentGlow : COLORS.secondaryGlow },
            ]}>
              <Ionicons
                name={mode === 'manual' ? 'search' : 'create-outline'}
                size={13}
                color={mode === 'manual' ? COLORS.accent : COLORS.secondary}
              />
            </View>
            <Text style={[styles.actionText, { color: mode === 'manual' ? COLORS.accent : COLORS.secondary }]}>
              {mode === 'manual' ? 'Back to Search' : 'Enter Manually'}
            </Text>
          </TouchableOpacity>
        )}
      </View>

      {/* ═══ Manual Entry Form ═══ */}
      <Animated.View style={[styles.manualForm, { maxHeight: formMaxHeight, opacity: formOpacity }]}>
        <View style={styles.manualInner}>
          <View style={styles.manualHeader}>
            <Ionicons name="create-outline" size={16} color={COLORS.accent} />
            <Text style={styles.manualTitle}>Enter Address</Text>
          </View>

          {/* House Number + Street (row) */}
          <View style={styles.fieldRow}>
            <View style={styles.fieldSmall}>
              <Text style={styles.fieldLabel}>House / Unit #</Text>
              <TextInput
                style={styles.fieldInput}
                placeholder="e.g. 183"
                placeholderTextColor={COLORS.textMuted}
                value={manualFields.houseNumber}
                onChangeText={(v) => updateManualField('houseNumber', v)}
                selectionColor={COLORS.primary}
              />
            </View>
            <View style={styles.fieldLarge}>
              <Text style={styles.fieldLabel}>Street *</Text>
              <TextInput
                style={styles.fieldInput}
                placeholder="e.g. Dr. V. Locsin St"
                placeholderTextColor={COLORS.textMuted}
                value={manualFields.street}
                onChangeText={(v) => updateManualField('street', v)}
                selectionColor={COLORS.primary}
              />
            </View>
          </View>

          {/* Barangay */}
          <View style={styles.fieldFull}>
            <Text style={styles.fieldLabel}>Barangay / Area</Text>
            <TextInput
              style={styles.fieldInput}
              placeholder="e.g. Brgy. Calindagan"
              placeholderTextColor={COLORS.textMuted}
              value={manualFields.barangay}
              onChangeText={(v) => updateManualField('barangay', v)}
              selectionColor={COLORS.primary}
            />
          </View>

          {/* City */}
          <View style={styles.fieldFull}>
            <Text style={styles.fieldLabel}>City / Municipality</Text>
            <TextInput
              style={styles.fieldInput}
              placeholder="Dumaguete City"
              placeholderTextColor={COLORS.textMuted}
              value={manualFields.city}
              onChangeText={(v) => updateManualField('city', v)}
              selectionColor={COLORS.primary}
            />
          </View>

          {/* Landmark */}
          <View style={styles.fieldFull}>
            <Text style={styles.fieldLabel}>Landmark <Text style={styles.optionalTag}>(optional)</Text></Text>
            <TextInput
              style={styles.fieldInput}
              placeholder="e.g. near Robinsons, beside Mercury Drug"
              placeholderTextColor={COLORS.textMuted}
              value={manualFields.landmark}
              onChangeText={(v) => updateManualField('landmark', v)}
              selectionColor={COLORS.primary}
            />
          </View>

          {/* Preview */}
          {manualPreview.length > 0 && (
            <View style={styles.previewBox}>
              <Ionicons name="location" size={14} color={COLORS.success} />
              <Text style={styles.previewText} numberOfLines={2}>{manualPreview}</Text>
            </View>
          )}

          {/* Submit */}
          <TouchableOpacity
            style={[styles.manualSubmit, !manualFields.street.trim() && styles.manualSubmitDisabled]}
            onPress={handleManualSubmit}
            disabled={!manualFields.street.trim() || manualLoading}
            activeOpacity={0.8}
          >
            {manualLoading ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <>
                <Ionicons name="checkmark-circle" size={18} color="#FFF" />
                <Text style={styles.manualSubmitText}>Use This Address</Text>
              </>
            )}
          </TouchableOpacity>
        </View>
      </Animated.View>

      {/* ═══ Search Results Dropdown ═══ */}
      {showResults && results.length > 0 && mode === 'search' && (
        <View style={styles.resultsContainer}>
          <FlatList
            data={results}
            renderItem={renderResultItem}
            keyExtractor={(item, index) => `${item.id ?? 'loc'}-${index}`}
            keyboardShouldPersistTaps="handled"
            style={styles.resultsList}
          />
        </View>
      )}

      {/* ═══ No Results — suggest manual entry ═══ */}
      {/* FIX: uses noResults flag instead of showResults + length === 0 */}
      {noResults && query.length >= 2 && !loading && mode === 'search' && (
        <View style={styles.noResults}>
          <Ionicons name="search-outline" size={20} color={COLORS.textMuted} style={{ marginBottom: 6 }} />
          <Text style={styles.noResultsText}>No locations found for "{query}"</Text>
          {showManualEntry && (
            <TouchableOpacity style={styles.noResultsBtn} onPress={toggleMode} activeOpacity={0.7}>
              <Ionicons name="create-outline" size={14} color={COLORS.accent} />
              <Text style={styles.noResultsBtnText}>Enter address manually</Text>
            </TouchableOpacity>
          )}
        </View>
      )}
    </View>
  );
};


// ─────────────────────────────────────────────
// STYLES
// ─────────────────────────────────────────────
const styles = StyleSheet.create({
  container: {
    position: 'relative',
    zIndex: 1000,
  },

  // ─── Search Bar ───
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surfaceLight,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },
  searchIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    fontSize: 15,
    color: COLORS.textPrimary,
    fontWeight: '500',
    padding: 0,
  },
  loading: {
    marginHorizontal: 8,
  },

  // ─── Action Row ───
  actionRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 8,
  },
  actionBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    gap: 8,
  },
  actionBtnActive: {
    borderColor: COLORS.accent + '40',
    backgroundColor: COLORS.accentGlow,
  },
  actionIcon: {
    width: 26,
    height: 26,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  actionText: {
    fontSize: 12,
    fontWeight: '600',
  },

  // ─── Manual Entry Form ───
  manualForm: {
    overflow: 'hidden',
  },
  manualInner: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 16,
    marginTop: 8,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },
  manualHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 14,
  },
  manualTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },

  // ─── Form Fields ───
  fieldRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 10,
  },
  fieldSmall: {
    flex: 0.35,
  },
  fieldLarge: {
    flex: 0.65,
  },
  fieldFull: {
    marginBottom: 10,
  },
  fieldLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: COLORS.textMuted,
    marginBottom: 5,
    letterSpacing: 0.2,
  },
  optionalTag: {
    fontSize: 10,
    fontWeight: '400',
    fontStyle: 'italic',
    color: COLORS.textMuted,
  },
  fieldInput: {
    backgroundColor: COLORS.surfaceElevated,
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: Platform.OS === 'ios' ? 11 : 9,
    fontSize: 14,
    color: COLORS.textPrimary,
    fontWeight: '500',
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },

  // ─── Preview ───
  previewBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: COLORS.successGlow,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 10,
    gap: 8,
    marginBottom: 12,
  },
  previewText: {
    flex: 1,
    fontSize: 12,
    fontWeight: '600',
    color: COLORS.success,
    lineHeight: 17,
  },

  // ─── Submit ───
  manualSubmit: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primary,
    paddingVertical: 13,
    borderRadius: 12,
    gap: 8,
  },
  manualSubmitDisabled: {
    opacity: 0.4,
  },
  manualSubmitText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#FFF',
  },

  // ─── Results ───
  resultsContainer: {
    position: 'absolute',
    top: '100%',
    left: 0,
    right: 0,
    backgroundColor: COLORS.surface,
    borderRadius: 14,
    marginTop: 6,
    maxHeight: 300,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.35,
    shadowRadius: 16,
    elevation: 10,
    zIndex: 1001,
    overflow: 'hidden',
  },
  resultsList: {
    borderRadius: 14,
  },
  resultItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 14,
    gap: 10,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
  },
  resultIconWrap: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: COLORS.surfaceElevated,
    justifyContent: 'center',
    alignItems: 'center',
  },
  resultTextContainer: {
    flex: 1,
  },
  resultTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  resultSubtitle: {
    fontSize: 11,
    color: COLORS.textMuted,
    lineHeight: 15,
  },

  // ─── No Results ───
  noResults: {
    position: 'absolute',
    top: '100%',
    left: 0,
    right: 0,
    backgroundColor: COLORS.surface,
    padding: 20,
    borderRadius: 14,
    marginTop: 6,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.35,
    shadowRadius: 16,
    elevation: 10,
    zIndex: 1001,
  },
  noResultsText: {
    color: COLORS.textMuted,
    fontSize: 13,
    fontWeight: '500',
    textAlign: 'center',
  },
  noResultsBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 12,
    backgroundColor: COLORS.accentGlow,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 10,
  },
  noResultsBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.accent,
  },
});

export default LocationSearch;