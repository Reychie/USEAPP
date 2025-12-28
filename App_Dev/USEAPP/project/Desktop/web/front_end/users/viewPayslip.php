<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication
include("../../auth/authentication_for_user.php");

// Get current user's ID
$userId = $_SESSION['authUser']['userId'];

// Clear any previous output that might interfere with headers
if (ob_get_level()) ob_clean();

// Log access for debugging
error_log("Employee viewPayslip.php accessed at: " . date('Y-m-d H:i:s') . " by user ID: $userId");

// Check if file parameter exists
if (!isset($_GET['file']) || empty($_GET['file'])) {
    error_log("Employee viewPayslip.php: Missing file parameter");
    header("HTTP/1.0 404 Not Found");
    echo "Error: No file specified";
    exit;
}

// Get and sanitize file path
$requestedFile = $_GET['file'];
$originalPath = $requestedFile;

// Remove any directory traversal attempts
$requestedFile = str_replace('..', '', $requestedFile);
$fileName = basename($requestedFile);

error_log("Employee viewPayslip.php: Requested file: " . $originalPath . " (sanitized to: " . $requestedFile . ")");

// Check for different file path formats
// Format 1: userId_salaryId.ext (standard format)
// Format 2: uploads/payslips/userId_salaryId.ext (relative format)
// Format 3: payslip_userId_salaryId.ext (legacy format)

// Extract user ID from the filename
$fileUserId = null;
$fileExt = null;
$salaryId = null;

