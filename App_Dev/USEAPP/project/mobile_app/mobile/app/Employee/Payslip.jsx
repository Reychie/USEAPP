import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  SafeAreaView, 
  ScrollView, 
  TouchableOpacity, 
  StatusBar,
  Platform,
  Alert,
  ActivityIndicator,
  Linking,
  Clipboard,
  Modal
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, MaterialCommunityIcons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Picker } from '@react-native-picker/picker';
import { payslipService } from '../services/api';
import { useAuth } from '../services/authContext';

// No Payslip Available Modal Component
const NoPayslipModal = ({ visible, onClose, userName }) => {
  return (
    <Modal
      animationType="fade"
      transparent={true}
      visible={visible}
      onRequestClose={onClose}
    >
      <View style={noPayslipStyles.centeredView}>
        <View style={noPayslipStyles.modalView}>
          <View style={noPayslipStyles.iconContainer}>
            <Ionicons name="information-circle-outline" size={60} color="#3B82F6" />
          </View>
          <Text style={noPayslipStyles.modalTitle}>No Payslip Available</Text>
          <Text style={noPayslipStyles.modalText}>
            {userName} has not worked any days this month. No payslip is available.
          </Text>
          <TouchableOpacity
            style={noPayslipStyles.button}
            onPress={onClose}
          >
            <Text style={noPayslipStyles.buttonText}>Understand</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
};

// Error modal for file access issues
const FileErrorModal = ({ visible, onClose, onRegenerate }) => {
  return (
    <Modal
      animationType="fade"
      transparent={true}
      visible={visible}
      onRequestClose={onClose}
    >
      <View style={noPayslipStyles.centeredView}>
        <View style={noPayslipStyles.modalView}>
          <View style={noPayslipStyles.iconContainer}>
            <Ionicons name="alert-circle-outline" size={60} color="#EF4444" />
          </View>
          <Text style={noPayslipStyles.modalTitle}>File Error</Text>
          <Text style={noPayslipStyles.modalText}>
            The payslip file could not be accessed. Please try regenerating it.
          </Text>
          <View style={noPayslipStyles.buttonContainer}>
            <TouchableOpacity
              style={[noPayslipStyles.button, noPayslipStyles.regenerateButton]}
              onPress={onRegenerate}
            >
              <Text style={noPayslipStyles.buttonText}>Regenerate</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[noPayslipStyles.button, noPayslipStyles.cancelButton]}
              onPress={onClose}
            >
              <Text style={noPayslipStyles.buttonText}>Cancel</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const Payslip = () => {
  const router = useRouter();
  const { user } = useAuth();
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [payslipData, setPayslipData] = useState([]);
  const [monthlySummary, setMonthlySummary] = useState({
    totalBasic: 0,
    totalOvertime: 0,
    totalDeductions: 0,
    totalNetPay: 0
  });
  const [noPayslipModalVisible, setNoPayslipModalVisible] = useState(false);
  const [fileErrorModalVisible, setFileErrorModalVisible] = useState(false);
  const [currentPayslip, setCurrentPayslip] = useState(null);

  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  // Redirect if not authenticated
  useEffect(() => {
    if (!user) {
      router.replace('/');
    }
  }, [user]);

  useEffect(() => {
    if (user) {
      fetchPayslipData();
    }
  }, [selectedMonth, selectedYear, user]);

  const fetchPayslipData = async () => {
    if (!user || !user.userId) {
      Alert.alert('Error', 'User information is missing. Please log in again.');
      return;
    }

    try {
      setLoading(true);
      const response = await payslipService.getPayslips(user.userId, selectedMonth, selectedYear);
      
      if (response.status) {
        setPayslipData(response.data.payslips || []);
        setMonthlySummary(response.data.monthlySummary || {
          totalBasic: 0,
          totalOvertime: 0,
          totalDeductions: 0,
          totalNetPay: 0
        });
      } else {
        console.error('Failed to load payslip data:', response.message);
        Alert.alert('Error', response.message || 'Failed to load payslip data');
      }
    } catch (error) {
      console.error('Error fetching payslip data:', error);
      Alert.alert('Error', 'Failed to load payslip data');
    } finally {
      setLoading(false);
    }
  };

  const generatePDF = async (userId, salaryId) => {
    try {
      setGenerating(true);
      const response = await payslipService.generatePayslip(userId, salaryId);
      
      if (response.status) {
        Alert.alert('Success', 'Payslip generated successfully');
        // If we have a pdfUrl in the response, open it
        if (response.data && response.data.pdfUrl) {
          await Linking.openURL(response.data.pdfUrl);
        }
        fetchPayslipData(); // Refresh the data
      } else {
        Alert.alert('Error', response.message || 'Failed to generate payslip');
      }
    } catch (error) {
      console.error('Error generating payslip:', error);
      Alert.alert('Error', 'Failed to generate payslip');
    } finally {
      setGenerating(false);
    }
  };

  const viewOrDownloadPayslip = async (record) => {
    try {
      if (!record || !record.payslipId) {
        // If no payslip ID, we need to generate it first
        generatePDF(record.userId, record.salaryId);
        return;
      }

      // Get the direct download URL
      const payslipUrl = payslipService.getPayslipUrl(record.payslipId, true);
      
      console.log('Opening payslip URL:', payslipUrl);
      
      // Try to open the URL
      const canOpen = await Linking.canOpenURL(payslipUrl);
      
      if (canOpen) {
        await Linking.openURL(payslipUrl);
      } else {
        // If can't open directly, show the file error modal
        setCurrentPayslip(record);
        setFileErrorModalVisible(true);
      }
    } catch (error) {
      console.error('Error opening payslip:', error);
      // Show the file error modal
      setCurrentPayslip(record);
      setFileErrorModalVisible(true);
    }
  };

  const regeneratePayslip = () => {
    if (currentPayslip) {
      generatePDF(currentPayslip.userId, currentPayslip.salaryId);
      setFileErrorModalVisible(false);
    }
  };

  // Handle navigation
  const handleNavigation = (route) => {
    router.push(route);
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar backgroundColor="#FFFFFF" barStyle="dark-content" />
      
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <Text style={styles.logoText}>ATTENDROLL</Text>
        </View>
        <View style={styles.profileContainer}>
          <View style={styles.profileIcon}>
            <FontAwesome5 name="user-alt" size={18} color="#000" />
          </View>
        </View>
      </View>

      {/* Main Content */}
      <View style={styles.mainContent}>
        <Text style={styles.title}>Payslip Records</Text>

        {/* Filter Section */}
        <View style={styles.filterContainer}>
          <View style={styles.filterItem}>
            <Text style={styles.filterLabel}>Month</Text>
            <Picker
              selectedValue={selectedMonth}
              onValueChange={setSelectedMonth}
              style={styles.picker}
            >
              {months.map((month, index) => (
                <Picker.Item key={index + 1} label={month} value={index + 1} />
              ))}
            </Picker>
          </View>
          <View style={styles.filterItem}>
            <Text style={styles.filterLabel}>Year</Text>
            <Picker
              selectedValue={selectedYear}
              onValueChange={setSelectedYear}
              style={styles.picker}
            >
              {[2025, 2024, 2023].map(year => (
                <Picker.Item key={year} label={year.toString()} value={year} />
              ))}
            </Picker>
          </View>
        </View>

        {/* Payslip Records */}
        {loading ? (
          <ActivityIndicator size="large" color="#6366F1" style={styles.loader} />
        ) : (
          <ScrollView style={styles.recordsContainer}>
            {payslipData.length > 0 ? (
              payslipData.map((record, index) => (
                <View key={index} style={styles.recordCard}>
                  <Text style={styles.periodText}>{record.period}</Text>
                  <View style={styles.detailsContainer}>
                    <View style={styles.detailRow}>
                      <Text style={styles.detailLabel}>Basic Salary:</Text>
                      <Text style={styles.detailValue}>₱{parseFloat(record.basicSalary || 0).toFixed(2)}</Text>
                    </View>
                    <View style={styles.detailRow}>
                      <Text style={styles.detailLabel}>Overtime:</Text>
                      <Text style={styles.detailValue}>₱{parseFloat(record.overtime || 0).toFixed(2)}</Text>
                    </View>
                    <View style={styles.detailRow}>
                      <Text style={styles.detailLabel}>Deductions:</Text>
                      <Text style={styles.detailValue}>₱{parseFloat(record.deductions || 0).toFixed(2)}</Text>
                    </View>
                    <View style={styles.detailRow}>
                      <Text style={styles.detailLabel}>Net Pay:</Text>
                      <Text style={[styles.detailValue, styles.netPayText]}>
                        ₱{parseFloat(record.netPay || 0).toFixed(2)}
                      </Text>
                    </View>
                    <View style={styles.statusContainer}>
                      <View style={[
                        styles.statusBadge,
                        record.status === 'Paid' ? styles.paidBadge : styles.pendingBadge
                      ]}>
                        <Text style={styles.statusText}>{record.status || 'Pending'}</Text>
                      </View>
                      <TouchableOpacity 
                        style={styles.downloadButton}
                        onPress={() => viewOrDownloadPayslip(record)}
                        disabled={generating}
                      >
                        {generating ? (
                          <ActivityIndicator size="small" color="#fff" />
                        ) : (
                          <Text style={styles.downloadButtonText}>
                            {record.pdfPath ? 'View Payslip' : 'Generate Payslip'}
                          </Text>
                        )}
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              ))
            ) : (
              <View style={styles.noDataContainer}>
                <Ionicons name="document-text-outline" size={60} color="#9CA3AF" />
                <Text style={styles.noDataText}>No payslip records found</Text>
                <Text style={styles.noDataSubtext}>
                  Payslip records for this period will appear here
                </Text>
              </View>
            )}
          </ScrollView>
        )}

        {/* Monthly Summary Section */}
        <View style={styles.summaryContainer}>
          <Text style={styles.summaryTitle}>Monthly Summary</Text>
          <View style={styles.summaryContent}>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Total Basic Salary:</Text>
              <Text style={styles.summaryValue}>
                ₱{parseFloat(monthlySummary.totalBasic || 0).toFixed(2)}
              </Text>
            </View>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Total Overtime:</Text>
              <Text style={styles.summaryValue}>
                ₱{parseFloat(monthlySummary.totalOvertime || 0).toFixed(2)}
              </Text>
            </View>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Total Deductions:</Text>
              <Text style={styles.summaryValue}>
                ₱{parseFloat(monthlySummary.totalDeductions || 0).toFixed(2)}
              </Text>
            </View>
            <View style={[styles.summaryRow, styles.netPayRow]}>
              <Text style={[styles.summaryLabel, styles.netPayLabel]}>Total Net Pay:</Text>
              <Text style={[styles.summaryValue, styles.netPayTotal]}>
                ₱{parseFloat(monthlySummary.totalNetPay || 0).toFixed(2)}
              </Text>
            </View>
          </View>
        </View>
      </View>

      {/* Bottom Navigation */}
      <View style={styles.bottomNav}>
        <TouchableOpacity 
          style={styles.bottomNavItem} 
          onPress={() => handleNavigation('/Employee/Dashboard')}
        >
          <Ionicons name="grid-outline" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Dashboard</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.bottomNavItem} 
          onPress={() => handleNavigation('/Employee/History')}
        >
          <MaterialIcons name="history" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>History</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.bottomNavItem} 
          onPress={() => handleNavigation('/Employee/Salary')}
        >
          <MaterialIcons name="attach-money" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Salary</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.bottomNavItem}>
          <FontAwesome5 name="file-invoice-dollar" size={22} color="#6366F1" />
          <Text style={styles.bottomNavText}>Payslip</Text>
        </TouchableOpacity>
      </View>

      {/* Modals */}
      <NoPayslipModal 
        visible={noPayslipModalVisible}
        onClose={() => setNoPayslipModalVisible(false)}
        userName={user ? `${user.firstName} ${user.lastName}` : 'Employee'}
      />

      <FileErrorModal
        visible={fileErrorModalVisible}
        onClose={() => setFileErrorModalVisible(false)}
        onRegenerate={regeneratePayslip}
      />
    </SafeAreaView>
  );
};

