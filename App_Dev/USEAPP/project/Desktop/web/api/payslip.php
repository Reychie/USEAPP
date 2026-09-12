<?php
// Include API config
include('config.php');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// First check for direct file downloads - this needs to run before other handlers
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'download' && isset($_GET['payslipId'])) {
        downloadPayslip($_GET['payslipId']);
        exit;
    } else if ($_GET['action'] === 'download_report' && isset($_GET['file'])) {
        downloadReport($_GET['file']);
        exit;
    } else if ($_GET['action'] === 'view_report' && isset($_GET['file'])) {
        viewReport($_GET['file']);
        exit;
    }
}

switch ($method) {
    case 'GET':
        // Get the requested action
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'view':
                // View payslip
                $payslipId = isset($_GET['payslipId']) ? intval($_GET['payslipId']) : 0;
                if ($payslipId) {
                    viewPayslip($payslipId);
                } else {
                    sendResponse(false, 'Payslip ID is required');
                }
                break;
            
            case 'download':
                // Download payslip
                $payslipId = isset($_GET['payslipId']) ? intval($_GET['payslipId']) : 0;
                if ($payslipId) {
                    downloadPayslip($payslipId);
                } else {
                    sendResponse(false, 'Payslip ID is required');
                }
                break;
            
            case 'download_report':
                // Download report file
                $filename = isset($_GET['file']) ? $_GET['file'] : '';
                if ($filename) {
                    downloadReport($filename);
                } else {
                    sendResponse(false, 'Report filename is required');
                }
                break;
            
            case 'view_report':
                // View report file in browser
                $filename = isset($_GET['file']) ? $_GET['file'] : '';
                if ($filename) {
                    viewReport($filename);
                } else {
                    sendResponse(false, 'Report filename is required');
                }
                break;
            
            case 'delete_report':
                // Delete report file
                $filename = isset($_GET['file']) ? $_GET['file'] : '';
                if ($filename) {
                    deleteReport($filename);
                } else {
                    sendResponse(false, 'Report filename is required');
                }
                break;
            
            case 'check_work_days':
                // Check work days and generate payslip if threshold is reached
                $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
                if ($userId) {
                    checkWorkDaysAndGeneratePayslip($userId);
                } else {
                    sendResponse(false, 'User ID is required');
                }
                break;
            
            default:
                // Handle regular payslip request
                handleGetPayslip();
                break;
        }
        break;
    case 'POST':
        // Get POST data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Check for action in both GET params and POST body
        $action = isset($_GET['action']) ? $_GET['action'] : (isset($data['action']) ? $data['action'] : '');
        
        switch ($action) {
            case 'export':
                // Handle payroll export
                exportPayroll($data);
                break;
            
            default:
                // Handle regular payslip generation
                handleGeneratePayslip();
                break;
        }
        break;
    default:
        sendResponse(false, 'Invalid request method');
        break;
}

function handleGetPayslip() {
    global $conn;
    
    // Check if this is a request for a specific payslip
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['payslipId'])) {
        $payslipId = sanitizeInput($_GET['payslipId']);
        viewPayslip($payslipId);
        return;
    }
    
    // Check for required parameters
    $userId = isset($_GET['userId']) ? sanitizeInput($_GET['userId']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? sanitizeInput($_GET['year']) : date('Y');
    
    if (!$userId) {
        sendResponse(false, 'User ID is required');
    }
    
    // Get payslip records for the user and period
    $query = "SELECT p.*, s.month, s.year, s.basicSalary, s.overtime, s.deductions, s.totalSalary 
              FROM payslip p
              JOIN salary s ON p.salaryId = s.salaryId
              WHERE p.userId = ? AND s.month = ? AND s.year = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payslips = [];
    while ($row = $result->fetch_assoc()) {
        $payslips[] = [
            'payslipId' => $row['payslipId'],
            'userId' => $row['userId'],
            'salaryId' => $row['salaryId'],
            'period' => getMonthName($row['month']) . ' ' . $row['year'],
            'basicSalary' => $row['basicSalary'],
            'overtime' => $row['overtime'],
            'deductions' => $row['deductions'],
            'netPay' => $row['totalSalary'],
            'status' => $row['status'],
            'pdfPath' => $row['pdfPath'],
            'createdAt' => $row['createdAt']
        ];
    }
    
    // If no payslips found, check if there's a salary record
    if (empty($payslips)) {
        $salaryQuery = "SELECT * FROM salary WHERE userId = ? AND month = ? AND year = ?";
        $salaryStmt = $conn->prepare($salaryQuery);
        $salaryStmt->bind_param("iii", $userId, $month, $year);
        $salaryStmt->execute();
        $salaryResult = $salaryStmt->get_result();
        
        if ($salaryResult->num_rows > 0) {
            $salary = $salaryResult->fetch_assoc();
            $payslips[] = [
                'payslipId' => null,
                'userId' => $userId,
                'salaryId' => $salary['salaryId'],
                'period' => getMonthName($salary['month']) . ' ' . $salary['year'],
                'basicSalary' => $salary['basicSalary'],
                'overtime' => $salary['overtime'],
                'deductions' => $salary['deductions'],
                'netPay' => $salary['totalSalary'],
                'status' => 'Pending',
                'pdfPath' => null,
                'createdAt' => null
            ];
        }
    }
    
    // Get monthly summary
    $monthlySummary = calculateMonthlySummary($userId, $month, $year);
    
    $response = [
        'payslips' => $payslips,
        'monthlySummary' => $monthlySummary
    ];
    
    sendResponse(true, 'Payslip data retrieved', $response);
}

