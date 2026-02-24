/**
 * utils/notification.js
 *
 * WashBox Push Notification Utility
 * Handles FCM token registration, backend sync, and notification listeners.
 *
 * NOTE: This file lives inside app/utils/ so Expo Router requires a
 * default export. The export below satisfies that requirement.
 */

import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { API_BASE_URL } from '../constants/config';

// ─────────────────────────────────────────────────────────────
// Detect Expo Go — FCM remote notifications not supported there
// ─────────────────────────────────────────────────────────────
const isExpoGo = Constants.appOwnership === 'expo';

// Dynamically import expo-notifications ONLY when NOT in Expo Go
// This prevents the import error from occurring
let Notifications = null;
let notificationsModule = null;

if (!isExpoGo) {
    // Use require instead of import to avoid hoisting issues
    notificationsModule = require('expo-notifications');
    Notifications = notificationsModule;
    
    // Configure foreground notification appearance
    Notifications.setNotificationHandler({
        handleNotification: async () => ({
            shouldShowAlert: true,
            shouldPlaySound: true,
            shouldSetBadge: true,
        }),
    });
} else {
    console.log('[FCM] Running in Expo Go - push notifications disabled');
}

/**
 * Ask permission + get FCM token + save to backend.
 * Call this once after the customer logs in successfully.
 *
 * @param {string} authToken  - Bearer token from login response (data.data.token)
 * @returns {string|null}     - The FCM token, or null if registration failed
 */
export async function registerForPushNotifications(authToken) {
    // FCM not supported in Expo Go — requires a development build
    if (isExpoGo || !Notifications) {
        console.log('[FCM] Skipping — Expo Go does not support FCM. Use a dev build.');
        return null;
    }

    // Only works on real devices, not simulators
    if (!Device.isDevice) {
        console.log('[FCM] Skipping — not a physical device (simulator/emulator)');
        return null;
    }

    // Android needs a notification channel
    if (Platform.OS === 'android') {
        try {
            await Notifications.setNotificationChannelAsync('washbox-default', {
                name: 'WashBox Notifications',
                importance: Notifications.AndroidImportance.MAX,
                vibrationPattern: [0, 250, 250, 250],
                lightColor: '#0EA5E9',
                sound: true,
            });
        } catch (channelError) {
            console.warn('[FCM] Failed to set notification channel:', channelError);
        }
    }

    // Check / request permission
    let finalStatus = 'denied';
    try {
        const { status: existingStatus } = await Notifications.getPermissionsAsync();
        finalStatus = existingStatus;

        if (existingStatus !== 'granted') {
            const { status } = await Notifications.requestPermissionsAsync();
            finalStatus = status;
        }
    } catch (permError) {
        console.error('[FCM] Permission error:', permError);
        return null;
    }

    if (finalStatus !== 'granted') {
        console.log('[FCM] Permission denied by user');
        return null;
    }

    // Get the raw FCM device token
    let fcmToken = null;
    try {
        const tokenData = await Notifications.getDevicePushTokenAsync();
        fcmToken = tokenData.data;
        console.log('[FCM] Token obtained:', fcmToken?.substring(0, 20) + '...');
    } catch (err) {
        console.error('[FCM] Failed to get device token:', err);
        return null;
    }

    if (!fcmToken) {
        console.warn('[FCM] Token is empty');
        return null;
    }

    // Save to Laravel backend
    if (authToken) {
        await saveFcmTokenToBackend(fcmToken, authToken);
    } else {
        console.warn('[FCM] No auth token provided — cannot save FCM token to backend');
    }

    return fcmToken;
}

/**
 * Save the FCM token to Laravel so the backend can send notifications.
 * Called automatically by registerForPushNotifications().
 */
async function saveFcmTokenToBackend(fcmToken, authToken) {
    try {
        const response = await fetch(`${API_BASE_URL}/v1/customer/fcm-token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`,
            },
            body: JSON.stringify({ fcm_token: fcmToken }),
        });

        if (response.ok) {
            console.log('[FCM] Token saved to backend successfully');
        } else {
            const body = await response.text();
            console.warn('[FCM] Backend rejected token:', response.status, body);
        }
    } catch (err) {
        console.error('[FCM] Failed to save token to backend:', err);
    }
}

/**
 * Clear the FCM token from the backend on logout.
 * Call this in your AuthContext logout function.
 *
 * @param {string} authToken  - Laravel Bearer token
 */
export async function clearFcmTokenOnLogout(authToken) {
    if (!authToken) return;

    try {
        await fetch(`${API_BASE_URL}/v1/customer/fcm-token`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`,
            },
        });
        console.log('[FCM] Token cleared on logout');
    } catch (err) {
        console.error('[FCM] Failed to clear token on logout:', err);
    }
}

/**
 * Set up listeners for notification tap events.
 * Call this once in your root _layout.js.
 *
 * @param {object} router  - Expo Router's router object
 * @returns {function}     - Cleanup function (call on unmount)
 */
export function setupNotificationListeners(router) {
    // Return empty cleanup function if notifications not available
    if (!Notifications || isExpoGo) {
        console.log('[FCM] Listeners not set up — running in Expo Go');
        return () => {};
    }

    // Fired when user TAPS a notification (app in background or closed)
    const tapSubscription = Notifications.addNotificationResponseReceivedListener(response => {
        const data = response.notification.request.content.data;
        console.log('[FCM] Notification tapped:', data);

        if (data?.laundry_id) {
            // Small delay to ensure navigation is ready
            setTimeout(() => {
                router.push(`/laundries/${data.laundry_id}`);
            }, 100);
        }
    });

    // Fired when notification arrives while app is OPEN
    const foregroundSubscription = Notifications.addNotificationReceivedListener(notification => {
        const data = notification.request.content.data;
        console.log('[FCM] Notification received in foreground, type:', data?.type);
    });

    return () => {
        try {
            tapSubscription.remove();
            foregroundSubscription.remove();
        } catch (e) {
            console.log('[FCM] Error removing listeners:', e);
        }
    };
}

// Add a default export to satisfy Expo Router (required for files in app/utils)
export default {
    registerForPushNotifications,
    clearFcmTokenOnLogout,
    setupNotificationListeners
};