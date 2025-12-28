import React from 'react';
import { Stack } from 'expo-router';
import { useAuth } from '../services/authContext';

export default function EmployeeLayout() {
  const { user } = useAuth();
  
  
  
  return (
    <Stack>
      <Stack.Screen name="Dashboard" options={{ headerShown: false }} />
      <Stack.Screen name="History" options={{ headerShown: false }} />
      <Stack.Screen name="Salary" options={{ headerShown: false }} />
      <Stack.Screen name="Payslip" options={{ headerShown: false }} />
    </Stack>
  );
} 
