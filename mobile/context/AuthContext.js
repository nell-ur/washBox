import React, { createContext, useState, useContext, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { STORAGE_KEYS } from '../constants/config';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [hasToken, setHasToken] = useState(false);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    const loadToken = async () => {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      setHasToken(!!token);
      setIsReady(true);
    };
    loadToken();
  }, []);

  const login = async (token, customerData) => {
    await AsyncStorage.setItem(STORAGE_KEYS.TOKEN, token);
    await AsyncStorage.setItem(STORAGE_KEYS.CUSTOMER, JSON.stringify(customerData));
    setHasToken(true);
  };

  const logout = async () => {
    await AsyncStorage.removeItem(STORAGE_KEYS.TOKEN);
    await AsyncStorage.removeItem(STORAGE_KEYS.CUSTOMER);
    setHasToken(false);
  };

  return (
    <AuthContext.Provider value={{ hasToken, isReady, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);