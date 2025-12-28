<?php
session_start();
include("../dB/config.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log access
error_log("payslipGenerator.php accessed at: " . date('Y-m-d H:i:s'));

// Check if request is from API
$isApi = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';

// Handle API request
if ($isApi) {
    header('Content-Type: application/json');
    
    // Get data from request - check both JSON and form data
    $postData = file_get_contents('php://input');
    $jsonData = json_decode($postData, true);
    
    // Log the incoming data
    error_log("payslipGenerator.php raw data: $postData");
    
    // Get userId and salaryId from either POST, JSON, or GET parameters
    $userId = isset($_POST['userId']) ? $_POST['userId'] : 
             (isset($jsonData['userId']) ? $jsonData['userId'] : 
             (isset($_GET['userId']) ? $_GET['userId'] : null));
             
    $salaryId = isset($_POST['salaryId']) ? $_POST['salaryId'] : 
               (isset($jsonData['salaryId']) ? $jsonData['salaryId'] : 
               (isset($_GET['salaryId']) ? $_GET['salaryId'] : null));
    
    error_log("payslipGenerator.php: userId=$userId, salaryId=$salaryId");
    
    if (!$userId) {
        error_log("payslipGenerator.php: Missing userId parameter");
        echo json_encode([
            'status' => 'error', 
            'message' => 'User ID is required'
        ]);
        exit;
    }
    
    if (!$salaryId) {
        error_log("payslipGenerator.php: Missing salaryId parameter");
        echo json_encode([
            'status' => 'error', 
            'message' => 'Salary ID is required'
        ]);
        exit;
    }
    
    // Generate payslip
    $result = generatePayslip($userId, $salaryId);
    
    if ($result['success']) {
        error_log("payslipGenerator.php: Successfully generated payslip at " . $result['filePath']);
        echo json_encode([
            'status' => 'success',
            'message' => 'Payslip generated successfully',
            'pdfUrl' => $result['filePath']
        ]);
    } else {
        error_log("payslipGenerator.php: Failed to generate payslip - " . $result['error']);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to generate payslip: ' . $result['error']
        ]);
    }
    exit;
}

