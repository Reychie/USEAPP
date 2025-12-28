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
import { attendanceService } from '../services/api';
import { useAuth } from '../services/authContext';

const History = () => {
  const router = useRouter();
  const { user } = useAuth(); 
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [loading, setLoading] = useState(false);
  const [attendanceData, setAttendanceData] = useState([]);
  const [monthlySummary, setMonthlySummary] = useState({
    totalDays: 0,
    presentDays: 0,
    lateDays: 0,
    absentDays: 0
  });

  useEffect(() => {
    if (user) {
      fetchAttendanceHistory();
    }
  }, [selectedMonth, selectedYear, user]);

  const fetchAttendanceHistory = async () => {
    try {
      setLoading(true);
      const response = await attendanceService.getAttendanceHistory(
        user.userId, 
        selectedMonth, 
        selectedYear
      );
      
      if (response.status) {
        
        const formattedRecords = response.data.records.map(record => ({
          date: formatDate(record.date),
          status: record.status,
          timeIn: record.timeIn ? formatTime(record.timeIn) : '-',
          timeOut: record.timeOut ? formatTime(record.timeOut) : '-',
          workHours: record.workHours || '-',
          location: record.location || 'Office'
        }));
        
        setAttendanceData(formattedRecords);
        setMonthlySummary(response.data.summary);
      } else {
        Alert.alert('Error', response.message || 'Failed to load attendance data');
      }
    } catch (error) {
      console.error('Error fetching attendance history:', error);
      Alert.alert('Error', 'Failed to load attendance data');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  };

  const formatTime = (timeString) => {
    if (!timeString) return '-';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours, 10);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
  };

  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

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
      <View style={styles.mainContent}>
        <Text style={styles.title}>Attendance History</Text>

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

        {}
        {loading ? (
          <ActivityIndicator size="large" color="#6366F1" style={styles.loader} />
        ) : (
          <ScrollView style={styles.tableContainer}>
            {attendanceData.length > 0 ? (
              attendanceData.map((record, index) => (
                <View key={index} style={styles.recordCard}>
                  <Text style={styles.dateText}>{record.date}</Text>
                  <View style={styles.recordDetails}>
                    <View style={[
                      styles.statusBadge,
                      record.status === 'Present' ? styles.presentBadge :
                      record.status === 'Late' ? styles.lateBadge :
                      styles.absentBadge
                    ]}>
                      <Text style={styles.statusText}>{record.status}</Text>
                    </View>
                    <View style={styles.timeContainer}>
                      <Text style={styles.timeLabel}>In: {record.timeIn}</Text>
                      <Text style={styles.timeLabel}>Out: {record.timeOut}</Text>
                    </View>
                    <View style={styles.detailRow}>
                      <Text style={styles.detailLabel}>Work Hours:</Text>
                      <Text style={styles.detailValue}>{record.workHours}</Text>
                    </View>
                    <View style={styles.detailRow}>
                      <Text style={styles.detailLabel}>Location:</Text>
                      <Text style={styles.detailValue}>{record.location}</Text>
                    </View>
                  </View>
                </View>
              ))
            ) : (
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyText}>No attendance records found for this period</Text>
              </View>
            )}
          </ScrollView>
        )}
        
        {}
        {!loading && attendanceData.length > 0 && (
          <View style={styles.summaryContainer}>
            <Text style={styles.summaryTitle}>Monthly Summary</Text>
            <View style={styles.summaryRow}>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Present</Text>
                <Text style={[styles.summaryValue, styles.presentText]}>{monthlySummary.presentDays}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Late</Text>
                <Text style={[styles.summaryValue, styles.lateText]}>{monthlySummary.lateDays}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Absent</Text>
                <Text style={[styles.summaryValue, styles.absentText]}>{monthlySummary.absentDays}</Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Total</Text>
                <Text style={styles.summaryValue}>{monthlySummary.totalDays}</Text>
              </View>
            </View>
          </View>
        )}
      </View>

      {}
      <View style={styles.bottomNav}>
        <TouchableOpacity 
          style={styles.bottomNavItem} 
          onPress={() => router.push('/Employee/Dashboard')}
        >
          <Ionicons name="home-outline" size={24} color="#6B7280" />
          <Text style={styles.navText}>Home</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.bottomNavItem}
          onPress={() => router.push('/Employee/History')}
        >
          <MaterialIcons name="history" size={24} color="#6366F1" />
          <Text style={[styles.navText, { color: '#6366F1' }]}>History</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.bottomNavItem}
          onPress={() => router.push('/Employee/Salary')}
        >
          <MaterialIcons name="monetization-on" size={24} color="#6B7280" />
          <Text style={styles.navText}>Salary</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.bottomNavItem}
          onPress={() => router.push('/Employee/Payslip')}
        >
          <MaterialCommunityIcons name="file-document-outline" size={24} color="#6B7280" />
          <Text style={styles.navText}>Payslip</Text>
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
  tableContainer: {
    flex: 1,
    height: '60%', 
    marginBottom: 10,
  },
  recordCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 15,
    marginBottom: 10,
    
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 1,
    },
    shadowOpacity: 0.1,
    shadowRadius: 1,
  },
  dateText: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 10,
  },
  recordDetails: {
    paddingLeft: 10,
  },
  statusBadge: {
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 4,
    alignSelf: 'flex-start',
    marginBottom: 10,
  },
  presentBadge: {
    backgroundColor: '#10B981',
  },
  lateBadge: {
    backgroundColor: '#F59E0B',
  },
  absentBadge: {
    backgroundColor: '#EF4444',
  },
  statusText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '500',
  },
  timeContainer: {
    marginBottom: 10,
  },
  timeLabel: {
    color: '#4B5563',
    fontSize: 14,
    marginBottom: 5,
  },
  detailRow: {
    flexDirection: 'row',
    marginBottom: 5,
  },
  detailLabel: {
    width: 100,
    color: '#6B7280',
  },
  detailValue: {
    color: '#4B5563',
  },
  summaryContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 15,
    marginTop: 20,
    marginBottom: 80,
  },
  summaryTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 15,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  summaryItem: {
    alignItems: 'center',
    flex: 1,
  },
  summaryLabel: {
    color: '#6B7280',
    marginBottom: 5,
  },
  summaryValue: {
    fontSize: 20,
    fontWeight: 'bold',
  },
  presentText: {
    color: '#10B981',
  },
  lateText: {
    color: '#F59E0B',
  },
  absentText: {
    color: '#EF4444',
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
  navText: {
    fontSize: 10,
    marginTop: 4,
    color: '#4B5563',
  },
  loader: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 50
  },
  emptyContainer: {
    padding: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyText: {
    color: '#6B7280',
    fontSize: 16,
  },
});

export default History;
