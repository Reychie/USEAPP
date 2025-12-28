import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  SafeAreaView, 
  ScrollView, 
  TouchableOpacity,
  Platform,
  StatusBar,
  Alert,
  ActivityIndicator,
  Linking
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Picker } from '@react-native-picker/picker';
import { adminService, payslipService } from '../services/api';
import { useAuth } from '../services/authContext';

const Payroll_Calculation = () => {
  const router = useRouter();
  const { user, logout } = useAuth();
  // Set default month and year to current date
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [payrollData, setPayrollData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [exportLoading, setExportLoading] = useState(false);
  const [payslipLoading, setPayslipLoading] = useState({});

  // Function to fetch payroll data from server
  const loadPayrollData = async () => {
    try {
      setLoading(true);
      const response = await adminService.getPayrollData(selectedMonth, selectedYear);
      
      if (response.status) {
        setPayrollData(response.data);
      } else {
        Alert.alert('Error', 'Failed to load payroll data');
      }
    } catch (error) {
      console.error('Error loading payroll data:', error);
      Alert.alert('Error', 'Failed to connect to the server');
    } finally {
      setLoading(false);
    }
  };

  // Effect to check user permissions and load data when filters change
  useEffect(() => {
    if (user && user.userRole !== 'admin') {
      Alert.alert('Access Denied', 'You do not have permission to access this page');
      router.push('/');
      return;
    }
    
    loadPayrollData();
  }, [selectedMonth, selectedYear]);

  // Month names for the picker
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  // Generate individual payslip for a user
  const generatePayslip = async (userId) => {
    try {
      setPayslipLoading(prev => ({ ...prev, [userId]: true }));
      
      const response = await payslipService.generatePayslip(userId, null);
      
      if (response.status) {
        if (response.data && response.data.pdfUrl) {
          await Linking.openURL(response.data.pdfUrl);
          Alert.alert('Success', 'Payslip opened successfully');
        } else {
          Alert.alert('Success', 'Payslip generated successfully');
        }
      } else {
        Alert.alert('Error', response.message || 'Failed to generate payslip');
      }
    } catch (error) {
      console.error('Error generating payslip:', error);
      Alert.alert('Error', 'Failed to generate payslip');
    } finally {
      setPayslipLoading(prev => ({ ...prev, [userId]: false }));
    }
  };

  // Export payroll data for all employees
  const exportPayroll = async () => {
    try {
      setExportLoading(true);
      
      const response = await payslipService.exportPayroll(user.userId, selectedMonth, selectedYear);
      
      if (response.status) {
        if (response.data && response.data.fileName) {
          Alert.alert(
            'Download Starting',
            'The payroll report will be downloaded automatically',
            [
              { 
                text: 'OK', 
                onPress: async () => {
                  const reportUrl = payslipService.getReportUrl(response.data.fileName);
                  
                  await Linking.openURL(reportUrl);
                }
              }
            ]
          );
        } else {
          Alert.alert('Error', 'Failed to get report download link');
        }
      } else {
        Alert.alert('Error', response.message || 'Failed to export payroll report');
      }
    } catch (error) {
      console.error('Error exporting payroll:', error);
      Alert.alert('Error', 'Failed to export payroll report');
    } finally {
      setExportLoading(false);
    }
  };

  // Handle month selection change
  const handleMonthChange = (month) => {
    setSelectedMonth(month);
  };

  // Handle year selection change
  const handleYearChange = (year) => {
    setSelectedYear(year);
  };

  // Handle user logout
  const handleLogout = async () => {
    try {
      await logout();
      router.replace('/');
    } catch (error) {
      console.error('Logout error:', error);
      Alert.alert('Error', 'Failed to logout. Please try again.');
    }
  };

  // Download report utility function
  const downloadReport = async (reportUrl) => {
    try {
      const canOpen = await Linking.canOpenURL(reportUrl);
      
      if (canOpen) {
        await Linking.openURL(reportUrl);
      } else {
        Alert.alert(
          'Error',
          'Cannot open the file. Please try again later.',
          [{ text: 'OK' }],
          { cancelable: true }
        );
      }
    } catch (error) {
      console.error('Error downloading report:', error);
      Alert.alert(
        'Error',
        'Failed to download the report. Please try again.',
        [{ text: 'OK' }],
        { cancelable: true }
      );
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar backgroundColor="#FFFFFF" barStyle="dark-content" />
      
      {/* Header with logo and profile */}
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

      <View style={styles.mainContent}>
        <Text style={styles.title}>Payroll Management</Text>

        {/* Filter section for month and year selection */}
        <View style={styles.filterCard}>
          <View style={styles.filterRow}>
            <View style={styles.filterItem}>
              <Text style={styles.filterLabel}>Month</Text>
              <Picker
                selectedValue={selectedMonth}
                onValueChange={handleMonthChange}
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
                onValueChange={handleYearChange}
                style={styles.picker}
              >
                {[2025, 2024, 2023].map(year => (
                  <Picker.Item key={year} label={year.toString()} value={year} />
                ))}
              </Picker>
            </View>
          </View>
        </View>

        <Text style={styles.sectionTitle}>Payroll Records</Text>
        
        {/* Loading indicator */}
        {loading ? (
            <View style={styles.loadingContainer}>
                <ActivityIndicator size="large" color="#007bff" />
                <Text style={styles.loadingText}>Loading payroll data...</Text>
            </View>
        ) : payrollData.length === 0 ? (
            <View style={styles.noDataContainer}>
                <Feather name="alert-circle" size={50} color="#6c757d" />
                <Text style={styles.noDataText}>No payroll data found for this period</Text>
            </View>
        ) : (
            <ScrollView contentContainerStyle={styles.payrollScrollContainer}>
                {payrollData.map((employee, index) => (
                    <View key={index} style={styles.employeeCard}>
                        <View style={styles.employeeHeader}>
                            <Text style={styles.employeeName}>{employee.name}</Text>
                            <View style={[
                                styles.statusBadge, 
                                employee.daysWorked < 22 ? styles.badgeDanger : styles.badgeSuccess
                            ]}>
                                <Text style={styles.statusText}>
                                    {employee.daysWorked < 22 ? 'Insufficient Days' : 'Complete'}
                                </Text>
                            </View>
                        </View>
                        
                        <View style={styles.detailRow}>
                            <Text style={styles.detailLabel}>Days Worked:</Text>
                            <Text style={styles.detailValue}>
                                {employee.daysWorked} / 22 required days
                                {employee.daysWorked < 22 && (
                                    <Text style={styles.warningText}> (Insufficient)</Text>
                                )}
                            </Text>
                        </View>
                        
                        <View style={styles.detailRow}>
                            <Text style={styles.detailLabel}>Basic Salary:</Text>
                            <Text style={styles.detailValue}>
                                ₱{employee.daysWorked < 22 ? '0.00' : employee.basicSalary.toFixed(2)}
                            </Text>
                        </View>
                        
                        <View style={styles.detailRow}>
                            <Text style={styles.detailLabel}>Overtime Pay:</Text>
                            <Text style={styles.detailValue}>
                                ₱{employee.daysWorked < 22 ? '0.00' : employee.overtimePay.toFixed(2)}
                            </Text>
                        </View>
                        
                        <View style={styles.detailRow}>
                            <Text style={styles.detailLabel}>Deductions:</Text>
                            <Text style={styles.detailValue}>
                                ₱{employee.daysWorked < 22 ? '0.00' : employee.deductions.toFixed(2)}
                            </Text>
                        </View>
                        
                        <View style={[styles.detailRow, styles.totalRow]}>
                            <Text style={styles.detailLabel}>Net Pay:</Text>
                            <Text style={[
                                styles.detailValue, 
                                styles.totalValue,
                                employee.daysWorked < 22 ? styles.zeroPayText : styles.normalPayText
                            ]}>
                                ₱{employee.daysWorked < 22 ? '0.00' : employee.netPay.toFixed(2)}
                            </Text>
                        </View>
                        
                        <View style={styles.actionRow}>
                            {employee.daysWorked < 22 ? (
                                <TouchableOpacity
                                    style={[styles.actionButton, styles.disabledButton]}
                                    onPress={() => Alert.alert(
                                        'Insufficient Work Days',
                                        `${employee.name} has not completed the required 22 work days. Current days worked: ${employee.daysWorked}.`,
                                        [{ text: 'OK' }]
                                    )}
                                >
                                    <Text style={styles.actionButtonText}>No Payslip</Text>
                                </TouchableOpacity>
                            ) : (
                                <TouchableOpacity
                                    style={[
                                        styles.actionButton,
                                        payslipLoading[employee.userId] ? styles.loadingButton : null
                                    ]}
                                    onPress={() => generatePayslip(employee.userId)}
                                    disabled={payslipLoading[employee.userId]}
                                >
                                    {payslipLoading[employee.userId] ? (
                                        <ActivityIndicator size="small" color="#fff" />
                                    ) : (
                                        <Text style={styles.actionButtonText}>Generate Payslip</Text>
                                    )}
                                </TouchableOpacity>
                            )}
                        </View>
                    </View>
                ))}
            </ScrollView>
        )}

        <TouchableOpacity 
          style={styles.exportButton} 
          onPress={exportPayroll}
          disabled={exportLoading}
        >
          {exportLoading ? (
            <ActivityIndicator size="small" color="#FFFFFF" />
          ) : (
            <Text style={styles.exportButtonText}>Export Payroll Report</Text>
          )}
        </TouchableOpacity>
      </View>

      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Dashboard')}>
          <Ionicons name="grid-outline" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Dashboard</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Attendance_Management')}>
          <MaterialIcons name="event-note" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Attendance</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem}>
          <FontAwesome5 name="calculator" size={24} color="#6366F1" />
          <Text style={styles.bottomNavText}>Payroll</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Employee_Management')}>
          <Feather name="users" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Employees</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={handleLogout}>
          <MaterialIcons name="logout" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Logout</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    elevation: 2,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  logoText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#000000',
  },
  profileContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  profileIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#F3F4F6',
    justifyContent: 'center',
    alignItems: 'center',
    marginLeft: 10,
  },
  mainContent: {
    flex: 1,
    padding: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#111827',
    marginBottom: 16,
  },
  filterCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    elevation: 2,
  },
  filterRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 10,
  },
  filterItem: {
    flex: 1,
  },
  filterLabel: {
    fontSize: 14,
    color: '#4B5563',
    marginBottom: 4,
  },
  picker: {
    height: 40,
    marginTop: -7,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#111827',
    marginTop: 8,
    marginBottom: 12,
  },
  employeeCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    padding: 16,
    marginBottom: 12,
    elevation: 2,
  },
  employeeHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  employeeName: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#111827',
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  badgeSuccess: {
    backgroundColor: '#D1FAE5',
  },
  badgeDanger: {
    backgroundColor: '#FEE2E2',
  },
  statusText: {
    fontSize: 12,
    fontWeight: 'bold',
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 6,
  },
  detailLabel: {
    fontSize: 14,
    color: '#4B5563',
  },
  detailValue: {
    fontSize: 14,
    color: '#111827',
    fontWeight: '500',
  },
  totalRow: {
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    marginTop: 6,
    paddingTop: 10,
  },
  totalValue: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  normalPayText: {
    color: '#047857',
  },
  zeroPayText: {
    color: '#DC2626',
  },
  warningText: {
    color: '#DC2626',
  },
  actionRow: {
    marginTop: 12,
    flexDirection: 'row',
    justifyContent: 'flex-end',
  },
  actionButton: {
    backgroundColor: '#6366F1',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    minWidth: 120,
  },
  disabledButton: {
    backgroundColor: '#9CA3AF',
  },
  loadingButton: {
    backgroundColor: '#6366F1',
  },
  actionButtonText: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 14,
  },
  bottomNav: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    elevation: 2,
  },
  bottomNavItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bottomNavText: {
    fontSize: 12,
    marginTop: 4,
    color: '#4B5563',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 40,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: '#4B5563',
  },
  noDataContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 40,
  },
  noDataText: {
    marginTop: 12,
    fontSize: 16,
    color: '#6c757d',
    textAlign: 'center',
  },
  buttonContainer: {
    marginTop: 20,
    flexDirection: 'row',
    justifyContent: 'center',
  },
  exportButton: {
    backgroundColor: '#6366F1',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 6,
  },
  exportButtonText: {
    color: 'white',
    fontWeight: 'bold',
    marginLeft: 8,
  },
  exportLoadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  payrollScrollContainer: {
    paddingBottom: 20,
  },
});

export default Payroll_Calculation;