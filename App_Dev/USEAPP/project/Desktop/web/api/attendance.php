<?php
// Include API configuration and utility functions
include('config.php');

// Enable debug mode for logging
$debugMode = true;
function debug_log($message) {
    global $debugMode;
    if ($debugMode) {
        error_log('[Attendance API] ' . $message);
    }
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

debug_log("Request method: " . $method);

// Route requests based on HTTP method
switch ($method) {
    case 'GET':
        // Handle GET requests (retrieving attendance records)
        handleGetAttendance();
        break;
    case 'POST':
        // Handle POST requests (clock in/out)
        handlePostAttendance();
        break;
    default:
        sendResponse(false, 'Method not allowed');
        break;
}

/**
 * Handle GET requests for attendance data
 * Supports both single day and monthly attendance
 */
function handleGetAttendance() {
    global $conn;
    
    // Get and sanitize request parameters
    $userId = isset($_GET['userId']) ? sanitizeInput($_GET['userId']) : null;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? sanitizeInput($_GET['year']) : date('Y');
    
    debug_log("Getting attendance for userId: $userId, date: $date, month: $month, year: $year");
    
    if (!$userId) {
        sendResponse(false, 'User ID is required');
    }
    
    // Base query
    $query = "SELECT * FROM attendance WHERE userId = ?";
    
    // Apply date filter
    if (isset($_GET['date'])) {
        $query .= " AND date = ?";
        $params = [$userId, $date];
        $types = "is";
    } else {
        // Filter by month and year 
        $query .= " AND MONTH(date) = ? AND YEAR(date) = ?";
        $params = [$userId, $month, $year];
        $types = "iii";
    }
    
    $query .= " ORDER BY date DESC, timeIn DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate work hours if both timeIn and timeOut exist
        $workHours = null;
        if($row['timeIn'] && $row['timeOut']) {
            $timeIn = new DateTime($row['timeIn']);
            $timeOut = new DateTime($row['timeOut']);
            $interval = $timeIn->diff($timeOut);
            $workHours = $interval->format('%H:%I');
        }
        
        $records[] = [
            'attendanceId' => $row['attendanceId'],
            'userId' => $row['userId'],
            'date' => $row['date'],
            'timeIn' => $row['timeIn'],
            'timeOut' => $row['timeOut'],
            'status' => $row['status'],
            'location' => $row['location'] ?? 'Office',
            'workHours' => $workHours
        ];
    }
    
    // For monthly view, include attendance summary
    if (!isset($_GET['date'])) {
        // Get monthly statistics
        $statsQuery = "SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
        FROM attendance 
        WHERE userId = ? 
        AND MONTH(date) = ? 
        AND YEAR(date) = ?";
        
        $statsStmt = $conn->prepare($statsQuery);
        $statsStmt->bind_param("iii", $userId, $month, $year);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        
        $summary = [
            'totalDays' => (int)($stats['total_days'] ?? 0),
            'presentDays' => (int)($stats['present_days'] ?? 0),
            'lateDays' => (int)($stats['late_days'] ?? 0),
            'absentDays' => (int)($stats['absent_days'] ?? 0)
        ];
        
        sendResponse(true, 'Attendance records retrieved', [
            'records' => $records,
            'summary' => $summary
        ]);
    } else {
        sendResponse(true, 'Attendance records retrieved', $records);
    }
}

/**
 * Handle POST requests for clock in/out actions
 */
function handlePostAttendance() {
    global $conn;
    
    // Get request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate required fields
    if (!isset($data['userId']) || !isset($data['action'])) {
        sendResponse(false, 'User ID and action (clockIn/clockOut) are required');
    }
    
    $userId = sanitizeInput($data['userId']);
    $action = sanitizeInput($data['action']);
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    // Handle clock in action
    if ($action === 'clockIn') {
        // Check if already clocked in
        $checkQuery = "SELECT * FROM attendance WHERE userId = '$userId' AND date = '$currentDate' AND timeOut IS NULL";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, 'Already clocked in today');
        }
        
        // Determine status (Present or Late)
        $status = 'Present';
        $startTime = '09:00:00'; 
        if ($currentTime > $startTime) {
            $status = 'Late';
        }
        
        // Insert clock in record
        $insertQuery = "INSERT INTO attendance (userId, date, timeIn, status) 
                        VALUES ('$userId', '$currentDate', '$currentTime', '$status')";
        
        if (mysqli_query($conn, $insertQuery)) {
            $attendanceId = mysqli_insert_id($conn);
            sendResponse(true, 'Successfully clocked in', [
                'attendanceId' => $attendanceId,
                'timeIn' => $currentTime,
                'status' => $status
            ]);
        } else {
            sendResponse(false, 'Clock in failed: ' . mysqli_error($conn));
        }
    } 
    
    else if ($action === 'clockOut') {
        // Find active clock-in record
        $findQuery = "SELECT attendanceId FROM attendance WHERE userId = '$userId' AND date = '$currentDate' AND timeOut IS NULL ORDER BY timeIn DESC LIMIT 1";
        $findResult = mysqli_query($conn, $findQuery);
        
        if (mysqli_num_rows($findResult) === 0) {
            sendResponse(false, 'No active clock in found for today');
        }
        
        $row = mysqli_fetch_assoc($findResult);
        $attendanceId = $row['attendanceId'];
        
        // Update record with clock out time
        $updateQuery = "UPDATE attendance SET timeOut = '$currentTime' WHERE attendanceId = $attendanceId";
        if (mysqli_query($conn, $updateQuery)) {
            sendResponse(true, 'Successfully clocked out', [
                'attendanceId' => $attendanceId,
                'timeOut' => $currentTime
            ]);
        } else {
            sendResponse(false, 'Clock out failed: ' . mysqli_error($conn));
        }
    } else {
        sendResponse(false, 'Invalid action. Use "clockIn" or "clockOut"');
    }
}
?> 
