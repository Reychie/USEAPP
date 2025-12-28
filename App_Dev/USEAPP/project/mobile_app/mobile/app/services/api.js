// Base API URL and path configuration
const API_BASE_URL = '192.168.1.5'; 
const API_PORT = '80'; // Standard HTTP port
const API_PATH = '/app_dev_last/Desktop/AR_Attendance/api';

// Function to construct the complete API URL
const getApiUrl = () => {
  return `http://${API_BASE_URL}:${API_PORT}${API_PATH}`;
};

// API endpoint definitions
const ENDPOINTS = {
  LOGIN: '/login.php',
  REGISTER: '/register.php',
  ATTENDANCE: '/attendance.php',
  SALARY: '/salary.php',
  PAYSLIP: '/payslip.php',
  ADMIN: '/admin.php',
  EMPLOYEE: '/employee.php'  
};

/**
 * Make an API request
 * @param {string} endpoint - API endpoint
 * @param {string} method - HTTP method (GET, POST, etc.)
 * @param {Object} data - Request body for POST/PUT requests
 * @param {Object} params - URL parameters for GET requests
 * @returns {Promise} - Promise that resolves to the API response
 */
export const apiRequest = async (endpoint, method = 'GET', data = null, params = {}) => {
  try {
    // Construct complete API URL
    const apiUrl = getApiUrl();
    const url = new URL(`${apiUrl}${endpoint}`);
    
    // Add query parameters for GET requests
    if (method === 'GET' && Object.keys(params).length > 0) {
      Object.keys(params).forEach(key => {
        if (params[key] !== undefined && params[key] !== null) {
          url.searchParams.append(key, params[key]);
        }
      });
    }
    
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
      },
    };
    
    // Add request body for non-GET requests
    if (method !== 'GET' && data) {
      options.body = JSON.stringify(data);
    }
    
    console.log(`Making ${method} request to ${url.toString()}`);
    
    // Execute the fetch request
    const response = await fetch(url.toString(), options);
    
    // Check if response is successful
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    
    // Get response text
    const text = await response.text();
    if (!text || text.trim() === '') {
      throw new Error('Empty response received from server');
    }
    
    try {
      // Parse JSON response
      const result = JSON.parse(text);
      return result;
    } catch (parseError) {
      console.error('Error parsing JSON:', parseError, 'Response text:', text);
      throw new Error(`Failed to parse JSON response: ${parseError.message}`);
    }
  } catch (error) {
    console.error('API request failed:', error);
    throw error;
  }
};

// Authentication service
export const authService = {
  login: (email, password) => 
    apiRequest(ENDPOINTS.LOGIN, 'POST', { email, password }),
  
  register: (userData) => 
    apiRequest(ENDPOINTS.REGISTER, 'POST', userData),
};

// Attendance tracking service
export const attendanceService = {
  getAttendance: (userId, date) => 
    apiRequest(ENDPOINTS.ATTENDANCE, 'GET', null, { userId, date }),
  
  getAttendanceHistory: (userId, month, year) =>
    apiRequest(ENDPOINTS.ATTENDANCE, 'GET', null, { userId, month, year }),
  
  clockIn: (userId) => 
    apiRequest(ENDPOINTS.ATTENDANCE, 'POST', { userId, action: 'clockIn' }),
  
  clockOut: (userId) => 
    apiRequest(ENDPOINTS.ATTENDANCE, 'POST', { userId, action: 'clockOut' }),
};

// Salary information service
export const salaryService = {
  getSalary: (userId, month, year) => 
    apiRequest(ENDPOINTS.SALARY, 'GET', null, { userId, month, year }),
};

// Payslip generation and management service
export const payslipService = {
  getPayslips: (userId, month, year) => 
    apiRequest(ENDPOINTS.PAYSLIP, 'GET', null, { userId, month, year }),
  
  generatePayslip: (userId, salaryId) => 
    apiRequest(ENDPOINTS.PAYSLIP, 'POST', { userId, salaryId }),
    
  viewPayslip: (payslipId) => 
    apiRequest(ENDPOINTS.PAYSLIP, 'GET', null, { action: 'view', payslipId }),
    
  exportPayroll: (userId, month, year) => 
    apiRequest(ENDPOINTS.PAYSLIP, 'POST', { userId, month, year, action: 'export' }, {}),
    
  getPayslipUrl: (payslipId, directDownload = false) => {
    const apiUrl = getApiUrl();
    
    // Add timestamp to prevent caching
    const timestamp = Date.now();
    let url = `${apiUrl}${ENDPOINTS.PAYSLIP}?action=download&payslipId=${payslipId}&_nocache=${timestamp}`;
    if (directDownload) {
      url += '&direct_download=true';
    }
    return url;
  },
  
  getReportUrl: (filename) => {
    const apiUrl = getApiUrl();
    return `${apiUrl}${ENDPOINTS.PAYSLIP}?action=download_report&file=${filename}&direct_download=true`;
  },
  
  // Check if employee has reached minimum work days for payslip generation
  checkWorkDaysAndGeneratePayslip: (userId) => 
    apiRequest(ENDPOINTS.PAYSLIP, 'GET', null, { action: 'check_work_days', userId })
};

// Admin dashboard and management service
export const adminService = {
  getDashboardStats: () => 
    apiRequest(ENDPOINTS.ADMIN, 'GET', null, { action: 'dashboard_stats' }),
  
  getAttendanceOverview: () =>
    apiRequest(ENDPOINTS.ADMIN, 'GET', null, { action: 'attendance_overview' }),
  
  getAttendanceRecords: (date) =>
    apiRequest(ENDPOINTS.ADMIN, 'GET', null, { action: 'attendance_records', date }),
  
  getPayrollData: (month, year) =>
    apiRequest(ENDPOINTS.ADMIN, 'GET', null, { action: 'payroll_data', month, year }),
  
  updateAttendance: (attendanceId, timeIn, timeOut) =>
    apiRequest(ENDPOINTS.ADMIN, 'POST', { 
      action: 'updateAttendance',
      attendanceId,
      timeIn,
      timeOut
    }),
  
  deleteAttendance: (attendanceId) =>
    apiRequest(ENDPOINTS.ADMIN, 'POST', {
      action: 'deleteAttendance',
      attendanceId
    }),
    
  // Employee management functions
  getEmployees: () =>
    apiRequest(ENDPOINTS.EMPLOYEE, 'GET', null, { action: 'getEmployees' }),
    
  addEmployee: (employeeData) =>
    apiRequest(ENDPOINTS.EMPLOYEE, 'POST', {
      action: 'addEmployee',
      ...employeeData
    }),
    
  updateEmployee: (employeeId, employeeData) =>
    apiRequest(ENDPOINTS.EMPLOYEE, 'POST', {
      action: 'updateEmployee',
      employeeId,
      ...employeeData
    }),
    
  deleteEmployee: (employeeId) =>
    apiRequest(ENDPOINTS.EMPLOYEE, 'POST', {
      action: 'deleteEmployee',
      employeeId
    }),
};

// Export all services as default
export default {
  authService,
  attendanceService,
  salaryService,
  payslipService,
  adminService,
}; 