function viewPayslip($payslipId) {
    global $conn;
    
    // Get payslip info
    $query = "SELECT p.*, s.month, s.year, s.basicSalary, s.overtime, s.deductions, s.totalSalary,
              u.firstName, u.lastName, u.email
              FROM payslip p
              JOIN salary s ON p.salaryId = s.salaryId
              JOIN users u ON p.userId = u.userId
              WHERE p.payslipId = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $payslipId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Payslip not found');
        return;
    }
    
    $payslip = $result->fetch_assoc();
    
    // Check if the file exists, if not, regenerate it
    if (!file_exists($payslip['pdfPath'])) {
        // Attempt to regenerate the PDF
        generatePayslipPDF($payslip);
    }
    
    // Send the file path in the response
    sendResponse(true, 'Payslip found', [
        'payslipId' => $payslipId,
        'employeeName' => $payslip['firstName'] . ' ' . $payslip['lastName'],
        'period' => getMonthName($payslip['month']) . ' ' . $payslip['year'],
        'pdfPath' => $payslip['pdfPath'],
        'pdfUrl' => getApiBaseUrl() . '?action=download&payslipId=' . $payslipId,
        'basicSalary' => $payslip['basicSalary'],
        'overtimePay' => $payslip['overtime'],
        'deductions' => $payslip['deductions'],
        'netPay' => $payslip['totalSalary']
    ]);
}

function getApiBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        ? 'https://'
        : 'http://';

    if (!empty($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = isset($_SERVER['PHP_SELF']) ? rtrim(dirname($_SERVER['PHP_SELF']), '/') : '/api';
        return $protocol . $host . $uri . '/payslip.php';
    }

    $configured = getenv('API_BASE_URL');
    if ($configured) {
        return rtrim($configured, '/') . '/payslip.php';
    }

    return $protocol . 'localhost/api/payslip.php';
}

function handleGeneratePayslip() {
    global $conn;
    
    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate required fields
    if (!isset($data['userId'])) {
        sendResponse(false, 'User ID is required');
    }
    
    $userId = sanitizeInput($data['userId']);
    $salaryId = isset($data['salaryId']) ? sanitizeInput($data['salaryId']) : null;
    
    // If no salary ID provided, get the latest salary record for the user
    if (!$salaryId) {
        $month = isset($data['month']) ? sanitizeInput($data['month']) : date('m');
        $year = isset($data['year']) ? sanitizeInput($data['year']) : date('Y');
        
        // Check work days first
        $workDaysQuery = "SELECT COUNT(*) as days_worked FROM attendance 
                         WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
        $workDaysStmt = $conn->prepare($workDaysQuery);
        $workDaysStmt->bind_param("iii", $userId, $month, $year);
        $workDaysStmt->execute();
        $workDaysResult = $workDaysStmt->get_result()->fetch_assoc();
        $daysWorked = $workDaysResult['days_worked'];
        
        // Check if employee has worked at least 1 day (lowered from 22 for testing)
        if ($daysWorked < 1) {
            sendResponse(false, 'Cannot generate payslip: Employee has not completed the required 1 work day. Current days worked: ' . $daysWorked);
            return;
        }
        
        // Continue with getting salary ID
        $salaryQuery = "SELECT salaryId FROM salary WHERE userId = ? AND month = ? AND year = ? LIMIT 1";
        $salaryStmt = $conn->prepare($salaryQuery);
        $salaryStmt->bind_param("iii", $userId, $month, $year);
        $salaryStmt->execute();
        $salaryResult = $salaryStmt->get_result();
        
        if ($salaryResult->num_rows === 0) {
            sendResponse(false, 'No salary record found for this period');
            return;
        }
        
        $salaryRow = $salaryResult->fetch_assoc();
        $salaryId = $salaryRow['salaryId'];
    } else {
        // If salaryId is provided, we still need to check work days
        $workDaysQuery = "SELECT COUNT(*) as days_worked, s.month, s.year 
                         FROM attendance a
                         JOIN salary s ON s.userId = a.userId AND MONTH(a.date) = s.month AND YEAR(a.date) = s.year
                         WHERE a.userId = ? AND s.salaryId = ?";
        $workDaysStmt = $conn->prepare($workDaysQuery);
        $workDaysStmt->bind_param("ii", $userId, $salaryId);
        $workDaysStmt->execute();
        $workDaysResult = $workDaysStmt->get_result()->fetch_assoc();
        $daysWorked = $workDaysResult['days_worked'];
        
        // Check if employee has worked at least 1 day (lowered from 22 for testing)
        if ($daysWorked < 1) {
            sendResponse(false, 'Cannot generate payslip: Employee has not completed the required 1 work day. Current days worked: ' . $daysWorked);
            return;
        }
    }
    
    // Check if payslip already exists
    $checkQuery = "SELECT * FROM payslip WHERE userId = ? AND salaryId = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $userId, $salaryId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $payslip = $checkResult->fetch_assoc();
        sendResponse(true, 'Payslip already generated', [
            'payslipId' => $payslip['payslipId'],
            'pdfPath' => $payslip['pdfPath'],
            'pdfUrl' => getApiBaseUrl() . '?action=download&payslipId=' . $payslip['payslipId']
        ]);
        return;
    }
    
    // Get salary details with user information
    $salaryQuery = "SELECT s.*, u.firstName, u.lastName, u.email 
                   FROM salary s 
                   JOIN users u ON s.userId = u.userId 
                   WHERE s.salaryId = ?";
    $salaryStmt = $conn->prepare($salaryQuery);
    $salaryStmt->bind_param("i", $salaryId);
    $salaryStmt->execute();
    $salaryResult = $salaryStmt->get_result();
    
    if ($salaryResult->num_rows === 0) {
        sendResponse(false, 'Salary record not found');
        return;
    }
    
    $salary = $salaryResult->fetch_assoc();
    
    // Create uploads/payslips directory if it doesn't exist
    $uploadDir = '../uploads/payslips';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate payslip PDF and get file path
    $pdfPath = generatePayslipPDF($salary);
    
    if (!$pdfPath) {
        sendResponse(false, 'Failed to create payslip file');
        return;
    }
    
    // Insert payslip record
    $insertQuery = "INSERT INTO payslip (userId, salaryId, pdfPath, status) 
                   VALUES (?, ?, ?, 'Paid')";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iis", $userId, $salaryId, $pdfPath);
    
    if ($insertStmt->execute()) {
        $payslipId = $insertStmt->insert_id;
        sendResponse(true, 'Payslip generated successfully', [
            'payslipId' => $payslipId,
            'pdfPath' => $pdfPath,
            'pdfUrl' => getApiBaseUrl() . '?action=download&payslipId=' . $payslipId
        ]);
    } else {
        sendResponse(false, 'Failed to generate payslip record: ' . $conn->error);
    }
}

