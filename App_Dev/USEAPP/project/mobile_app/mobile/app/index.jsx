import React, { useState, useEffect } from 'react'
import { Link, useRouter } from 'expo-router'
import {
  StyleSheet,
  Text,
  View,
  TextInput,
  TouchableOpacity,
  Image,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from 'react-native'

// Import logo and authentication context
import logo from '../assets/ATTEND ROLL.png' 
import { useAuth } from './services/authContext'

// Login screen component
const LoginScreen = () => {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const { login, isLoading, isAuthenticated, error, user } = useAuth()
  const router = useRouter()

  // Redirect authenticated users based on role
  useEffect(() => {
    if (isAuthenticated) {
      if (user?.userRole === 'admin') {
        router.push('/Admin/Dashboard')
      } else if (user?.userRole === 'user') {
        router.push('/Employee/Dashboard')
      }
    }
  }, [isAuthenticated, user, router])

  // Handle login submission
  const handleLogin = async () => {
    if (!email.trim() || !password.trim()) {
      Alert.alert('Validation Error', 'Email and password are required.')
      return
    }

    const result = await login(email, password)
    
    if (!result.success) {
      Alert.alert('Login Failed', result.message || 'Invalid credentials')
    }
    
  }

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={styles.screen}
    >
      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Logo section */}
        <View style={styles.logoContainer}>
          <Image source={logo} style={styles.logo} />
        </View>

        {/* Login form */}
        <View style={styles.form}>
          <TextInput
            placeholder="Email"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
            style={styles.input}
          />
          <TextInput
            placeholder="Password"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            autoCapitalize="none"
            style={styles.input}
          />

          {/* Login button */}
          <TouchableOpacity 
            style={styles.button} 
            onPress={handleLogin}
            disabled={isLoading}
          >
            {isLoading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.buttonText}>Login</Text>
            )}
          </TouchableOpacity>

          {/* Additional links */}
          <TouchableOpacity>
            <Link href="/signup" style={styles.link}>Don't have an account? Sign up</Link>
          </TouchableOpacity>
          <TouchableOpacity>
            <Text style={styles.link}>Forgot your password?</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  )
}

export default LoginScreen

// Styles
const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: '#fff',
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingVertical: 32,
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 40,
  },
  logo: {
    width: 140,
    height: 140,
    borderRadius: 70,
    resizeMode: 'cover',
  },
  form: {
    width: '100%',
  },
  input: {
    height: 50,
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 6,
    marginBottom: 16,
    paddingHorizontal: 16,
    fontSize: 16,
  },
  button: {
    backgroundColor: '#000',
    paddingVertical: 14,
    borderRadius: 6,
    alignItems: 'center',
    marginBottom: 20,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  link: {
    color: '#007bff',
    textAlign: 'center',
    fontSize: 14,
    marginTop: 10,
  },
})
