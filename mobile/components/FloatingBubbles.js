import React, { useEffect, useRef } from 'react';
import { View, StyleSheet, Animated, Dimensions } from 'react-native';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

const COLORS = {
  bubble1: 'rgba(14, 165, 233, 0.08)',
  bubble2: 'rgba(59, 130, 246, 0.06)',
  bubble3: 'rgba(139, 92, 246, 0.05)',
};

// Single Bubble Component
const Bubble = ({ delay, duration, size, startX, color }) => {
  const translateY = useRef(new Animated.Value(SCREEN_HEIGHT)).current;
  const translateX = useRef(new Animated.Value(startX)).current;
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    // Delay before starting
    setTimeout(() => {
      // Fade in and scale up
      Animated.parallel([
        Animated.timing(opacity, {
          toValue: 1,
          duration: 1000,
          useNativeDriver: true,
        }),
        Animated.spring(scale, {
          toValue: 1,
          tension: 20,
          friction: 7,
          useNativeDriver: true,
        }),
      ]).start();

      // Float up continuously
      Animated.loop(
        Animated.parallel([
          Animated.timing(translateY, {
            toValue: -100,
            duration: duration,
            useNativeDriver: true,
          }),
          Animated.sequence([
            Animated.timing(translateX, {
              toValue: startX + 30,
              duration: duration / 2,
              useNativeDriver: true,
            }),
            Animated.timing(translateX, {
              toValue: startX - 30,
              duration: duration / 2,
              useNativeDriver: true,
            }),
          ]),
        ])
      ).start();
    }, delay);
  }, []);

  return (
    <Animated.View
      style={[
        styles.bubble,
        {
          width: size,
          height: size,
          backgroundColor: color,
          opacity,
          transform: [
            { translateY },
            { translateX },
            { scale },
          ],
        },
      ]}
    />
  );
};

// Main Component
export default function FloatingBubbles({ count = 15 }) {
  // Generate random bubbles
  const bubbles = Array.from({ length: count }).map((_, index) => ({
    id: index,
    delay: Math.random() * 5000,
    duration: 15000 + Math.random() * 10000,
    size: 40 + Math.random() * 80,
    startX: Math.random() * SCREEN_WIDTH,
    color: [COLORS.bubble1, COLORS.bubble2, COLORS.bubble3][
      Math.floor(Math.random() * 3)
    ],
  }));

  return (
    <View style={styles.container} pointerEvents="none">
      {bubbles.map((bubble) => (
        <Bubble
          key={bubble.id}
          delay={bubble.delay}
          duration={bubble.duration}
          size={bubble.size}
          startX={bubble.startX}
          color={bubble.color}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    ...StyleSheet.absoluteFillObject,
    overflow: 'hidden',
  },
  bubble: {
    position: 'absolute',
    borderRadius: 999,
    bottom: 0,
  },
});