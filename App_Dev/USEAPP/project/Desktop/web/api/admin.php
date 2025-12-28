<?php
// Include database configuration
include('../dB/config.php');

// Set CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

// Handle GET requests for retrieving data
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Check for specified action
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        switch ($action) {
            case 'dashboard_stats':
                // Get today's date for filtering
                $today = date('Y-m-d');
                
                // Count total employees
                $totalQuery = "SELECT COUNT(*) as total FROM users WHERE userRole='user'";
                $totalResult = mysqli_query($conn, $totalQuery);
                $totalData = mysqli_fetch_assoc($totalResult);
                $totalEmployees = $totalData['total'];
                
                // Count present employees
                $presentQuery = "SELECT COUNT(DISTINCT userId) as present_count 
                               FROM attendance 
                               WHERE date = '$today' 
                               AND (status = 'Present' OR status = 'Late')";
                $presentResult = mysqli_query($conn, $presentQuery);
                $presentData = mysqli_fetch_assoc($presentResult);
                $presentEmployees = $presentData['present_count'];
                
                // Calculate absent employees
                $absentEmployees = $totalEmployees - $presentEmployees;
                
                // Return dashboard statistics
                echo json_encode([
                    'status' => true,
                    'data' => [
                        'totalEmployees' => $totalEmployees,
                        'presentEmployees' => $presentEmployees,
                        'absentEmployees' => $absentEmployees,
                        'today' => $today
                    ]
                ]);
                break;

            case 'attendance_overview':
                // Get today's date
                $today = date('Y-m-d');
                
                // Query to get attendance overview for all employees
                $query = "SELECT u.userId, u.firstName, u.lastName,
                        COALESCE(a.status, 'Absent') as status,
                        a.timeIn, a.timeOut, a.date
                        FROM users u
                        LEFT JOIN attendance a ON u.userId = a.userId 
                        AND a.date = '$today'
                        WHERE u.userRole = 'user'
                        ORDER BY u.firstName ASC";
                
                $result = mysqli_query($conn, $query);
                
                $attendanceData = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $attendanceData[] = [
                        'userId' => $row['userId'],
                        'name' => $row['firstName'] . ' ' . $row['lastName'],
                        'status' => $row['status'],
                        'timeIn' => $row['timeIn'] ? date('h:i A', strtotime($row['timeIn'])) : '-',
                        'timeOut' => $row['timeOut'] ? date('h:i A', strtotime($row['timeOut'])) : '-',
                        'date' => $row['date'] ? date('M d, Y', strtotime($row['date'])) : date('M d, Y')
                    ];
                }
                
                // Return attendance overview data
                echo json_encode([
                    'status' => true,
                    'data' => $attendanceData,
                    'today' => $today
                ]);
                break;

            case 'attendance_records':
                // Get date parameter, default to today
                $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                
                // Query to get detailed attendance records for a specific date
                $query = "SELECT a.attendanceId, a.userId, a.date, a.timeIn, a.timeOut, a.status, 
                        u.firstName, u.lastName 
                        FROM attendance a 
                        JOIN users u ON a.userId = u.userId 
                        WHERE a.date = ?
                        ORDER BY a.timeIn DESC";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $attendanceRecords = [];
                while($row = $result->fetch_assoc()) {
                    $attendanceRecords[] = [
                        'attendanceId' => $row['attendanceId'],
                        'userId' => $row['userId'],
                        'name' => $row['firstName'] . ' ' . $row['lastName'],
                        'date' => date('M d, Y', strtotime($row['date'])),
                        'status' => $row['status'],
                        'timeIn' => $row['timeIn'] ? date('h:i A', strtotime($row['timeIn'])) : '-',
                        'timeOut' => $row['timeOut'] ? date('h:i A', strtotime($row['timeOut'])) : '-'
                    ];
                }
                
                // Return attendance records data
                echo json_encode([
                    'status' => true,
                    'data' => $attendanceRecords,
                    'date' => $date
                ]);
                break;

            case 'payroll_data':
                // Get month and year parameters, default to current month/year
                $month = isset($_GET['month']) ? $_GET['month'] : date('m');
                $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
                
                // Get all employees
                $query = "SELECT userId, firstName, lastName FROM users WHERE userRole='user'";
                $result = $conn->query($query);
                
                $payrollData = [];
                while($employee = $result->fetch_assoc()) {
                    // Calculate payroll data for each employee
                    $payroll = calculatePayroll($employee['userId'], $month, $year);
                    
                    $payrollData[] = [
                        'userId' => $employee['userId'],
                        'name' => $employee['firstName'] . ' ' . $employee['lastName'],
                        'daysWorked' => $payroll['daysWorked'],
                        'requiredDays' => $payroll['requiredDays'],
                        'basicSalary' => $payroll['basicSalary'],
                        'overtimePay' => $payroll['overtimePay'],
                        'deductions' => $payroll['deductions'],
                        'tax' => $payroll['tax'],
                        'netPay' => $payroll['netPay'],
                        'insufficientDays' => $payroll['insufficientDays']
                    ];
                }
                
                echo json_encode([
                    'status' => true,
                    'data' => $payrollData,
                    'month' => $month,
                    'year' => $year
                ]);
                break;

            default:
                // Handle invalid action
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid action specified.'
                ]);
                break;
        }
    } else {
        // No action specified
        echo json_encode([
            'status' => false,
            'message' => 'No action specified.'
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle POST requests for updating data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'updateAttendance':
                if (isset($data['attendanceId']) && isset($data['timeIn']) && isset($data['timeOut'])) {
                    $attendanceId = $data['attendanceId'];
                    $timeIn = $data['timeIn'];
                    $timeOut = $data['timeOut'];
                    
                    // Update attendance record
                    $query = "UPDATE attendance SET timeIn = ?, timeOut = ? WHERE attendanceId = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssi", $timeIn, $timeOut, $attendanceId);
                    
                    if ($stmt->execute()) {
                        echo json_encode([
                            'status' => true,
                            'message' => 'Attendance record updated successfully.'
                        ]);
                    } else {
                        echo json_encode([
                            'status' => false,
                            'message' => 'Failed to update attendance record: ' . $conn->error
                        ]);
                    }
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Missing required fields for updating attendance.'
                    ]);
                }
                break;
                
            case 'deleteAttendance':
                if (isset($data['attendanceId'])) {
                    $attendanceId = $data['attendanceId'];
                    
                    
                    $query = "DELETE FROM attendance WHERE attendanceId = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $attendanceId);
                    
                    if ($stmt->execute()) {
                        echo json_encode([
                            'status' => true,
                            'message' => 'Attendance record deleted successfully.'
                        ]);
                    } else {
                        echo json_encode([
                            'status' => false,
                            'message' => 'Failed to delete attendance record: ' . $conn->error
                        ]);
                    }
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Missing attendanceId for deleting attendance.'
                    ]);
                }
                break;
                
            default:
                
                echo json_encode([
                    'status' => false,
                    'message' => 'Invalid action specified.'
                ]);
                break;
        }
    } else {
        
        echo json_encode([
            'status' => false,
            'message' => 'Invalid or missing action.'
        ]);
    }
} else {
    
    echo json_encode([
        'status' => false,
        'message' => 'Method not allowed.'
    ]);
}


