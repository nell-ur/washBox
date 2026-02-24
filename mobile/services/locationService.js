import * as Location from 'expo-location';
import { Platform, Alert, Linking } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Only import TaskManager on native platforms
let TaskManager;
if (Platform.OS !== 'web') {
  try {
    TaskManager = require('expo-task-manager');
  } catch (error) {
    console.warn('expo-task-manager not available:', error.message);
  }
}

const LAST_KNOWN_LOCATION_KEY = 'washbox-last-known-location';

// Define background task only on native platforms
if (Platform.OS !== 'web' && TaskManager) {
  const LOCATION_TASK_NAME = 'washbox-location-tracking';

  TaskManager.defineTask(LOCATION_TASK_NAME, async ({ data, error }) => {
    if (error) {
      console.error('Location tracking error:', error);
      return;
    }

    if (data && data.locations && data.locations.length > 0) {
      const location = data.locations[0];
      const locationData = {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
        accuracy: location.coords.accuracy,
        timestamp: location.timestamp,
      };

      await AsyncStorage.setItem(LAST_KNOWN_LOCATION_KEY, JSON.stringify(locationData));
      console.log('Background location update:', locationData);
    }
  });
}

// Web geolocation API wrapper
const getWebLocation = () => {
  return new Promise((resolve, reject) => {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
      reject(new Error('Geolocation not supported'));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          altitude: position.coords.altitude,
          speed: position.coords.speed,
          heading: position.coords.heading,
          timestamp: position.timestamp,
          source: 'web',
        });
      },
      (error) => {
        reject(error);
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 30000,
      }
    );
  });
};


// ═══════════════════════════════════════════════
// PHILIPPINE ADDRESS INTELLIGENCE
// ═══════════════════════════════════════════════

const ABBREVIATIONS = {
  'dr':       'Doctor',
  'dra':      'Doctora',
  'engr':     'Engineer',
  'atty':     'Attorney',
  'gen':      'General',
  'gov':      'Governor',
  'pres':     'President',
  'sen':      'Senator',
  'cong':     'Congressman',
  'capt':     'Captain',
  'col':      'Colonel',
  'sgt':      'Sergeant',
  'fr':       'Father',
  'sr':       'Sister',
  'sis':      'Sister',
  'bro':      'Brother',
  'hon':      'Honorable',
  'maj':      'Major',
  'lt':       'Lieutenant',
  'prof':     'Professor',
  'st':       'Street',
  'sts':      'Streets',
  'ave':      'Avenue',
  'blvd':     'Boulevard',
  'rd':       'Road',
  'drv':      'Drive',
  'hwy':      'Highway',
  'ln':       'Lane',
  'pl':       'Place',
  'ct':       'Court',
  'cir':      'Circle',
  'ext':      'Extension',
  'cor':      'Corner',
  'brgy':     'Barangay',
  'bgy':      'Barangay',
  'bgry':     'Barangay',
  'poblacion':'Poblacion',
  'pob':      'Poblacion',
  'subd':     'Subdivision',
  'subdv':    'Subdivision',
  'vill':     'Village',
  'vlg':      'Village',
  'cmpd':     'Compound',
  'bldg':     'Building',
  'flr':      'Floor',
  'rm':       'Room',
  'phs':      'Phase',
  'ph':       'Phase',
  'blk':      'Block',
  'nat':      'National',
  'natl':     'National',
  'mt':       'Mount',
  'mtn':      'Mountain',
  'sto':      'Santo',
  'sta':      'Santa',
  'neg':      'Negros',
  'occ':      'Occidental',
  'or':       'Oriental',
};

