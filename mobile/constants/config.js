// config.js

// For Android Emulator, use:
 //export const API_BASE_URL = 'http://10.0.2.2:8000/api';

// For iOS Simulator, use:
//export const API_BASE_URL = 'http://localhost:8000/api';

// For Physical Device (replace with your computer's IP):
 //export const API_BASE_URL = 'http://192.168.1.9:8000/api';
export const API_BASE_URL = 'http://washboxlaundry.infinityfreeapp.com/api';

// App Configuration
export const APP_NAME = 'WashBox';
export const APP_VERSION = '1.0.0';

// Colors
export const COLORS = {
  primary: '#3D3B6B',
  primaryLight: '#4F4D8C',
  primaryDark: '#2B2952',
  secondary: '#10B981',
  success: '#10B981',
  warning: '#F59E0B',
  danger: '#EF4444',
  info: '#3B82F6',
  white: '#FFFFFF',
  black: '#000000',
  gray: {
    50: '#F9FAFB',
    100: '#F3F4F6',
    200: '#E5E7EB',
    300: '#D1D5DB',
    400: '#9CA3AF',
    500: '#6B7280',
    600: '#4B5563',
    700: '#374151',
    800: '#1F2937',
    900: '#111827',
  },
};

// API Endpoints
export const ENDPOINTS = {
  // Auth
  LOGIN: '/v1/login',
  REGISTER: '/v1/register',
  LOGOUT: '/v1/logout',
  FORGOT_PASSWORD: '/v1/forgot-password',
  RESET_PASSWORD: '/v1/reset-password',
  
  // User
  USER: '/v1/user',
  PROFILE: '/v1/profile',
  CHANGE_PASSWORD: '/v1/profile/password',
  
  // Branches
  BRANCHES: '/v1/branches',
  BRANCH_NEAREST: '/v1/branches/nearest',
  
  // Services
  SERVICES: '/v1/services',
  
  // Pricing
  PRICING: '/v1/pricing',
  PRICING_CALCULATE: '/v1/pricing/calculate',
  
  // Promotions
  PROMOTIONS: '/v1/promotions',
  PROMOTIONS_ACTIVE: '/v1/promotions/active',
  PROMOTIONS_FEATURED: '/v1/promotions/featured',
  VALIDATE_PROMO: '/v1/promotions/validate-code',
  
  // Orders
  LAUNDRIES: '/v1/laundries',
  LAUNDRY_TRACK: (id) => `/v1/laundries/${id}/track`,
  LAUNDRY_CANCEL: (id) => `/v1/laundries/${id}/cancel`,
  LAUNDRY_RECEIPT: (id) => `/v1/laundries/${id}/receipt`,
  LAUNDRY_RATE: (id) => `/v1/laundries/${id}/rate`,
  
  // Pickups
  PICKUPS: '/v1/pickups',
  
  // Notifications
  NOTIFICATIONS: '/v1/notifications',
  DEVICE_TOKEN: '/v1/device-token',
};

// Storage Keys
export const STORAGE_KEYS = {
  TOKEN: 'token',
  CUSTOMER: 'customer',
  PREFERRED_BRANCH: 'preferred_branch',
  DEVICE_TOKEN: 'device_token',
};

// Validation Rules
export const VALIDATION = {
  PASSWORD_MIN_LENGTH: 8,
  PHONE_MIN_LENGTH: 10,
  PHONE_MAX_LENGTH: 11,
  NAME_MIN_LENGTH: 3,
};

// Order Status
export const LAUNDRY_STATUS = {
  PENDING: 'pending',
  RECEIVED: 'received',
  WASHING: 'washing',
  DRYING: 'drying',
  FOLDING: 'folding',
  READY: 'ready',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
};

// Order Status Colors
export const LAUNDRY_STATUS_COLORS = {
  pending: '#F59E0B',
  received: '#3B82F6',
  washing: '#8B5CF6',
  drying: '#8B5CF6',
  folding: '#8B5CF6',
  ready: '#10B981',
  completed: '#10B981',
  cancelled: '#EF4444',
};

// Order Status Labels
export const LAUNDRY_STATUS_LABELS = {
  pending: 'Pending',
  received: 'Received',
  washing: 'Washing',
  drying: 'Drying',
  folding: 'Folding',
  ready: 'Ready for Pickup',
  completed: 'Completed',
  cancelled: 'Cancelled',
};

export default {
  API_BASE_URL,
  APP_NAME,
  APP_VERSION,
  COLORS,
  ENDPOINTS,
  STORAGE_KEYS,
  VALIDATION,
  LAUNDRY_STATUS,
  LAUNDRY_STATUS_COLORS,
  LAUNDRY_STATUS_LABELS,
};

export const MAP_CONFIG = {
  // Use different tile URLs based on environment
  tileUrl: process.env.NODE_ENV === 'production' 
    ? 'https://your-tile-server/{z}/{x}/{y}.png'
    : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
  
  // Cache tiles locally
  cacheEnabled: true,
  cacheMaxAge: 7 * 24 * 60 * 60 * 1000, // 7 days
  
  // Attribution (required by OSM)
  attributionText: '© OpenStreetMap contributors',
};