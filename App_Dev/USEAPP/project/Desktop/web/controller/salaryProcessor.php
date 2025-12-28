<?php
session_start();
include("../dB/config.php");

function calculateSalary($userId, $month, $year) {
    global $conn;
    $basicSalary = 20000.00;
    $overtimeRate = 150.00;

    $query = "SELECT COUNT(*) as present_days, 
              SUM(TIMESTAMPDIFF(HOUR, timeIn, timeOut)) as total_hours 
              FROM attendance 
              WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $presentDays = $result['present_days'];
    $totalHours = $result['total_hours'];
    
    // Check if employee worked for at least 22 days
    if ($presentDays < 22) {
        // Return zero values if employee hasn't worked 22 days
        return [
            'basicSalary' => 0.00,
            'overtime' => 0.00,
            'deductions' => 0.00,
            'tax' => 0.00,
            'totalSalary' => 0.00,
            'workDays' => $presentDays,
            'requiredDays' => 22
        ];
    }
    
    // Normal calculation if employee worked 22 days or more
    $overtimeHours = max(0, $totalHours - ($presentDays * 8));
    $overtimePay = $overtimeHours * $overtimeRate;
    
    // Calculate attendance deductions (if less than 20 days)
    $attendanceDeduction = ($presentDays < 20) ? ($basicSalary * 0.1) : 0;
    
    // Calculate gross salary before tax
    $grossSalary = $basicSalary + $overtimePay;
    
    // Calculate tax based on gross salary
    $tax = calculateTax($grossSalary);
    
    // Calculate total deductions (attendance + tax)
    $totalDeductions = $attendanceDeduction + $tax;
    
    // Calculate final salary after all deductions
    $totalSalary = $grossSalary - $totalDeductions;

    return [
        'basicSalary' => $basicSalary,
        'overtime' => $overtimePay,
        'deductions' => $attendanceDeduction,
        'tax' => $tax,
        'totalDeductions' => $totalDeductions,
        'totalSalary' => $totalSalary,
        'workDays' => $presentDays,
        'requiredDays' => 22
    ];
}

// Calculate tax based on progressive tax brackets
function calculateTax($grossSalary) {
    // Tax brackets in PHP (simplified example)
    // Monthly income tax brackets
    if ($grossSalary <= 10000) {
        // 0% tax bracket
        return 0;
    } else if ($grossSalary <= 15000) {
        // 10% tax bracket for income between 10,000 and 15,000
        return ($grossSalary - 10000) * 0.10;
    } else if ($grossSalary <= 25000) {
        // 15% tax bracket for income between 15,000 and 25,000
        return (5000 * 0.10) + ($grossSalary - 15000) * 0.15;
    } else if ($grossSalary <= 40000) {
        // 20% tax bracket for income between 25,000 and 40,000
        return (5000 * 0.10) + (10000 * 0.15) + ($grossSalary - 25000) * 0.20;
    } else {
        // 25% tax bracket for income above 40,000
        return (5000 * 0.10) + (10000 * 0.15) + (15000 * 0.20) + ($grossSalary - 40000) * 0.25;
    }
}
?>