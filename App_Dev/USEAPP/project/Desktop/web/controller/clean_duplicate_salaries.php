<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials - hardcoded for reliability
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

echo "<h1>Salary Records Cleanup Utility</h1>";

// Step 1: Detect duplicate salary records for the same user, month and year
echo "<h2>Detecting Duplicate Records</h2>";

$query = "SELECT userId, month, year, COUNT(*) as record_count 
          FROM salary 
          GROUP BY userId, month, year 
          HAVING COUNT(*) > 1";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " sets of duplicate records:</p>";
    
    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>Period</th><th>Number of Duplicates</th><th>Action</th></tr>";
    
    $duplicates = array();
    while ($row = $result->fetch_assoc()) {
        $duplicates[] = $row;
        $period = date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year']));
        echo "<tr>";
        echo "<td>" . $row['userId'] . "</td>";
        echo "<td>" . $period . "</td>";
        echo "<td>" . $row['record_count'] . "</td>";
        echo "<td>Will keep highest ID, delete others</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Step 2: Keep only the highest salaryId for each set of duplicates
    echo "<h2>Cleaning Up Duplicate Records</h2>";
    
    $cleaned = 0;
    foreach ($duplicates as $dup) {
        // Find all salary IDs for this user, month, year
        $findQuery = "SELECT salaryId FROM salary 
                     WHERE userId = ? AND month = ? AND year = ? 
                     ORDER BY salaryId DESC";
        
        $stmt = $conn->prepare($findQuery);
        $stmt->bind_param("iii", $dup['userId'], $dup['month'], $dup['year']);
        $stmt->execute();
        $salaryResult = $stmt->get_result();
        
        // Keep track of highest ID (first one due to DESC order)
        $keepId = null;
        $deleteIds = array();
        
        $first = true;
        while ($salRow = $salaryResult->fetch_assoc()) {
            if ($first) {
                $keepId = $salRow['salaryId'];
                $first = false;
            } else {
                $deleteIds[] = $salRow['salaryId'];
            }
        }
        
        // Delete duplicate records
        if (!empty($deleteIds)) {
            $deleteQuery = "DELETE FROM salary WHERE salaryId IN (" . implode(',', $deleteIds) . ")";
            if ($conn->query($deleteQuery)) {
                $cleaned += count($deleteIds);
                echo "<p>Deleted " . count($deleteIds) . " duplicate records for User " . $dup['userId'] . 
                     " for " . date('F Y', mktime(0, 0, 0, $dup['month'], 1, $dup['year'])) . 
                     " (Keeping salary ID: " . $keepId . ")</p>";
            } else {
                echo "<p style='color:red;'>Error deleting duplicates: " . $conn->error . "</p>";
            }
        }
    }
    
    echo "<p style='color:green;'>Cleanup completed! Deleted a total of $cleaned duplicate salary records.</p>";
    
} else {
    echo "<p style='color:green;'>Great news! No duplicate salary records were found in the database.</p>";
}

// Step 3: Check for orphaned payslip records
echo "<h2>Checking for Orphaned Payslip Records</h2>";

$orphanQuery = "SELECT p.* FROM payslip p 
              LEFT JOIN salary s ON p.salaryId = s.salaryId
              WHERE s.salaryId IS NULL";
$orphanResult = $conn->query($orphanQuery);

if ($orphanResult && $orphanResult->num_rows > 0) {
    echo "<p>Found " . $orphanResult->num_rows . " orphaned payslip records.</p>";
    
    echo "<table border='1'>";
    echo "<tr><th>Payslip ID</th><th>User ID</th><th>Salary ID</th><th>Path</th></tr>";
    
    while ($row = $orphanResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['payslipId'] . "</td>";
        echo "<td>" . $row['userId'] . "</td>";
        echo "<td>" . $row['salaryId'] . "</td>";
        echo "<td>" . $row['pdfPath'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Delete orphaned payslips
    $deleteOrphanQuery = "DELETE p FROM payslip p 
                         LEFT JOIN salary s ON p.salaryId = s.salaryId 
                         WHERE s.salaryId IS NULL";
    
    if ($conn->query($deleteOrphanQuery)) {
        echo "<p style='color:green;'>Successfully deleted " . $conn->affected_rows . " orphaned payslip records.</p>";
    } else {
        echo "<p style='color:red;'>Error deleting orphaned payslips: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>Great news! No orphaned payslip records were found.</p>";
}

$conn->close();
echo "<p>Database cleanup completed.</p>";
echo "<p><a href='../view/users/Payslip.php'>Return to Payslip Page</a></p>";
?> 