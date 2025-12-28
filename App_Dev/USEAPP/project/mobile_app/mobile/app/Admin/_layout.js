import React from 'react';
import { Stack } from 'expo-router';
import { useAuth } from '../services/authContext';

export default function AdminLayout() {
  const { user } = useAuth();
  
  
  
  return (
    <Stack>
      <Stack.Screen name="Dashboard" options={{ headerShown: false }} />
      <Stack.Screen name="Attendance_Management" options={{ headerShown: false }} />
      <Stack.Screen name="Employee_Management" options={{ headerShown: false }} />
      <Stack.Screen name="Payroll_Calculation" options={{ headerShown: false }} />
    </Stack>
  );
} 
