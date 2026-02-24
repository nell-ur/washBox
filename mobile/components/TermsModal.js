import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  Modal,
  TouchableOpacity,
  Dimensions,
  StatusBar,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const { height } = Dimensions.get('window');

const COLORS = {
  background: '#0A1128',
  cardDark: '#1A2847',
  cardLight: '#253454',
  primary: '#0EA5E9',
  textPrimary: '#FFFFFF',
  textSecondary: '#94A3B8',
  textMuted: '#64748B',
  success: '#10B981',
  warning: '#F59E0B',
  danger: '#EF4444',
  border: '#1E293B',
};

const TermsModal = ({ visible, onClose }) => {
  const [expandedIndex, setExpandedIndex] = useState(null);

  const termsData = [
    {
      icon: 'document-text',
      title: 'Service Agreement',
      content: 'We will provide laundry services as per your selected package and preferences. The services include washing, drying and folding, as specified.',
    },
    {
      icon: 'cash',
      title: 'Pricing and Payment',
      content: 'Our pricing is based on the weight of the laundry and the selected package. Payment is due upon pick-up or delivery, and we accept cash, GCash, or bank transfer. We kindly request that you prepare the exact amount for payment.',
    },
    {
      icon: 'shirt',
      title: 'Care Instructions',
      content: 'It is your responsibility to provide accurate care instructions for your clothes. We cannot be held liable for any damage caused by incorrect or missing care instructions.',
    },
    {
      icon: 'car',
      title: 'Pick-up and Delivery',
      content: 'We offer free scheduled pick-up and delivery services. While we strive to accommodate special requests, we cannot guarantee the exact time of pick-up and delivery. A failed attempt or attempts at scheduled pick-up and/or delivery may incur a charge.',
    },
    {
      icon: 'shield-checkmark',
      title: 'Responsibility for Items',
      content: 'We take utmost care in handling your laundry; however, we are not responsible for any loss or damage to items that are not machine washable or machine dryable, or for any items left in pockets, such as money, coins, jewelry, pens, or other valuables.',
    },
    {
      icon: 'alert-circle',
      title: 'Lost or Damaged Items',
      content: 'In the event of lost or damaged items, we will evaluate the situation and compensate accordingly, up to a maximum liability of up to 10x the amount of our laundry service. Please note that items with pre-existing damage or delicate fabrics may have limited liability.',
    },
    {
      icon: 'chatbubble-ellipses',
      title: 'Timely Feedback',
      content: 'If you have any concerns or feedback regarding our services, please contact us within 24 hours upon receipt of your laundry. We will make every effort to address your concerns and provide a satisfactory resolution.',
    },
    {
      icon: 'cloud-offline',
      title: 'Force Majeure',
      content: 'We shall not be held liable for any delays or failure to perform our obligations due to events beyond our control, including but not limited to natural disasters, acts of government, or unforeseen circumstances.',
    },
    {
      icon: 'lock-closed',
      title: 'Data Privacy',
      content: 'We value your privacy and will handle your personal information in accordance with applicable data protection laws. By availing our services, you consent to the collection, use, and storage of your personal information for the purpose of providing the laundry services.',
    },
    {
      icon: 'ban',
      title: 'Termination of Services',
      content: 'We reserve the right to refuse or discontinue our services if we believe that the laundry contents may pose a risk to our employees, equipment, or other customers, or if there is a violation of these terms and conditions.',
    },
    {
      icon: 'create',
      title: 'Amendments',
      content: 'We reserve the right to modify or update these terms and conditions at any time. Any changes will be communicated to you through our website, email, or other suitable means.',
    },
  ];

  const toggleExpand = (index) => {
    setExpandedIndex(expandedIndex === index ? null : index);
  };

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={true}
      onRequestClose={onClose}
      statusBarTranslucent
    >
      <View style={styles.overlay}>
        <View style={styles.modalContainer}>
          {/* Header */}
          <View style={styles.header}>
            <View style={styles.headerLeft}>
              <View style={styles.headerIconContainer}>
                <Ionicons name="document-text" size={24} color={COLORS.primary} />
              </View>
              <View>
                <Text style={styles.headerTitle}>Terms & Conditions</Text>
                <Text style={styles.headerSubtitle}>WashBox Laundry Services</Text>
              </View>
            </View>
            <TouchableOpacity onPress={onClose} style={styles.closeButton}>
              <Ionicons name="close" size={24} color={COLORS.textSecondary} />
            </TouchableOpacity>
          </View>

          {/* Drag Indicator */}
          <View style={styles.dragIndicator} />

          {/* Content */}
          <ScrollView
            style={styles.scrollView}
            showsVerticalScrollIndicator={false}
            contentContainerStyle={styles.scrollContent}
          >
            {termsData.map((term, index) => (
              <TouchableOpacity
                key={index}
                style={[
                  styles.termItem,
                  expandedIndex === index && styles.termItemExpanded,
                ]}
                onPress={() => toggleExpand(index)}
                activeOpacity={0.7}
              >
                <View style={styles.termHeader}>
                  <View style={styles.termNumberContainer}>
                    <Text style={styles.termNumber}>{index + 1}</Text>
                  </View>
                  <View style={[styles.termIconContainer, { backgroundColor: COLORS.primary + '20' }]}>
                    <Ionicons name={term.icon} size={18} color={COLORS.primary} />
                  </View>
                  <Text style={styles.termTitle}>{term.title}</Text>
                  <Ionicons 
                    name={expandedIndex === index ? 'chevron-up' : 'chevron-down'} 
                    size={20} 
                    color={COLORS.textMuted} 
                  />
                </View>
                
                {expandedIndex === index && (
                  <View style={styles.termContent}>
                    <Text style={styles.termContentText}>{term.content}</Text>
                  </View>
                )}
              </TouchableOpacity>
            ))}

            {/* Agreement Footer */}
            <View style={styles.agreementBox}>
              <Ionicons name="checkmark-circle" size={24} color={COLORS.success} />
              <Text style={styles.agreementText}>
                By availing our laundry services, you acknowledge that you have read, understood, and agreed to these terms and conditions.
              </Text>
            </View>

            {/* Version Info */}
            <Text style={styles.versionText}>
              Last updated: January 2026 • Version 1.0
            </Text>
          </ScrollView>

          {/* Footer Button */}
          <View style={styles.footer}>
            <TouchableOpacity 
              style={styles.acceptButton}
              onPress={onClose}
            >
              <Ionicons name="checkmark-circle" size={20} color="#fff" />
              <Text style={styles.acceptButtonText}>I Understand</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    justifyContent: 'flex-end',
  },
  modalContainer: {
    backgroundColor: COLORS.background,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: height * 0.9,
    minHeight: height * 0.7,
  },
  dragIndicator: {
    width: 40,
    height: 4,
    backgroundColor: COLORS.border,
    borderRadius: 2,
    alignSelf: 'center',
    marginTop: 12,
    marginBottom: 8,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  headerIconContainer: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: COLORS.primary + '20',
    justifyContent: 'center',
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
  closeButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: COLORS.cardDark,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 20,
  },
  termItem: {
    backgroundColor: COLORS.cardDark,
    borderRadius: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },
  termItemExpanded: {
    borderColor: COLORS.primary + '50',
  },
  termHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    gap: 10,
  },
  termNumberContainer: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  termNumber: {
    fontSize: 12,
    fontWeight: '700',
    color: '#fff',
  },
  termIconContainer: {
    width: 32,
    height: 32,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  termTitle: {
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textPrimary,
  },
  termContent: {
    paddingHorizontal: 14,
    paddingBottom: 14,
    paddingTop: 0,
  },
  termContentText: {
    fontSize: 13,
    color: COLORS.textSecondary,
    lineHeight: 20,
    marginLeft: 66,
  },
  agreementBox: {
    flexDirection: 'row',
    backgroundColor: COLORS.success + '15',
    borderRadius: 12,
    padding: 16,
    marginTop: 10,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: COLORS.success + '30',
    gap: 12,
  },
  agreementText: {
    flex: 1,
    fontSize: 13,
    color: COLORS.success,
    lineHeight: 20,
  },
  versionText: {
    textAlign: 'center',
    color: COLORS.textMuted,
    fontSize: 12,
    marginBottom: 10,
  },
  footer: {
    padding: 20,
    paddingBottom: 34,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  acceptButton: {
    flexDirection: 'row',
    backgroundColor: COLORS.primary,
    paddingVertical: 16,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  acceptButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#fff',
  },
});

export default TermsModal;