function generatePayslipPDF($data) {
    // Check if FPDF is available
    if (!class_exists('FPDF')) {
        // Try to include FPDF
        $fpdfPaths = [
            '../controller/fpdf/fpdf.php',
            '../../controller/fpdf/fpdf.php',
            dirname(__FILE__) . '/../controller/fpdf/fpdf.php'
        ];
        
        $fpdfLoaded = false;
        foreach ($fpdfPaths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $fpdfLoaded = true;
                break;
            }
        }
        
        if (!$fpdfLoaded) {
            // Fallback to text file if FPDF isn't available
            return generateTextPayslip($data);
        }
    }
    
    // Create a unique file name with PDF extension and timestamp to prevent caching
    $timestamp = time();
    $pdfFileName = 'payslip_' . $data['userId'] . '_' . $data['salaryId'] . '_' . $timestamp . '.pdf';
    $pdfPath = '../uploads/payslips/' . $pdfFileName;
    
    // Ensure the directory exists
    $dir = dirname($pdfPath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    try {
        // Create PDF - with UTF-8 encoding
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Add company header
        $pdf->Cell(0, 10, 'ATTENDROLL', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'PAYSLIP', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'Period: ' . getMonthName($data['month']) . ' ' . $data['year'], 0, 1, 'C');
        $pdf->Ln(5);
        
        // Add employee info
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Employee Information', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, 'Name:', 0);
        $pdf->Cell(0, 7, $data['firstName'] . ' ' . $data['lastName'], 0, 1);
        $pdf->Cell(50, 7, 'Email:', 0);
        $pdf->Cell(0, 7, isset($data['email']) ? $data['email'] : 'N/A', 0, 1);
        $pdf->Cell(50, 7, 'Days Worked:', 0);
        $pdf->Cell(0, 7, isset($data['daysWorked']) ? $data['daysWorked'] . ' days' : 'N/A', 0, 1);
        $pdf->Ln(5);
        
        // Add salary details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Salary Details', 0, 1);
        $pdf->SetFont('Arial', '', 11);
        
        // Use PHP to format the currency values
        // Note: Using PHP for formatting instead of relying on ₱ symbol, using PHP_EOL to PHP
        $pdf->Cell(100, 7, 'Basic Salary:', 0);
        $pdf->Cell(0, 7, 'PHP ' . number_format($data['basicSalary'], 2), 0, 1);
        
        $pdf->Cell(100, 7, 'Overtime Pay:', 0);
        $pdf->Cell(0, 7, 'PHP ' . number_format($data['overtime'], 2), 0, 1);
        
        $pdf->Cell(100, 7, 'Attendance Deductions:', 0);
        $pdf->Cell(0, 7, 'PHP ' . number_format($data['deductions'], 2), 0, 1);
        
        // Include tax if available
        if (isset($data['tax'])) {
            $pdf->Cell(100, 7, 'Tax Deductions:', 0);
            $pdf->Cell(0, 7, 'PHP ' . number_format($data['tax'], 2), 0, 1);
        }
        
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line(10, $pdf->GetY() + 3, 200, $pdf->GetY() + 3);
        $pdf->Ln(7);
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(100, 7, 'Net Salary:', 0);
        $pdf->Cell(0, 7, 'PHP ' . number_format($data['totalSalary'], 2), 0, 1);
        
        $pdf->Ln(10);
        
        // Add footer
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        
        // Output PDF to file
        $pdf->Output('F', $pdfPath);
        
        return $pdfPath;
    } catch (Exception $e) {
        error_log('Error generating PDF: ' . $e->getMessage());
        // Fallback to text file if PDF generation fails
        return generateTextPayslip($data);
    }
}

