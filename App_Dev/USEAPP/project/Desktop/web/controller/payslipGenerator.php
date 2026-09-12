<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to ensure clean JSON responses
ob_start();

// Include database configuration
include("../dB/config.php");
include("generateTextPayslip.php");  // Include the text payslip fallback functionality

// Try to load FPDF with absolute path - try multiple locations
$fpdfAvailable = false;
$fpdfPaths = [
    dirname(__FILE__) . '/fpdf/fpdf.php',
    dirname(__FILE__) . '/../controller/fpdf/fpdf.php',
    dirname(dirname(__FILE__)) . '/temp_fpdf/fpdf.php'
];

foreach ($fpdfPaths as $path) {
    error_log("Trying to load FPDF from path: $path");
    if (file_exists($path)) {
        try {
            require($path);
            $fpdfAvailable = true;
            error_log("FPDF library loaded successfully from: $path");
            break;
        } catch (Exception $e) {
            error_log("Failed to load FPDF library from $path: " . $e->getMessage());
        }
    }
}

if (!$fpdfAvailable) {
    error_log("FPDF library not found in any expected location");
}

// Ensure uploads directories exist
$payslipsDir = dirname(__FILE__) . '/../uploads/payslips';
if (!file_exists($payslipsDir)) {
    if (mkdir($payslipsDir, 0777, true)) {
        error_log("Created payslips directory at: $payslipsDir");
    } else {
        error_log("Failed to create payslips directory at: $payslipsDir");
    }
}

