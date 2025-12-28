<?php
session_start();
include(dirname(dirname(__FILE__)) . "/dB/config.php");

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if this is a POST or GET request
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

// Get month and year from request
if ($isPost) {
    $requestBody = file_get_contents('php://input');
    parse_str($requestBody, $postData);
    $month = isset($postData['month']) ? intval($postData['month']) : date('m');
    $year = isset($postData['year']) ? intval($postData['year']) : date('Y');
} else {
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
}

// Create directories if they don't exist
$reportsDir = dirname(dirname(__FILE__)) . '/uploads/reports';
$rootReportsDir = dirname(dirname(dirname(__FILE__))) . '/uploads/reports';

foreach ([$reportsDir, $rootReportsDir] as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            if ($isPost) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => "Failed to create directory: $dir"]);
                exit;
            } else {
                die("Failed to create directory: $dir");
            }
        }
    }
}

// Try to load FPDF with absolute path
$fpdfAvailable = false;
$fpdfAbsolutePath = dirname(__FILE__) . '/fpdf/fpdf.php';

if (file_exists($fpdfAbsolutePath)) {
    try {
        require($fpdfAbsolutePath);
        $fpdfAvailable = true;
    } catch (Exception $e) {
        error_log("Failed to load FPDF library: " . $e->getMessage());
    }
}

try {
    if ($fpdfAvailable) {
        // Try to generate PDF report
        $reportInfo = generatePdfReport($month, $year, $reportsDir);
    } else {
        // Fallback to text report
        $reportInfo = generateTextReport($month, $year, $reportsDir);
    }
    
    // Copy to root directory for maximum compatibility
    if (file_exists($rootReportsDir)) {
        $rootFilePath = $rootReportsDir . '/' . $reportInfo['fileName'];
        copy($reportInfo['filePath'], $rootFilePath);
    }
    
    // Respond based on request type
    if ($isPost) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Report generated successfully',
            'fileName' => $reportInfo['fileName'],
            'downloadUrl' => '../api/payslip.php?action=download_report&file=' . $reportInfo['fileName']
        ]);
        exit;
    } else {
        // For GET requests, force download the file
        header('Content-Type: ' . $reportInfo['contentType']);
        header('Content-Disposition: attachment; filename="' . $reportInfo['downloadName'] . '"');
        header('Content-Length: ' . filesize($reportInfo['filePath']));
        readfile($reportInfo['filePath']);
        exit;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Error generating report: " . $e->getMessage());
    
    // Respond based on request type
    if ($isPost) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    } else {
        // For GET requests, show error and redirect back
        echo "<script>
            alert('Error generating report: " . $e->getMessage() . "');
            window.history.back();
        </script>";
        exit;
    }
}

function generatePdfReport($month, $year, $reportsDir) {
    global $conn;
    
    // Create a new PDF document
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Set document properties
    $pdf->SetTitle('Payroll Report - ' . getMonthName($month) . ' ' . $year);
    $pdf->SetAuthor('AttendRoll System');
    
    // Add header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Payroll Report', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, getMonthName($month) . ' ' . $year, 0, 1, 'C');
    
    // Add company information
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'AttendRoll Company', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Set header for the table
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(50, 8, 'Employee', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Days', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Basic Salary', 1, 0, 'R', true);
    $pdf->Cell(30, 8, 'Overtime', 1, 0, 'R', true);
    $pdf->Cell(30, 8, 'Deductions', 1, 0, 'R', true);
    $pdf->Cell(30, 8, 'Net Pay', 1, 1, 'R', true);
    
    // Get employee salary data
    $query = "SELECT u.firstName, u.lastName, u.userId
              FROM users u 
              WHERE u.userRole = 'user'
              ORDER BY u.firstName, u.lastName";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database query error: " . mysqli_error($conn));
    }
    
    // Setup totals
    $totalBasic = 0;
    $totalOvertime = 0;
    $totalDeductions = 0;
    $totalNetPay = 0;
    
    // Set font for data
    $pdf->SetFont('Arial', '', 9);
    
    // Loop through employees
    while ($employee = mysqli_fetch_assoc($result)) {
        // Calculate payroll for this employee
        $payrollData = calculatePayroll($employee['userId'], $month, $year);
        
        // Only include employees with worked days
        if ($payrollData['daysWorked'] > 0) {
            // Add employee data to PDF
            $employeeName = $employee['firstName'] . ' ' . $employee['lastName'];
            $pdf->Cell(50, 7, $employeeName, 1, 0, 'L');
            $pdf->Cell(20, 7, $payrollData['daysWorked'], 1, 0, 'C');
            $pdf->Cell(30, 7, '₱' . number_format($payrollData['basicSalary'], 2), 1, 0, 'R');
            $pdf->Cell(30, 7, '₱' . number_format($payrollData['overtimePay'], 2), 1, 0, 'R');
            $pdf->Cell(30, 7, '₱' . number_format($payrollData['deductions'], 2), 1, 0, 'R');
            $pdf->Cell(30, 7, '₱' . number_format($payrollData['netPay'], 2), 1, 1, 'R');
            
            // Update totals
            $totalBasic += $payrollData['basicSalary'];
            $totalOvertime += $payrollData['overtimePay'];
            $totalDeductions += $payrollData['deductions'];
            $totalNetPay += $payrollData['netPay'];
        }
    }
    
    // Add summary row
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 8, 'TOTAL', 1, 0, 'L', true);
    $pdf->Cell(20, 8, '', 1, 0, 'C', true);
    $pdf->Cell(30, 8, '₱' . number_format($totalBasic, 2), 1, 0, 'R', true);
    $pdf->Cell(30, 8, '₱' . number_format($totalOvertime, 2), 1, 0, 'R', true);
    $pdf->Cell(30, 8, '₱' . number_format($totalDeductions, 2), 1, 0, 'R', true);
    $pdf->Cell(30, 8, '₱' . number_format($totalNetPay, 2), 1, 1, 'R', true);
    
    // Add footer
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
    
    // Create file name
    $fileName = 'Payroll_Report_' . $month . '_' . $year . '_' . time() . '.pdf';
    $filePath = $reportsDir . '/' . $fileName;
    
    // Output the PDF
    $pdf->Output('F', $filePath);
    
    return [
        'fileName' => $fileName,
        'filePath' => $filePath,
        'contentType' => 'application/pdf',
        'downloadName' => 'Payroll_Report_' . getMonthName($month) . '_' . $year . '.pdf'
    ];
}

