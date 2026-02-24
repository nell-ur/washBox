import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LocationService } from '../services/locationService';

export const useLocationStore = create(
  persist(
    (set, get) => ({
      // State
      pickupLocation: null,
      deliveryLocation: null,
      userLocation: null,
      recentLocations: [],
      selectedAddress: null,
      
      // Actions
      setPickupLocation: (location) => {
        set({ pickupLocation: location });
        
        // Add to recent locations
        if (location) {
          get().addRecentLocation(location);
        }
      },
      
      setDeliveryLocation: (location) => {
        set({ deliveryLocation: location });
        
        // Add to recent locations
        if (location) {
          get().addRecentLocation(location);
        }
      },
      
      setUserLocation: async () => {
        try {
          const location = await LocationService.getCurrentLocation();
          const address = await LocationService.getAddressFromCoordinate(location);
          
          set({ 
            userLocation: location,
            selectedAddress: {
              ...location,
              address,
              name: 'Current Location',
              type: 'current',
            }
          });
        } catch (error) {
          console.error('Error setting user location:', error);
        }
      },
      
      addRecentLocation: (location) => {
        set((state) => {
          // Remove if already exists
          const filtered = state.recentLocations.filter(
            loc => loc.address !== location.address
          );
          
          // Add to beginning
          const updated = [location, ...filtered.slice(0, 9)];
          
          return { recentLocations: updated };
        });
      },
      
      clearRecentLocations: () => {
        set({ recentLocations: [] });
      },
      
      clearAllLocations: () => {
        set({
          pickupLocation: null,
          deliveryLocation: null,
          selectedAddress: null,
        });
      },
      
      swapLocations: () => {
        set((state) => ({
          pickupLocation: state.deliveryLocation,
          deliveryLocation: state.pickupLocation,
        }));
      },
      
      // Getters
      getFormattedLocations: () => {
        const state = get();
        return {
          pickup: state.pickupLocation ? {
            ...state.pickupLocation,
            title: 'Pickup',
            color: '#4CAF50',
          } : null,
          delivery: state.deliveryLocation ? {
            ...state.deliveryLocation,
            title: 'Delivery',
            color: '#FF9800',
          } : null,
          user: state.userLocation ? {
            ...state.userLocation,
            title: 'Your Location',
            color: '#2196F3',
          } : null,
        };
      },
      
      // Validation
      isValidForOrder: () => {
        const state = get();
        return !!state.pickupLocation && !!state.deliveryLocation;
      },
      
      // Calculate distance between pickup and delivery
      getDistance: () => {
        const state = get();
        if (!state.pickupLocation || !state.deliveryLocation) {
          return null;
        }
        
        return LocationService.calculateDistance(
          state.pickupLocation,
          state.deliveryLocation
        );
      },
    }),
    {
      name: 'washbox-location-store',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({
        recentLocations: state.recentLocations,
      }),
    }
  )
);