const DUMAGUETE_LANDMARKS = {
  'locsin':           'Doctor V. Locsin Street, Dumaguete City',
  'dr v locsin':      'Doctor V. Locsin Street, Dumaguete City',
  'dr locsin':        'Doctor V. Locsin Street, Dumaguete City',
  'silliman':         'Silliman Avenue, Dumaguete City',
  'silliman ave':     'Silliman Avenue, Dumaguete City',
  'perdices':         'Perdices Street, Dumaguete City',
  'cervantes':        'Cervantes Street, Dumaguete City',
  'san jose':         'San Jose Street, Dumaguete City',
  'san juan':         'San Juan Street, Dumaguete City',
  'pinili':           'Pinili Street, Dumaguete City',
  'real':             'Real Street, Dumaguete City',
  'rizal blvd':       'Rizal Boulevard, Dumaguete City',
  'rizal boulevard':  'Rizal Boulevard, Dumaguete City',
  'colon':            'Colon Street Extension, Dumaguete City',
  'santa rosa':       'Santa Rosa Street, Dumaguete City',
  'aldecoa':          'Aldecoa Drive, Dumaguete City',
  'calindagan':       'Barangay Calindagan, Dumaguete City',
  'bantayan':         'Barangay Bantayan, Dumaguete City',
  'bagacay':          'Barangay Bagacay, Dumaguete City',
  'taclobo':          'Barangay Taclobo, Dumaguete City',
  'candau-ay':        'Barangay Candau-ay, Dumaguete City',
  'daro':             'Barangay Daro, Dumaguete City',
  'piapi':            'Barangay Piapi, Dumaguete City',
  'bajumpandan':      'Barangay Bajumpandan, Dumaguete City',
  'batinguel':        'Barangay Batinguel, Dumaguete City',
  'junob':            'Barangay Junob, Dumaguete City',
  'motong':           'Barangay Motong, Dumaguete City',
  'tabuc-tubig':      'Barangay Tabuc-tubig, Dumaguete City',
  'robinsons':        'Robinsons Place Dumaguete',
  'lee plaza':        'Lee Plaza, Dumaguete City',
  'cfc':              'City Finance Center, Dumaguete City',
  'norsu':            'Negros Oriental State University, Dumaguete City',
  'foundation':       'Foundation University, Dumaguete City',
};

/**
 * Extract house number from various Filipino address formats.
 * Handles: "183", "183-A", "Block 5 Lot 12", "Unit 3B", etc.
 */
const extractHouseNumber = (input) => {
  if (!input) return null;
  const text = String(input).trim();

  const patterns = [
    /^((?:block|blk|lot|lt|unit|rm|room)[\s.\-]*\d[\d\-A-Za-z\s]*)/i,
    /^(\d+[A-Za-z]?)\b/i,
    /^(\d+)[-\s]*([A-Za-z])?\b/i,
    /^([A-Z]\d+)/i,
    /^(\d+)$/i,
  ];

  for (const pattern of patterns) {
    const match = text.match(pattern);
    if (match && match[1]) return match[1].trim();
  }

  const simpleMatch = text.match(/^(\d+[-\d\w]*)/);
  return simpleMatch ? simpleMatch[1].trim() : null;
};

/**
 * Parse a Philippine-style address string into components.
 */
const parsePhilippineAddress = (raw) => {
  if (!raw) return null;

  const parts = raw.split(',').map(s => s.trim()).filter(Boolean);

  let houseNumber = '';
  let street = '';
  let barangay = '';
  let city = '';
  let province = '';

  const knownBarangays = [
    'taclobo', 'daro', 'bantayan', 'calindagan', 'piapi', 'bagacay',
    'candau-ay', 'bajumpandan', 'batinguel', 'junob', 'motong', 'tabuc-tubig',
  ];

  const houseMatch = parts[0]?.match(/^(\d[\d\-A-Za-z]*)\s*(.*)$/);
  const blockLotMatch = parts[0]?.match(/^((?:block|blk|lot|lt|unit|rm|room)[\s.\-]*\d[\d\-A-Za-z\s]*)/i);

  if (blockLotMatch) {
    houseNumber = blockLotMatch[1].trim();
    const remainder = parts[0].slice(blockLotMatch[0].length).trim();
    if (remainder) street = remainder;
  } else if (houseMatch && houseMatch[1].length <= 6) {
    houseNumber = houseMatch[1];
    if (houseMatch[2]) street = houseMatch[2];
  }

  const remaining = street ? parts.slice(1) : parts.slice(houseNumber ? 1 : 0);

  for (const part of remaining) {
    const lower = part.toLowerCase().replace(/\./g, '');

    if (/^(brgy|bgy|bgry|barangay)\b/i.test(lower)) {
      barangay = part;
      continue;
    }
    if (/city$/i.test(lower) || /^dumaguete/i.test(lower) ||
        /^(cebu|manila|davao|bacolod|sibulan|tanjay|bais|bayawan|guihulngan|valencia)/i.test(lower)) {
      city = part;
      continue;
    }
    if (/^negros/i.test(lower) || /oriental$/i.test(lower) ||
        /occidental$/i.test(lower) || /^(region|province)/i.test(lower)) {
      province = part;
      continue;
    }
    if (!street) {
      street = part;
    } else if (!barangay) {
      if (knownBarangays.some(b => lower.includes(b))) {
        barangay = part;
      }
    }
  }

  if (!street && !houseNumber && parts.length > 0) {
    street = parts[0];
  }

  return {
    houseNumber: houseNumber.trim(),
    street: street.trim(),
    barangay: barangay.trim(),
    city: city.trim() || 'Dumaguete City',
    province: province.trim() || 'Negros Oriental',
    raw,
  };
};

