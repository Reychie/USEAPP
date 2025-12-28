<?php
/**
 * Script to generate test attendance data SQL
 * This file generates SQL statements to insert test attendance records
 * Used for development and testing purposes only
 */

// Set the user ID based on the example
$userId = 3; // Using the value from the example SQL
$email = "alejo.angeloreychie@gmail.com"; // For documentation purposes

// Starting attendance ID
$attendanceId = 6;

// Start and end dates - extended to May 31 to get 21-22 weekdays
$startDate = "2025-05-01";
$endDate = "2025-05-31"; // Extended to get enough weekdays

// Time values to keep consistent
$timeIn = "06:32:03";
$timeOut = "05:32:03";
$status = "Present";
$location = "Office";

// Generate SQL statements
echo "-- Generated SQL statements for attendance records\n";
echo "-- User: $email (ID: $userId)\n";
echo "-- Date range: $startDate to $endDate (excluding weekends)\n\n";

// Initialize date objects for iteration
$date = new DateTime($startDate);
$end = new DateTime($endDate);
$end->modify('+1 day'); // to include the end date

$count = 0; // To keep track of total records

// Loop through each day in the date range
while ($date < $end) {
    $currentDate = $date->format('Y-m-d');
    
    // Skip weekends (Saturday=6, Sunday=0)
    $dayOfWeek = $date->format('w');
    if ($dayOfWeek != 0 && $dayOfWeek != 6) {
        // Create SQL insert statement for each weekday
        $sql = "INSERT INTO `attendance` (`attendanceId`, `userId`, `date`, `timeIn`, `timeOut`, `status`, `location`, `reason`) VALUES ";
        $sql .= "('$attendanceId', '$userId', '$currentDate', '$timeIn', '$timeOut', '$status', '$location', NULL);";
        echo $sql . "\n";
        
        // Increment attendance ID for the next record
        $attendanceId++;
        $count++; // Count the records
    }
    
    // Move to the next day
    $date->modify('+1 day');
}

// Output summary of generated SQL
echo "\n-- Total records: " . $count . "\n";
?> 