<?php
// This file serves as a secure proxy to get payslip files
session_start();
// Clear any previous output that might interfere with headers
ob_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication if needed
if (file_exists("../../auth/authentication.php")) {
    include("../../auth/authentication.php");
}

// Log access for debugging
error_log("viewPayslip.php accessed at: " . date('Y-m-d H:i:s'));

// Check if file parameter is set
if (!isset($_GET['file'])) {
    error_log("viewPayslip.php: Missing file parameter");
    header('HTTP/1.1 400 Bad Request');
    echo 'Error: Missing file parameter';
    exit;
}

// Get and sanitize file path
$requestedFile = $_GET['file'];
$originalPath = $requestedFile;

// Remove any directory traversal attempts
$requestedFile = str_replace('..', '', $requestedFile);
$fileName = basename($requestedFile);

error_log("viewPayslip.php: Requested file: " . $originalPath . " (sanitized to: " . $requestedFile . ")");

// Define all possible locations where the file might be stored
$possiblePaths = [];

// Standard paths relative to this file - direct path check
$possiblePaths[] = dirname(__FILE__) . '/../../' . $requestedFile;

// Extract user ID and salary ID if the filename matches any known pattern
$fileUserId = null;
$salaryId = null;
$fileExt = null;

// Try to extract information from filename
if (preg_match('/^(?:payslip_)?(\d+)_(\d+)(?:_\d+)?\.(?:pdf|txt)$/', $fileName, $matches)) {
    $fileUserId = $matches[1];
    $salaryId = $matches[2];
    
    // Get file extension
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    
    // Use glob to find all files matching the pattern in various locations
    $baseDirs = [
        dirname(__FILE__) . '/../../uploads/payslips/',
        dirname(dirname(dirname(__FILE__))) . '/uploads/payslips/',
        rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . '/uploads/payslips/',
    ];
    
    foreach ($baseDirs as $dir) {
        if (file_exists($dir)) {
            // Try with and without payslip_ prefix
            $patterns = [
                $fileUserId . '_' . $salaryId . '*.' . $fileExt,
                'payslip_' . $fileUserId . '_' . $salaryId . '*.' . $fileExt
            ];
            
            foreach ($patterns as $pattern) {
                $matches = glob($dir . $pattern);
                if (!empty($matches)) {
                    foreach ($matches as $match) {
                        $possiblePaths[] = $match;
                    }
                }
            }
        }
    }
}

// Add the filename directly to various paths as a fallback
$possiblePaths[] = dirname(__FILE__) . '/../../uploads/payslips/' . $fileName;
$possiblePaths[] = dirname(dirname(dirname(__FILE__))) . '/uploads/payslips/' . $fileName;
$possiblePaths[] = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . '/uploads/payslips/' . $fileName;

// Search for the file
$filePath = null;
foreach ($possiblePaths as $path) {
    error_log("viewPayslip.php: Checking path: $path");
    if (file_exists($path)) {
        $filePath = $path;
        error_log("viewPayslip.php: File found at: $path");
        break;
    }
}

// If file not found, provide detailed error
if (!$filePath) {
    error_log("viewPayslip.php: File not found in any location: $fileName");
    header('HTTP/1.1 404 Not Found');
    echo "<html><head><title>Error</title></head><body>";
    echo "<h3>Payslip File Not Found</h3>";
    echo "<p>The requested file '$fileName' could not be found.</p>";
    echo "<p>We checked the following locations:</p><ul>";
    
    foreach ($possiblePaths as $path) {
        echo "<li>" . htmlspecialchars($path) . "</li>";
    }
    
    echo "</ul>";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
    echo "</body></html>";
    exit;
}

// Get the file extension
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
error_log("viewPayslip.php: File extension: $extension");

// Set content type based on extension
switch ($extension) {
    case 'pdf':
        $contentType = 'application/pdf';
        break;
    case 'txt':
        $contentType = 'text/plain';
        break;
    default:
        error_log("viewPayslip.php: Unsupported file type: $extension");
        header('HTTP/1.1 415 Unsupported Media Type');
        echo "Error: Unsupported file type: $extension";
        exit;
}

// Make sure we don't have any output before headers
if (ob_get_length()) ob_clean();

// Output the file - ensure proper headers and disable caching
error_log("viewPayslip.php: Serving file: $filePath with content type: $contentType");

// Disable caching completely
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Past date

// Set content type and disposition
header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));

// Output file content
readfile($filePath);
exit;
?> 