// Styles for No Payslip Modal
const noPayslipStyles = StyleSheet.create({
  centeredView: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0, 0, 0, 0.5)'
  },
  modalView: {
    backgroundColor: 'white',
    borderRadius: 12,
    padding: 25,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 5,
    width: '85%',
    maxWidth: 400,
  },
  iconContainer: {
    marginBottom: 15,
  },
  modalTitle: {
    fontSize: 22,
    fontWeight: '600',
    marginBottom: 10,
    textAlign: 'center'
  },
  modalText: {
    fontSize: 16,
    marginBottom: 20,
    textAlign: 'center',
    lineHeight: 22,
    color: '#4B5563'
  },
  button: {
    backgroundColor: '#3B82F6',
    borderRadius: 8,
    padding: 12,
    elevation: 2,
    minWidth: 120,
    alignItems: 'center',
  },
  buttonText: {
    color: 'white',
    fontWeight: '600',
    fontSize: 16,
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: '100%',
  },
  regenerateButton: {
    backgroundColor: '#6366F1',
    flex: 1,
    marginRight: 5,
  },
  cancelButton: {
    backgroundColor: '#9CA3AF',
    flex: 1,
    marginLeft: 5,
  },
});

// Main styles
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F7FA',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#FFFFFF',
    paddingVertical: 12,
    paddingHorizontal: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  logoText: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  profileContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  profileIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#E5E7EB',
    justifyContent: 'center',
    alignItems: 'center',
  },
  mainContent: {
    flex: 1,
    padding: 15,
    marginBottom: 65,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 20,
  },
  filterContainer: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 15,
    marginBottom: 20,
  },
  filterItem: {
    flex: 1,
    marginHorizontal: 5,
  },
  filterLabel: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 5,
  },
  picker: {
    backgroundColor: '#F3F4F6',
    borderRadius: 6,
  },
  recordsContainer: {
    flex: 1,
  },
  recordCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 15,
    marginBottom: 10,
  },
  periodText: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 10,
  },
  detailsContainer: {
    paddingLeft: 10,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 5,
  },
  detailLabel: {
    color: '#6B7280',
  },
  detailValue: {
    fontWeight: '500',
  },
  netPayText: {
    color: '#10B981',
    fontWeight: 'bold',
  },
  statusContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
  },
  statusBadge: {
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 4,
  },
  paidBadge: {
    backgroundColor: '#10B981',
  },
  pendingBadge: {
    backgroundColor: '#F59E0B',
  },
  statusText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '500',
  },
  downloadButton: {
    backgroundColor: '#1F2937',
    padding: 8,
    borderRadius: 6,
  },
  downloadButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
  },
  noDataContainer: {
    padding: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 40,
  },
  noDataText: {
    color: '#6B7280',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 8,
  },
  noDataSubtext: {
    color: '#3B82F6',
    fontSize: 14,
    fontWeight: '500',
  },
  summaryContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 15,
    marginTop: 20,
  },
  summaryTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 15,
  },
  summaryContent: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  summaryRow: {
    flex: 1,
    backgroundColor: '#F3F4F6',
    padding: 15,
    borderRadius: 8,
    minWidth: '45%',
  },
  summaryLabel: {
    color: '#6B7280',
    marginBottom: 5,
  },
  summaryValue: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#1F2937',
  },
  netPayRow: {
    backgroundColor: '#10B981',
  },
  netPayLabel: {
    color: '#FFFFFF',
  },
  netPayTotal: {
    color: '#FFFFFF',
  },
  bottomNav: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 65,
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    paddingVertical: 8,
    paddingHorizontal: 5,
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  bottomNavItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bottomNavText: {
    fontSize: 10,
    marginTop: 4,
    color: '#4B5563',
  },
  loader: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
});

export default Payslip;