const expandAbbreviations = (text) => {
  if (!text) return '';

  const cleaned = text
    .replace(/\b([A-Za-z])\.\s*/g, '$1 ')
    .replace(/\./g, '')
    .replace(/\s+/g, ' ')
    .trim();

  return cleaned
    .split(' ')
    .map((tok) => {
      const lower = tok.toLowerCase();
      if (tok.length === 1) return tok;
      return ABBREVIATIONS[lower] || tok;
    })
    .join(' ');
};

const composeAddress = ({ houseNumber, street, barangay, city, province }) => {
  const parts = [];
  if (houseNumber) parts.push(houseNumber);
  if (street)      parts.push(street);
  if (barangay)    parts.push(barangay);
  if (city)        parts.push(city);
  if (province)    parts.push(province);
  return parts.join(', ');
};

const matchLocalLandmark = (query) => {
  const lower = query.toLowerCase()
    .replace(/\./g, '')
    .replace(/,/g, '')
    .replace(/\s+/g, ' ')
    .trim();

  if (DUMAGUETE_LANDMARKS[lower]) return DUMAGUETE_LANDMARKS[lower];

  for (const [key, value] of Object.entries(DUMAGUETE_LANDMARKS)) {
    if (lower.includes(key)) return value;
  }

  return null;
};


// ═══════════════════════════════════════════════
// LOCATION SERVICE
// ═══════════════════════════════════════════════

