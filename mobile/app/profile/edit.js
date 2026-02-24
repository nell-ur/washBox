import React, { useState, useEffect } from 'react';
import { 
  View, 
  Text, 
  TextInput, 
  TouchableOpacity, 
  StyleSheet, 
  ActivityIndicator, 
  Alert, 
  ScrollView, 
  Platform,
  Animated,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Picker } from '@react-native-picker/picker';
import { API_BASE_URL, STORAGE_KEYS } from '../../constants/config';

const COLORS = {
  background: '#0A1128',
  cardDark: '#1A2847',
  cardLight: '#253454',
  primary: '#0EA5E9',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  success: '#10B981',
  border: '#1E293B',
};

export default function EditProfileScreen() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [branches, setBranches] = useState([]);
  const [fadeAnim] = useState(new Animated.Value(0));
  
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    address: '',
    preferred_branch_id: '',
  });

  useEffect(() => {
    initializeData();
  }, []);

  useEffect(() => {
    if (!loading) {
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true,
      }).start();
    }
  }, [loading]);

  const initializeData = async () => {
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      
      if (!token) {
        router.replace('/(auth)/login');
        return;
      }

      const headers = { 
        'Authorization': `Bearer ${token}`, 
        'Accept': 'application/json' 
      };

      const [profileRes, branchRes] = await Promise.all([
        fetch(`${API_BASE_URL}/v1/user`, { headers }),
        fetch(`${API_BASE_URL}/v1/branches`, { headers })
      ]);

      if (profileRes.status === 401) {
        await AsyncStorage.multiRemove([STORAGE_KEYS.TOKEN, STORAGE_KEYS.CUSTOMER]);
        router.replace('/(auth)/login');
        return;
      }

      const profileJson = await profileRes.json();
      const branchJson = await branchRes.json();

      if (branchJson.success && branchJson.data && Array.isArray(branchJson.data.branches)) {
        setBranches(branchJson.data.branches);
      }

      if (profileJson.success) {
        const p = profileJson.data.customer; 
        setFormData({
          name: p.name || '',
          phone: p.phone || '',
          address: p.address || '',
          preferred_branch_id: p.preferred_branch_id || '', 
        });
      }
    } catch (e) {
      console.error("Initialization Error:", e);
      Alert.alert("Error", "Failed to sync data with WashBox server.");
    } finally {
      setLoading(false);
    }
  };

  const handleUpdate = async () => {
    if (!formData.name) {
      Alert.alert("Validation", "Name is required.");
      return;
    }

    setSaving(true);
    try {
      const token = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN);
      const res = await fetch(`${API_BASE_URL}/v1/profile`, {
        method: 'PUT', 
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      if (res.status === 401) {
        Alert.alert("Session Expired", "Please log in again.");
        router.replace('/(auth)/login');
        return;
      }

      const json = await res.json();
      if (json.success) {
        Alert.alert("Success", "Profile updated successfully!");
        router.back();
      } else {
        Alert.alert("Update Failed", json.message || "Please check your inputs.");
      }
    } catch (e) {
      Alert.alert("Connection Error", "Check your internet connection.");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading profile...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
        >
          <Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <View style={styles.headerCenter}>
          <Text style={styles.headerTitle}>Edit Profile</Text>
          <Text style={styles.headerSubtitle}>Update your information</Text>
        </View>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        style={styles.content} 
        showsVerticalScrollIndicator={false}
      >
        <Animated.View style={{ opacity: fadeAnim }}>
          {/* Profile Avatar Section */}
          <View style={styles.avatarSection}>
            <View style={styles.avatarContainer}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>
                  {formData.name ? formData.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??'}
                </Text>
              </View>
              <TouchableOpacity style={styles.avatarEditButton}>
                <Ionicons name="camera" size={16} color={COLORS.textPrimary} />
              </TouchableOpacity>
            </View>
            <Text style={styles.avatarName}>{formData.name || 'Your Name'}</Text>
          </View>

          {/* Form Card */}
          <View style={styles.formCard}>
            <View style={styles.cardHeader}>
              <Ionicons name="person-outline" size={24} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Personal Information</Text>
            </View>

            {/* Name Input */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Full Name</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="person" size={20} color={COLORS.textMuted} />
                <TextInput 
                  style={styles.textInput} 
                  value={formData.name} 
                  placeholder="Enter your full name"
                  placeholderTextColor={COLORS.textMuted}
                  onChangeText={(t) => setFormData({...formData, name: t})} 
                />
              </View>
            </View>

            {/* Phone Input */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Phone Number</Text>
              <View style={styles.inputContainer}>
                <Ionicons name="call" size={20} color={COLORS.textMuted} />
                <TextInput 
                  style={styles.textInput} 
                  value={formData.phone} 
                  keyboardType="phone-pad"
                  placeholder="Enter phone number"
                  placeholderTextColor={COLORS.textMuted}
                  onChangeText={(t) => setFormData({...formData, phone: t})} 
                />
              </View>
            </View>

            {/* Address Input */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Address</Text>
              <View style={[styles.inputContainer, styles.textAreaContainer]}>
                <Ionicons name="location" size={20} color={COLORS.textMuted} style={styles.textAreaIcon} />
                <TextInput 
                  style={[styles.textInput, styles.textArea]} 
                  value={formData.address} 
                  placeholder="Enter your complete address"
                  placeholderTextColor={COLORS.textMuted}
                  multiline
                  numberOfLines={3}
                  textAlignVertical="top"
                  onChangeText={(t) => setFormData({...formData, address: t})} 
                />
              </View>
            </View>
          </View>

          {/* Branch Selection Card */}
          <View style={styles.formCard}>
            <View style={styles.cardHeader}>
              <Ionicons name="business-outline" size={24} color={COLORS.primary} />
              <Text style={styles.cardTitle}>Preferred Branch</Text>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Select Branch</Text>
              <View style={styles.pickerContainer}>
                <Ionicons name="location" size={20} color={COLORS.textMuted} style={styles.pickerIcon} />
                <Picker
                  selectedValue={formData.preferred_branch_id || ""}
                  onValueChange={(itemValue) => setFormData({...formData, preferred_branch_id: itemValue})}
                  style={styles.picker}
                  dropdownIconColor={COLORS.primary}
                >
                  <Picker.Item label="Select a Branch" value="" color={COLORS.textMuted} />
                  {branches.map((branch) => (
                    <Picker.Item 
                      key={branch.id} 
                      label={`${branch.name} - ${branch.city || 'Negros'}`} 
                      value={branch.id} 
                      color={Platform.OS === 'ios' ? COLORS.textPrimary : '#000'} 
                    />
                  ))}
                </Picker>
              </View>
              <Text style={styles.inputHint}>
                Choose your nearest branch for faster service
              </Text>
            </View>
          </View>

          {/* Save Button */}
          <View style={styles.buttonContainer}>
            <TouchableOpacity 
              style={[styles.saveButton, saving && styles.saveButtonDisabled]} 
              onPress={handleUpdate} 
              disabled={saving}
            >
              {saving ? (
                <ActivityIndicator color={COLORS.textPrimary} />
              ) : (
                <>
                  <Ionicons name="checkmark-circle" size={24} color={COLORS.textPrimary} />
                  <Text style={styles.saveButtonText}>Save Changes</Text>
                </>
              )}
            </TouchableOpacity>

            <TouchableOpacity 
              style={styles.cancelButton} 
              onPress={() => router.back()}
              disabled={saving}
            >
              <Text style={styles.cancelButtonText}>Cancel</Text>
            </TouchableOpacity>
          </View>

          <View style={{ height: 40 }} />
        </Animated.View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: 16,
    fontSize: 14,
  },
  
  // Header
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 20,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerCenter: {
    flex: 1,
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  headerSubtitle: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginTop: 2,
  },
  
  // Content
  content: {
    flex: 1,
  },
  
  // Avatar Section
  avatarSection: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  avatarContainer: {
    position: 'relative',
    marginBottom: 16,
  },
  avatar: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 4,
    borderColor: COLORS.cardDark,
  },
  avatarText: {
    fontSize: 36,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  avatarEditButton: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: COLORS.background,
  },
  avatarName: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  
  // Form Card
  formCard: {
    backgroundColor: COLORS.cardDark,
    marginHorizontal: 20,
    marginBottom: 16,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 24,
    gap: 12,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  
  // Input Groups
  inputGroup: {
    marginBottom: 20,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: 8,
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.cardLight,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    gap: 12,
  },
  textInput: {
    flex: 1,
    color: COLORS.textPrimary,
    fontSize: 16,
  },
  textAreaContainer: {
    alignItems: 'flex-start',
    paddingVertical: 12,
  },
  textAreaIcon: {
    marginTop: 4,
  },
  textArea: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  inputHint: {
    fontSize: 12,
    color: COLORS.textMuted,
    marginTop: 6,
    marginLeft: 4,
  },
  
  // Picker
  pickerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.cardLight,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
    paddingLeft: 16,
  },
  pickerIcon: {
    marginRight: 12,
  },
  picker: {
    flex: 1,
    color: COLORS.textPrimary,
    height: 55,
  },
  
  // Buttons
  buttonContainer: {
    paddingHorizontal: 20,
    marginTop: 8,
  },
  saveButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.primary,
    paddingVertical: 16,
    borderRadius: 16,
    gap: 10,
    marginBottom: 12,
  },
  saveButtonDisabled: {
    opacity: 0.7,
  },
  saveButtonText: {
    color: COLORS.textPrimary,
    fontSize: 16,
    fontWeight: '700',
  },
  cancelButton: {
    alignItems: 'center',
    paddingVertical: 16,
  },
  cancelButtonText: {
    color: COLORS.textSecondary,
    fontSize: 16,
    fontWeight: '600',
  },
});