// Fallback function for text payslip
function generateTextPayslip($data) {
    // Create an unique file name with timestamp to prevent caching
    $timestamp = time();
    $textFileName = 'payslip_' . $data['userId'] . '_' . $data['salaryId'] . '_' . $timestamp . '.txt';
    $textPath = '../uploads/payslips/' . $textFileName;
    
    // Ensure the directory exists
    $dir = dirname($textPath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // For simplicity, we'll create a text file since FPDF is not available
    $content = "======== PAYSLIP ========\n\n";
    $content .= "Employee: " . $data['firstName'] . " " . $data['lastName'] . "\n";
    $content .= "Period: " . getMonthName($data['month']) . " " . $data['year'] . "\n\n";
    $content .= "Basic Salary: PHP " . number_format($data['basicSalary'], 2) . "\n";
    $content .= "Overtime Pay: PHP " . number_format($data['overtime'], 2) . "\n";
    $content .= "Deductions: PHP " . number_format($data['deductions'], 2) . "\n";
    
    // Include tax if available
    if (isset($data['tax'])) {
        $content .= "Tax Deductions: PHP " . number_format($data['tax'], 2) . "\n";
    }
    
    $content .= "------------------------\n";
    $content .= "Net Pay: PHP " . number_format($data['totalSalary'], 2) . "\n";
    $content .= "\n\nGenerated on: " . date('Y-m-d H:i:s');
    
    // Write to file with UTF-8 encoding
    $success = file_put_contents($textPath, "\xEF\xBB\xBF" . $content); // Add UTF-8 BOM
    
    return $success ? $textPath : false;
}

function calculateMonthlySummary($userId, $month, $year) {
    global $conn;
    
    // Get all salaries for the user in the specified month/year
    $query = "SELECT SUM(basicSalary) as totalBasic, 
              SUM(overtime) as totalOvertime, 
              SUM(deductions) as totalDeductions, 
              SUM(totalSalary) as totalNetPay 
              FROM salary 
              WHERE userId = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return [
        'totalBasic' => (float)($result['totalBasic'] ?? 0),
        'totalOvertime' => (float)($result['totalOvertime'] ?? 0),
        'totalDeductions' => (float)($result['totalDeductions'] ?? 0),
        'totalNetPay' => (float)($result['totalNetPay'] ?? 0)
    ];
}

function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    return $months[(int)$month] ?? '';
}

function downloadPayslip($payslipId) {
    global $conn;
    
    // Get payslip info
    $query = "SELECT p.*, s.month, s.year, s.basicSalary, s.overtime, s.deductions, s.totalSalary,
              u.firstName, u.lastName, u.email 
              FROM payslip p
              JOIN salary s ON p.salaryId = s.salaryId
              JOIN users u ON p.userId = u.userId
              WHERE p.payslipId = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $payslipId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "Payslip not found";
        exit;
    }
    
    $payslip = $result->fetch_assoc();
    
    // Store user ID and salary ID for later use
    $userId = $payslip['userId'];
    $salaryId = $payslip['salaryId'];
    
    // Path to the file
    $oldFilePath = $payslip['pdfPath'];
    
    // Check for any existing payslip files with this userId and salaryId pattern and delete them
    $payslipsDir = dirname(__FILE__) . '/../uploads/payslips/';
    if (file_exists($payslipsDir)) {
        $files = scandir($payslipsDir);
        foreach ($files as $file) {
            // Match the pattern for both old and new format files
            if (preg_match('/^(payslip_)?' . $userId . '_' . $salaryId . '(\.[a-z]+)$/', $file)) {
                @unlink($payslipsDir . $file);
                error_log("Deleted old payslip file: " . $payslipsDir . $file);
            }
        }
    }
    
    // Always regenerate the PDF with current data and a timestamp to avoid caching
    $newFilePath = generatePayslipPDF($payslip);
    
    if (!$newFilePath) {
        echo "Failed to generate payslip";
        exit;
    }
    
    // Update the path in the database
    $updateQuery = "UPDATE payslip SET pdfPath = ? WHERE payslipId = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("si", $newFilePath, $payslipId);
    $updateStmt->execute();
    
    // Get filename
    $filename = basename($newFilePath);
    $employeeName = $payslip['firstName'] . '_' . $payslip['lastName'];
    $period = getMonthName($payslip['month']) . '_' . $payslip['year'];
    
    // Determine file extension and content type
    $fileExtension = pathinfo($newFilePath, PATHINFO_EXTENSION);
    if (strtolower($fileExtension) === 'pdf') {
        $contentType = 'application/pdf';
        $downloadName = "Payslip_{$employeeName}_{$period}.pdf";
    } else {
        $contentType = 'text/plain; charset=UTF-8';
        $downloadName = "Payslip_{$employeeName}_{$period}.txt";
    }
    
    // Check if direct download requested
    $directDownload = isset($_GET['direct_download']) && $_GET['direct_download'] === 'true';
    
    // Set appropriate headers with no-cache directives
    header('Content-Type: ' . $contentType);
    
    if ($directDownload) {
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    } else {
        // For mobile viewing in browser
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
    }
    
    // Add cache-busting random parameter to the URL
    $cacheParam = '?nocache=' . time() . rand(1000, 9999);
    
    header('Content-Length: ' . filesize($newFilePath));
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($newFilePath);
    exit;
}

