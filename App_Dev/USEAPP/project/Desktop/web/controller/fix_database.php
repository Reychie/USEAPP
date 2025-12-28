<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials - hardcoded for this script for reliability
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "it322";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Fix Utility</h1>";

// Check if daysWorked column exists in salary table
$result = $conn->query("SHOW COLUMNS FROM salary LIKE 'daysWorked'");
$columnExists = ($result && $result->num_rows > 0);

echo "<h2>Salary Table Structure</h2>";
if ($columnExists) {
    echo "<p>The 'daysWorked' column already exists in the salary table.</p>";
} else {
    echo "<p>The 'daysWorked' column does not exist. Adding it now...</p>";
    
    // Add the column
    if ($conn->query("ALTER TABLE salary ADD COLUMN daysWorked INT DEFAULT 0 AFTER totalSalary")) {
        echo "<p style='color:green;'>Successfully added 'daysWorked' column!</p>";
    } else {
        echo "<p style='color:red;'>Failed to add column: " . $conn->error . "</p>";
    }
}

// Fix payslip file paths
echo "<h2>Payslip Path Check</h2>";

// Get all payslip records
$result = $conn->query("SELECT payslipId, userId, salaryId, pdfPath FROM payslip");

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " payslip records to check.</p>";
    
    echo "<table border='1'>";
    echo "<tr><th>Payslip ID</th><th>User ID</th><th>Salary ID</th><th>Current Path</th><th>File Exists</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $payslipId = $row['payslipId'];
        $userId = $row['userId'];
        $salaryId = $row['salaryId'];
        $currentPath = $row['pdfPath'];
        
        // Check multiple locations
        $locations = [
            dirname(__FILE__) . '/../' . $currentPath,
            dirname(__FILE__) . '/../uploads/payslips/' . basename($currentPath),
            dirname(__FILE__) . '/../../uploads/payslips/' . basename($currentPath)
        ];
        
        $fileExists = false;
        $validPath = '';
        foreach ($locations as $path) {
            if (file_exists($path)) {
                $fileExists = true;
                $validPath = $path;
                break;
            }
        }
        
        echo "<tr>";
        echo "<td>$payslipId</td>";
        echo "<td>$userId</td>";
        echo "<td>$salaryId</td>";
        echo "<td>$currentPath</td>";
        
        if ($fileExists) {
            echo "<td style='color:green;'>Found at: " . basename($validPath) . "</td>";
            echo "<td>No action needed</td>";
        } else {
            echo "<td style='color:red;'>Not found</td>";
            
            // Try to fix the path
            $correctPath = 'uploads/payslips/' . $userId . '_' . $salaryId . '.pdf';
            
            // Update the record with correct path
            $updateStmt = $conn->prepare("UPDATE payslip SET pdfPath = ? WHERE payslipId = ?");
            $updateStmt->bind_param("si", $correctPath, $payslipId);
            
            if ($updateStmt->execute()) {
                echo "<td style='color:blue;'>Path updated to: $correctPath</td>";
            } else {
                echo "<td style='color:red;'>Failed to update path: " . $conn->error . "</td>";
            }
            
            $updateStmt->close();
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No payslip records found or error querying table.</p>";
}

// Ensure uploads directories exist
echo "<h2>Creating Uploads Directories</h2>";

$directories = [
    dirname(__FILE__) . '/../uploads',
    dirname(__FILE__) . '/../uploads/payslips',
    dirname(__FILE__) . '/../../uploads/payslips'
];

foreach ($directories as $dir) {
    echo "<p>Checking directory: " . $dir . " - ";
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<span style='color:green;'>Created successfully</span></p>";
        } else {
            echo "<span style='color:red;'>Failed to create</span></p>";
        }
    } else {
        echo "<span style='color:blue;'>Already exists</span></p>";
    }
}

$conn->close();
echo "<p>Database fix utility completed.</p>";
?> 