// app/_layout.js
import { useEffect } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { Platform } from 'react-native';

// Import web CSS only on web platform
if (Platform.OS === 'web') {
  require('../styles/globals.css');
}

// Prevent splash screen from auto-hiding
SplashScreen.preventAutoHideAsync();

function RootLayoutNav() {
  const { hasToken, isReady } = useAuth();
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => {
    if (!isReady) return;

    const inAuthGroup = segments[0] === '(auth)';

    if (!hasToken && !inAuthGroup) {
      // Force unauthenticated users to login
      router.replace('/(auth)/login');
    } else if (hasToken && inAuthGroup) {
      // Redirect authenticated users away from login/register
      router.replace('/(tabs)');
    }
    
    SplashScreen.hideAsync();
  }, [hasToken, isReady, segments]);

  if (!isReady) return null;

  return (
    <Stack screenOptions={{ 
      headerShown: false,
      contentStyle: {
        backgroundColor: '#0A1128',
      },
      animation: 'fade',
    }}>
      {/* Auth Group - Login/Register */}
      <Stack.Screen name="(auth)" />
      
      {/* Main Tabs - Home, Orders, Profile */}
      <Stack.Screen name="(tabs)" />
      
      {/* Laundry Details - matches app/laundries/[id].js */}
      <Stack.Screen 
        name="laundries/[id]" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      
      {/* Pickups History - matches app/pickups/index.js */}
      <Stack.Screen 
        name="pickups/index" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      
      {/* Notifications - matches app/notifications.js */}
      <Stack.Screen 
        name="notifications" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      
      {/* Laundry Confirm - matches app/laundry-confirm.js */}
      <Stack.Screen 
        name="laundry-confirm" 
        options={{
          presentation: 'modal',
          animation: 'slide_from_bottom',
        }}
      />
      
      {/* Profile Edit - matches app/profile/edit.js */}
      <Stack.Screen 
        name="profile/edit" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      
      {/* Promotions - matches app/promotions/index.js */}
      <Stack.Screen 
        name="promotions/index" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
      
      {/* Ratings - matches app/ratings/index.js */}
      <Stack.Screen 
        name="ratings/index" 
        options={{
          presentation: 'card',
          animation: 'slide_from_right',
        }}
      />
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <RootLayoutNav />
    </AuthProvider>
  );
}