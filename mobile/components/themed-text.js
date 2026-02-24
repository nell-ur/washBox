import React from 'react';
import { Text } from 'react-native';
import { useThemeColor } from '@/hooks/useThemeColor';

export function ThemedText({ style, lightColor, darkColor, ...rest }) {
  const color = useThemeColor({ light: lightColor, dark: darkColor }, 'text');
  
  return <Text style={[{ color }, style]} {...rest} />;
}