<?php
session_start();
include("../dB/config.php");

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['authUser'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Get the current user's ID
$userId = $_SESSION['authUser']['userId'];

// Get period from request
$period = isset($_POST['period']) ? $_POST['period'] : null;

if (!$period) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Period is required'
    ]);
    exit;
}

// Parse period (Format: "Month Year" e.g., "April 2025")
$periodParts = explode(' ', $period);
if (count($periodParts) != 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid period format'
    ]);
    exit;
}

$month = date('m', strtotime($periodParts[0] . ' 1'));
$year = $periodParts[1];

// Query to count work days in the given period
$query = "SELECT COUNT(*) as work_days FROM attendance 
          WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $userId, $month, $year);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$workDays = (int)$result['work_days'];

// Get salary and tax information
$taxQuery = "SELECT basicSalary, overtime, tax FROM salary 
            WHERE userId = ? AND month = ? AND year = ?";
$taxStmt = $conn->prepare($taxQuery);
$taxStmt->bind_param("iss", $userId, $month, $year);
$taxStmt->execute();
$taxResult = $taxStmt->get_result()->fetch_assoc();

// Calculate tax based on salaryProcessor.php logic if not available in database
$tax = 0;
$basicSalary = 20000; // Default
$overtime = 0;

if ($taxResult) {
    $basicSalary = (float)$taxResult['basicSalary'];
    $overtime = (float)$taxResult['overtime'];
    $tax = isset($taxResult['tax']) ? (float)$taxResult['tax'] : calculateTax($basicSalary + $overtime);
}

// Return work days information
echo json_encode([
    'status' => 'success',
    'workDays' => $workDays,
    'requiredDays' => 22,
    'tax' => $tax
]);
exit;

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