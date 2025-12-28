import React, { useState } from 'react';
import { StyleSheet, Text, View, TextInput, TouchableOpacity, ScrollView, Alert, Pressable, ActivityIndicator } from 'react-native';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useRouter } from 'expo-router';
import { authService } from './services/api';

const Signup = () => {
  const router = useRouter();
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [cpassword, setCPassword] = useState('');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [gender, setGender] = useState('');
  const [birthday, setBirthday] = useState(new Date());
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleSignup = async () => {
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    let newErrors = {};

    if (!termsAccepted) newErrors.terms = 'You must accept the terms and conditions.';
    if (password !== cpassword) newErrors.password = 'Passwords do not match.';
    if (!firstName || !lastName || !email || !password || !phoneNumber || !gender)
      newErrors.fields = 'Please fill out all required fields.';
    if (!emailRegex.test(email)) 
      newErrors.email = 'Please enter a valid email address.';
    if (password.length < 6)
      newErrors.passwordLength = 'Password must be at least 6 characters long.';
    if (!/^\d{10}$/.test(phoneNumber))
      newErrors.phone = 'Please enter a valid 10-digit phone number.';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    setLoading(true);

    try {
      
      const formattedDate = birthday.toISOString().split('T')[0];
      
      const userData = {
        firstName,
        lastName,
        email,
        password,
        phoneNumber,
        gender,
        birthday: formattedDate
      };
      
      const response = await authService.register(userData);
      
      if (response.status) {
        Alert.alert('Success', 'Account created successfully!', [
          {
            text: 'OK',
            onPress: () => router.push('/')
          }
        ]);
      } else {
        Alert.alert('Error', response.message || 'Registration failed');
      }
    } catch (error) {
      console.error('Registration error:', error);
      Alert.alert('Connection Error', 'Could not connect to the server. Please check your internet connection.');
    } finally {
      setLoading(false);
    }
  };

  const CustomCheckbox = ({ checked, onChange, label }) => (
    <Pressable style={styles.checkboxContainer} onPress={() => onChange(!checked)}>
      <Text style={styles.checkbox}>{checked ? '☑️' : '⬜️'}</Text>
      <Text style={styles.checkboxLabel}>{label}</Text>
    </Pressable>
  );

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>Create an Account</Text>
      
      {errors.fields && <Text style={styles.errorText}>{errors.fields}</Text>}
      
      <TextInput style={styles.input} placeholder="First Name" value={firstName} onChangeText={setFirstName} />
      <TextInput style={styles.input} placeholder="Last Name" value={lastName} onChangeText={setLastName} />
      <TextInput 
        style={[styles.input, errors.email && styles.inputError]} 
        placeholder="Email" 
        keyboardType="email-address" 
        value={email} 
        onChangeText={(text) => {
          setEmail(text);
          setErrors({...errors, email: null});
        }} 
      />
      {errors.email && <Text style={styles.errorText}>{errors.email}</Text>}
      
      <TextInput 
        style={[styles.input, (errors.password || errors.passwordLength) && styles.inputError]}
        placeholder="Password" 
        secureTextEntry 
        value={password} 
        onChangeText={setPassword} 
      />
      {errors.passwordLength && <Text style={styles.errorText}>{errors.passwordLength}</Text>}
      
      <TextInput 
        style={[styles.input, errors.password && styles.inputError]}
        placeholder="Confirm Password" 
        secureTextEntry 
        value={cpassword} 
        onChangeText={setCPassword} 
      />
      {errors.password && <Text style={styles.errorText}>{errors.password}</Text>}
      
      <TextInput 
        style={[styles.input, errors.phone && styles.inputError]}
        placeholder="Phone Number" 
        keyboardType="phone-pad" 
        value={phoneNumber} 
        onChangeText={setPhoneNumber} 
      />
      {errors.phone && <Text style={styles.errorText}>{errors.phone}</Text>}

      <Picker selectedValue={gender} onValueChange={setGender} style={styles.input}>
        <Picker.Item label="Select Gender" value="" />
        <Picker.Item label="Male" value="Male" />
        <Picker.Item label="Female" value="Female" />
      </Picker>

      <TouchableOpacity style={styles.datePickerButton} onPress={() => setShowDatePicker(true)}>
        <Text style={styles.datePickerText}>Birthday: {birthday.toDateString()}</Text>
      </TouchableOpacity>
      {showDatePicker && (
        <DateTimePicker
          value={birthday}
          mode="date"
          display="default"
          onChange={(event, selectedDate) => {
            const currentDate = selectedDate || birthday;
            setShowDatePicker(false);
            setBirthday(currentDate);
          }}
        />
      )}

      <CustomCheckbox
        label="I agree to the terms and conditions"
        checked={termsAccepted}
        onChange={setTermsAccepted}
      />
      {errors.terms && <Text style={styles.errorText}>{errors.terms}</Text>}

      <TouchableOpacity 
        style={styles.button} 
        onPress={handleSignup}
        disabled={loading}
      >
        {loading ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.buttonText}>Create Account</Text>
        )}
      </TouchableOpacity>
    </ScrollView>
  );
};

export default Signup;

const styles = StyleSheet.create({
  container: {
    padding: 20,
    backgroundColor: '#fff',
    flexGrow: 1,
    justifyContent: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 20,
    textAlign: 'center',
  },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    padding: 10,
    marginBottom: 15,
  },
  datePickerButton: {
    padding: 10,
    backgroundColor: '#eee',
    borderRadius: 8,
    marginBottom: 15,
  },
  datePickerText: {
    color: '#333',
  },
  checkboxContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  checkboxLabel: {
    marginLeft: 10,
    fontSize: 14,
  },
  checkbox: {
    fontSize: 22,
  },
  button: {
    backgroundColor: '#0d6efd',
    padding: 15,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  errorText: {
    color: 'red',
    fontSize: 12,
    marginBottom: 5,
  },
  inputError: {
    borderColor: 'red',
  }
});
