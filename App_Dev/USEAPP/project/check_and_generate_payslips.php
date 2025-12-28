<?php
// This script should be run by a cron job once per day
// Recommended cron schedule: 0 0 * * * php /path/to/check_and_generate_payslips.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture any output from the config file
ob_start();
require_once 'Desktop/AR_Attendance/dB/config.php';
ob_end_clean();

// Log file path
$logFile = __DIR__ . '/payslip_generator.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message\n";
    
    // Output to console
    echo $formattedMessage;
    
    // Write to log file
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Function to create a salary record when none exists - using correct table structure
function createSalaryRecord($userId, $month, $year) {
    global $conn, $logFile;
    
    // For simplicity, we'll use a default basic salary of 20000
    $basicSalary = 20000;
    $overtime = 0;
    $bonus = 0; // Added bonus as it's in the table
    $deductions = 0;
    $tax = 0; // Added tax as it's in the table
    $totalSalary = $basicSalary + $overtime + $bonus - $deductions - $tax;
    
    $query = "INSERT INTO salary (userId, month, year, basicSalary, overtime, bonus, deductions, tax, totalSalary) 
              VALUES ($userId, $month, $year, $basicSalary, $overtime, $bonus, $deductions, $tax, $totalSalary)";
    
    logMessage("DEBUG: Executing SQL: " . $query);
    
    if ($conn->query($query)) {
        $salaryId = $conn->insert_id;
        logMessage("  - Created new salary record (ID: $salaryId) for user $userId");
        return $salaryId;
    } else {
        logMessage("  - Failed to create salary record: " . $conn->error);
        return false;
    }
}

