import React from 'react';
import { Stack } from 'expo-router';
import { AuthProvider } from './services/authContext';

export default function RootLayout() {
  return (
    <AuthProvider>
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="index" />
        <Stack.Screen name="signup" options={{ headerShown: true, title: "Sign Up" }} />
        <Stack.Screen name="Employee" />
        <Stack.Screen name="Admin" />
        <Stack.Screen name="logout" options={{ headerShown: false }} />
      </Stack>
    </AuthProvider>
  );
} 
