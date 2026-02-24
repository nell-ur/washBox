// app/(auth)/_layout.js
import { Stack } from 'expo-router';
import { COLORS } from '../../constants/config';

export default function AuthLayout() {
  return (
    <Stack
      screenOptions={{
        headerShown: false,
        // Using the white color from your config for consistency
        contentStyle: { backgroundColor: COLORS.white }, 
        animation: 'fade', // Smooth transition between login and register
      }}
    >
      <Stack.Screen name="login" />
      <Stack.Screen name="register" />
      <Stack.Screen name="forgot-password" />
    </Stack>
  );
}