function generatePayslip($userId, $salaryId) {
    global $conn;
    
    try {
        error_log("generatePayslip: Starting generation for userId=$userId, salaryId=$salaryId");
        
        // Make sure userId and salaryId are numeric
        if (!is_numeric($userId) || !is_numeric($salaryId)) {
            return ['success' => false, 'error' => 'Invalid user ID or salary ID'];
        }
        
        // Get salary details
        $query = "SELECT s.*, u.firstName, u.lastName 
                  FROM salary s 
                  JOIN users u ON s.userId = u.userId 
                  WHERE s.salaryId = ? AND s.userId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $salaryId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("generatePayslip: Salary record not found for userId=$userId, salaryId=$salaryId");
            return ['success' => false, 'error' => 'Salary record not found'];
        }
        
        $salary = $result->fetch_assoc();
        
        // Get work days information
        $workDaysQuery = "SELECT COUNT(*) as present_days FROM attendance 
                          WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
        $workDaysStmt = $conn->prepare($workDaysQuery);
        $workDaysStmt->bind_param("iii", $userId, $salary['month'], $salary['year']);
        $workDaysStmt->execute();
        $workDaysResult = $workDaysStmt->get_result()->fetch_assoc();
        $workDays = $workDaysResult['present_days'];
        $requiredDays = 22;
        
        // Check if payslip already exists in database
        $checkQuery = "SELECT * FROM payslip WHERE userId = ? AND salaryId = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ii", $userId, $salaryId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        // Determine the standard filename - now using PDF extension
        $standardFileName = $userId . '_' . $salaryId . '.pdf';
        
        // Check for existing payslip entry
        $existingPath = null;
        if ($checkResult->num_rows > 0) {
            $payslip = $checkResult->fetch_assoc();
            $existingPath = $payslip['pdfPath'];
            error_log("generatePayslip: Found existing payslip record with path: " . $existingPath);
            
            // Since we're going to overwrite, let's continue with generation
        }
        
        // Define all possible payslip directories we could write to
        $possibleDirs = [
            '../uploads/payslips',
            dirname(__FILE__) . '/../uploads/payslips',
            $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/uploads/payslips'
        ];
        
        // Find a writable directory
        $uploadDir = null;
        foreach ($possibleDirs as $dir) {
            if (!file_exists($dir)) {
                error_log("generatePayslip: Directory doesn't exist, trying to create: $dir");
                if (@mkdir($dir, 0755, true)) {
                    $uploadDir = $dir;
                    error_log("generatePayslip: Successfully created directory: $dir");
                    break;
                }
            } else if (is_writable($dir)) {
                $uploadDir = $dir;
                error_log("generatePayslip: Found writable directory: $dir");
                break;
            }
        }
        
        if (!$uploadDir) {
            error_log("generatePayslip: Failed to find or create a writable directory");
            return ['success' => false, 'error' => 'Unable to write to payslips directory'];
        }
        
        // Full path to save the PDF
        $filePath = $uploadDir . '/' . $standardFileName;
        
        // Try to load FPDF
        $fpdfPath = dirname(__FILE__) . '/../controller/fpdf/fpdf.php';
        if (!file_exists($fpdfPath)) {
            $fpdfPath = dirname(__FILE__) . '/fpdf/fpdf.php';
        }
        
        if (file_exists($fpdfPath)) {
            require_once($fpdfPath);
            
            // Create PDF
            try {
                $pdf = new FPDF();
                $pdf->AddPage();
                
                // Set document title
                $pdf->SetTitle('Payslip - ' . $salary['firstName'] . ' ' . $salary['lastName']);
                
                // Add header
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(0, 10, 'PAYSLIP', 0, 1, 'C');
                
                // Add employee info
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Employee: ' . $salary['firstName'] . ' ' . $salary['lastName'], 0, 1);
                $pdf->Cell(0, 10, 'Period: ' . date('F Y', mktime(0, 0, 0, $salary['month'], 1, $salary['year'])), 0, 1);
                
                // Add work days information
                $pdf->SetFont('Arial', '', 11);
                $pdf->Cell(0, 10, 'Work Days: ' . $workDays . ' / ' . $requiredDays . ' required days', 0, 1);
                
                if ($workDays < $requiredDays) {
                    $pdf->SetTextColor(255, 0, 0); // Red color for warning
                    $pdf->Cell(0, 10, 'NOTICE: Payslip shows zero values because employee has not completed', 0, 1);
                    $pdf->Cell(0, 5, 'the required ' . $requiredDays . ' work days.', 0, 1);
                    $pdf->SetTextColor(0, 0, 0); // Reset to black
                    $pdf->Ln(5);
                    
                    // Salary details (zero values)
                    $pdf->Cell(100, 8, 'Basic Salary:', 0);
                    $pdf->Cell(0, 8, '₱0.00', 0, 1);
                    $pdf->Cell(100, 8, 'Overtime:', 0);
                    $pdf->Cell(0, 8, '₱0.00', 0, 1);
                    $pdf->Cell(100, 8, 'Deductions:', 0);
                    $pdf->Cell(0, 8, '₱0.00', 0, 1);
                    
                    // Add tax if available
                    if (isset($salary['tax'])) {
                        $pdf->Cell(100, 8, 'Tax:', 0);
                        $pdf->Cell(0, 8, '₱0.00', 0, 1);
                    }
                    
                    // Draw a line
                    $pdf->Line(10, $pdf->GetY() + 4, 200, $pdf->GetY() + 4);
                    $pdf->Ln(8);
                    
                    // Net pay
                    $pdf->SetFont('Arial', 'B', 11);
                    $pdf->Cell(100, 8, 'Net Pay:', 0);
                    $pdf->Cell(0, 8, '₱0.00', 0, 1);
                } else {
                    $pdf->Ln(5);
                    
                    // Salary details
                    $pdf->Cell(100, 8, 'Basic Salary:', 0);
                    $pdf->Cell(0, 8, '₱' . number_format($salary['basicSalary'], 2), 0, 1);
                    $pdf->Cell(100, 8, 'Overtime:', 0);
                    $pdf->Cell(0, 8, '₱' . number_format($salary['overtime'], 2), 0, 1);
                    $pdf->Cell(100, 8, 'Deductions:', 0);
                    $pdf->Cell(0, 8, '₱' . number_format($salary['deductions'], 2), 0, 1);
                    
                    // Add tax if available
                    if (isset($salary['tax'])) {
                        $pdf->Cell(100, 8, 'Tax:', 0);
                        $pdf->Cell(0, 8, '₱' . number_format($salary['tax'], 2), 0, 1);
                    }
                    
                    // Draw a line
                    $pdf->Line(10, $pdf->GetY() + 4, 200, $pdf->GetY() + 4);
                    $pdf->Ln(8);
                    
                    // Net pay
                    $pdf->SetFont('Arial', 'B', 11);
                    $pdf->Cell(100, 8, 'Net Pay:', 0);
                    $pdf->Cell(0, 8, '₱' . number_format($salary['totalSalary'], 2), 0, 1);
                }
                
                // Add footer
                $pdf->Ln(15);
                $pdf->SetFont('Arial', 'I', 8);
                $pdf->Cell(0, 5, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
                $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
                
                // Output PDF to file
                $success = $pdf->Output('F', $filePath);
                error_log("generatePayslip: PDF generated successfully at: $filePath");
            } catch (Exception $e) {
                error_log("generatePayslip: PDF generation error: " . $e->getMessage());
                return ['success' => false, 'error' => 'Failed to generate PDF: ' . $e->getMessage()];
            }
        } else {
            // Fallback to text file if FPDF is not available
            error_log("generatePayslip: FPDF not found, falling back to text file");
            
            // Generate text file (works on all platforms)
            $content = "PAYSLIP\n\n";
            $content .= "Employee: " . $salary['firstName'] . " " . $salary['lastName'] . "\n";
            $content .= "Period: " . date('F Y', mktime(0, 0, 0, $salary['month'], 1, $salary['year'])) . "\n\n";
            
            // Add work days information
            $content .= "Work Days: " . $workDays . " / " . $requiredDays . " required days\n\n";
            
            if ($workDays < $requiredDays) {
                $content .= "NOTICE: Payslip shows zero values because employee has not completed the required " . $requiredDays . " work days.\n\n";
                $content .= "Basic Salary: ₱0.00\n";
                $content .= "Overtime: ₱0.00\n";
                $content .= "Deductions: ₱0.00\n";
                if (isset($salary['tax'])) {
                    $content .= "Tax: ₱0.00\n";
                }
                $content .= "----------------------------\n";
                $content .= "Net Pay: ₱0.00\n\n";
            } else {
                $content .= "Basic Salary: ₱" . number_format($salary['basicSalary'], 2) . "\n";
                $content .= "Overtime: ₱" . number_format($salary['overtime'], 2) . "\n";
                $content .= "Deductions: ₱" . number_format($salary['deductions'], 2) . "\n";
                if (isset($salary['tax'])) {
                    $content .= "Tax: ₱" . number_format($salary['tax'], 2) . "\n";
                }
                $content .= "----------------------------\n";
                $content .= "Net Pay: ₱" . number_format($salary['totalSalary'], 2) . "\n\n";
            }
            
            $content .= "Generated on: " . date('Y-m-d H:i:s');
            
            // Using .txt extension for fallback
            $standardFileName = $userId . '_' . $salaryId . '.txt';
            $filePath = $uploadDir . '/' . $standardFileName;
            
            error_log("generatePayslip: Attempting to write text file to: $filePath");
            $success = file_put_contents($filePath, $content);
            
            if (!$success) {
                error_log("generatePayslip: Failed to create text file");
                return ['success' => false, 'error' => 'Failed to create payslip file'];
            }
        }
        
        // Use consistent relative path format for database storage
        $relativePath = 'uploads/payslips/' . basename($filePath);
        
        // Update or insert payslip record
        if ($existingPath) {
            $updateQuery = "UPDATE payslip SET pdfPath = ?, status = 'Paid', updatedAt = NOW() 
                            WHERE userId = ? AND salaryId = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("sii", $relativePath, $userId, $salaryId);
            
            if (!$updateStmt->execute()) {
                error_log("generatePayslip: Failed to update database - " . $conn->error);
            } else {
                error_log("generatePayslip: Updated existing payslip record");
            }
        } else {
            $insertQuery = "INSERT INTO payslip (userId, salaryId, pdfPath, status, createdAt) 
                            VALUES (?, ?, ?, 'Paid', NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iis", $userId, $salaryId, $relativePath);
            
            if (!$insertStmt->execute()) {
                error_log("generatePayslip: Failed to insert database record - " . $conn->error);
            } else {
                error_log("generatePayslip: Created new payslip record");
            }
        }
        
        error_log("generatePayslip: Successfully generated payslip, stored in DB with path: $relativePath");
        return ['success' => true, 'filePath' => $relativePath];
    } catch (Exception $e) {
        error_log("generatePayslip Exception: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
} 