function exportPayroll($data = null) {
    global $conn;
    
    // Enable error logging for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    error_log("Starting payroll export process");
    
    // Get data either from function parameter or directly from input
    if (!$data) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
    }
    
    // Get userId (required), month and year
    $userId = isset($data['userId']) ? sanitizeInput($data['userId']) : null;
    $month = isset($data['month']) ? sanitizeInput($data['month']) : date('m');
    $year = isset($data['year']) ? sanitizeInput($data['year']) : date('Y');
    
    // Debug log
    error_log("Exporting payroll for userId: $userId, month: $month, year: $year");
    
    // Validate userId
    if (!$userId) {
        sendResponse(false, 'User ID is required');
        return;
    }
    
    try {
        // Get all employees with salary data for the period
        $query = "SELECT u.userId, u.firstName, u.lastName, u.email,
                s.basicSalary, s.overtime, s.deductions, s.tax, s.totalSalary,
                s.month, s.year
                FROM users u
                JOIN salary s ON u.userId = s.userId
                WHERE s.month = ? AND s.year = ?";
        
        // For admin users, get all employees; for regular users, only get their own data
        $isAdmin = checkIfAdmin($userId);
        if (!$isAdmin) {
            $query .= " AND u.userId = ?";
        }
        
        $query .= " ORDER BY u.firstName ASC";
        
        // Check if the statement was prepared successfully
        if ($stmt = $conn->prepare($query)) {
            if ($isAdmin) {
                $stmt->bind_param("ii", $month, $year);
            } else {
                $stmt->bind_param("iii", $month, $year, $userId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                sendResponse(false, 'No payroll data found for this period');
                return;
            }
        } else {
            error_log("SQL preparation error: " . $conn->error . " for query: " . $query);
            sendResponse(false, 'Database error: ' . $conn->error);
            return;
        }
        
        // Create file path for the report
        $reportDir = '../uploads/reports';
        if (!file_exists($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        // Decide if we use PDF or text report
        $usePdf = false;
        
        // Check if FPDF is available
        if (!class_exists('FPDF')) {
            // Try to include FPDF
            $fpdfPaths = [
                '../controller/fpdf/fpdf.php',
                '../../controller/fpdf/fpdf.php',
                dirname(__FILE__) . '/../controller/fpdf/fpdf.php'
            ];
            
            foreach ($fpdfPaths as $path) {
                if (file_exists($path)) {
                    try {
                        require_once($path);
                        $usePdf = true;
                        error_log("FPDF loaded from $path");
                        break;
                    } catch (Exception $e) {
                        error_log("Error loading FPDF from $path: " . $e->getMessage());
                    }
                }
            }
        } else {
            $usePdf = true;
            error_log("FPDF class already loaded");
        }
        
        // Collect employee data
        $employees = [];
        $totalBasic = 0;
        $totalOT = 0;
        $totalDeductions = 0;
        $totalTax = 0;
        $totalNetPay = 0;
        
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
            $totalBasic += $row['basicSalary'];
            $totalOT += $row['overtime'];
            $totalDeductions += $row['deductions'];
            $totalTax += isset($row['tax']) ? $row['tax'] : 0;
            $totalNetPay += $row['totalSalary'];
        }
        
        // Generate a unique filename
        $timestamp = time();
        $reportFileName = "Payroll_Report_{$month}_{$year}_{$timestamp}";
        
        if ($usePdf) {
            try {
                // Generate PDF report
                $reportFileName .= ".pdf";
                $reportPath = $reportDir . '/' . $reportFileName;
                
                error_log("Generating PDF report at: $reportPath");
                
                // Create PDF
                $pdf = new FPDF('L', 'mm', 'A4'); // Landscape mode
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                
                // Add header
                $pdf->Cell(0, 10, 'ATTENDROLL', 0, 1, 'C');
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->Cell(0, 10, 'PAYROLL REPORT', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(0, 5, 'Period: ' . getMonthName($month) . ' ' . $year, 0, 1, 'C');
                $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
                $pdf->Ln(5);
                
                // Table header
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(220, 220, 220);
                $pdf->Cell(70, 7, 'Employee Name', 1, 0, 'C', true);
                $pdf->Cell(15, 7, 'Days', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Basic Salary', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Overtime Pay', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Deductions', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Tax', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Net Pay', 1, 1, 'C', true);
                
                // Table rows
                $pdf->SetFont('Arial', '', 9);
                foreach ($employees as $emp) {
                    $pdf->Cell(70, 6, $emp['firstName'] . ' ' . $emp['lastName'], 1, 0, 'L');
                    
                    // Get work days from attendance table instead of salary table
                    $workDaysQuery = "SELECT COUNT(*) as days_worked 
                                     FROM attendance 
                                     WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
                    $workDaysStmt = $conn->prepare($workDaysQuery);
                    $workDaysStmt->bind_param("iii", $emp['userId'], $emp['month'], $emp['year']);
                    $workDaysStmt->execute();
                    $workDaysResult = $workDaysStmt->get_result()->fetch_assoc();
                    $daysWorked = $workDaysResult['days_worked'] ?? 0;
                    
                    $pdf->Cell(15, 6, $daysWorked, 1, 0, 'C');
                    $pdf->Cell(30, 6, 'PHP ' . number_format($emp['basicSalary'], 2), 1, 0, 'R');
                    $pdf->Cell(30, 6, 'PHP ' . number_format($emp['overtime'], 2), 1, 0, 'R');
                    $pdf->Cell(30, 6, 'PHP ' . number_format($emp['deductions'], 2), 1, 0, 'R');
                    $pdf->Cell(30, 6, 'PHP ' . number_format(isset($emp['tax']) ? $emp['tax'] : 0, 2), 1, 0, 'R');
                    $pdf->Cell(30, 6, 'PHP ' . number_format($emp['totalSalary'], 2), 1, 1, 'R');
                }
                
                // Summary
                $pdf->Ln(5);
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(70, 7, 'TOTAL', 0, 0, 'L');
                $pdf->Cell(15, 7, '', 0, 0, 'C');
                $pdf->Cell(30, 7, 'PHP ' . number_format($totalBasic, 2), 0, 0, 'R');
                $pdf->Cell(30, 7, 'PHP ' . number_format($totalOT, 2), 0, 0, 'R');
                $pdf->Cell(30, 7, 'PHP ' . number_format($totalDeductions, 2), 0, 0, 'R');
                $pdf->Cell(30, 7, 'PHP ' . number_format($totalTax, 2), 0, 0, 'R');
                $pdf->Cell(30, 7, 'PHP ' . number_format($totalNetPay, 2), 0, 1, 'R');
                
                // Footer
                $pdf->SetY(-15);
                $pdf->SetFont('Arial', 'I', 8);
                $pdf->Cell(0, 10, 'This is a computer-generated document. No signature is required.', 0, 0, 'C');
                
                // Save PDF
                $pdf->Output('F', $reportPath);
                error_log("PDF generated successfully");
            } catch (Exception $e) {
                error_log('Error generating PDF report: ' . $e->getMessage());
                $usePdf = false; // Fallback to text report
            }
        }
        
        // Fallback to text report if PDF generation failed or not available
        if (!$usePdf) {
            $reportFileName .= ".txt";
            $reportPath = $reportDir . '/' . $reportFileName;
            error_log("Generating text report at: $reportPath");
            
            // Generate report content
            $content = "============ PAYROLL REPORT ============\n";
            $content .= "Period: " . getMonthName($month) . " " . $year . "\n";
            $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            $content .= str_pad("Employee Name", 30) . str_pad("Days", 10) . str_pad("Basic", 15) . str_pad("OT Pay", 15) . str_pad("Deductions", 15) . str_pad("Tax", 15) . str_pad("Net Pay", 15) . "\n";
            $content .= str_repeat("-", 115) . "\n";
            
            foreach ($employees as $emp) {
                $employeeName = $emp['firstName'] . ' ' . $emp['lastName'];
                $content .= str_pad(substr($employeeName, 0, 29), 30);
                $content .= str_pad($emp['daysWorked'], 10);
                $content .= str_pad("PHP " . number_format($emp['basicSalary'], 2), 15);
                $content .= str_pad("PHP " . number_format($emp['overtime'], 2), 15);
                $content .= str_pad("PHP " . number_format($emp['deductions'], 2), 15);
                $content .= str_pad("PHP " . number_format(isset($emp['tax']) ? $emp['tax'] : 0, 2), 15);
                $content .= str_pad("PHP " . number_format($emp['totalSalary'], 2), 15) . "\n";
            }
            
            $content .= str_repeat("-", 115) . "\n";
            $content .= str_pad("TOTAL", 30);
            $content .= str_pad("", 10);
            $content .= str_pad("PHP " . number_format($totalBasic, 2), 15);
            $content .= str_pad("PHP " . number_format($totalOT, 2), 15);
            $content .= str_pad("PHP " . number_format($totalDeductions, 2), 15);
            $content .= str_pad("PHP " . number_format($totalTax, 2), 15);
            $content .= str_pad("PHP " . number_format($totalNetPay, 2), 15) . "\n";
            
            // Write to file
            try {
                file_put_contents($reportPath, $content);
                error_log("Text report generated successfully");
            } catch (Exception $e) {
                error_log("Error writing text report to file: " . $e->getMessage());
                sendResponse(false, 'Failed to generate report file: ' . $e->getMessage());
                return;
            }
        }
        
        // Create the download URL
        $downloadUrl = getApiBaseUrl() . '?action=download_report&file=' . basename($reportPath);
        error_log("Report download URL: $downloadUrl");
        
        // Return success response
        sendResponse(true, 'Payroll report exported successfully', [
            'fileName' => basename($reportPath),
            'downloadUrl' => $downloadUrl,
            'isPdf' => $usePdf
        ]);
        
    } catch (Exception $e) {
        error_log("Fatal error in exportPayroll: " . $e->getMessage());
        sendResponse(false, 'An error occurred while exporting payroll: ' . $e->getMessage());
    }
}

// Helper function to check if user is admin
function checkIfAdmin($userId) {
    global $conn;
    
    $query = "SELECT userRole FROM users WHERE userId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $userData = $result->fetch_assoc();
    return $userData['userRole'] === 'admin';
}

function downloadReport($filename) {
    // For security, sanitize the filename
    $filename = basename(sanitizeInput($filename));
    
    // Define possible report paths
    $reportPaths = [
        '../uploads/reports/' . $filename,
        dirname(__FILE__) . '/../uploads/reports/' . $filename
    ];
    
    $filePath = null;
    foreach ($reportPaths as $path) {
        if (file_exists($path)) {
            $filePath = $path;
            break;
        }
    }
    
    if (!$filePath) {
        echo "Report file not found";
        exit;
    }
    
    // Determine file extension and content type
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    
    if (strtolower($fileExtension) === 'pdf') {
        $contentType = 'application/pdf';
        $downloadName = 'Payroll_Report.' . $fileExtension;
    } else {
        $contentType = 'application/octet-stream';
        $downloadName = 'Payroll_Report.txt';
    }
    
    // Check if direct download requested
    $directDownload = isset($_GET['direct_download']) && $_GET['direct_download'] === 'true';
    
    // Set appropriate headers
    header('Content-Type: ' . $contentType);
    
    if ($directDownload) {
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    } else {
        // For mobile viewing in browser
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
    }
    
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($filePath);
    exit;
}

function viewReport($filename) {
    // For security, sanitize the filename
    $filename = basename(sanitizeInput($filename));
    
    // Define possible report paths
    $reportPaths = [
        '../uploads/reports/' . $filename,
        dirname(__FILE__) . '/../uploads/reports/' . $filename
    ];
    
    $filePath = null;
    foreach ($reportPaths as $path) {
        if (file_exists($path)) {
            $filePath = $path;
            break;
        }
    }
    
    if (!$filePath) {
        echo "Report file not found";
        exit;
    }
    
    // Determine file extension and content type
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    
    if (strtolower($fileExtension) === 'pdf') {
        $contentType = 'application/pdf';
    } else {
        $contentType = 'text/plain; charset=UTF-8';
    }
    
    // Set appropriate headers for viewing in browser
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output file
    readfile($filePath);
    exit;
}

function deleteReport($filename) {
    // Security check to prevent directory traversal
    $filename = basename(sanitizeInput($filename));
    
    // Log the deletion attempt
    error_log("deleteReport: Attempting to delete file: $filename");
    
    // Define all possible report paths to check
    $reportPaths = [
        '../uploads/reports/' . $filename,
        '../../uploads/reports/' . $filename,
        dirname(__FILE__) . '/../uploads/reports/' . $filename,
        dirname(dirname(dirname(__FILE__))) . '/uploads/reports/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/uploads/reports/' . $filename
    ];
    
    $foundFile = false;
    $allSuccess = true;
    $failedPaths = [];
    
    // Try to delete from all possible locations
    foreach ($reportPaths as $path) {
        error_log("deleteReport: Checking path: $path");
        if (file_exists($path)) {
            $foundFile = true;
            error_log("deleteReport: File found at: $path");
            
            // Check if the file is writable before attempting to delete
            if (!is_writable($path)) {
                error_log("deleteReport: File is not writable: $path");
                $allSuccess = false;
                $failedPaths[] = $path;
                continue;
            }
            
            if (!unlink($path)) {
                error_log("deleteReport: Failed to delete file: $path");
                $allSuccess = false;
                $failedPaths[] = $path;
            } else {
                error_log("deleteReport: Successfully deleted file: $path");
            }
        }
    }
    
    // Send response
    if (!$foundFile) {
        error_log("deleteReport: File not found in any location: $filename");
        sendResponse(false, 'Report file not found');
    } else if ($allSuccess) {
        error_log("deleteReport: File deleted successfully from all locations");
        sendResponse(true, 'Report file deleted successfully');
    } else {
        error_log("deleteReport: Failed to delete file from some locations: " . implode(", ", $failedPaths));
        sendResponse(false, 'Failed to delete report file from all locations. Please try again.');
    }
}

function checkWorkDaysAndGeneratePayslip($userId) {
    global $conn;
    
    // Validate user - fix query to handle potential column name variations
    $userQuery = "SELECT * FROM users WHERE userId = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        sendResponse(false, 'Invalid user');
        return;
    }
    
    $user = $userResult->fetch_assoc();
    
    // Check if user is an employee by checking userRole field
    if (!isset($user['userRole']) || $user['userRole'] !== 'user') {
        sendResponse(false, 'User is not an employee');
        return;
    }
    
    $employeeName = $user['firstName'] . ' ' . $user['lastName'];
    
    // Get current month and year
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    // Count work days for the current month
    $workDaysQuery = "SELECT COUNT(*) as workDays 
                      FROM attendance 
                      WHERE userId = ? 
                      AND MONTH(date) = ? 
                      AND YEAR(date) = ? 
                      AND status = 'Present'";
    
    $workDaysStmt = $conn->prepare($workDaysQuery);
    $workDaysStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
    $workDaysStmt->execute();
    $workDaysResult = $workDaysStmt->get_result();
    $workDaysRow = $workDaysResult->fetch_assoc();
    $workDays = $workDaysRow['workDays'];
    
    // Response data
    $responseData = [
        'userId' => $userId,
        'employeeName' => $employeeName,
        'currentMonth' => $currentMonth,
        'currentYear' => $currentYear,
        'workDays' => $workDays,
        'requiredDays' => 1, // Lowered from 22 for testing
        'payslipGenerated' => false,
        'payslipData' => null
    ];
    
    // Check if employee has reached the threshold (1 day for testing)
    if ($workDays < 1) {
        sendResponse(true, 'Employee has not reached required work days yet', $responseData);
        return;
    }
    
    // Check if payslip already exists for this month
    $checkPayslipQuery = "SELECT p.* 
                          FROM payslip p 
                          JOIN salary s ON p.salaryId = s.salaryId 
                          WHERE p.userId = ? 
                          AND s.month = ? 
                          AND s.year = ?";
    
    $checkStmt = $conn->prepare($checkPayslipQuery);
    $checkStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    // If payslip already exists, return it
    if ($checkResult->num_rows > 0) {
        $payslip = $checkResult->fetch_assoc();
        $responseData['payslipGenerated'] = true;
        $responseData['payslipData'] = [
            'payslipId' => $payslip['payslipId'],
            'pdfPath' => $payslip['pdfPath'],
            'pdfUrl' => getApiBaseUrl() . '?action=download&payslipId=' . $payslip['payslipId'],
            'status' => $payslip['status'],
            'createdAt' => $payslip['createdAt']
        ];
        
        sendResponse(true, 'Payslip has already been generated', $responseData);
        return;
    }
    
    // Check if salary record exists
    $salaryQuery = "SELECT * FROM salary 
                    WHERE userId = ? AND month = ? AND year = ?";
    
    $salaryStmt = $conn->prepare($salaryQuery);
    $salaryStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
    $salaryStmt->execute();
    $salaryResult = $salaryStmt->get_result();
    
    if ($salaryResult->num_rows === 0) {
        // Create a salary record since one doesn't exist
        $salaryId = createSalaryRecord($userId, $currentMonth, $currentYear);
        if (!$salaryId) {
            sendResponse(false, 'Failed to create salary record for the current month', $responseData);
            return;
        }
        
        // Retrieve the newly created salary record
        $salaryStmt = $conn->prepare($salaryQuery);
        $salaryStmt->bind_param("iii", $userId, $currentMonth, $currentYear);
        $salaryStmt->execute();
        $salaryResult = $salaryStmt->get_result();
        
        if ($salaryResult->num_rows === 0) {
            sendResponse(false, 'Failed to retrieve newly created salary record', $responseData);
            return;
        }
    }
    
    $salary = $salaryResult->fetch_assoc();
    $salaryId = $salary['salaryId'];
    
    // Now we have verified everything, generate the payslip
    
    // Define all possible payslip directories we could write to
    $possibleDirs = [
        '../uploads/payslips',
        dirname(__FILE__) . '/../uploads/payslips',
        $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/uploads/payslips'
    ];
    
    $dir = null;
    foreach ($possibleDirs as $possibleDir) {
        if (!file_exists($possibleDir)) {
            if (mkdir($possibleDir, 0777, true)) {
                $dir = $possibleDir;
                break;
            }
        } else if (is_writable($possibleDir)) {
            $dir = $possibleDir;
            break;
        }
    }
    
    if (!$dir) {
        sendResponse(false, 'Unable to write to payslips directory', $responseData);
        return;
    }
    
    // Generate payslip PDF
    $pdfPath = generatePayslipPDF($salary);
    
    if (!$pdfPath) {
        sendResponse(false, 'Failed to create payslip file', $responseData);
        return;
    }
    
    // Insert payslip record
    $insertQuery = "INSERT INTO payslip (userId, salaryId, pdfPath, status)
                    VALUES (?, ?, ?, 'Generated')";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("iis", $userId, $salaryId, $pdfPath);
    $insertSuccess = $insertStmt->execute();
    
    if ($insertSuccess) {
        $payslipId = $insertStmt->insert_id;
        $responseData['payslipGenerated'] = true;
        $responseData['payslipData'] = [
            'payslipId' => $payslipId,
            'pdfPath' => $pdfPath,
            'pdfUrl' => getApiBaseUrl() . '?action=download&payslipId=' . $payslipId,
            'status' => 'Generated',
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        sendResponse(true, 'Payslip generated successfully', $responseData);
    } else {
        sendResponse(false, 'Failed to generate payslip record: ' . $conn->error, $responseData);
    }
}

function createSalaryRecord($userId, $month, $year) {
    global $conn;
    
    // For simplicity, we'll use a default basic salary of 20000
    $basicSalary = 20000;
    $overtime = 0;
    $bonus = 0; // Added bonus as it's in the table
    $deductions = 0;
    $tax = 0; // Added tax as it's in the table
    $totalSalary = $basicSalary + $overtime + $bonus - $deductions - $tax;
    
    $query = "INSERT INTO salary (userId, month, year, basicSalary, overtime, bonus, deductions, tax, totalSalary) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Failed to prepare salary record creation query: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iiiddddd", $userId, $month, $year, $basicSalary, $overtime, $bonus, $deductions, $tax, $totalSalary);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    } else {
        error_log("Failed to create salary record: " . $stmt->error);
        return false;
    }
}
?> 