import React, { createContext, useState, useContext } from 'react';
import { authService } from './api';

// Simple in-memory storage for user data since AsyncStorage may not be available
const memoryStorage = {
  userData: null,
  setItem: (key, value) => {
    memoryStorage.userData = value;
  },
  getItem: () => {
    return memoryStorage.userData;
  },
  removeItem: () => {
    memoryStorage.userData = null;
  }
};

// Create authentication context
const AuthContext = createContext(null);

// Authentication provider component
export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  // Handle user login
  const login = async (email, password) => {
    setError(null);
    setIsLoading(true);
    
    try {
      const response = await authService.login(email, password);
      
      if (response.status) {
        const userData = response.data;
        setUser(userData);
        memoryStorage.setItem('user', JSON.stringify(userData));
        return { success: true };
      } else {
        setError(response.message || 'Login failed');
        return { success: false, message: response.message };
      }
    } catch (e) {
      const errorMsg = e.message || 'An error occurred during login';
      setError(errorMsg);
      return { success: false, message: errorMsg };
    } finally {
      setIsLoading(false);
    }
  };

  // Handle user logout
  const logout = async () => {
    setUser(null);
    memoryStorage.removeItem('user');
  };

  // Context value with authentication state and functions
  const value = {
    user,
    isLoading,
    error,
    login,
    logout,
    isAuthenticated: !!user
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

// Custom hook to use auth context
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

// Default export
export default useAuth; 