// Function to generate payslip using the existing API
function generatePayslip($userId, $salaryId) {
    global $logFile;
    
    // Create cURL request to the payslip API
    $apiUrl = 'http://localhost/app_dev_last/Desktop/AR_Attendance/api/payslip.php';
    $postData = json_encode([
        'userId' => $userId,
        'salaryId' => $salaryId
    ]);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        logMessage("  - cURL error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        
        if ($result && isset($result['status']) && $result['status']) {
            return true;
        } else {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Unknown error';
            logMessage("  - API error: $errorMsg");
        }
    } else {
        logMessage("  - API call failed with HTTP code $httpCode");
        if ($response) {
            logMessage("  - Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
        }
    }
    
    return false;
}

// Start script
logMessage("Starting automatic payslip generation process...");

// Check connection
if ($conn->connect_error) {
    logMessage("ERROR: Database connection failed: " . $conn->connect_error);
    die();
}

logMessage("Database connection successful");

// Current month and year
$currentMonth = date('m');
$currentYear = date('Y');
logMessage("Processing payslips for period: " . date('F Y'));

// Get all active employees - using userRole instead of userType and removing status condition
$employeeQuery = "SELECT userId, firstName, lastName, email FROM users WHERE userRole = 'user'";
$employeeResult = $conn->query($employeeQuery);

if ($employeeResult === false) {
    logMessage("ERROR: Failed to query users table: " . $conn->error);
    
    // Try to determine the correct column name for user type
    $columnsQuery = "SHOW COLUMNS FROM users";
    $columnsResult = $conn->query($columnsQuery);
    
    if ($columnsResult) {
        $typeColumn = null;
        while ($column = $columnsResult->fetch_assoc()) {
            $fieldName = $column['Field'];
            if (in_array(strtolower($fieldName), ['usertype', 'type', 'role', 'user_type', 'user_role', 'userrole'])) {
                $typeColumn = $fieldName;
                break;
            }
        }
        
        if ($typeColumn) {
            logMessage("Found user type column: $typeColumn");
            $employeeQuery = "SELECT userId, firstName, lastName, email FROM users WHERE $typeColumn = 'user'";
            $employeeResult = $conn->query($employeeQuery);
        } else {
            logMessage("Could not determine user type column name. Available columns:");
            $columnsResult = $conn->query($columnsQuery);
            while ($column = $columnsResult->fetch_assoc()) {
                logMessage("  - " . $column['Field']);
            }
        }
    } else {
        logMessage("Failed to get columns information: " . $conn->error);
    }
}

if ($employeeResult && $employeeResult->num_rows > 0) {
    logMessage("Found " . $employeeResult->num_rows . " active employees");
    
    $processedCount = 0;
    $payslipsGenerated = 0;
    
    while ($employee = $employeeResult->fetch_assoc()) {
        $userId = $employee['userId'];
        $employeeName = $employee['firstName'] . ' ' . $employee['lastName'];
        
        logMessage("Processing employee: $employeeName (ID: $userId)");
        $processedCount++;
        
        try {
            // Count work days for the current month
            $workDaysQuery = "SELECT COUNT(*) as workDays 
                              FROM attendance 
                              WHERE userId = ? 
                              AND MONTH(date) = ? 
                              AND YEAR(date) = ? 
                              AND status = 'Present'";
            
            $workDaysStmt = $conn->prepare($workDaysQuery);
            if (!$workDaysStmt) {
                logMessage("ERROR: Failed to prepare work days query: " . $conn->error);
                continue;
            }
            
            $workDaysStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
            $workDaysStmt->execute();
            $workDaysResult = $workDaysStmt->get_result();
            
            if (!$workDaysResult) {
                logMessage("ERROR: Failed to execute work days query: " . $workDaysStmt->error);
                continue;
            }
            
            $workDaysRow = $workDaysResult->fetch_assoc();
            $workDays = $workDaysRow['workDays'];
            
            logMessage("  - Work days this month: $workDays");
            
            // If employee has worked 1 day or more (lowered from 22 for testing)
            if ($workDays >= 1) {
                logMessage("  - Employee has reached 1 work day threshold!");
                
                // Check if payslip already exists for this month
                $checkPayslipQuery = "SELECT p.payslipId 
                                      FROM payslip p 
                                      JOIN salary s ON p.salaryId = s.salaryId 
                                      WHERE p.userId = ? 
                                      AND s.month = ? 
                                      AND s.year = ?";
                
                $checkStmt = $conn->prepare($checkPayslipQuery);
                if (!$checkStmt) {
                    logMessage("ERROR: Failed to prepare check payslip query: " . $conn->error);
                    continue;
                }
                
                $checkStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if (!$checkResult) {
                    logMessage("ERROR: Failed to execute check payslip query: " . $checkStmt->error);
                    continue;
                }
                
                if ($checkResult->num_rows > 0) {
                    logMessage("  - Payslip already exists for this month");
                    continue; // Skip to next employee
                }
                
                // Check if salary record exists
                $salaryQuery = "SELECT salaryId FROM salary 
                                WHERE userId = ? AND month = ? AND year = ?";
                
                $salaryStmt = $conn->prepare($salaryQuery);
                $salaryStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
                $salaryStmt->execute();
                $salaryResult = $salaryStmt->get_result();
                
                if ($salaryResult->num_rows > 0) {
                    $salaryId = $salaryResult->fetch_assoc()['salaryId'];
                    logMessage("  - Found salary record (ID: $salaryId)");
                    
                    // Generate payslip
                    $payslipGenerated = generatePayslip($userId, $salaryId);
                    if ($payslipGenerated) {
                        logMessage("  - Successfully generated payslip");
                        $payslipsGenerated++;
                    } else {
                        logMessage("  - Failed to generate payslip");
                    }
                } else {
                    logMessage("  - No salary record found for this month. Creating one now.");
                    // Create a salary record since none exists
                    $salaryId = createSalaryRecord($userId, $currentMonth, $currentYear);
                    if ($salaryId) {
                        // Generate payslip with the new salary record
                        logMessage("  - Generating payslip with newly created salary record");
                        $payslipGenerated = generatePayslip($userId, $salaryId);
                        if ($payslipGenerated) {
                            logMessage("  - Successfully generated payslip");
                            $payslipsGenerated++;
                        } else {
                            logMessage("  - Failed to generate payslip");
                        }
                    } else {
                        logMessage("  - Skipping payslip generation due to salary record creation failure");
                    }
                }
            } else {
                logMessage("  - Not enough work days yet. 1 day required, currently at $workDays days");
            }
        } catch (Exception $e) {
            logMessage("ERROR: Exception while processing employee $userId: " . $e->getMessage());
        }
    }
    
    logMessage("Payslip generation summary:");
    logMessage("  - Total employees processed: $processedCount");
    logMessage("  - Payslips generated: $payslipsGenerated");
} else {
    $error = $conn->error ? $conn->error : "No active employees found or query failed";
    logMessage("Query error or no active employees found: $error");
}

// Close database connection
$conn->close();
logMessage("Process completed");
?> 