// Check if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user ID, month, and year from request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('m');
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    } else {
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $regenerate = isset($_GET['regenerate']) && $_GET['regenerate'] == '1';
        
        // For GET requests, we'll include debugging output
        echo "<h2>Payslip Generation</h2>";
        echo "<p>Generating payslip for user ID: $userId, month: $month, year: $year";
        if ($regenerate) echo " (Force regeneration)";
        echo "</p>";
        echo "<hr>";
    }
    
    // Debug information
    error_log("Generating payslip for user $userId, month $month, year $year");
    
    // Validate input
    if ($userId <= 0) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Check if salary record exists
    $query = "SELECT salaryId FROM salary 
              WHERE userId = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No salary record exists, create one
        $payrollData = calculatePayroll($userId, $month, $year);
        
        // Insert salary record - Check if daysWorked column exists in the table
        $checkColumnQuery = "SHOW COLUMNS FROM salary LIKE 'daysWorked'";
        $columnResult = $conn->query($checkColumnQuery);
        $hasDaysWorkedColumn = $columnResult->num_rows > 0;
        
        if ($hasDaysWorkedColumn) {
            $query = "INSERT INTO salary (userId, month, year, basicSalary, overtime, deductions, tax, totalSalary, daysWorked) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiidddddi", $userId, $month, $year, 
                            $payrollData['basicSalary'], $payrollData['overtimePay'], 
                            $payrollData['deductions'], $payrollData['tax'],
                            $payrollData['netPay'], $payrollData['daysWorked']);
        } else {
            // No daysWorked column, use query without it
            $query = "INSERT INTO salary (userId, month, year, basicSalary, overtime, deductions, tax, totalSalary) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiiddddd", $userId, $month, $year, 
                            $payrollData['basicSalary'], $payrollData['overtimePay'], 
                            $payrollData['deductions'], $payrollData['tax'],
                            $payrollData['netPay']);
        }
        
        if ($stmt->execute()) {
            $salaryId = $stmt->insert_id;
            error_log("Created new salary record with ID: $salaryId");
        } else {
            error_log("Error creating salary record: " . $conn->error);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create salary record: ' . $conn->error]);
            exit;
        }
    } else {
        // Use existing salary record
        $row = $result->fetch_assoc();
        $salaryId = $row['salaryId'];
        error_log("Found existing salary record with ID: $salaryId");
    }
    
    // Check if payslip already exists
    $query = "SELECT payslipId, pdfPath FROM payslip 
              WHERE userId = ? AND salaryId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $salaryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0 && !$regenerate) {
        // Payslip already exists, return it
        $payslip = $result->fetch_assoc();
        $pdfPath = $payslip['pdfPath'];
        
        // Check if file exists - use absolute path for checking
        $absolutePath = dirname(__FILE__) . "/../" . $pdfPath;
        error_log("Checking if file exists at: $absolutePath");
        
        if (file_exists($absolutePath)) {
            // Use a URL path that's relative to the admin view
            $pdfUrl = $pdfPath;
            error_log("Found existing payslip file at: $pdfPath, returning URL: $pdfUrl");
            
            // Ensure proper JSON response with clean output
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Payslip found', 'pdfUrl' => $pdfUrl]);
            exit;
        } else {
            error_log("Payslip file not found at: $absolutePath, regenerating...");
            // File doesn't exist, regenerate it
            $success = false;
            
            if ($fpdfAvailable) {
                $success = generatePayslip($userId, $salaryId);
                $pdfPath = 'uploads/payslips/' . $userId . '_' . $salaryId . '.pdf';
            }
            
            if (!$success) {
                // Fallback to text payslip
                error_log("Falling back to text payslip");
                $pdfPath = generateTextPayslip($userId, $salaryId);
                if (!$pdfPath) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to generate payslip']);
                    exit;
                }
            }
            
            // Use a URL path that's relative to the admin view
            $pdfUrl = $pdfPath;
            
            // Clean any prior output and set proper JSON headers
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Payslip regenerated', 'pdfUrl' => $pdfUrl]);
            exit;
        }
    } else {
        error_log("No payslip found, generating new one");
        // Generate new payslip
        $success = false;
        
        if ($fpdfAvailable) {
            $success = generatePayslip($userId, $salaryId);
            $pdfPath = 'uploads/payslips/' . $userId . '_' . $salaryId . '.pdf';
        }
        
        if (!$success) {
            // Fallback to text payslip
            error_log("Falling back to text payslip");
            $pdfPath = generateTextPayslip($userId, $salaryId);
            if (!$pdfPath) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to generate payslip']);
                exit;
            }
        }
        
        // Use a URL path that's relative to the admin view
        $pdfUrl = $pdfPath;
        
        // Clean any prior output and set proper JSON headers
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Payslip generated', 'pdfUrl' => $pdfUrl]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Add a script to redirect back to the payroll calculation page
        echo "<script>";
        echo "setTimeout(function() {";
        echo "  window.location.href = '../front_end/admin/Payroll_Calculation.php?month=" . $month . "&year=" . $year . "';";
        echo "}, 3000);";  // 3-second delay before redirecting
        echo "</script>";
        echo "<p>Payslip generation completed. You will be redirected back to the Payroll Calculation page in 3 seconds.</p>";
        echo "<p>If you are not redirected, <a href='../front_end/admin/Payroll_Calculation.php?month=" . $month . "&year=" . $year . "'>click here</a>.</p>";
        exit;
    }
}

