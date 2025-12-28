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
  ActivityIndicator,
  Alert
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, MaterialCommunityIcons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Picker } from '@react-native-picker/picker';
import { salaryService } from '../services/api';
import { useAuth } from '../services/authContext';

const Salary = () => {
  const router = useRouter();
  const { user } = useAuth(); 
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [loading, setLoading] = useState(false);
  const [salaryDetails, setSalaryDetails] = useState({
    basicSalary: 0,
    overtimePay: 0,
    bonus: 0,
    deductions: 0,
    totalSalary: 0
  });
  const [attendanceSummary, setAttendanceSummary] = useState({
    workingDays: 0,
    presentDays: 0,
    lateDays: 0,
    absentDays: 0
  });

  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  useEffect(() => {
    if (user) {
      fetchSalaryData();
    }
  }, [selectedMonth, selectedYear, user]);

  const fetchSalaryData = async () => {
    if (!user) return;
    
    try {
      setLoading(true);
      const response = await salaryService.getSalary(user.userId, selectedMonth, selectedYear);
      
      if (response.status) {
        
        const salaryData = response.data.salaryDetails || {};
        setSalaryDetails({
          basicSalary: salaryData.basicSalary || 0,
          overtimePay: salaryData.overtimePay || 0,
          bonus: salaryData.bonus || 0,
          deductions: salaryData.deductions || 0,
          totalSalary: salaryData.totalSalary || 0
        });
        
        
        const summaryData = response.data.attendanceSummary || {};
        setAttendanceSummary({
          workingDays: summaryData.workingDays || 0,
          presentDays: summaryData.presentDays || 0,
          lateDays: summaryData.lateDays || 0,
          absentDays: summaryData.absentDays || 0
        });
      } else {
        Alert.alert('Error', response.message || 'Failed to load salary data');
      }
    } catch (error) {
      console.error('Error fetching salary data:', error);
      Alert.alert('Error', 'Failed to load salary data');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar backgroundColor="#FFFFFF" barStyle="dark-content" />
      
      {}
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

      {}
      <ScrollView style={styles.mainContent}>
        <Text style={styles.title}>Salary Details</Text>

        {}
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

        {loading ? (
          <ActivityIndicator size="large" color="#6366F1" style={styles.loader} />
        ) : (
          <>
            {}
            <View style={styles.card}>
              <Text style={styles.cardTitle}>Earnings & Deductions</Text>
              
              <View style={styles.salaryRow}>
                <Text style={styles.salaryLabel}>Basic Salary</Text>
                <Text style={styles.salaryValue}>₱{parseFloat(salaryDetails.basicSalary || 0).toFixed(2)}</Text>
              </View>

              {salaryDetails.overtimePay > 0 && (
                <View style={styles.salaryRow}>
                  <Text style={styles.salaryLabel}>Overtime Pay</Text>
                  <Text style={styles.salaryValue}>₱{parseFloat(salaryDetails.overtimePay || 0).toFixed(2)}</Text>
                </View>
              )}

              {salaryDetails.bonus > 0 && (
                <View style={styles.salaryRow}>
                  <Text style={styles.salaryLabel}>Bonus</Text>
                  <Text style={styles.salaryValue}>₱{parseFloat(salaryDetails.bonus || 0).toFixed(2)}</Text>
                </View>
              )}

              {salaryDetails.deductions > 0 && (
                <View style={styles.salaryRow}>
                  <Text style={styles.salaryLabel}>Deductions</Text>
                  <Text style={[styles.salaryValue, styles.deductionText]}>
                    -₱{parseFloat(salaryDetails.deductions || 0).toFixed(2)}
                  </Text>
                </View>
              )}

              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Total Net Pay</Text>
                <Text style={styles.totalValue}>₱{parseFloat(salaryDetails.totalSalary || 0).toFixed(2)}</Text>
              </View>
            </View>

            {}
            <View style={styles.card}>
              <Text style={styles.cardTitle}>Attendance Summary</Text>
              <View style={styles.summaryGrid}>
                <View style={[styles.summaryBox, styles.workingBox]}>
                  <Text style={styles.summaryLabel}>Working Days</Text>
                  <Text style={styles.summaryValue}>{attendanceSummary.workingDays} days</Text>
                </View>
                <View style={[styles.summaryBox, styles.presentBox]}>
                  <Text style={styles.summaryLabel}>Present Days</Text>
                  <Text style={styles.summaryValue}>{attendanceSummary.presentDays} days</Text>
                </View>
                <View style={[styles.summaryBox, styles.lateBox]}>
                  <Text style={styles.summaryLabel}>Late Days</Text>
                  <Text style={styles.summaryValue}>{attendanceSummary.lateDays} days</Text>
                </View>
                <View style={[styles.summaryBox, styles.absentBox]}>
                  <Text style={styles.summaryLabel}>Absent Days</Text>
                  <Text style={styles.summaryValue}>{attendanceSummary.absentDays} days</Text>
                </View>
              </View>
            </View>
          </>
        )}

        {}
        <View style={styles.notesCard}>
          <Text style={styles.notesTitle}>Salary Notes:</Text>
          <View style={styles.notesList}>
            <Text style={styles.noteItem}>• Basic salary is fixed at ₱20,000 per month</Text>
            <Text style={styles.noteItem}>• Overtime rate is ₱150 per hour</Text>
            <Text style={styles.noteItem}>• Deductions apply for absences and late arrivals</Text>
            <Text style={styles.noteItem}>• 10% deduction applies if less than 20 days worked</Text>
          </View>
        </View>
      </ScrollView>

      {}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Employee/Dashboard')}>
          <Ionicons name="grid-outline" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Dashboard</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Employee/History')}>
          <MaterialIcons name="history" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>History</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Employee/Payslip')}>
          <MaterialCommunityIcons name="file-document-outline" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Payslip</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem}>
          <MaterialIcons name="attach-money" size={24} color="#6366F1" />
          <Text style={styles.bottomNavText}>Salary</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/')}>
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
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 15,
    marginBottom: 20,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 15,
  },
  salaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  salaryLabel: {
    color: '#4B5563',
    fontSize: 16,
  },
  salaryValue: {
    fontSize: 16,
    fontWeight: '500',
  },
  deductionText: {
    color: '#EF4444',
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 15,
    marginTop: 5,
  },
  totalLabel: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  totalValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#10B981',
  },
  summaryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  summaryBox: {
    flex: 1,
    padding: 15,
    borderRadius: 8,
    minWidth: '45%',
  },
  workingBox: {
    backgroundColor: '#F3F4F6',
  },
  presentBox: {
    backgroundColor: '#10B981',
  },
  lateBox: {
    backgroundColor: '#F59E0B',
  },
  absentBox: {
    backgroundColor: '#EF4444',
  },
  summaryLabel: {
    color: '#FFFFFF',
    marginBottom: 5,
  },
  summaryValue: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: 'bold',
  },
  notesCard: {
    backgroundColor: '#EFF6FF',
    borderRadius: 10,
    padding: 15,
    marginBottom: 20,
  },
  notesTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 10,
    color: '#1E40AF',
  },
  notesList: {
    paddingLeft: 10,
  },
  noteItem: {
    color: '#4B5563',
    marginBottom: 5,
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
    marginTop: 20,
  },
});

export default Salary;