// Try timestamp format: userId_salaryId_timestamp.ext (new format)
if (preg_match('/^(\d+)_(\d+)_\d+\.(pdf|txt)$/', $fileName, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    $fileExt = $matches[3];
}
// Try standard format: userId_salaryId.ext (old format)
else if (preg_match('/^(\d+)_(\d+)\.(pdf|txt)$/', $fileName, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    $fileExt = $matches[3];
}
// Try full path format with timestamp: path/to/userId_salaryId_timestamp.ext
else if (preg_match('/payslips\/(\d+)_(\d+)_\d+\.(pdf|txt)$/', $requestedFile, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    $fileExt = $matches[3];
}
// Try full path format: path/to/userId_salaryId.ext
else if (preg_match('/payslips\/(\d+)_(\d+)\.(pdf|txt)$/', $requestedFile, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    $fileExt = $matches[3];
}
// Try legacy format with timestamp: payslip_userId_salaryId_timestamp.ext
else if (preg_match('/payslip_(\d+)_(\d+)_\d+\.(pdf|txt)$/', $fileName, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    $fileExt = $matches[3];
}
// Try legacy format: payslip_userId_salaryId.ext
else if (preg_match('/payslip_(\d+)_(\d+)\.(pdf|txt)$/', $fileName, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    $fileExt = $matches[3];
}

if (!$fileUserId || !$salaryId) {
    error_log("Employee viewPayslip.php: Invalid file format: $fileName");
    header("HTTP/1.0 400 Bad Request");
    echo "Invalid file format";
    exit;
}

// Security check: Ensure the user can only access their own files
if ($fileUserId != $userId && !isset($_SESSION['authUser']['isAdmin'])) {
    error_log("Employee viewPayslip.php: Access denied for user $userId trying to access file for user $fileUserId");
    header("HTTP/1.0 403 Forbidden");
    echo "Access denied: You don't have permission to access this file";
    exit;
}

// Standardize the filename to help with searching - don't append extension here
$standardFilePattern = $fileUserId . "_" . $salaryId;

// Define all possible locations where the file might be stored
$possiblePaths = [];

// First try to find any file matching the userId_salaryId pattern regardless of timestamp
$payslipsDir = dirname(__FILE__) . '/../../uploads/payslips/';
if (file_exists($payslipsDir)) {
    $files = glob($payslipsDir . $standardFilePattern . "*." . ($fileExt ?: '*'));
    foreach ($files as $match) {
        $possiblePaths[] = $match;
    }
}

// Also try the project root payslips directory
$rootPayslipsDir = $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/uploads/payslips/';
if (file_exists($rootPayslipsDir)) {
    $files = glob($rootPayslipsDir . $standardFilePattern . "*." . ($fileExt ?: '*'));
    foreach ($files as $match) {
        $possiblePaths[] = $match;
    }
}

// Also try with payslip_ prefix
$files = glob($payslipsDir . "payslip_" . $standardFilePattern . "*." . ($fileExt ?: '*'));
foreach ($files as $match) {
    $possiblePaths[] = $match;
}

// Add the requested file path as a direct check
if (!empty($requestedFile)) {
    $possiblePaths[] = dirname(__FILE__) . '/../../' . $requestedFile;
    $possiblePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/' . $requestedFile;
}

// Add more fallback paths for direct checking
$possiblePaths[] = dirname(__FILE__) . '/../../uploads/payslips/' . $fileName;
$possiblePaths[] = dirname(dirname(dirname(__FILE__))) . '/uploads/payslips/' . $fileName;
$possiblePaths[] = dirname(__FILE__) . '/../../../uploads/payslips/' . $fileName;
$possiblePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/Desktop/AR_Attendance/uploads/payslips/' . $fileName;
$possiblePaths[] = $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/uploads/payslips/' . $fileName;

// Search for the file
$filePath = null;
error_log("Employee viewPayslip.php: Searching for file: $standardFilePattern");
foreach ($possiblePaths as $path) {
    error_log("Employee viewPayslip.php: Checking path: $path");
    if (file_exists($path)) {
        $filePath = $path;
        error_log("Employee viewPayslip.php: File found at: $path");
        break;
    }
}

// If file not found, automatically generate it
if (!$filePath) {
    error_log("Employee viewPayslip.php: File not found in any location, automatically generating payslip for userId=$userId, salaryId=$salaryId");
    
    // Include generator code
    include("../../controller/payslipGenerator.php");
    
    // Generate payslip
    $result = generatePayslip($userId, $salaryId);
    
    if ($result['success']) {
        // Get the new path and check if it exists
        $newFilePath = dirname(__FILE__) . '/../../' . $result['filePath'];
        
        error_log("Employee viewPayslip.php: Regenerated payslip, checking path: $newFilePath");
        
        if (file_exists($newFilePath)) {
            $filePath = $newFilePath;
            error_log("Employee viewPayslip.php: Successfully regenerated payslip at: $filePath");
        } else {
            // Try to find the file in other common locations - use glob to find any files matching the pattern
            $altDirs = [
                dirname(__FILE__) . '/../uploads/payslips/',
                dirname(__FILE__) . '/../../uploads/payslips/',
                $_SERVER['DOCUMENT_ROOT'] . '/app_dev_last/uploads/payslips/'
            ];
            
            foreach ($altDirs as $dir) {
                if (file_exists($dir)) {
                    $pattern = $dir . $userId . '_' . $salaryId . '*.' . ($fileExt ?: '*');
                    $matches = glob($pattern);
                    
                    if (!empty($matches)) {
                        $filePath = $matches[0]; // Use the first match
                        error_log("Employee viewPayslip.php: Found at alternative location: $filePath");
                        break;
                    }
                    
                    // Also try with payslip_ prefix
                    $pattern = $dir . 'payslip_' . $userId . '_' . $salaryId . '*.' . ($fileExt ?: '*');
                    $matches = glob($pattern);
                    
                    if (!empty($matches)) {
                        $filePath = $matches[0]; // Use the first match
                        error_log("Employee viewPayslip.php: Found with payslip_ prefix at: $filePath");
                        break;
                    }
                }
            }
            
            if (!$filePath) {
                error_log("Employee viewPayslip.php: Regenerated payslip not found in any location");
            }
        }
    } else {
        error_log("Employee viewPayslip.php: Failed to regenerate payslip: " . $result['error']);
    }
}

// If still not found after regeneration attempt, show error
if (!$filePath) {
    header('HTTP/1.1 500 Internal Server Error');
    echo "<html><head><title>Error</title></head><body>";
    echo "<h3>Payslip Generation Failed</h3>";
    echo "<p>We couldn't generate your payslip at this time. Please try again later.</p>";
    echo "<p><a href='javascript:history.back()' class='btn btn-secondary'>Go Back</a></p>";
    echo "</body></html>";
    exit;
}

// Get the file extension
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
error_log("Employee viewPayslip.php: File extension: $extension");

// Set content type based on extension
switch ($extension) {
    case 'pdf':
        $contentType = 'application/pdf';
        break;
    case 'txt':
        $contentType = 'text/plain';
        break;
    default:
        error_log("Employee viewPayslip.php: Unsupported file type: $extension");
        header('HTTP/1.1 415 Unsupported Media Type');
        echo "Error: Unsupported file type: $extension";
        exit;
}

// Make sure we don't have any output before headers
if (ob_get_length()) ob_clean();

// Disable caching completely
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Past date

// Set content type and disposition
header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="payslip_' . $fileUserId . '_' . date('Y-m-d') . '.' . $extension . '"');
header('Content-Length: ' . filesize($filePath));

// Output file content
error_log("Employee viewPayslip.php: Successfully serving file: $filePath");
readfile($filePath);
exit;
?> 