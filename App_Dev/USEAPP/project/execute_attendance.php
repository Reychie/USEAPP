<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'Desktop/AR_Attendance/dB/config.php';

echo "Connecting to database...\n";

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connection successful!\n\n";

// Get SQL statements from the generate_attendance.php script
echo "Generating SQL statements...\n";
$sql_statements = [];
ob_start();
include 'generate_attendance.php';
$output = ob_get_clean();

// Parse the output to extract SQL statements
$lines = explode("\n", $output);
foreach ($lines as $line) {
    // Skip comments and empty lines
    if (empty($line) || strpos($line, '--') === 0) {
        continue;
    }
    
    // If it's an INSERT statement, add it to our array
    if (strpos($line, 'INSERT INTO') !== false) {
        $sql_statements[] = $line;
    }
}

echo "Found " . count($sql_statements) . " SQL statements to execute.\n\n";

// Find the highest attendance ID in the database
$maxIdQuery = "SELECT MAX(attendanceId) as max_id FROM attendance";
$result = $conn->query($maxIdQuery);
$maxId = 0;
if ($result && $row = $result->fetch_assoc()) {
    $maxId = (int)$row['max_id'];
    echo "Current highest attendance ID in database: " . $maxId . "\n\n";
}

// Execute each SQL statement
$success_count = 0;
$error_count = 0;
$errors = [];
$inserted_ids = [];

try {
    foreach ($sql_statements as $index => $sql) {
        echo "Executing statement " . ($index + 1) . "... ";
        
        // Extract the values from the SQL statement
        if (preg_match("/VALUES \('(\d+)', '(\d+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)', '([^']+)', ([^)]+)\)/", $sql, $matches)) {
            $userId = $matches[2];
            $date = $matches[3];
            $timeIn = $matches[4];
            $timeOut = $matches[5];
            $status = $matches[6];
            $location = $matches[7];
            $reason = $matches[8];
            
            // Increment attendance ID to avoid duplicates
            $newAttendanceId = $maxId + 1;
            $maxId = $newAttendanceId;
            
            // Create new SQL with updated ID
            $newSql = "INSERT INTO `attendance` (`attendanceId`, `userId`, `date`, `timeIn`, `timeOut`, `status`, `location`, `reason`) VALUES ";
            $newSql .= "('$newAttendanceId', '$userId', '$date', '$timeIn', '$timeOut', '$status', '$location', $reason);";
            
            // Execute the modified SQL
            if ($conn->query($newSql) === TRUE) {
                echo "SUCCESS (ID: $newAttendanceId)\n";
                $success_count++;
                $inserted_ids[] = $newAttendanceId;
            } else {
                echo "ERROR: " . $conn->error . "\n";
                $error_count++;
                $errors[] = "Statement " . ($index + 1) . ": " . $conn->error;
            }
        } else {
            // If regex fails, try the original SQL
            if ($conn->query($sql) === TRUE) {
                echo "SUCCESS (original SQL)\n";
                $success_count++;
            } else {
                echo "ERROR: " . $conn->error . "\n";
                $error_count++;
                $errors[] = "Statement " . ($index + 1) . ": " . $conn->error;
            }
        }
    }
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "At line: " . $e->getLine() . "\n";
}

// Display summary
echo "\n--------- EXECUTION SUMMARY ---------\n";
echo "Total statements: " . count($sql_statements) . "\n";
echo "Successful: " . $success_count . "\n";
echo "Failed: " . $error_count . "\n";

if (count($inserted_ids) > 0) {
    echo "Inserted attendance IDs: " . implode(', ', $inserted_ids) . "\n";
}

if ($error_count > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "- " . $error . "\n";
    }
} else {
    echo "\nAll statements executed successfully!\n";
}

// Close the connection
$conn->close();
echo "\nDatabase connection closed.\n";
?> 