<?php
session_start();
include("../dB/config.php");

if(isset($_POST['action'])) {
    $userId = $_SESSION['authUser']['userId'];
    $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $time = date('H:i:s');
    $action = $_POST['action'];
    $location = $_POST['location'] ?? 'Office';
    $reason = $_POST['reason'] ?? NULL;

    if($action == 'timeIn') {
        // Check if already timed in
        $checkQuery = "SELECT * FROM attendance WHERE userId = ? AND date = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("is", $userId, $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Already timed in today']);
            exit;
        }

        // Record time in
        $query = "INSERT INTO attendance (userId, date, timeIn, status, location) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $status = ($time > '09:00:00') ? 'Late' : 'Present';
        $stmt->bind_param("issss", $userId, $date, $time, $status, $location);

    } else if($action == 'timeOut') {
        // Update time out
        $query = "UPDATE attendance SET timeOut = ? WHERE userId = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sis", $time, $userId, $date);

    } else if($action == 'absent') {
        // Check if attendance record already exists for this date
        $checkQuery = "SELECT * FROM attendance WHERE userId = ? AND date = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("is", $userId, $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Attendance record already exists for this date']);
            exit;
        }

        // Record absent
        $query = "INSERT INTO attendance (userId, date, status, reason) VALUES (?, ?, 'Absent', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $userId, $date, $reason);
    } else if($action == 'update') {
        $attendanceId = $_POST['attendanceId'];
        $timeIn = $_POST['timeIn'];
        $timeOut = $_POST['timeOut'];
        
        // Calculate status based on time in
        $status = (strtotime($timeIn) > strtotime('09:00:00')) ? 'Late' : 'Present';
        
        $query = "UPDATE attendance 
                  SET timeIn = ?, timeOut = ?, status = ? 
                  WHERE attendanceId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $timeIn, $timeOut, $status, $attendanceId);

        if($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Attendance updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update attendance']);
        }
        exit;
    } else if($action == 'delete') {
        $attendanceId = $_POST['attendanceId'];
        
        $query = "DELETE FROM attendance WHERE attendanceId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $attendanceId);

        if($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Attendance record deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete attendance record']);
        }
        exit;
    }

    if($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => ucfirst($action) . ' recorded successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to record ' . $action]);
    }
}

// Add function to mark users as absent automatically at end of day
function markAbsentUsers() {
    global $conn;
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Only run this after work hours (e.g., after 6 PM)
    if($time >= '18:00:00') {
        // Get all users who haven't recorded attendance today
        $query = "INSERT INTO attendance (userId, date, status)
                  SELECT u.userId, ?, 'Absent'
                  FROM users u
                  LEFT JOIN attendance a ON u.userId = a.userId AND a.date = ?
                  WHERE a.attendanceId IS NULL AND u.userRole = 'user'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $date, $date);
        $stmt->execute();
    }
}

// Call this function at the end of the script
markAbsentUsers();
?>