function calculatePayroll($userId, $month, $year) {
    global $conn;
    
    
    $query = "SELECT COUNT(*) as days_worked, 
              SUM(TIMESTAMPDIFF(HOUR, timeIn, timeOut)) as total_hours
              FROM attendance 
              WHERE userId = ? 
              AND MONTH(date) = ? 
              AND YEAR(date) = ?
              AND status IN ('Present', 'Late')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $daysWorked = $data['days_worked'] ?? 0;
    $totalHours = $data['total_hours'] ?? 0;
    
    
    if ($daysWorked < 22) {
        
        return [
            'daysWorked' => $daysWorked,
            'requiredDays' => 22,
            'basicSalary' => 0,
            'overtimePay' => 0,
            'deductions' => 0,
            'tax' => 0,
            'netPay' => 0,
            'insufficientDays' => true
        ];
    }
    
    
    $regularHours = $daysWorked * 8;
    $overtimeHours = max(0, $totalHours - $regularHours);
    
    
    $basicSalary = 20000.00; 
    $overtimeRate = 150.00;   
    $overtime = $overtimeHours * $overtimeRate;
    
    
    $attendanceDeduction = 0;
    if ($daysWorked < 20) {
        $attendanceDeduction = $basicSalary * 0.1; 
    }
    
    
    $grossSalary = $basicSalary + $overtime;
    
    
    $tax = calculateTax($grossSalary);
    
    
    $totalDeductions = $attendanceDeduction + $tax;
    
    
    $netPay = $grossSalary - $totalDeductions;
    
    return [
        'daysWorked' => $daysWorked,
        'requiredDays' => 22,
        'basicSalary' => $basicSalary,
        'overtimePay' => $overtime,
        'deductions' => $attendanceDeduction,
        'tax' => $tax,
        'netPay' => $netPay,
        'insufficientDays' => false
    ];
}


function calculateTax($grossSalary) {
    
    
    if ($grossSalary <= 10000) {
        
        return 0;
    } else if ($grossSalary <= 15000) {
        
        return ($grossSalary - 10000) * 0.10;
    } else if ($grossSalary <= 25000) {
        
        return (5000 * 0.10) + ($grossSalary - 15000) * 0.15;
    } else if ($grossSalary <= 40000) {
        
        return (5000 * 0.10) + (10000 * 0.15) + ($grossSalary - 25000) * 0.20;
    } else {
        
        return (5000 * 0.10) + (10000 * 0.15) + (15000 * 0.20) + ($grossSalary - 40000) * 0.25;
    }
}
?> 
