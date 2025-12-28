<?php

include('config.php');


$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        
        handleGetSalary();
        break;
    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function handleGetSalary() {
    global $conn;
    
    
    $userId = isset($_GET['userId']) ? sanitizeInput($_GET['userId']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? sanitizeInput($_GET['year']) : date('Y');
    
    if (!$userId) {
        sendResponse(false, 'User ID is required');
    }
    
    
    $checkQuery = "SELECT * FROM salary WHERE userId = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        
        $salaryData = $result->fetch_assoc();
        
        
        $attendanceSummary = getAttendanceSummary($userId, $month, $year);
        
        $response = [
            'salaryDetails' => $salaryData,
            'attendanceSummary' => $attendanceSummary
        ];
        
        sendResponse(true, 'Salary data retrieved', $response);
    } else {
        
        $salaryData = calculateSalary($userId, $month, $year);
        
        
        $attendanceSummary = getAttendanceSummary($userId, $month, $year);
        
        $response = [
            'salaryDetails' => $salaryData,
            'attendanceSummary' => $attendanceSummary
        ];
        
        sendResponse(true, 'Salary data calculated', $response);
    }
}

function calculateSalary($userId, $month, $year) {
    global $conn;
    $basicSalary = 20000.00;
    $overtimeRate = 150.00;

    
    $query = "SELECT COUNT(*) as total_days,
              SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
              SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
              SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
              SUM(TIME_TO_SEC(TIMEDIFF(timeOut, timeIn)))/3600 as total_hours
              FROM attendance 
              WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?
              AND timeOut IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $totalAttendanceDays = $result['total_days'] ?? 0;
    $presentDays = $result['present_days'] ?? 0;
    $lateDays = $result['late_days'] ?? 0;
    $absentDays = $result['absent_days'] ?? 0;
    $totalHours = $result['total_hours'] ?? 0;
    $firstAttendanceDate = $result['first_date'] ?? null;
    
    
    $regularHours = $totalAttendanceDays * 8; 
    $overtimeHours = max(0, $totalHours - $regularHours);
    $overtimePay = $overtimeHours * $overtimeRate;
    
    
    $deductions = 0;
    $daysWorked = $presentDays + $lateDays; 
    
    
    if ($totalAttendanceDays > 0) {
        
        
        if ($totalAttendanceDays < 5) {
            
            $deductions = 0;
        } else {
            
            $expectedWorkDays = 0;
            
            if ($firstAttendanceDate) {
                
                $firstDay = intval(date('d', strtotime($firstAttendanceDate)));
                
                
                $lastDayOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                
                for ($day = $firstDay; $day <= $lastDayOfMonth; $day++) {
                    $date = mktime(0, 0, 0, $month, $day, $year);
                    $weekday = date('N', $date); 
                    
                    
                    if ($weekday <= 5) {
                        $expectedWorkDays++;
                    }
                }
                
                
                
                if ($expectedWorkDays > 0 && $daysWorked < ($expectedWorkDays * 0.5)) {
                    $deductions = $basicSalary * 0.1; 
                }
            }
        }
    }
    
    
    $totalSalary = $basicSalary + $overtimePay - $deductions;
    
    
    $insertQuery = "INSERT INTO salary (userId, month, year, basicSalary, overtime, deductions, totalSalary) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE 
                   basicSalary = VALUES(basicSalary),
                   overtime = VALUES(overtime),
                   deductions = VALUES(deductions),
                   totalSalary = VALUES(totalSalary)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iiidddd", $userId, $month, $year, $basicSalary, $overtimePay, $deductions, $totalSalary);
    $stmt->execute();
    
    $salaryId = $stmt->insert_id ?: getSalaryId($userId, $month, $year);
    
    return [
        'salaryId' => $salaryId,
        'month' => $month,
        'year' => $year,
        'basicSalary' => $basicSalary,
        'overtimePay' => $overtimePay,
        'deductions' => $deductions,
        'totalSalary' => $totalSalary,
        'overtimeHours' => $overtimeHours
    ];
}

function getAttendanceSummary($userId, $month, $year) {
    global $conn;
    
    
    $query = "SELECT COUNT(*) as total_days,
              SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
              SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
              SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
              FROM attendance 
              WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    
    $workingDays = $result['total_days'] ?? 0;
    $presentDays = $result['present_days'] ?? 0;
    $lateDays = $result['late_days'] ?? 0;
    $absentDays = $result['absent_days'] ?? 0;
    
    return [
        'workingDays' => $workingDays,
        'presentDays' => $presentDays,
        'lateDays' => $lateDays,
        'absentDays' => $absentDays
    ];
}

function getSalaryId($userId, $month, $year) {
    global $conn;
    
    $query = "SELECT salaryId FROM salary WHERE userId = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result ? $result['salaryId'] : 0;
}
?> 
