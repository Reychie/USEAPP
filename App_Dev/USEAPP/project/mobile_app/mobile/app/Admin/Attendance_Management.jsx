import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  SafeAreaView, 
  ScrollView, 
  TouchableOpacity,
  Modal,
  Platform,
  StatusBar,
  Alert,
  ActivityIndicator
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import DateTimePicker from '@react-native-community/datetimepicker';
import { adminService } from '../services/api';
import { useAuth } from '../services/authContext';

const Attendance_Management = () => {
  const router = useRouter();
  const { user, logout } = useAuth();
  const [loading, setLoading] = useState(true);
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [selectedAttendance, setSelectedAttendance] = useState(null);
  const [attendanceData, setAttendanceData] = useState([]);
  const [editTimeIn, setEditTimeIn] = useState('');
  const [editTimeOut, setEditTimeOut] = useState('');
  const [showTimePicker, setShowTimePicker] = useState(false);
  const [timePickerMode, setTimePickerMode] = useState('timeIn'); 

  
  const loadAttendanceData = async () => {
    try {
      setLoading(true);
      const dateString = selectedDate.toISOString().split('T')[0]; 
      const response = await adminService.getAttendanceRecords(dateString);
      
      if (response.status) {
        setAttendanceData(response.data);
      } else {
        Alert.alert('Error', 'Failed to load attendance records');
      }
    } catch (error) {
      console.error('Error loading attendance records:', error);
      Alert.alert('Error', 'Failed to connect to the server');
    } finally {
      setLoading(false);
    }
  };

  
  useEffect(() => {
    
    if (user && user.userRole !== 'admin') {
      Alert.alert('Access Denied', 'You do not have permission to access this page');
      router.push('/');
      return;
    }
    
    loadAttendanceData();
  }, [selectedDate]);

  const handleEdit = (attendance) => {
    
    const timeIn = attendance.timeIn !== '-' ? attendance.timeIn : '';
    const timeOut = attendance.timeOut !== '-' ? attendance.timeOut : '';
    
    setEditTimeIn(timeIn);
    setEditTimeOut(timeOut);
    setSelectedAttendance(attendance);
    setShowEditModal(true);
  };

  const handleTimePickerChange = (event, selectedTime) => {
    if (selectedTime) {
      
      const hours = selectedTime.getHours().toString().padStart(2, '0');
      const minutes = selectedTime.getMinutes().toString().padStart(2, '0');
      const formattedTime = `${hours}:${minutes} ${hours >= 12 ? 'PM' : 'AM'}`;
      
      
      if (timePickerMode === 'timeIn') {
        setEditTimeIn(formattedTime);
      } else {
        setEditTimeOut(formattedTime);
      }
    }
    setShowTimePicker(false);
  };

  const handleSaveEdit = async () => {
    if (!selectedAttendance) return;
    
    try {
      const timeInParts = editTimeIn !== '' 
        ? editTimeIn.split(' ')[0].split(':')
        : ['00', '00'];
        
      const timeOutParts = editTimeOut !== ''
        ? editTimeOut.split(' ')[0].split(':')
        : ['00', '00'];
      
      
      const timeIn = `${timeInParts[0]}:${timeInParts[1]}:00`;
      const timeOut = `${timeOutParts[0]}:${timeOutParts[1]}:00`;
      
      const response = await adminService.updateAttendance(
        selectedAttendance.attendanceId,
        timeIn,
        timeOut
      );
      
      if (response.status) {
        Alert.alert('Success', 'Attendance record updated successfully');
        setShowEditModal(false);
        loadAttendanceData(); 
      } else {
        Alert.alert('Error', response.message || 'Failed to update attendance record');
      }
    } catch (error) {
      console.error('Error updating attendance:', error);
      Alert.alert('Error', 'Failed to update attendance record');
    }
  };

  const handleDelete = async (attendanceId) => {
    Alert.alert(
      "Delete Attendance",
      "Are you sure you want to delete this attendance record?",
      [
        {
          text: "Cancel",
          style: "cancel"
        },
        {
          text: "Delete",
          onPress: async () => {
            try {
              const response = await adminService.deleteAttendance(attendanceId);
              
              if (response.status) {
                Alert.alert('Success', 'Attendance record deleted successfully');
                loadAttendanceData(); 
              } else {
                Alert.alert('Error', response.message || 'Failed to delete attendance record');
              }
            } catch (error) {
              console.error('Error deleting attendance:', error);
              Alert.alert('Error', 'Failed to connect to the server');
            }
          },
          style: "destructive"
        }
      ]
    );
  };
  
  const handleDateChange = (event, date) => {
    if (date) {
      setSelectedDate(date);
    }
    setShowFilterModal(false);
  };

  
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
      <View style={styles.mainContent}>
        <View style={styles.titleContainer}>
          <Text style={styles.title}>Employee Attendance Records</Text>
          <TouchableOpacity 
            style={styles.filterButton}
            onPress={() => setShowFilterModal(true)}
          >
            <MaterialIcons name="filter-list" size={24} color="#FFF" />
            <Text style={styles.filterButtonText}>Filter</Text>
          </TouchableOpacity>
        </View>
        
        {}
        <View style={styles.dateDisplay}>
          <Text style={styles.dateText}>
            {selectedDate.toLocaleDateString('en-US', { 
              weekday: 'long', 
              year: 'numeric', 
              month: 'long', 
              day: 'numeric'
            })}
          </Text>
        </View>

        {loading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#6366F1" />
          </View>
        ) : (
          <ScrollView style={styles.tableContainer}>
            {attendanceData.length === 0 ? (
              <View style={styles.noDataContainer}>
                <Text style={styles.noDataText}>No attendance records found for this date</Text>
              </View>
            ) : (
              attendanceData.map((item) => (
                <View key={item.attendanceId} style={styles.tableRow}>
                  <View style={styles.rowContent}>
                    <Text style={styles.employeeName}>{item.name}</Text>
                    <Text style={styles.dateText}>{item.date}</Text>
                    <View style={[
                      styles.statusBadge,
                      item.status === 'Present' ? styles.presentBadge :
                      item.status === 'Late' ? styles.lateBadge :
                      styles.absentBadge
                    ]}>
                      <Text style={styles.statusText}>{item.status}</Text>
                    </View>
                    <View style={styles.timeContainer}>
                      <Text style={styles.timeText}>In: {item.timeIn}</Text>
                      <Text style={styles.timeText}>Out: {item.timeOut}</Text>
                    </View>
                    <View style={styles.actionContainer}>
                      <TouchableOpacity 
                        style={styles.editButton}
                        onPress={() => handleEdit(item)}
                      >
                        <Text style={styles.editButtonText}>Edit</Text>
                      </TouchableOpacity>
                      <TouchableOpacity 
                        style={styles.deleteButton}
                        onPress={() => handleDelete(item.attendanceId)}
                      >
                        <Text style={styles.deleteButtonText}>Delete</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              ))
            )}
          </ScrollView>
        )}
      </View>

      {}
      <Modal
        visible={showFilterModal}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setShowFilterModal(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Filter Attendance</Text>
            <DateTimePicker
              value={selectedDate}
              mode="date"
              display="default"
              onChange={handleDateChange}
            />
          </View>
        </View>
      </Modal>

      {}
      {selectedAttendance && (
        <Modal
          visible={showEditModal}
          transparent={true}
          animationType="slide"
          onRequestClose={() => setShowEditModal(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <Text style={styles.modalTitle}>Edit Attendance</Text>
              
              <View style={styles.modalField}>
                <Text style={styles.modalLabel}>Employee:</Text>
                <Text style={styles.modalValue}>{selectedAttendance.name}</Text>
              </View>
              
              <View style={styles.modalField}>
                <Text style={styles.modalLabel}>Date:</Text>
                <Text style={styles.modalValue}>{selectedAttendance.date}</Text>
              </View>
              
              <View style={styles.modalField}>
                <Text style={styles.modalLabel}>Status:</Text>
                <Text style={styles.modalValue}>{selectedAttendance.status}</Text>
              </View>
              
              <View style={styles.modalField}>
                <Text style={styles.modalLabel}>Time In:</Text>
                <TouchableOpacity 
                  style={styles.timePickerButton}
                  onPress={() => {
                    setTimePickerMode('timeIn');
                    setShowTimePicker(true);
                  }}
                >
                  <Text style={styles.timePickerButtonText}>{editTimeIn || 'Select Time'}</Text>
                </TouchableOpacity>
              </View>
              
              <View style={styles.modalField}>
                <Text style={styles.modalLabel}>Time Out:</Text>
                <TouchableOpacity 
                  style={styles.timePickerButton}
                  onPress={() => {
                    setTimePickerMode('timeOut');
                    setShowTimePicker(true);
                  }}
                >
                  <Text style={styles.timePickerButtonText}>{editTimeOut || 'Select Time'}</Text>
                </TouchableOpacity>
              </View>
              
              {showTimePicker && (
                <DateTimePicker
                  value={new Date()}
                  mode="time"
                  is24Hour={false}
                  display="default"
                  onChange={handleTimePickerChange}
                />
              )}
              
              <View style={styles.modalActions}>
                <TouchableOpacity 
                  style={styles.modalCancelButton}
                  onPress={() => setShowEditModal(false)}
                >
                  <Text style={styles.modalCancelButtonText}>Cancel</Text>
                </TouchableOpacity>
                <TouchableOpacity 
                  style={styles.modalSaveButton}
                  onPress={handleSaveEdit}
                >
                  <Text style={styles.modalSaveButtonText}>Save</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>
      )}

      {}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.bottomNavItem} onPress={() => router.push('/Admin/Dashboard')}>
          <Ionicons name="grid-outline" size={24} color="#4B5563" />
          <Text style={styles.bottomNavText}>Dashboard</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.bottomNavItem}>
          <MaterialIcons name="event-note" size={24} color="#6366F1" />
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
  titleContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
  },
  filterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1F2937',
    padding: 8,
    borderRadius: 8,
  },
  filterButtonText: {
    color: '#FFF',
    marginLeft: 5,
  },
  tableContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 10,
  },
  tableRow: {
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    paddingVertical: 10,
  },
  rowContent: {
    padding: 10,
  },
  employeeName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 5,
  },
  dateText: {
    color: '#6B7280',
    marginBottom: 5,
  },
  statusBadge: {
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 4,
    alignSelf: 'flex-start',
    marginBottom: 5,
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
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '500',
  },
  timeContainer: {
    marginVertical: 5,
  },
  timeText: {
    color: '#4B5563',
  },
  editButton: {
    backgroundColor: '#1F2937',
    padding: 8,
    borderRadius: 6,
    alignSelf: 'flex-start',
    marginTop: 5,
  },
  editButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
  },
  actionContainer: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 5,
  },
  deleteButton: {
    backgroundColor: '#DC2626',
    padding: 8,
    borderRadius: 6,
    alignSelf: 'flex-start',
  },
  deleteButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
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
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    backgroundColor: '#FFFFFF',
    padding: 20,
    borderRadius: 10,
    width: '80%',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 15,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 30,
  },
  noDataContainer: {
    padding: 30,
    alignItems: 'center',
  },
  noDataText: {
    fontSize: 16,
    color: '#6B7280',
  },
  dateDisplay: {
    backgroundColor: '#FFFFFF',
    padding: 12,
    marginBottom: 10,
    borderRadius: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 1,
    elevation: 2,
  },
  modalField: {
    marginBottom: 15,
  },
  modalLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: '#6B7280',
    marginBottom: 4,
  },
  modalValue: {
    fontSize: 16,
    color: '#111827',
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 20,
  },
  modalCancelButton: {
    flex: 1,
    backgroundColor: '#E5E7EB',
    padding: 12,
    borderRadius: 6,
    marginRight: 8,
    alignItems: 'center',
  },
  modalCancelButtonText: {
    color: '#4B5563',
    fontWeight: '500',
  },
  modalSaveButton: {
    flex: 1,
    backgroundColor: '#6366F1',
    padding: 12,
    borderRadius: 6,
    marginLeft: 8,
    alignItems: 'center',
  },
  modalSaveButtonText: {
    color: '#FFFFFF',
    fontWeight: '500',
  },
  timePickerButton: {
    backgroundColor: '#F3F4F6',
    padding: 12,
    borderRadius: 6,
    width: '100%',
    marginTop: 4,
    borderWidth: 1,
    borderColor: '#D1D5DB'
  },
  timePickerButtonText: {
    color: '#111827',
    fontSize: 16,
  },
});

export default Attendance_Management;
