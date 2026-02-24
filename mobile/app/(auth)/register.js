import { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Dimensions,
  Image,
  Animated,
} from 'react-native';
import { Link, router } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { Picker } from '@react-native-picker/picker';
import { StatusBar } from 'expo-status-bar';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';
import { LinearGradient } from 'expo-linear-gradient';

const { width, height } = Dimensions.get('window');

export default function RegisterScreen() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    phone: '',
    address: '',
    preferredBranchId: '',
  });
  
  const [branches, setBranches] = useState([]);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingBranches, setLoadingBranches] = useState(true);
  const [errors, setErrors] = useState({});

  // Animation values
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(50)).current;
  const logoScale = useRef(new Animated.Value(0.5)).current;
  const logoPulse = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    fetchBranches();
    
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 800,
        useNativeDriver: true,
      }),
      Animated.spring(slideAnim, {
        toValue: 0,
        tension: 50,
        friction: 7,
        useNativeDriver: true,
      }),
      Animated.spring(logoScale, {
        toValue: 1,
        tension: 40,
        friction: 5,
        useNativeDriver: true,
      }),
    ]).start();

    // Subtle pulse animation
    Animated.loop(
      Animated.sequence([
        Animated.timing(logoPulse, {
          toValue: 1.05,
          duration: 2000,
          useNativeDriver: true,
        }),
        Animated.timing(logoPulse, {
          toValue: 1,
          duration: 2000,
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, []);

  const fetchBranches = async () => {
    try {
      setLoadingBranches(true);
      const response = await fetch(`${API_BASE_URL}/v1/branches`, {
        headers: { 'Accept': 'application/json' }
      });
      
      const json = await response.json(); 
      
      if (json.success && json.data && Array.isArray(json.data.branches)) {
        const branchesList = json.data.branches;
        setBranches(branchesList);
        
        if (branchesList.length > 0) {
          setFormData(prev => ({
            ...prev,
            preferredBranchId: branchesList[0].id,
          }));
        }
      }
    } catch (error) {
      console.error('Error fetching branches:', error);
      setBranches([
        { id: 1, name: 'Sibulan Branch', city: 'Sibulan' },
        { id: 2, name: 'Dumaguete Branch', city: 'Dumaguete' },
      ]);
    } finally {
      setLoadingBranches(false);
    }
  };

  const updateField = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: null }));
    }
  };

  const validate = () => {
    const newErrors = {};
    if (!formData.name.trim()) newErrors.name = 'Full name is required';
    if (!formData.email.trim()) newErrors.email = 'Email is required';
    if (!formData.phone.trim()) newErrors.phone = 'Phone number is required';
    if (formData.password.length < 8) newErrors.password = 'Password must be 8+ characters';
    if (formData.password !== formData.passwordConfirmation) {
      newErrors.passwordConfirmation = 'Passwords do not match';
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleRegister = async () => {
    if (loading || !validate()) return;

    setLoading(true);

    setTimeout(async () => {
      const payload = {
        name: formData.name,
        email: formData.email,
        password: formData.password,
        password_confirmation: formData.passwordConfirmation,
        phone: formData.phone,
        address: formData.address,
        preferred_branch_id: formData.preferredBranchId,
      };

      try {
        const response = await fetch(`${API_BASE_URL}/v1/register`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
          await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);

          Alert.alert(
            'Success', 
            'Account created successfully! Please sign in.', 
            [
              { 
                text: 'Sign In Now', 
                onPress: () => router.replace('/(auth)/login') 
              }
            ],
            { cancelable: false }
          );

          if (Platform.OS === 'web') {
             setTimeout(() => router.replace('/(auth)/login'), 1500);
          }
        } else {
          const errorMsg = data.errors ? Object.values(data.errors).flat().join('\n') : data.message;
          Alert.alert('Registration Failed', errorMsg);
        }
      } catch (error) {
        console.error('Registration error:', error);
        Alert.alert('Error', 'Unable to reach the server. Check your connection.');
      } finally {
        setLoading(false);
      }
    }, 0);
  };

  return (
    <>
      <StatusBar style="light" />
      <LinearGradient
        colors={['#0A0E27', '#1A1F3A', '#0A0E27']}
        style={styles.container}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      >
        {/* Floating Orbs Background */}
        <View style={styles.backgroundOrbs}>
          <LinearGradient
            colors={['rgba(139, 92, 246, 0.15)', 'rgba(236, 72, 153, 0.05)']}
            style={[styles.orb, styles.orb1]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          />
          <LinearGradient
            colors={['rgba(14, 165, 233, 0.15)', 'rgba(59, 130, 246, 0.05)']}
            style={[styles.orb, styles.orb2]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          />
        </View>

        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.keyboardView}
        >
          <ScrollView
            contentContainerStyle={styles.scrollContent}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            <Animated.View 
              style={[
                styles.content,
                {
                  opacity: fadeAnim,
                  transform: [{ translateY: slideAnim }],
                },
              ]}
            >
              {/* Logo Section - Circular Logo */}
              <Animated.View 
                style={[
                  styles.logoSection,
                  {
                    transform: [
                      { scale: Animated.multiply(logoScale, logoPulse) }
                    ],
                  },
                ]}
              >
                <View style={styles.logoContainer}>
                  <Image
                    source={require('../../assets/images/logo.png')}
                    style={styles.logo}
                    resizeMode="contain"
                  />
                </View>
              </Animated.View>

              {/* Header */}
              <View style={styles.headerSection}>
                <Text style={styles.headerTitle}>Create Account</Text>
                <Text style={styles.headerSubtitle}>
                  Join thousands of satisfied customers
                </Text>
              </View>

              {/* Register Form */}
              <View style={styles.formCard}>
                {/* Full Name */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Full Name</Text>
                  <View style={[styles.inputContainer, errors.name && styles.inputError]}>
                    <Ionicons 
                      name="person" 
                      size={20} 
                      color={errors.name ? "#EF4444" : "#8B5CF6"} 
                      style={styles.inputIcon}
                    />
                    <TextInput
                      style={styles.input}
                      placeholder="Juan Dela Cruz"
                      placeholderTextColor="#64748B"
                      value={formData.name}
                      onChangeText={(text) => updateField('name', text)}
                      editable={!loading}
                    />
                  </View>
                  {errors.name && (
                    <View style={styles.errorContainer}>
                      <Ionicons name="alert-circle" size={14} color="#EF4444" />
                      <Text style={styles.errorText}>{errors.name}</Text>
                    </View>
                  )}
                </View>

                {/* Email */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Email Address</Text>
                  <View style={[styles.inputContainer, errors.email && styles.inputError]}>
                    <Ionicons 
                      name="mail" 
                      size={20} 
                      color={errors.email ? "#EF4444" : "#8B5CF6"} 
                      style={styles.inputIcon}
                    />
                    <TextInput
                      style={styles.input}
                      placeholder="your.email@example.com"
                      placeholderTextColor="#64748B"
                      value={formData.email}
                      onChangeText={(text) => updateField('email', text)}
                      keyboardType="email-address"
                      autoCapitalize="none"
                      autoCorrect={false}
                      editable={!loading}
                    />
                  </View>
                  {errors.email && (
                    <View style={styles.errorContainer}>
                      <Ionicons name="alert-circle" size={14} color="#EF4444" />
                      <Text style={styles.errorText}>{errors.email}</Text>
                    </View>
                  )}
                </View>

                {/* Phone */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Phone Number</Text>
                  <View style={[styles.inputContainer, errors.phone && styles.inputError]}>
                    <Ionicons 
                      name="call" 
                      size={20} 
                      color={errors.phone ? "#EF4444" : "#8B5CF6"} 
                      style={styles.inputIcon}
                    />
                    <TextInput
                      style={styles.input}
                      placeholder="09XX XXX XXXX"
                      placeholderTextColor="#64748B"
                      value={formData.phone}
                      onChangeText={(text) => updateField('phone', text)}
                      keyboardType="phone-pad"
                      editable={!loading}
                    />
                  </View>
                  {errors.phone && (
                    <View style={styles.errorContainer}>
                      <Ionicons name="alert-circle" size={14} color="#EF4444" />
                      <Text style={styles.errorText}>{errors.phone}</Text>
                    </View>
                  )}
                </View>

                {/* Address */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Address (Optional)</Text>
                  <View style={styles.inputContainer}>
                    <Ionicons 
                      name="location" 
                      size={20} 
                      color="#8B5CF6" 
                      style={styles.inputIcon}
                    />
                    <TextInput
                      style={[styles.input, styles.textAreaInput]}
                      placeholder="Street, Barangay, City"
                      placeholderTextColor="#64748B"
                      value={formData.address}
                      onChangeText={(text) => updateField('address', text)}
                      multiline
                      numberOfLines={2}
                      editable={!loading}
                    />
                  </View>
                </View>

                {/* Preferred Branch */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Preferred Branch</Text>
                  <View style={styles.pickerContainer}>
                    <Ionicons name="business" size={20} color="#8B5CF6" style={styles.inputIcon} />
                    {loadingBranches ? (
                      <View style={styles.pickerLoading}>
                        <ActivityIndicator size="small" color="#8B5CF6" />
                      </View>
                    ) : (
                      <Picker
                        selectedValue={formData.preferredBranchId}
                        onValueChange={(value) => updateField('preferredBranchId', value)}
                        style={styles.picker}
                        enabled={!loading}
                        dropdownIconColor="#8B5CF6"
                      >
                        {branches.map((branch) => (
                          <Picker.Item
                            key={branch.id}
                            label={`${branch.name} - ${branch.city}`}
                            value={branch.id}
                          />
                        ))}
                      </Picker>
                    )}
                  </View>
                </View>

                {/* Password */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Password</Text>
                  <View style={[styles.inputContainer, errors.password && styles.inputError]}>
                    <Ionicons 
                      name="lock-closed" 
                      size={20} 
                      color={errors.password ? "#EF4444" : "#8B5CF6"} 
                      style={styles.inputIcon}
                    />
                    <TextInput
                      style={[styles.input, styles.passwordInputText]}
                      placeholder="Min. 8 characters"
                      placeholderTextColor="#64748B"
                      value={formData.password}
                      onChangeText={(text) => updateField('password', text)}
                      secureTextEntry={!showPassword}
                      editable={!loading}
                    />
                    <TouchableOpacity
                      style={styles.eyeButton}
                      onPress={() => setShowPassword(!showPassword)}
                      activeOpacity={0.7}
                    >
                      <Ionicons
                        name={showPassword ? 'eye' : 'eye-off'}
                        size={20}
                        color="#64748B"
                      />
                    </TouchableOpacity>
                  </View>
                  {errors.password && (
                    <View style={styles.errorContainer}>
                      <Ionicons name="alert-circle" size={14} color="#EF4444" />
                      <Text style={styles.errorText}>{errors.password}</Text>
                    </View>
                  )}
                </View>

                {/* Confirm Password */}
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>Confirm Password</Text>
                  <View style={[styles.inputContainer, errors.passwordConfirmation && styles.inputError]}>
                    <Ionicons 
                      name="lock-closed" 
                      size={20} 
                      color={errors.passwordConfirmation ? "#EF4444" : "#8B5CF6"} 
                      style={styles.inputIcon}
                    />
                    <TextInput
                      style={[styles.input, styles.passwordInputText]}
                      placeholder="Re-enter password"
                      placeholderTextColor="#64748B"
                      value={formData.passwordConfirmation}
                      onChangeText={(text) => updateField('passwordConfirmation', text)}
                      secureTextEntry={!showConfirmPassword}
                      editable={!loading}
                    />
                    <TouchableOpacity
                      style={styles.eyeButton}
                      onPress={() => setShowConfirmPassword(!showConfirmPassword)}
                      activeOpacity={0.7}
                    >
                      <Ionicons
                        name={showConfirmPassword ? 'eye' : 'eye-off'}
                        size={20}
                        color="#64748B"
                      />
                    </TouchableOpacity>
                  </View>
                  {errors.passwordConfirmation && (
                    <View style={styles.errorContainer}>
                      <Ionicons name="alert-circle" size={14} color="#EF4444" />
                      <Text style={styles.errorText}>{errors.passwordConfirmation}</Text>
                    </View>
                  )}
                </View>

                {/* Terms */}
                <Text style={styles.termsText}>
                  By creating an account, you agree to our{' '}
                  <Text style={styles.termsLink}>Terms of Service</Text> and{' '}
                  <Text style={styles.termsLink}>Privacy Policy</Text>
                </Text>

                {/* Register Button */}
                <TouchableOpacity
                  style={[styles.registerButton, loading && styles.registerButtonDisabled]}
                  onPress={handleRegister}
                  disabled={loading}
                  activeOpacity={0.9}
                >
                  <LinearGradient
                    colors={loading ? ['#64748B', '#475569'] : ['#8B5CF6', '#EC4899']}
                    style={styles.registerButtonGradient}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 0 }}
                  >
                    {loading ? (
                      <ActivityIndicator color="#FFFFFF" size="small" />
                    ) : (
                      <>
                        <Text style={styles.registerButtonText}>Create Account</Text>
                        <Ionicons name="arrow-forward" size={20} color="#FFFFFF" />
                      </>
                    )}
                  </LinearGradient>
                </TouchableOpacity>
              </View>

              {/* Login Link */}
              <View style={styles.loginSection}>
                <Text style={styles.loginText}>Already have an account? </Text>
                <Link href="/(auth)/login" asChild>
                  <TouchableOpacity disabled={loading} activeOpacity={0.7}>
                    <Text style={styles.loginLink}>Sign In</Text>
                  </TouchableOpacity>
                </Link>
              </View>
            </Animated.View>
          </ScrollView>
        </KeyboardAvoidingView>
      </LinearGradient>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  backgroundOrbs: {
    position: 'absolute',
    width: '100%',
    height: '100%',
  },
  orb: {
    position: 'absolute',
    borderRadius: 999,
  },
  orb1: {
    width: 280,
    height: 280,
    top: -80,
    right: -90,
  },
  orb2: {
    width: 250,
    height: 250,
    bottom: -60,
    left: -70,
  },
  keyboardView: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 24,
    paddingTop: Platform.OS === 'ios' ? 70 : 50,
    paddingBottom: 40,
  },
  content: {
    flex: 1,
  },

  // Logo Section - CIRCULAR (For circular logo image)
  logoSection: {
    alignItems: 'center',
    marginBottom: 40, // Increased since brand text is removed
  },
  logoContainer: {
    width: 120,
    height: 120,
    borderRadius: 60, // Perfect circle
    shadowColor: '#0EA5E9',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.5,
    shadowRadius: 20,
    elevation: 12,
  },
  logo: {
    width: 120,
    height: 120,
    borderRadius: 60, // Ensures circular clipping
  },

  // Header Section
  headerSection: {
    marginBottom: 24,
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: '700',
    color: '#FFFFFF',
    marginBottom: 6,
    letterSpacing: -0.5,
  },
  headerSubtitle: {
    fontSize: 15,
    color: '#94A3B8',
    lineHeight: 22,
    textAlign: 'center',
  },

  // Form Card
  formCard: {
    backgroundColor: 'rgba(28, 35, 64, 0.6)',
    borderRadius: 24,
    padding: 24,
    marginBottom: 24,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },

  // Input Styles
  inputGroup: {
    marginBottom: 18,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#E2E8F0',
    marginBottom: 8,
    letterSpacing: 0.3,
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
    paddingHorizontal: 16,
    minHeight: 56,
  },
  inputError: {
    borderColor: '#EF4444',
    backgroundColor: 'rgba(239, 68, 68, 0.1)',
  },
  inputIcon: {
    marginRight: 12,
  },
  input: {
    flex: 1,
    fontSize: 15,
    color: '#FFFFFF',
    fontWeight: '500',
    paddingVertical: 16,
  },
  textAreaInput: {
    minHeight: 72,
    textAlignVertical: 'top',
  },
  passwordInputText: {
    paddingRight: 40,
  },
  eyeButton: {
    position: 'absolute',
    right: 16,
    padding: 8,
  },
  errorContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 6,
    paddingLeft: 4,
  },
  errorText: {
    color: '#EF4444',
    fontSize: 13,
    marginLeft: 6,
    fontWeight: '500',
  },

  // Picker
  pickerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
    paddingLeft: 16,
    height: 56,
    overflow: 'hidden',
  },
  pickerLoading: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  picker: {
    flex: 1,
    height: 56,
    color: '#FFFFFF',
    marginLeft: -4,
  },

  // Terms
  termsText: {
    textAlign: 'center',
    color: '#94A3B8',
    fontSize: 12,
    lineHeight: 18,
    marginTop: 8,
    marginBottom: 20,
  },
  termsLink: {
    color: '#8B5CF6',
    fontWeight: '600',
  },

  // Register Button
  registerButton: {
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: '#8B5CF6',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.4,
    shadowRadius: 16,
    elevation: 8,
  },
  registerButtonDisabled: {
    opacity: 0.6,
  },
  registerButtonGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 18,
    gap: 10,
  },
  registerButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.5,
  },

  // Login Section
  loginSection: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loginText: {
    color: '#94A3B8',
    fontSize: 15,
    fontWeight: '500',
  },
  loginLink: {
    color: '#8B5CF6',
    fontSize: 15,
    fontWeight: '700',
  },
});