function calculatePayroll($userId, $month, $year) {
    global $conn;
    
    // Count working days
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
    
    // Only calculate salary if employee has worked days
    if($daysWorked > 0) {
        // Calculate overtime (hours worked beyond 8 hours per day)
        $regularHours = $daysWorked * 8;
        $overtimeHours = max(0, $totalHours - $regularHours);
        
        // Calculate pay
        $basicSalary = 20000.00; // Base salary per month
        $overtimeRate = 150.00;   // Overtime rate per hour
        $overtime = $overtimeHours * $overtimeRate;
        
        // Calculate attendance deductions
        $attendanceDeduction = 0;
        if ($daysWorked < 20) {
            $attendanceDeduction = $basicSalary * 0.1; // 10% deduction for less than 20 days
        }
        
        // Calculate gross salary
        $grossSalary = $basicSalary + $overtime;
        
        // Calculate tax based on progressive tax brackets
        $tax = calculateTax($grossSalary);
        
        // Calculate net pay after all deductions
        $netPay = $grossSalary - $attendanceDeduction - $tax;
    } else {
        // No worked days = no salary
        $basicSalary = 0;
        $overtime = 0;
        $attendanceDeduction = 0;
        $tax = 0;
        $netPay = 0;
    }
    
    return [
        'daysWorked' => $daysWorked,
        'basicSalary' => $basicSalary,
        'overtimePay' => $overtime,
        'deductions' => $attendanceDeduction,
        'tax' => $tax,
        'netPay' => $netPay
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

function generatePayslip($userId, $salaryId) {
    global $conn;
    
    // Create payslips directory if it doesn't exist
    $payslipsDir = '../uploads/payslips';
    $payslipsDirAbs = dirname(__FILE__) . '/../uploads/payslips';
    error_log("Checking payslips directory at: $payslipsDirAbs");

    // Try creating directory if it doesn't exist
    if (!file_exists($payslipsDirAbs)) {
        error_log("Payslips directory doesn't exist, attempting to create it");
        if (!mkdir($payslipsDirAbs, 0755, true)) {
            error_log("Failed to create directory: $payslipsDirAbs");
            
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
        } else {
            error_log("Successfully created payslips directory at: $payslipsDirAbs");
        }
    } else {
        error_log("Payslips directory exists at: $payslipsDirAbs");
    }
    
    // Get salary details with additional error logging
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
    error_log("Generating payslip for: " . $salary['firstName'] . " " . $salary['lastName'] . 
              ", Month: " . $salary['month'] . ", Year: " . $salary['year'] . 
              ", Days worked: " . $salary['daysWorked']);
    
    try {
    // Generate PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    
    // Add payslip content
    $pdf->Cell(0,10,'PAYSLIP',0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,'Employee: '.$salary['firstName'].' '.$salary['lastName'],0,1);
    $pdf->Cell(0,10,'Month: '.$salary['month'].'/'.$salary['year'],0,1);
    $pdf->Cell(0,10,'Basic Salary: PHP '.number_format($salary['basicSalary'], 2),0,1);
    $pdf->Cell(0,10,'Overtime: PHP '.number_format($salary['overtime'], 2),0,1);
    $pdf->Cell(0,10,'Attendance Deductions: PHP '.number_format($salary['deductions'], 2),0,1);
    
    // Add tax information
    $tax = isset($salary['tax']) ? $salary['tax'] : 0;
    $pdf->Cell(0,10,'Tax Deductions: PHP '.number_format($tax, 2),0,1);
    
    // Calculate total deductions
    $totalDeductions = $salary['deductions'] + $tax;
    $pdf->Cell(0,10,'Total Deductions: PHP '.number_format($totalDeductions, 2),0,1);
    
    $pdf->Cell(0,10,'Net Salary: PHP '.number_format($salary['totalSalary'], 2),0,1);
    
    // Delete any existing payslip files for this user and salary ID
    $existingFiles = glob(dirname(__FILE__) . '/../uploads/payslips/' . $userId . '_' . $salaryId . '*.pdf');
    foreach ($existingFiles as $file) {
        if (file_exists($file)) {
            @unlink($file);
            error_log("Deleted old payslip file: " . $file);
        }
    }
    
    // Save PDF - create the path for the database and file with timestamp
    $timestamp = time();
    $relativeFilePath = 'uploads/payslips/'.$userId.'_'.$salaryId.'_'.$timestamp.'.pdf';
    $fullFilePath = dirname(__FILE__) . '/../' . $relativeFilePath;
    
    // Log the paths for debugging
    error_log("Saving PDF to: $fullFilePath (relative path for database: $relativeFilePath)");
    
    // Output the PDF to file
    $pdf->Output('F', $fullFilePath);
    
    if (!file_exists($fullFilePath)) {
        error_log("Failed to create PDF file at: $fullFilePath");
        return false;
    }
    
    error_log("Generated PDF at: $fullFilePath");
    
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
        error_log("Updating existing payslip record");
    } else {
        // Insert new record - Note: We're not using 'status' field as it might not exist
        $query = "INSERT INTO payslip (userId, salaryId, pdfPath) 
                  VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $userId, $salaryId, $relativeFilePath);
        error_log("Inserting new payslip record");
    }
    
    $result = $stmt->execute();
    if (!$result) {
        error_log("Failed to update payslip record: " . $conn->error);
    }
    return $result;
    } catch (Exception $e) {
        error_log("Error generating payslip: " . $e->getMessage());
        return false;
    }
}