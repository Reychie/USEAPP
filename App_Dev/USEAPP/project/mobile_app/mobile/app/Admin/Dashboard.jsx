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
  Dimensions,
  ActivityIndicator,
  Alert
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { adminService } from '../services/api';
import { useAuth } from '../services/authContext';

const Dashboard = () => {
  const router = useRouter();
  const { user, logout } = useAuth();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalEmployees: 0,
    presentEmployees: 0,
    absentEmployees: 0
  });
  const [attendanceData, setAttendanceData] = useState([]);
  
  
  const loadDashboardStats = async () => {
    try {
      setLoading(true);
      const response = await adminService.getDashboardStats();
      
      if (response.status) {
        setStats({
          totalEmployees: response.data.totalEmployees,
          presentEmployees: response.data.presentEmployees,
          absentEmployees: response.data.absentEmployees
        });
      } else {
        Alert.alert('Error', 'Failed to load dashboard statistics');
      }
    } catch (error) {
      console.error('Error loading dashboard stats:', error);
      Alert.alert('Error', 'Failed to connect to the server');
    } finally {
      setLoading(false);
    }
  };
  
  
  const loadAttendanceOverview = async () => {
    try {
      const response = await adminService.getAttendanceOverview();
      
      if (response.status) {
        setAttendanceData(response.data);
      } else {
        Alert.alert('Error', 'Failed to load attendance data');
      }
    } catch (error) {
      console.error('Error loading attendance overview:', error);
    }
  };
  
  
  useEffect(() => {
    
    if (user && user.userRole !== 'admin') {
      Alert.alert('Access Denied', 'You do not have permission to access this page');
      router.push('/');
      return;
    }
    
    loadDashboardStats();
    loadAttendanceOverview();
  }, []);

  
  const handleLogout = async () => {
    try {
      await logout();
      router.replace('/');
    } catch (error) {
      console.error('Logout error:', error);
      Alert.alert('Error', 'Failed to logout. Please try again.');
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
        <Text style={styles.dashboardTitle}>DASHBOARD</Text>

        {loading ? (
          <ActivityIndicator size="large" color="#6366F1" />
        ) : (
          <>
            {}
            <View style={styles.totalEmployeesCard}>
              <Text style={styles.cardTitle}>Total Employees</Text>
              <View style={styles.countContainer}>
                <FontAwesome5 name="users" size={22} color="#FFF" />
                <Text style={styles.countText}>{stats.totalEmployees}</Text>
              </View>
            </View>

            {}
            <View style={styles.statsContainer}>
              {}
              <View style={styles.presentCard}>
                <Text style={styles.statCardTitle}>Employees Present</Text>
                <View style={styles.statCountContainer}>
                  <MaterialIcons name="event-available" size={22} color="#FFF" />
                  <Text style={styles.statCountText}>
                    {stats.presentEmployees} / {stats.totalEmployees}
                  </Text>
                </View>
              </View>
              
              {}
              <View style={styles.absentCard}>
                <Text style={styles.statCardTitle}>Absent Employees</Text>
                <View style={styles.statCountContainer}>
                  <MaterialIcons name="event-busy" size={22} color="#FFF" />
                  <Text style={styles.statCountText}>
                    {stats.absentEmployees} / {stats.totalEmployees}
                  </Text>
                </View>
              </View>
            </View>

            {}
            <View style={styles.attendanceContainer}>
              <View style={styles.attendanceHeader}>
                <Text style={styles.attendanceTitle}>Attendance Overview</Text>
              </View>
              
              {}
              {attendanceData.map((employee) => (
                <View key={employee.userId} style={styles.mobileCard}>
                  <Text style={styles.mobileCardName}>{employee.name}</Text>
                  <View style={styles.mobileCardDetails}>
                    <View style={styles.mobileCardRow}>
                      <Text style={styles.mobileCardLabel}>Status:</Text>
                      <View style={[
                        styles.statusContainerMobile,
                        employee.status === 'Present' ? styles.presentStatus :
                        employee.status === 'Late' ? styles.lateStatus : 
                        styles.absentStatus
                      ]}>
                        <Text style={[
                          styles.statusText,
                          employee.status === 'Present' ? styles.presentStatusText :
                          employee.status === 'Late' ? styles.lateStatusText : 
                          styles.absentStatusText
                        ]}>{employee.status}</Text>
                      </View>
                    </View>
                    <View style={styles.mobileCardRow}>
                      <Text style={styles.mobileCardLabel}>Time In:</Text>
                      <Text style={styles.mobileCardValue}>{employee.timeIn}</Text>
                    </View>
                    <View style={styles.mobileCardRow}>
                      <Text style={styles.mobileCardLabel}>Time Out:</Text>
                      <Text style={styles.mobileCardValue}>{employee.timeOut}</Text>
                    </View>
                    <View style={styles.mobileCardRow}>
                      <Text style={styles.mobileCardLabel}>Date:</Text>
                      <Text style={styles.mobileCardValue}>{employee.date}</Text>
                    </View>
                  </View>
                </View>
              ))}
            </View>
          </>
        )}

        {}
        <View style={styles.footer}>
          <Text style={styles.footerText}>© Copyright AttendRoll. All Rights Reserved</Text>
          <Text style={styles.footerText}>Designed by Zal&Sherly</Text>
        </View>
      </ScrollView>

      {}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Dashboard')}>
          <Ionicons name="grid-outline" size={24} color="#6366F1" />
          <Text style={styles.bottomNavText}>Dashboard</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Attendance_Management')}>
          <MaterialIcons name="event-note" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Attendance</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Payroll_Calculation')}>
          <FontAwesome5 name="calculator" size={24} color="#4B5563" />
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

export default Dashboard;

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
  dashboardTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  totalEmployeesCard: {
    backgroundColor: '#1F2937',
    borderRadius: 10,
    padding: 16,
    marginBottom: 16,
  },
  cardTitle: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '500',
    textAlign: 'center',
    marginBottom: 10,
  },
  countContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  countText: {
    color: '#FFFFFF',
    fontSize: 32,
    fontWeight: 'bold',
    marginLeft: 10,
  },
  statsContainer: {
    marginBottom: 16,
  },
  presentCard: {
    backgroundColor: '#10B981',
    borderRadius: 10,
    padding: 16,
    marginBottom: 12,
  },
  absentCard: {
    backgroundColor: '#EF4444',
    borderRadius: 10,
    padding: 16,
  },
  statCardTitle: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '500',
    textAlign: 'center',
    marginBottom: 8,
  },
  statCountContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  statCountText: {
    color: '#FFFFFF',
    fontSize: 22,
    fontWeight: 'bold',
    marginLeft: 10,
  },
  attendanceContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    overflow: 'hidden',
    marginBottom: 16,
  },
  attendanceHeader: {
    backgroundColor: '#1F2937',
    padding: 14,
  },
  attendanceTitle: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '500',
  },
  
  mobileCard: {
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    padding: 15,
  },
  mobileCardName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
    color: '#1F2937',
  },
  mobileCardDetails: {
    paddingLeft: 8,
  },
  mobileCardRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  mobileCardLabel: {
    width: 80,
    fontSize: 14,
    color: '#6B7280',
    fontWeight: '500',
  },
  mobileCardValue: {
    fontSize: 14,
    color: '#4B5563',
  },
  statusContainerMobile: {
    padding: 5,
    borderRadius: 5,
    alignItems: 'center',
    justifyContent: 'center',
    minWidth: 60,
  },
  presentStatus: {
    backgroundColor: '#D1FAE5', 
  },
  lateStatus: {
    backgroundColor: '#FEF3C7', 
  },
  absentStatus: {
    backgroundColor: '#FEE2E2', 
  },
  statusText: {
    fontSize: 12,
    fontWeight: '500',
  },
  presentStatusText: {
    color: '#10B981', 
  },
  lateStatusText: {
    color: '#F59E0B', 
  },
  absentStatusText: {
    color: '#EF4444', 
  },
  footer: {
    padding: 16,
    alignItems: 'center',
  },
  footerText: {
    color: '#6B7280',
    fontSize: 12,
    marginBottom: 4,
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
});
