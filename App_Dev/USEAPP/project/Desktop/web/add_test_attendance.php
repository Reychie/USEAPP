<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include("../dB/config.php");

// Function to add test attendance data
function addTestAttendanceData($conn) {
    echo "<h1>Adding Test Attendance Data</h1>";
    
    // First, get all users with 'user' role
    $userQuery = "SELECT userId, firstName, lastName FROM users WHERE userRole = 'user'";
    $userResult = mysqli_query($conn, $userQuery);
    
    if (!$userResult) {
        die("Error querying users: " . mysqli_error($conn));
    }
    
    // Current month and year
    $month = date('m');
    $year = date('Y');
    
    echo "<p>Adding attendance data for month: $month, year: $year</p>";
    
    // Store added records count
    $addedRecords = 0;
    
    // Loop through each user
    while ($user = mysqli_fetch_assoc($userResult)) {
        $userId = $user['userId'];
        $name = $user['firstName'] . ' ' . $user['lastName'];
        
        echo "<h3>Adding data for user: $name (ID: $userId)</h3>";
        
        // Get number of days in the current month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // Number of working days to add (between 15-22 days)
        $workingDays = mt_rand(15, 22);
        
        echo "<p>Adding $workingDays working days</p>";
        
        // Random days to add attendance
        $days = range(1, $daysInMonth);
        shuffle($days);
        $selectedDays = array_slice($days, 0, $workingDays);
        sort($selectedDays);
        
        // Loop through selected days
        foreach ($selectedDays as $day) {
            // Format the date
            $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
            
            // Check if weekend (skip weekends)
            $dayOfWeek = date('N', strtotime($date));
            if ($dayOfWeek > 5) {
                continue; // Skip weekends
            }
            
            // Random check-in time between 7:30 AM and 9:00 AM
            $hourIn = mt_rand(7, 8);
            $minIn = ($hourIn == 7) ? mt_rand(30, 59) : mt_rand(0, 59);
            $timeIn = sprintf("%02d:%02d:00", $hourIn, $minIn);
            
            // Random check-out time between 5:00 PM and 7:30 PM
            $hourOut = mt_rand(17, 19);
            $minOut = ($hourOut == 19) ? mt_rand(0, 30) : mt_rand(0, 59);
            $timeOut = sprintf("%02d:%02d:00", $hourOut, $minOut);
            
            // Determine status (90% chance of Present, 10% chance of Late)
            $status = (mt_rand(1, 10) > 1) ? 'Present' : 'Late';
            
            // Check if attendance record already exists
            $checkQuery = "SELECT * FROM attendance WHERE userId = ? AND date = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("is", $userId, $date);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                echo "<p>Record for date $date already exists, skipping</p>";
                continue;
            }
            
            // Insert attendance record
            $insertQuery = "INSERT INTO attendance (userId, date, timeIn, timeOut, status) VALUES (?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("issss", $userId, $date, $timeIn, $timeOut, $status);
            
            if ($insertStmt->execute()) {
                $addedRecords++;
                echo "<p>Added attendance for $date - In: $timeIn, Out: $timeOut, Status: $status</p>";
            } else {
                echo "<p>Error adding attendance for $date: " . $insertStmt->error . "</p>";
            }
        }
    }
    
    echo "<h2>Added $addedRecords attendance records</h2>";
    
    return $addedRecords;
}

// Run the function to add test data
$addedRecords = addTestAttendanceData($conn);

// Link back to test export and reports
echo "<p><a href='test_export.php'>Run Export Test</a></p>";
echo "<p><a href='front_end/admin/view_reports.php'>View Reports</a></p>";
?> 