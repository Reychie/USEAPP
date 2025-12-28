<?php
// Function to generate a text payslip as fallback
function generateTextPayslip($userId, $salaryId) {
    global $conn;
    
    // Create payslips directory if it doesn't exist
    $payslipsDir = dirname(__FILE__) . '/../uploads/payslips';
    error_log("Checking payslips directory at: $payslipsDir");
    
    // Try creating directory if it doesn't exist
    if (!file_exists($payslipsDir)) {
        error_log("Payslips directory doesn't exist in text generator, attempting to create it");
        if (!mkdir($payslipsDir, 0755, true)) {
            error_log("Failed to create directory: $payslipsDir");
            
            // Try alternative paths
            $altDirectories = [
                dirname(dirname(__FILE__)) . '/uploads/payslips',
                dirname(__FILE__) . '/../../uploads/payslips'
            ];
            
            foreach ($altDirectories as $dir) {
                error_log("Trying to create alternative directory: $dir");
                if (!file_exists($dir)) {
                    if (mkdir($dir, 0755, true)) {
                        $payslipsDir = $dir;
                        error_log("Successfully created alternative payslips directory: $dir");
                        break;
                    }
                } else {
                    $payslipsDir = $dir;
                    error_log("Found existing alternative payslips directory: $dir");
                    break;
                }
            }
            
            if (!file_exists($payslipsDir)) {
                error_log("Could not create or find any valid payslips directory");
                return false;
            }
        } else {
            error_log("Successfully created payslips directory at: $payslipsDir");
        }
    } else {
        error_log("Payslips directory exists at: $payslipsDir");
    }
    
    // Get salary details
    $query = "SELECT s.*, u.firstName, u.lastName 
              FROM salary s 
              JOIN users u ON s.userId = u.userId 
              WHERE s.salaryId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $salaryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("No salary record found for salaryId: $salaryId");
        return false;
    }
    
    $salary = $result->fetch_assoc();
    error_log("Generating text payslip for: " . $salary['firstName'] . " " . $salary['lastName'] . 
              ", Month: " . $salary['month'] . ", Year: " . $salary['year'] . 
              ", Days worked: " . $salary['daysWorked']);
    
    // Generate text content
    $content = "======== PAYSLIP ========\n\n";
    $content .= "Employee: " . $salary['firstName'] . " " . $salary['lastName'] . "\n";
    $content .= "Month: " . getMonthTextName($salary['month']) . " " . $salary['year'] . "\n\n";
    $content .= "Basic Salary: PHP " . number_format($salary['basicSalary'], 2) . "\n";
    $content .= "Overtime: PHP " . number_format($salary['overtime'], 2) . "\n";
    $content .= "Deductions: PHP " . number_format($salary['deductions'], 2) . "\n";
    $content .= "------------------------\n";
    $content .= "Net Pay: PHP " . number_format($salary['totalSalary'], 2) . "\n\n";
    $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
    
    // Save text file - create the path for the database and file
    $timestamp = time();
    $relativeFilePath = 'uploads/payslips/' . $userId . '_' . $salaryId . '_' . $timestamp . '.txt';
    $fullFilePath = dirname(__FILE__) . '/../' . $relativeFilePath;
    
    // Log the paths for debugging
    error_log("Saving text payslip to: $fullFilePath (relative path for database: $relativeFilePath)");
    
    if (file_put_contents($fullFilePath, $content) === false) {
        error_log("Failed to write text file: $fullFilePath");
        return false;
    }
    
    // Check if payslip record already exists
    $query = "SELECT payslipId FROM payslip WHERE userId = ? AND salaryId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $salaryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $query = "UPDATE payslip SET pdfPath = ? WHERE userId = ? AND salaryId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $relativeFilePath, $userId, $salaryId);
        error_log("Updating existing payslip record (text version)");
    } else {
        // Insert new record - Note: We're not using 'status' field as it might not exist
        $query = "INSERT INTO payslip (userId, salaryId, pdfPath) 
                  VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $userId, $salaryId, $relativeFilePath);
        error_log("Inserting new payslip record (text version)");
    }
    
    $result = $stmt->execute();
    if (!$result) {
        error_log("Failed to update payslip record: " . $conn->error);
    }
    
    return $result ? $relativeFilePath : false;
}

// Helper function to get month name
function getMonthTextName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    return $months[(int)$month] ?? '';
}
?> 