function generateTextReport($month, $year, $reportsDir) {
    global $conn;
    
    // Start building report content
    $content = "================ PAYROLL REPORT ================\n";
    $content .= "Month: " . getMonthName($month) . " " . $year . "\n";
    $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
    $content .= "=============================================\n\n";
    
    // Get employee salary data
    $query = "SELECT u.firstName, u.lastName, u.userId
              FROM users u 
              WHERE u.userRole = 'user'
              ORDER BY u.firstName, u.lastName";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database query error: " . mysqli_error($conn));
    }
    
    // Setup totals
    $totalBasic = 0;
    $totalOvertime = 0;
    $totalDeductions = 0;
    $totalNetPay = 0;
    $employeeCount = 0;
    
    // Loop through employees
    while ($employee = mysqli_fetch_assoc($result)) {
        // Calculate payroll for this employee
        $payrollData = calculatePayroll($employee['userId'], $month, $year);
        
        // Only include employees with worked days
        if ($payrollData['daysWorked'] > 0) {
            $employeeCount++;
            $employeeName = $employee['firstName'] . ' ' . $employee['lastName'];
            
            $content .= "Employee: " . str_pad($employeeName, 30) . "\n";
            $content .= "Days Worked: " . str_pad($payrollData['daysWorked'], 10) . "\n";
            $content .= "Basic Salary: " . str_pad('₱' . number_format($payrollData['basicSalary'], 2), 15) . "\n";
            $content .= "Overtime: " . str_pad('₱' . number_format($payrollData['overtimePay'], 2), 15) . "\n";
            $content .= "Deductions: " . str_pad('₱' . number_format($payrollData['deductions'], 2), 15) . "\n";
            $content .= "Net Pay: " . str_pad('₱' . number_format($payrollData['netPay'], 2), 15) . "\n";
            $content .= "--------------------------------------------\n\n";
            
            // Update totals
            $totalBasic += $payrollData['basicSalary'];
            $totalOvertime += $payrollData['overtimePay'];
            $totalDeductions += $payrollData['deductions'];
            $totalNetPay += $payrollData['netPay'];
        }
    }
    
    // Add summary
    $content .= "\n===================== SUMMARY ====================\n";
    $content .= "Total Employees: " . $employeeCount . "\n";
    $content .= "Total Basic Salary: ₱" . number_format($totalBasic, 2) . "\n";
    $content .= "Total Overtime: ₱" . number_format($totalOvertime, 2) . "\n";
    $content .= "Total Deductions: ₱" . number_format($totalDeductions, 2) . "\n";
    $content .= "Total Net Pay: ₱" . number_format($totalNetPay, 2) . "\n";
    $content .= "==============================================\n\n";
    $content .= "This is a computer-generated document. No signature is required.\n";
    
    // Create file name
    $fileName = 'Payroll_Report_' . $month . '_' . $year . '_' . time() . '.txt';
    $filePath = $reportsDir . '/' . $fileName;
    
    // Write content to file
    if (file_put_contents($filePath, $content) === false) {
        throw new Exception("Failed to write text report to file");
    }
    
    return [
        'fileName' => $fileName,
        'filePath' => $filePath,
        'contentType' => 'text/plain',
        'downloadName' => 'Payroll_Report_' . getMonthName($month) . '_' . $year . '.txt'
    ];
}

// Helper function to calculate payroll for an employee
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
    
    // Initialize salary variables
    $basicSalary = 0;
    $overtime = 0;
    $deductions = 0;
    $netPay = 0;
    
    // Only calculate salary if employee has worked days
    if($daysWorked > 0) {
        // Calculate overtime (hours worked beyond 8 hours per day)
        $regularHours = $daysWorked * 8;
        $overtimeHours = max(0, $totalHours - $regularHours);
        
        // Calculate pay
        $basicSalary = 20000.00; // Base salary per month
        $overtimeRate = 150.00;   // Overtime rate per hour
        $overtime = $overtimeHours * $overtimeRate;
        
        // Calculate deductions
        $deductions = 0;
        if ($daysWorked < 20) {
            $deductions = $basicSalary * 0.1; // 10% deduction for less than 20 days
        }
        
        $netPay = $basicSalary + $overtime - $deductions;
    }
    
    return [
        'daysWorked' => $daysWorked,
        'basicSalary' => $basicSalary,
        'overtimePay' => $overtime,
        'deductions' => $deductions,
        'netPay' => $netPay
    ];
}

// Helper function to get month name
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    return $months[(int)$month] ?? '';
}
?> 