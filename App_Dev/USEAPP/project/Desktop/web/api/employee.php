<?php

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include database configuration
include('../dB/config.php');

// Start session for potential user authentication
session_start();

// Default response structure
$response = [
    'status' => false,
    'message' => 'Invalid request',
    'data' => null
];

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'getEmployees') {
        try {
            // Query to get all employees
            $query = "SELECT u.userId AS id, u.firstName, u.lastName, u.email, 
                      u.phoneNumber AS phone, u.gender, u.birthday 
                      FROM users u 
                      WHERE u.userRole = 'user' 
                      ORDER BY u.lastName, u.firstName";
            
            $result = mysqli_query($conn, $query);
            
            $employees = [];
            while($row = mysqli_fetch_assoc($result)) {
                $employees[] = $row;
            }
            
            $response = [
                'status' => true,
                'message' => 'Employees retrieved successfully',
                'data' => $employees
            ];
        } catch (Exception $e) {
            $response = [
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data from request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    $action = isset($data['action']) ? $data['action'] : '';
    
    // Handle employee creation
    if ($action === 'addEmployee') {
        try {
            // Get employee data from request
            $firstName = isset($data['firstName']) ? $data['firstName'] : '';
            $lastName = isset($data['lastName']) ? $data['lastName'] : '';
            $email = isset($data['email']) ? $data['email'] : '';
            $phone = isset($data['phone']) ? $data['phone'] : '';
            $gender = isset($data['gender']) ? $data['gender'] : '';
            $birthday = isset($data['birthday']) ? $data['birthday'] : null;
            
            // Validate required fields
            if (empty($firstName) || empty($lastName) || empty($email)) {
                $response = [
                    'status' => false,
                    'message' => 'Required fields are missing',
                    'data' => null
                ];
            } else {
                // Check if email already exists
                $checkQuery = "SELECT COUNT(*) as count FROM users WHERE email = '$email'";
                $checkResult = mysqli_query($conn, $checkQuery);
                $checkData = mysqli_fetch_assoc($checkResult);
                
                if ($checkData['count'] > 0) {
                    $response = [
                        'status' => false,
                        'message' => 'Email already exists',
                        'data' => null
                    ];
                } else {
                    // Set default password for new employee
                    $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
                    
                    // Insert new employee record
                    $query = "INSERT INTO users (firstName, lastName, email, password, phoneNumber, 
                              gender, birthday, userRole) 
                              VALUES ('$firstName', '$lastName', '$email', '$defaultPassword', '$phone', 
                              '$gender', '$birthday', 'user')";
                    
                    if (mysqli_query($conn, $query)) {
                        $newUserId = mysqli_insert_id($conn);
                        
                        $response = [
                            'status' => true,
                            'message' => 'Employee added successfully',
                            'data' => ['id' => $newUserId]
                        ];
                    } else {
                        throw new Exception(mysqli_error($conn));
                    }
                }
            }
        } catch (Exception $e) {
            $response = [
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Handle employee update
    if ($action === 'updateEmployee') {
        try {
            // Get updated employee data
            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : 0;
            $firstName = isset($data['firstName']) ? $data['firstName'] : '';
            $lastName = isset($data['lastName']) ? $data['lastName'] : '';
            $email = isset($data['email']) ? $data['email'] : '';
            $phone = isset($data['phone']) ? $data['phone'] : '';
            $gender = isset($data['gender']) ? $data['gender'] : '';
            $birthday = isset($data['birthday']) ? $data['birthday'] : null;
            
            // Validate required fields
            if (empty($employeeId) || empty($firstName) || empty($lastName) || empty($email)) {
                $response = [
                    'status' => false,
                    'message' => 'Required fields are missing',
                    'data' => null
                ];
            } else {
                // Check if employee exists
                $checkQuery = "SELECT COUNT(*) as count FROM users WHERE userId = $employeeId";
                $checkResult = mysqli_query($conn, $checkQuery);
                $checkData = mysqli_fetch_assoc($checkResult);
                
                if ($checkData['count'] === 0) {
                    $response = [
                        'status' => false,
                        'message' => 'Employee not found',
                        'data' => null
                    ];
                } else {
                    // Check if email is already used by another employee
                    $emailCheckQuery = "SELECT COUNT(*) as count FROM users WHERE email = '$email' AND userId != $employeeId";
                    $emailCheckResult = mysqli_query($conn, $emailCheckQuery);
                    $emailCheckData = mysqli_fetch_assoc($emailCheckResult);
                    
                    if ($emailCheckData['count'] > 0) {
                        $response = [
                            'status' => false,
                            'message' => 'Email already exists for another employee',
                            'data' => null
                        ];
                    } else {
                        // Update employee record
                        $query = "UPDATE users SET 
                                  firstName = '$firstName', 
                                  lastName = '$lastName',
                                  email = '$email',
                                  phoneNumber = '$phone',
                                  gender = '$gender',
                                  birthday = '$birthday'
                                  WHERE userId = $employeeId";
                        
                        if (mysqli_query($conn, $query)) {
                            $response = [
                                'status' => true,
                                'message' => 'Employee updated successfully',
                                'data' => null
                            ];
                        } else {
                            throw new Exception(mysqli_error($conn));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $response = [
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    if ($action === 'deleteEmployee') {
        try {
            $employeeId = isset($data['employeeId']) ? $data['employeeId'] : 0;
            
            if (empty($employeeId)) {
                $response = [
                    'status' => false,
                    'message' => 'Employee ID is required',
                    'data' => null
                ];
            } else {
                
                $checkQuery = "SELECT COUNT(*) as count FROM users WHERE userId = $employeeId AND userRole = 'user'";
                $checkResult = mysqli_query($conn, $checkQuery);
                $checkData = mysqli_fetch_assoc($checkResult);
                
                if ($checkData['count'] === 0) {
                    $response = [
                        'status' => false,
                        'message' => 'Employee not found',
                        'data' => null
                    ];
                } else {
                    
                    $query = "DELETE FROM users WHERE userId = $employeeId";
                    
                    if (mysqli_query($conn, $query)) {
                        $response = [
                            'status' => true,
                            'message' => 'Employee deleted successfully',
                            'data' => null
                        ];
                    } else {
                        throw new Exception(mysqli_error($conn));
                    }
                }
            }
        } catch (Exception $e) {
            $response = [
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}

echo json_encode($response);
?> 
