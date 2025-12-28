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
  ActivityIndicator
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, MaterialCommunityIcons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { attendanceService, payslipService } from '../services/api';
import { useAuth } from '../services/authContext';

const Dashboard = () => {
  const router = useRouter();
  const { user, logout } = useAuth(); 
  const [loading, setLoading] = useState(false);
  const [todayAttendance, setTodayAttendance] = useState({
    timeIn: null,
    timeOut: null,
    status: null
  });
  const [attendanceRecords, setAttendanceRecords] = useState([]);
  const [monthlyStats, setMonthlyStats] = useState({
    present: 0,
    late: 0,
    absent: 0
  });
  const [date, setDate] = useState(new Date());
  const [attendance, setAttendance] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [timeText, setTimeText] = useState('');
  const [timeInterval, setTimeInterval] = useState(null);
  const [monthSummary, setMonthSummary] = useState({
    presentDays: 0,
    absentDays: 0,
    lateDays: 0,
    monthlyWorkTarget: 22,
  });
  const [payslipNotification, setPayslipNotification] = useState(null);

  useEffect(() => {
    if (user) {
      loadAttendanceData();
      loadMonthlyStats(); 
      checkForAutomaticPayslip();
    }
  }, [user]);

  
  const loadMonthlyStats = async () => {
    try {
      const currentMonth = new Date().getMonth() + 1; 
      const currentYear = new Date().getFullYear();
      
      const response = await attendanceService.getAttendanceHistory(
        user.userId,
        currentMonth,
        currentYear
      );
      
      if (response.status && response.data && response.data.summary) {
        const summary = response.data.summary;
        setMonthlyStats({
          present: summary.presentDays || 0,
          late: summary.lateDays || 0,
          absent: summary.absentDays || 0
        });
      }
    } catch (error) {
      console.error('Failed to load monthly statistics:', error);
      
    }
  };

  const loadAttendanceData = async () => {
    try {
      setLoading(true);
      const today = new Date().toISOString().split('T')[0]; 
      const response = await attendanceService.getAttendance(user.userId, today);
      
      if (response.status && response.data) {
        setAttendanceRecords(response.data);
        
        
        if (response.data.length > 0) {
          const latestRecord = response.data[0]; 
          setTodayAttendance({
            timeIn: latestRecord.timeIn,
            timeOut: latestRecord.timeOut,
            status: latestRecord.timeOut ? 'Completed' : 'In Progress'
          });
        }
      }
    } catch (error) {
      console.error('Failed to load attendance data:', error);
      Alert.alert('Error', 'Failed to load attendance data');
    } finally {
      setLoading(false);
    }
  };

  const handleClockIn = async () => {
    try {
      setLoading(true);
      const response = await attendanceService.clockIn(user.userId);
      
      if (response.status) {
        Alert.alert('Success', 'Clock in recorded successfully');
        
        setTodayAttendance({
          ...todayAttendance,
          timeIn: response.data.timeIn,
          status: 'In Progress'
        });
        loadAttendanceData();
        loadMonthlyStats(); 
      } else {
        Alert.alert('Error', response.message || 'Failed to clock in');
      }
    } catch (error) {
      console.error('Clock in error:', error);
      Alert.alert('Error', 'Failed to clock in');
    } finally {
      setLoading(false);
    }
  };

  const handleClockOut = async () => {
    try {
      setLoading(true);
      const response = await attendanceService.clockOut(user.userId);
      
      if (response.status) {
        Alert.alert('Success', 'Clock out recorded successfully');
        
        setTodayAttendance({
          ...todayAttendance,
          timeOut: response.data.timeOut,
          status: 'Completed'
        });
        loadAttendanceData();
        loadMonthlyStats(); 
      } else {
        Alert.alert('Error', response.message || 'Failed to clock out');
      }
    } catch (error) {
      console.error('Clock out error:', error);
      Alert.alert('Error', 'Failed to clock out');
    } finally {
      setLoading(false);
    }
  };

  
  const handleLogout = async () => {
    try {
      await logout();
      router.push('/');
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  
  const formatTime = (timeString) => {
    if (!timeString) return '--:--';
    
    
    if (timeString.includes(':')) {
      const timeParts = timeString.split(':');
      const hours = parseInt(timeParts[0]);
      const minutes = timeParts[1];
      const ampm = hours >= 12 ? 'PM' : 'AM';
      const formattedHours = hours % 12 || 12;
      return `${formattedHours}:${minutes} ${ampm}`;
    }
    
    
    const date = new Date(timeString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  
  const formatDate = (dateString) => {
    if (!dateString) return '';
    
    
    if (dateString.includes('-')) {
      const [year, month, day] = dateString.split('-');
      const date = new Date(year, month - 1, day);
      return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    }
    
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
  };

  
  const checkForAutomaticPayslip = async () => {
    try {
      const response = await payslipService.checkWorkDaysAndGeneratePayslip(user.userId);
      
      if (response.status) {
        
        if (response.data.payslipGenerated) {
          setPayslipNotification({
            type: 'success',
            title: 'Payslip Generated',
            message: `Congratulations! You've reached ${response.data.workDays} work days this month, and your payslip has been automatically generated.`,
            buttonText: 'View Payslip',
            onPress: () => router.push('/Employee/Payslip')
          });
        } 
        
        else if (response.data.workDays >= response.data.requiredDays) {
          setPayslipNotification({
            type: 'info',
            title: 'Payslip Available',
            message: `You've reached ${response.data.workDays} work days this month. Your payslip is available in the Payslip section.`,
            buttonText: 'View Payslip',
            onPress: () => router.push('/Employee/Payslip')
          });
        }
        
        setMonthSummary(prev => ({
          ...prev,
          presentDays: response.data.workDays,
          monthlyWorkTarget: response.data.requiredDays
        }));
      }
    } catch (error) {
      console.error('Error checking for automatic payslip:', error);
    }
  };

  
  const renderPayslipNotification = () => {
    if (!payslipNotification) return null;
    
    const bgColor = payslipNotification.type === 'success' ? '#10B981' : '#3B82F6';
    const iconName = payslipNotification.type === 'success' ? 'checkmark-circle' : 'information-circle';
    
    return (
      <View style={[styles.notificationContainer, { backgroundColor: bgColor }]}>
        <View style={styles.notificationContent}>
          <Ionicons name={iconName} size={24} color="white" style={styles.notificationIcon} />
          <View style={styles.notificationTextContainer}>
            <Text style={styles.notificationTitle}>{payslipNotification.title}</Text>
            <Text style={styles.notificationMessage}>{payslipNotification.message}</Text>
          </View>
        </View>
        <TouchableOpacity 
          style={styles.notificationButton}
          onPress={payslipNotification.onPress}
        >
          <Text style={styles.notificationButtonText}>{payslipNotification.buttonText}</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.closeButton}
          onPress={() => setPayslipNotification(null)}
        >
          <Ionicons name="close" size={20} color="white" />
        </TouchableOpacity>
      </View>
    );
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
      {renderPayslipNotification()}

      {}
      <ScrollView style={styles.mainContent}>
        {}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Today's Attendance</Text>
          <Text style={styles.dateText}>{new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</Text>
          
          {loading ? (
            <ActivityIndicator size="large" color="#6366F1" style={styles.loader} />
          ) : !todayAttendance.timeIn ? (
            <TouchableOpacity style={styles.clockInButton} onPress={handleClockIn}>
              <Text style={styles.buttonText}>🟢 Clock In</Text>
            </TouchableOpacity>
          ) : !todayAttendance.timeOut ? (
            <TouchableOpacity style={styles.clockOutButton} onPress={handleClockOut}>
              <Text style={styles.buttonText}>🔴 Clock Out</Text>
            </TouchableOpacity>
          ) : (
            <View style={styles.completedContainer}>
              <Text style={styles.completedText}>✓ Attendance Completed</Text>
              <View style={[styles.statusBadge, styles.presentBadge]}>
                <Text style={styles.statusText}>{todayAttendance.status}</Text>
              </View>
            </View>
          )}

          <View style={styles.timeContainer}>
            <Text style={styles.timeLabel}>Time In: {formatTime(todayAttendance.timeIn)}</Text>
            <Text style={styles.timeLabel}>Time Out: {formatTime(todayAttendance.timeOut)}</Text>
          </View>
        </View>

        {}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Monthly Statistics</Text>
          <View style={styles.statsGrid}>
            <View style={styles.statBox}>
              <Text style={styles.statLabel}>Present</Text>
              <Text style={[styles.statValue, styles.presentText]}>{monthlyStats.present}</Text>
            </View>
            <View style={styles.statBox}>
              <Text style={styles.statLabel}>Late</Text>
              <Text style={[styles.statValue, styles.lateText]}>{monthlyStats.late}</Text>
            </View>
            <View style={styles.statBox}>
              <Text style={styles.statLabel}>Absent</Text>
              <Text style={[styles.statValue, styles.absentText]}>{monthlyStats.absent}</Text>
            </View>
          </View>
        </View>

        {}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Recent Attendance History</Text>
          {attendanceRecords.length > 0 ? (
            attendanceRecords.map((record, index) => (
              <View key={index} style={styles.historyItem}>
                <Text style={styles.historyDate}>{formatDate(record.date)}</Text>
                <View style={[
                  styles.statusBadge, 
                  record.status === 'Present' ? styles.presentBadge : 
                  record.status === 'Late' ? styles.lateBadge : 
                  styles.absentBadge
                ]}>
                  <Text style={styles.statusText}>{record.status || 'Unknown'}</Text>
                </View>
                <Text style={styles.historyTime}>In: {formatTime(record.timeIn)}</Text>
                <Text style={styles.historyTime}>Out: {formatTime(record.timeOut)}</Text>
                <Text style={styles.historyHours}>
                  {record.timeOut ? calculateHours(record.timeIn, record.timeOut) : 'In Progress'}
                </Text>
              </View>
            ))
          ) : (
            <Text style={styles.noRecords}>No attendance records available</Text>
          )}
        </View>

        {}
        <View style={styles.footer}>
          <Text style={styles.footerText}>© Copyright AttendRoll. All Rights Reserved</Text>
          <Text style={styles.footerText}>Designed by Zal&Sherly</Text>
        </View>
      </ScrollView>

      {}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.bottomNavItem}>
          <Ionicons name="grid-outline" size={24} color="#6366F1" />
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
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Employee/Salary')}>
          <MaterialIcons name="attach-money" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Salary</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={handleLogout}>
          <MaterialIcons name="logout" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Logout</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
};


const calculateHours = (timeIn, timeOut) => {
  if (!timeIn || !timeOut) return '--';
  
  
  if (typeof timeIn === 'string' && timeIn.includes(':') && 
      typeof timeOut === 'string' && timeOut.includes(':')) {
    const [inHours, inMinutes] = timeIn.split(':').map(Number);
    const [outHours, outMinutes] = timeOut.split(':').map(Number);
    
    let totalMinutes = (outHours * 60 + outMinutes) - (inHours * 60 + inMinutes);
    if (totalMinutes < 0) totalMinutes += 24 * 60; 
    
    const hours = totalMinutes / 60;
    return `${hours.toFixed(1)} hrs`;
  }
  
  
  const startTime = new Date(timeIn).getTime();
  const endTime = new Date(timeOut).getTime();
  const diffMs = endTime - startTime;
  const diffHrs = diffMs / (1000 * 60 * 60);
  
  return `${diffHrs.toFixed(1)} hrs`;
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
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 16,
    marginBottom: 16,
    elevation: 2,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 10,
  },
  dateText: {
    color: '#6B7280',
    marginBottom: 15,
  },
  clockInButton: {
    backgroundColor: '#10B981',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 15,
  },
  clockOutButton: {
    backgroundColor: '#EF4444',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 15,
  },
  buttonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
  },
  completedContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 15,
  },
  completedText: {
    fontSize: 16,
    color: '#10B981',
    fontWeight: 'bold',
  },
  timeContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  timeLabel: {
    fontSize: 16,
  },
  statsGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statBox: {
    flex: 1,
    alignItems: 'center',
    padding: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 8,
    marginHorizontal: 4,
  },
  statLabel: {
    color: '#6B7280',
    marginBottom: 5,
  },
  statValue: {
    fontSize: 24,
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
  historyItem: {
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    paddingVertical: 12,
  },
  historyDate: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 5,
  },
  statusBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 12,
    marginBottom: 8,
  },
  presentBadge: {
    backgroundColor: '#D1FAE5',
  },
  lateBadge: {
    backgroundColor: '#FEF3C7',
  },
  absentBadge: {
    backgroundColor: '#FEE2E2',
  },
  statusText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#000000',
  },
  historyTime: {
    marginBottom: 2,
  },
  historyHours: {
    fontWeight: 'bold',
  },
  noRecords: {
    textAlign: 'center',
    color: '#6B7280',
    paddingVertical: 10,
  },
  loader: {
    padding: 20,
  },
  footer: {
    marginTop: 20,
    marginBottom: 20,
    alignItems: 'center',
  },
  footerText: {
    color: '#6B7280',
    fontSize: 12,
    marginBottom: 2,
  },
  bottomNav: {
    flexDirection: 'row',
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
    paddingVertical: 8,
    justifyContent: 'space-between',
    paddingHorizontal: 10,
  },
  bottomNavItem: {
    alignItems: 'center',
    flex: 1,
  },
  bottomNavText: {
    fontSize: 12,
    marginTop: 2,
    color: '#4B5563',
  },
  notificationContainer: {
    margin: 16,
    borderRadius: 10,
    overflow: 'hidden',
    marginBottom: 20,
  },
  notificationContent: {
    flexDirection: 'row',
    padding: 16,
  },
  notificationIcon: {
    marginRight: 12,
  },
  notificationTextContainer: {
    flex: 1,
  },
  notificationTitle: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 16,
    marginBottom: 4,
  },
  notificationMessage: {
    color: 'white',
    fontSize: 14,
  },
  notificationButton: {
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.2)',
    padding: 12,
    alignItems: 'center',
  },
  notificationButtonText: {
    color: 'white',
    fontWeight: 'bold',
  },
  closeButton: {
    position: 'absolute',
    top: 10,
    right: 10,
    padding: 5,
  },
});

export default Dashboard;
