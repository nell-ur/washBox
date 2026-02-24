import { useColorScheme } from 'react-native';

export function useThemeColor(
  colors,
  colorName
) {
  const theme = useColorScheme() ?? 'light';
  return colors[theme] || colors.light || '#000';
}