export const LocationService = {

  // ========== PERMISSIONS ==========
  async requestPermissions() {
    try {
      if (Platform.OS === 'web') {
        return new Promise((resolve) => {
          resolve({ foreground: 'granted', background: 'undetermined' });
        });
      }

      const { status: foregroundStatus } = await Location.requestForegroundPermissionsAsync();

      if (foregroundStatus !== 'granted') {
        throw new Error('Location permission denied');
      }

      let backgroundStatus = 'undetermined';
      try {
        const { status } = await Location.requestBackgroundPermissionsAsync();
        backgroundStatus = status;
      } catch (error) {
        console.warn('Background permission not available:', error.message);
      }

      return { foreground: foregroundStatus, background: backgroundStatus };
    } catch (error) {
      console.error('Permission error:', error);
      throw error;
    }
  },


  // ========== LOCATION GETTERS ==========
  async getCurrentLocation(options = {}) {
    try {
      if (Platform.OS === 'web') {
        return await getWebLocation();
      }

      const { status } = await Location.getForegroundPermissionsAsync();

      if (status !== 'granted') {
        await this.requestPermissions();
      }

      const location = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
        timeout: 15000,
        maximumAge: 30000,
        ...options,
      });

      const locationData = {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
        accuracy: location.coords.accuracy,
        altitude: location.coords.altitude,
        speed: location.coords.speed,
        heading: location.coords.heading,
        timestamp: location.timestamp,
        source: 'native',
      };

      await this.storeLocation(locationData);
      return locationData;
    } catch (error) {
      console.error('Location error:', error.message);

      const lastLocation = await this.getLastKnownLocation();
      if (lastLocation) {
        return { ...lastLocation, source: 'cached', isFallback: true };
      }

      return await this.getDefaultLocation();
    }
  },

  async getLastKnownLocation() {
    try {
      const stored = await AsyncStorage.getItem(LAST_KNOWN_LOCATION_KEY);
      return stored ? JSON.parse(stored) : null;
    } catch (_error) {
      return null;
    }
  },

  async storeLocation(location) {
    try {
      await AsyncStorage.setItem(LAST_KNOWN_LOCATION_KEY, JSON.stringify(location));
    } catch (error) {
      console.error('Storage error:', error);
    }
  },

  async getDefaultLocation() {
    return {
      latitude: 9.3068,
      longitude: 123.3054,
      accuracy: 10000,
      timestamp: Date.now(),
      source: 'default',
      isFallback: true,
    };
  },


  // ========== LOCATION TRACKING ==========
  async startLocationTracking(orderId) {
    if (Platform.OS === 'web' || !TaskManager) {
      console.warn('Background location tracking not available on web');
      return false;
    }

    try {
      await Location.startLocationUpdatesAsync('washbox-location-tracking', {
        accuracy: Location.Accuracy.High,
        timeInterval: 10000,
        distanceInterval: 10,
        foregroundService: {
          notificationTitle: 'WashBox Delivery Tracking',
          notificationBody: 'Your delivery is being tracked',
          notificationColor: '#0EA5E9',
        },
        pausesUpdatesAutomatically: false,
      });
      return true;
    } catch (error) {
      console.error('Tracking error:', error);
      return false;
    }
  },

  async stopLocationTracking() {
    if (Platform.OS === 'web' || !TaskManager) return false;

    try {
      await Location.stopLocationUpdatesAsync('washbox-location-tracking');
      return true;
    } catch (error) {
      console.error('Stop tracking error:', error);
      return false;
    }
  },


  // ═══════════════════════════════════════════════
  // GEOCODING & SEARCH
  // ═══════════════════════════════════════════════

  async searchLocations(query, options = {}) {
    if (!query || query.trim().length < 2) {
      return [];
    }

    const limit = options.limit || 10;
    const parsed = parsePhilippineAddress(query);
    const expanded = expandAbbreviations(query);

    let viewboxParams = '';
    const center = options.center || (await this.getLastKnownLocation()) || { latitude: 9.3068, longitude: 123.3054 };
    if (center && center.latitude && center.longitude) {
      const delta = options.viewboxDelta || 0.12;
      viewboxParams = `&viewbox=${center.longitude - delta},${center.latitude - delta},${center.longitude + delta},${center.latitude + delta}&bounded=1`;
    }

    const landmarkMatch = matchLocalLandmark(query);

    const freeSearch = async (q, extraParams = '') => {
      const params = new URLSearchParams({
        format: 'json',
        q,
        limit,
        addressdetails: 1,
        'accept-language': 'en',
        countrycodes: options.countryCodes || 'ph',
      });

      const url = `https://nominatim.openstreetmap.org/search?${params.toString()}${extraParams || viewboxParams}`;

      try {
        const resp = await fetch(url, {
          headers: { 'User-Agent': 'WashBox Mobile App/1.0.0' },
          signal: options.signal,
        });
        if (!resp.ok) return [];
        const data = await resp.json();
        return Array.isArray(data) ? data : [];
      } catch (err) {
        if (err.name === 'AbortError') throw err;
        console.error('Nominatim fetch error:', err);
        return [];
      }
    };

    const structuredSearch = async (street, city, extras = {}) => {
      const params = new URLSearchParams({
        format: 'json',
        street: street || '',
        city: city || 'Dumaguete',
        country: 'Philippines',
        limit,
        addressdetails: 1,
        'accept-language': 'en',
        ...extras,
      });

      const url = `https://nominatim.openstreetmap.org/search?${params.toString()}`;

      try {
        const resp = await fetch(url, {
          headers: { 'User-Agent': 'WashBox Mobile App/1.0.0' },
          signal: options.signal,
        });
        if (!resp.ok) return [];
        const data = await resp.json();
        return Array.isArray(data) ? data : [];
      } catch (err) {
        if (err.name === 'AbortError') throw err;
        console.error('Nominatim structured error:', err);
        return [];
      }
    };

    const photonSearch = async (q) => {
      try {
        const params = new URLSearchParams({ q, limit, lang: 'en' });
        if (center?.latitude && center?.longitude) {
          params.set('lat', center.latitude);
          params.set('lon', center.longitude);
        }

        const url = `https://photon.komoot.io/api/?${params.toString()}`;

        const resp = await fetch(url, {
          headers: { 'User-Agent': 'WashBox Mobile App/1.0.0' },
          signal: options.signal,
        });
        if (!resp.ok) return [];

        const data = await resp.json();
        if (!data?.features?.length) return [];

        return data.features.map((f) => ({
          place_id: f.properties.osm_id || `photon_${f.geometry.coordinates.join('_')}`,
          display_name: [f.properties.name, f.properties.street, f.properties.city, f.properties.state, f.properties.country].filter(Boolean).join(', '),
          lat: f.geometry.coordinates[1],
          lon: f.geometry.coordinates[0],
          address: {
            house_number: f.properties.housenumber,
            road: f.properties.street,
            city: f.properties.city || f.properties.town || f.properties.village,
            state: f.properties.state,
            country: f.properties.country,
          },
          type: f.properties.osm_value || 'place',
        }));
      } catch (err) {
        if (err.name === 'AbortError') throw err;
        console.error('Photon error:', err);
        return [];
      }
    };

    const dropHouseNumber = (s) => {
      const houseNum = extractHouseNumber(s);
      return houseNum ? s.replace(houseNum, '').replace(/^\s*[,\-]\s*/, '').trim() : s;
    };
    const removeTrailingCity = (s) => s.replace(/\bcity\.?$/i, '').trim();

    let results = [];

    if (landmarkMatch) {
      results = await freeSearch(landmarkMatch);
      if (results.length) return this._formatResults(results, parsed);
    }

    if (parsed?.street) {
      const expandedStreet = expandAbbreviations(parsed.street);
      const cityName = parsed.city.replace(/\bcity\b/i, '').trim() || 'Dumaguete';

      results = await structuredSearch(expandedStreet, cityName);
      if (results.length) return this._formatResults(results, parsed);

      const streetNoNum = dropHouseNumber(expandedStreet);
      if (streetNoNum !== expandedStreet) {
        results = await structuredSearch(streetNoNum, cityName);
        if (results.length) return this._formatResults(results, parsed);
      }
    }

    results = await freeSearch(expanded);
    if (results.length) return this._formatResults(results, parsed);

    const expandedNoNum = dropHouseNumber(expanded);
    if (expandedNoNum !== expanded) {
      results = await freeSearch(expandedNoNum);
      if (results.length) return this._formatResults(results, parsed);
    }

    const withoutCity = removeTrailingCity(expandedNoNum);
    if (withoutCity !== expandedNoNum && withoutCity.length >= 3) {
      results = await freeSearch(withoutCity);
      if (results.length) return this._formatResults(results, parsed);
    }

    const tokens = expanded.split(' ');
    if (tokens.length >= 3) {
      results = await freeSearch(tokens.slice(-3).join(' '));
      if (results.length) return this._formatResults(results, parsed);
    }
    if (tokens.length >= 2) {
      results = await freeSearch(tokens.slice(-2).join(' '));
      if (results.length) return this._formatResults(results, parsed);
    }

    results = await freeSearch(dropHouseNumber(query.trim()), '');
    if (results.length) return this._formatResults(results, parsed);

    results = await photonSearch(expanded);
    if (results.length) return this._formatResults(results, parsed);

    results = await photonSearch(query.trim());
    if (results.length) return this._formatResults(results, parsed);

    console.debug('[WashBox] All geocoding attempts failed for:', query);
    return [];
  },

  _formatResults(rawResults, parsed) {
    return rawResults.map((item) => {
      const geocodeAddress = {
        house_number: item.address?.house_number,
        road: item.address?.road,
        barangay: item.address?.suburb || item.address?.village,
        city: item.address?.city || item.address?.town || item.address?.municipality,
        state: item.address?.state,
        country: item.address?.country,
      };

      // Always preserve user-typed house number
      if (parsed?.houseNumber) {
        geocodeAddress.house_number = parsed.houseNumber;
      }

      const displayName = this._buildDisplayName(geocodeAddress, parsed, item.display_name);

      return {
        id: item.place_id,
        name: displayName,
        displayName,
        coordinate: {
          latitude: parseFloat(item.lat),
          longitude: parseFloat(item.lon),
        },
        address: geocodeAddress,
        parsedAddress: parsed,
        type: item.type,
      };
    });
  },

  _buildDisplayName(address, parsed, fallback) {
    const parts = [];

    const houseNum = parsed?.houseNumber || address.house_number;
    if (houseNum) parts.push(houseNum);

    if (address.road) {
      parts.push(address.road);
    } else if (parsed?.street) {
      parts.push(expandAbbreviations(parsed.street));
    }

    const brgy = address.barangay || parsed?.barangay;
    if (brgy) parts.push(brgy);

    const city = address.city || parsed?.city;
    if (city) {
      let cleanCity = city;
      if (cleanCity.toLowerCase() === 'dumaguete') cleanCity = 'Dumaguete City';
      parts.push(cleanCity);
    }

    if (parts.length >= 2) return parts.join(', ');

    return fallback || parts.join(', ') || 'Unknown location';
  },


  // ========== MANUAL ADDRESS ENTRY ==========
  async createManualLocation({ houseNumber, street, barangay, city, province, landmark }) {
    // Extract house number if embedded in street
    let extractedHouseNumber = houseNumber;
    let cleanStreet = street;

    if (!extractedHouseNumber && street) {
      extractedHouseNumber = extractHouseNumber(street);
      if (extractedHouseNumber) {
        cleanStreet = street.replace(extractedHouseNumber, '').replace(/^\s*[,\-]\s*/, '').trim();
      }
    }

    const composed = composeAddress({
      houseNumber: extractedHouseNumber,
      street: expandAbbreviations(cleanStreet || ''),
      barangay: expandAbbreviations(barangay || ''),
      city: city || 'Dumaguete City',
      province: province || 'Negros Oriental',
    });

    let coordinate = null;

    const streetExpanded = expandAbbreviations(cleanStreet || '');
    const cityClean = (city || 'Dumaguete City').replace(/\bcity\b/i, '').trim();

    try {
      const searchQuery = extractedHouseNumber
        ? `${extractedHouseNumber} ${streetExpanded}, ${cityClean}`
        : `${streetExpanded}, ${cityClean}`;

      const results = await this.searchLocations(searchQuery, { limit: 1 });
      if (results.length > 0) coordinate = results[0].coordinate;
    } catch (e) {
      console.debug('[WashBox] Manual geocode failed:', e);
    }

    if (!coordinate && barangay) {
      try {
        const results = await this.searchLocations(
          `${expandAbbreviations(barangay)}, ${cityClean}`,
          { limit: 1 }
        );
        if (results.length > 0) coordinate = results[0].coordinate;
      } catch (e) {
        console.debug('[WashBox] Barangay geocode failed:', e);
      }
    }

    if (!coordinate) {
      const current = await this.getLastKnownLocation();
      coordinate = current || { latitude: 9.3068, longitude: 123.3054 };
    }

    return {
      id: `manual_${Date.now()}`,
      name: composed,
      displayName: composed,
      coordinate,
      address: {
        house_number: extractedHouseNumber,
        road: streetExpanded,
        barangay: expandAbbreviations(barangay || ''),
        city: city || 'Dumaguete City',
        state: province || 'Negros Oriental',
        country: 'Philippines',
        landmark: landmark || '',
      },
      type: 'manual',
      isManual: true,
    };
  },

  parseAddress(raw) {
    return parsePhilippineAddress(raw);
  },

  composeAddress(parts) {
    return composeAddress(parts);
  },

  extractHouseNumber(text) {
    return extractHouseNumber(text);
  },


  // ========== REVERSE GEOCODING ==========
  async reverseGeocode(coordinate) {
    const params = new URLSearchParams({
      format: 'json',
      lat: coordinate.latitude,
      lon: coordinate.longitude,
      zoom: 16,  // 16 = barangay precision; 18 returns sitio-level noise like "Santan"
      addressdetails: 1,
      'accept-language': 'en',
    });

    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?${params.toString()}`,
        { headers: { 'User-Agent': 'WashBox Mobile App/1.0.0' } }
      );

      if (!response.ok) throw new Error('Reverse geocode failed');

      const data = await response.json();
      const a = data.address || {};

      const cleanAddress = this._buildCleanAddress(a, coordinate);

      return {
        address: cleanAddress,
        components: a,
        coordinate: {
          latitude: parseFloat(data.lat),
          longitude: parseFloat(data.lon),
        },
      };
    } catch (error) {
      console.error('Reverse geocode error:', error);
      throw error;
    }
  },

  /**
   * Build a clean Filipino address from Nominatim address components.
   *
   * Nominatim hierarchy for PH:
   *   house_number
   *   road              → keep
   *   quarter / hamlet  → SKIP (sitio level, e.g. "Santan", "Santa")
   *   neighbourhood     → SKIP (also sitio-level in PH OSM data)
   *   suburb            → keep (barangay level, e.g. "Taclobo")
   *   city              → keep
   *
   * zoom: 16 prevents Nominatim from returning sitio components at all.
   * This method is a second layer of protection.
   */
  _buildCleanAddress(a, coordinate) {
    const parts = [];

    if (a.house_number) parts.push(a.house_number);
    if (a.road)         parts.push(a.road);

    // Only use suburb — intentionally skipping quarter, hamlet, neighbourhood
    // which are all sitio-level in Philippine OSM data.
    const barangay = a.suburb || a.village;
    if (barangay) parts.push(barangay);

    const city = a.city || a.town || a.municipality;
    if (city) {
      let cleanCity = city;
      if (cleanCity.toLowerCase() === 'dumaguete') cleanCity = 'Dumaguete City';
      parts.push(cleanCity);
    }

    if (parts.length >= 2) return parts.join(', ');

    if (coordinate) {
      return `${coordinate.latitude.toFixed(4)}, ${coordinate.longitude.toFixed(4)}`;
    }

    return 'Unknown address';
  },

  async getAddressFromCoordinate(coordinate) {
    try {
      const result = await this.reverseGeocode(coordinate);
      return result.address;
    } catch (error) {
      console.error('Address error:', error);
      return `${coordinate.latitude.toFixed(4)}, ${coordinate.longitude.toFixed(4)}`;
    }
  },


  // ========== DISTANCE CALCULATIONS ==========
  calculateDistance(coord1, coord2) {
    const R = 6371e3;
    const phi1 = (coord1.latitude * Math.PI) / 180;
    const phi2 = (coord2.latitude * Math.PI) / 180;
    const deltaPhi = ((coord2.latitude - coord1.latitude) * Math.PI) / 180;
    const deltaLambda = ((coord2.longitude - coord1.longitude) * Math.PI) / 180;

    const a =
      Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
      Math.cos(phi1) * Math.cos(phi2) *
      Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
  },

  formatDistance(meters) {
    if (meters < 1000) return `${Math.round(meters)} m`;
    return `${(meters / 1000).toFixed(1)} km`;
  },

  isWithinDeliveryRadius(userLocation, pickupLocation, radiusMeters = 5000) {
    const distance = this.calculateDistance(userLocation, pickupLocation);
    return {
      isWithinRadius: distance <= radiusMeters,
      distance,
      formattedDistance: this.formatDistance(distance),
    };
  },


  // ========== MISC UTILITIES ==========
  async openLocationSettings() {
    if (Platform.OS === 'ios') {
      await Linking.openURL('app-settings:');
    } else if (Platform.OS === 'android') {
      await Linking.openSettings();
    } else {
      Alert.alert(
        'Location Settings',
        'Please enable location permissions in your browser settings.',
        [{ text: 'OK' }]
      );
    }
  },

  // ========== DEV UTILITY ==========
  testAddressParser() {
    const testCases = [
      '183, Dr. V. Locsin Street, Dumaguete City',
      '183 Dr. V. Locsin St, Dumaguete',
      'Dr. V. Locsin Street, Dumaguete City',
      'Brgy. Calindagan, Dumaguete City',
      'Taclobo, Dumaguete City',
    ];

    console.log('=== WashBox Address Parser Test ===');
    testCases.forEach((input) => {
      const parsed = parsePhilippineAddress(input);
      const composed = composeAddress(parsed);
      console.log(`Input:    "${input}"`);
      console.log(`Composed: "${composed}"`);
      console.log('---');
    });
  },
};