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
  TextInput,
  Alert,
  ActivityIndicator
} from 'react-native';
import { Ionicons, FontAwesome5, MaterialIcons, Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import DateTimePicker from '@react-native-community/datetimepicker';
import { Picker } from '@react-native-picker/picker';
import { useAuth } from '../services/authContext';
import { adminService } from '../services/api';

const Employee_Management = () => {
  const router = useRouter();
  const { user, logout } = useAuth();
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState(null);
  const [loading, setLoading] = useState(true);
  const [employees, setEmployees] = useState([]);
  const [formSubmitting, setFormSubmitting] = useState(false);

  
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [gender, setGender] = useState('Male');
  const [birthday, setBirthday] = useState(new Date());
  const [showDatePicker, setShowDatePicker] = useState(false);

  
  useEffect(() => {
    
    if (user && user.userRole !== 'admin') {
      Alert.alert('Access Denied', 'You do not have permission to access this page');
      router.push('/');
      return;
    }
    
    loadEmployees();
  }, []);

  const loadEmployees = async () => {
    try {
      setLoading(true);
      const response = await adminService.getEmployees();
      
      if (response.status) {
        setEmployees(response.data);
      } else {
        Alert.alert('Error', response.message || 'Failed to load employees');
      }
    } catch (error) {
      console.error('Error loading employees:', error);
      Alert.alert('Error', 'Failed to connect to the server');
    } finally {
      setLoading(false);
    }
  };

  const handleAdd = async () => {
    if (!validateForm()) return;
    
    try {
      setFormSubmitting(true);
      
      
      const formattedBirthday = birthday.toISOString().split('T')[0]; 
      
      const employeeData = {
        firstName,
        lastName,
        email,
        phone,
        gender,
        birthday: formattedBirthday
      };
      
      const response = await adminService.addEmployee(employeeData);
      
      if (response.status) {
        Alert.alert('Success', 'Employee added successfully');
        setShowAddModal(false);
        clearForm();
        loadEmployees(); 
      } else {
        Alert.alert('Error', response.message || 'Failed to add employee');
      }
    } catch (error) {
      console.error('Error adding employee:', error);
      Alert.alert('Error', 'Failed to connect to the server');
    } finally {
      setFormSubmitting(false);
    }
  };

  const handleUpdate = async () => {
    if (!validateForm()) return;
    
    try {
      setFormSubmitting(true);
      
      
      const formattedBirthday = birthday.toISOString().split('T')[0]; 
      
      const employeeData = {
        firstName,
        lastName,
        email,
        phone,
        gender,
        birthday: formattedBirthday
      };
      
      const response = await adminService.updateEmployee(selectedEmployee.id, employeeData);
      
      if (response.status) {
        Alert.alert('Success', 'Employee updated successfully');
        setShowEditModal(false);
        clearForm();
        loadEmployees(); 
      } else {
        Alert.alert('Error', response.message || 'Failed to update employee');
      }
    } catch (error) {
      console.error('Error updating employee:', error);
      Alert.alert('Error', 'Failed to connect to the server');
    } finally {
      setFormSubmitting(false);
    }
  };

  const handleDelete = (id) => {
    Alert.alert(
      'Delete Employee',
      'Are you sure you want to delete this employee?',
      [
        {
          text: 'Cancel',
          style: 'cancel'
        },
        {
          text: 'Delete',
          onPress: async () => {
            try {
              const response = await adminService.deleteEmployee(id);
              
              if (response.status) {
                Alert.alert('Success', 'Employee deleted successfully');
                loadEmployees(); 
              } else {
                Alert.alert('Error', response.message || 'Failed to delete employee');
              }
            } catch (error) {
              console.error('Error deleting employee:', error);
              Alert.alert('Error', 'Failed to connect to the server');
            }
          },
          style: 'destructive'
        }
      ]
    );
  };

  const handleEdit = (employee) => {
    setSelectedEmployee(employee);
    setFirstName(employee.firstName);
    setLastName(employee.lastName);
    setEmail(employee.email);
    setPhone(employee.phone || '');
    setGender(employee.gender || 'Male');
    
    
    if (employee.birthday) {
      const [year, month, day] = employee.birthday.split('-');
      setBirthday(new Date(year, month - 1, day));
    }
    
    setShowEditModal(true);
  };

  const validateForm = () => {
    if (!firstName.trim()) {
      Alert.alert('Validation Error', 'First name is required');
      return false;
    }
    
    if (!lastName.trim()) {
      Alert.alert('Validation Error', 'Last name is required');
      return false;
    }
    
    if (!email.trim()) {
      Alert.alert('Validation Error', 'Email is required');
      return false;
    }
    
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      Alert.alert('Validation Error', 'Please enter a valid email address');
      return false;
    }
    
    return true;
  };

  const clearForm = () => {
    setFirstName('');
    setLastName('');
    setEmail('');
    setPhone('');
    setGender('Male');
    setBirthday(new Date());
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

  const EmployeeForm = ({ isEdit, onSubmit, onClose }) => (
    <View style={styles.modalContent}>
      <Text style={styles.modalTitle}>{isEdit ? 'Edit Employee' : 'Add New Employee'}</Text>
      
      <TextInput
        style={styles.input}
        placeholder="First Name"
        value={firstName}
        onChangeText={setFirstName}
      />
      <TextInput
        style={styles.input}
        placeholder="Last Name"
        value={lastName}
        onChangeText={setLastName}
      />
      <TextInput
        style={styles.input}
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
      />
      <TextInput
        style={styles.input}
        placeholder="Phone Number"
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
      />
      
      <Picker
        selectedValue={gender}
        onValueChange={setGender}
        style={styles.input}
      >
        <Picker.Item label="Male" value="Male" />
        <Picker.Item label="Female" value="Female" />
      </Picker>

      <TouchableOpacity
        style={styles.datePickerButton}
        onPress={() => setShowDatePicker(true)}
      >
        <Text>Select Birthday: {birthday.toDateString()}</Text>
      </TouchableOpacity>

      {showDatePicker && (
        <DateTimePicker
          value={birthday}
          mode="date"
          onChange={(event, date) => {
            setShowDatePicker(false);
            if (date) setBirthday(date);
          }}
        />
      )}

      <View style={styles.modalButtons}>
        <TouchableOpacity 
          style={[styles.submitButton, formSubmitting && styles.disabledButton]}
          onPress={onSubmit}
          disabled={formSubmitting}
        >
          {formSubmitting ? (
            <ActivityIndicator size="small" color="#FFFFFF" />
          ) : (
            <Text style={styles.buttonText}>
              {isEdit ? 'Update Employee' : 'Add Employee'}
            </Text>
          )}
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.cancelButton}
          onPress={onClose}
          disabled={formSubmitting}
        >
          <Text style={styles.buttonText}>Cancel</Text>
        </TouchableOpacity>
      </View>
    </View>
  );

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
          <Text style={styles.title}>Employee List</Text>
          <TouchableOpacity 
            style={styles.addButton}
            onPress={() => {
              clearForm();
              setShowAddModal(true);
            }}
          >
            <Text style={styles.addButtonText}>+ Add Employee</Text>
          </TouchableOpacity>
        </View>

        {loading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#6366F1" />
          </View>
        ) : (
          <ScrollView style={styles.tableContainer}>
            {employees.length === 0 ? (
              <View style={styles.noDataContainer}>
                <Text style={styles.noDataText}>No employees found</Text>
              </View>
            ) : (
              employees.map((employee) => (
                <View key={employee.id} style={styles.employeeCard}>
                  <View style={styles.employeeInfo}>
                    <Text style={styles.employeeName}>
                      {employee.firstName} {employee.lastName}
                    </Text>
                    <Text style={styles.employeeDetail}>{employee.email}</Text>
                    <Text style={styles.employeeDetail}>{employee.phone}</Text>
                    <Text style={styles.employeeDetail}>{employee.gender}</Text>
                  </View>
                  <View style={styles.actionButtons}>
                    <TouchableOpacity 
                      style={styles.editButton}
                      onPress={() => handleEdit(employee)}
                    >
                      <Text style={styles.editButtonText}>Edit</Text>
                    </TouchableOpacity>
                    <TouchableOpacity 
                      style={styles.deleteButton}
                      onPress={() => handleDelete(employee.id)}
                    >
                      <Text style={styles.deleteButtonText}>Remove</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              ))
            )}
          </ScrollView>
        )}
      </View>

      {}
      <Modal
        visible={showAddModal}
        transparent={true}
        animationType="slide"
      >
        <View style={styles.modalOverlay}>
          <EmployeeForm
            isEdit={false}
            onSubmit={handleAdd}
            onClose={() => setShowAddModal(false)}
          />
        </View>
      </Modal>

      {}
      <Modal
        visible={showEditModal}
        transparent={true}
        animationType="slide"
      >
        <View style={styles.modalOverlay}>
          <EmployeeForm
            isEdit={true}
            onSubmit={handleUpdate}
            onClose={() => setShowEditModal(false)}
          />
        </View>
      </Modal>

      {}
      <View style={styles.bottomNav}>
        <TouchableOpacity 
          style={styles.navItem} 
          onPress={() => router.push('/Admin/Dashboard')}
        >
          <FontAwesome5 name="th-large" size={20} color="#6B7280" />
          <Text style={styles.navText}>Dashboard</Text>
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={styles.navItem}
          onPress={() => router.push('/Admin/Attendance_Management')}
        >
          <FontAwesome5 name="calendar-check" size={20} color="#6B7280" />
          <Text style={styles.navText}>Attendance</Text>
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={styles.navItem}
          onPress={() => router.push('/Admin/Payroll_Calculation')}
        >
          <FontAwesome5 name="calculator" size={20} color="#6B7280" />
          <Text style={styles.navText}>Payroll</Text>
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={[styles.navItem, styles.activeNavItem]}
        >
          <FontAwesome5 name="users" size={20} color="#6366F1" />
          <Text style={[styles.navText, styles.activeNavText]}>Employees</Text>
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={styles.navItem}
          onPress={handleLogout}
        >
          <FontAwesome5 name="sign-out-alt" size={20} color="#6B7280" />
          <Text style={styles.navText}>Logout</Text>
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
  addButton: {
    backgroundColor: '#10B981',
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 6,
  },
  addButtonText: {
    color: '#FFFFFF',
    fontWeight: '500',
  },
  tableContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 10,
  },
  employeeCard: {
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    padding: 15,
  },
  employeeInfo: {
    marginBottom: 10,
  },
  employeeName: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 4,
  },
  employeeDetail: {
    color: '#4B5563',
    marginBottom: 2,
  },
  actionButtons: {
    flexDirection: 'row',
    gap: 10,
  },
  editButton: {
    backgroundColor: '#1F2937',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 4,
  },
  editButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
  },
  deleteButton: {
    backgroundColor: '#DC2626',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 4,
  },
  deleteButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
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
    width: '90%',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 15,
  },
  input: {
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 6,
    padding: 10,
    marginBottom: 12,
  },
  datePickerButton: {
    borderWidth: 1,
    borderColor: '#E5E7EB',
    borderRadius: 6,
    padding: 10,
    marginBottom: 12,
  },
  modalButtons: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 10,
  },
  submitButton: {
    flex: 1,
    backgroundColor: '#10B981',
    padding: 12,
    borderRadius: 6,
    alignItems: 'center',
  },
  cancelButton: {
    flex: 1,
    backgroundColor: '#DC2626',
    padding: 12,
    borderRadius: 6,
    alignItems: 'center',
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: '500',
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
  navItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  navText: {
    fontSize: 10,
    marginTop: 4,
    color: '#4B5563',
  },
  activeNavItem: {
    backgroundColor: '#E5E7EB',
  },
  activeNavText: {
    fontWeight: 'bold',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 50,
  },
  noDataContainer: {
    padding: 30,
    alignItems: 'center',
  },
  noDataText: {
    fontSize: 16,
    color: '#6B7280',
  },
  disabledButton: {
    backgroundColor: '#9CA3AF',
  },
});

export default Employee_Management;
