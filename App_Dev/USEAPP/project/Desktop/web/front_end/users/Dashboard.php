
<?php
include("../../auth/authentication_for_user.php");
include("./includes/sidebar.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("../../dB/config.php");

// Get current user's information
$userId = $_SESSION['authUser']['userId'];
$today = date('Y-m-d');

// Get user's attendance for today
$query = "SELECT * FROM attendance WHERE userId = ? AND date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $userId, $today);
$stmt->execute();
$result = $stmt->get_result();
$todayAttendance = $result->fetch_assoc();

// Get user's attendance history
$historyQuery = "SELECT * FROM attendance 
                WHERE userId = ? 
                ORDER BY date DESC, timeIn DESC 
                LIMIT 5";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $userId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            margin-left: 100px;
            margin-top: 100px;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-clock {
            width: 100%;
            padding: 1rem;
            font-size: 1.2rem;
        }
        .timestamp {
            font-size: 1.1rem;
            color: #6c757d;
        }
        .history-table {
            margin-top: 2rem;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5em 1em;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <!-- Attendance Card -->
            <div class="col-md-6 mb-4">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        <h2>Today's Attendance</h2>
                        <p class="text-muted"><?php echo date('l, F j, Y'); ?></p>
                    </div>

                    <?php if(!$todayAttendance): ?>
                        <div class="d-grid">
                            <button class="btn btn-success btn-clock rounded-5 mb-3" 
                                    onclick="markAttendance('timeIn')">
                                🟢 Clock In
                            </button>
                        </div>
                    <?php elseif(!$todayAttendance['timeOut']): ?>
                        <div class="d-grid">
                            <button class="btn btn-danger btn-clock rounded-5 mb-3" 
                                    onclick="markAttendance('timeOut')">
                                🔴 Clock Out
                            </button>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-<?php echo ($todayAttendance && isset($todayAttendance['status']) && $todayAttendance['status'] == 'Present') ? 'success' : 'warning'; ?> status-badge">
                                <?php echo $todayAttendance['status'] ?? 'Not Set'; ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <h4 class="text-success mb-3">✓ Attendance Completed</h4>
                            <span class="badge bg-<?php echo $todayAttendance['status'] == 'Present' ? 'success' : 'warning'; ?> status-badge">
                                <?php echo $todayAttendance['status']; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="border-top pt-3 mt-3">
                        <p class="mb-1 timestamp">
                            <strong>Time In:</strong> 
                            <span id="lastClockIn">
                                <?php echo ($todayAttendance && isset($todayAttendance['timeIn'])) ? 
                                      date('h:i A', strtotime($todayAttendance['timeIn'])) : '--:--'; ?>
                            <   /span>
                        </p>
                        <p class="mb-0 timestamp">
                            <strong>Time Out:</strong> 
                            <span id="lastClockOut">
                                <?php echo ($todayAttendance && isset($todayAttendance['timeOut'])) ? 
                                      date('h:i A', strtotime($todayAttendance['timeOut'])) : '--:--'; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="col-md-6 mb-4">
                <div class="card p-4">
                    <h3 class="mb-4">Monthly Statistics</h3>
                    <?php
                    $month = date('m');
                    $year = date('Y');
                    
                    // Get monthly statistics
                    $statsQuery = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
                        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
                    FROM attendance 
                    WHERE userId = ? 
                    AND MONTH(date) = ? 
                    AND YEAR(date) = ?";
                    
                    $statsStmt = $conn->prepare($statsQuery);
                    $statsStmt->bind_param("iii", $userId, $month, $year);
                    $statsStmt->execute();
                    $stats = $statsStmt->get_result()->fetch_assoc();
                    ?>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <h5 class="text-success">Present</h5>
                                <h3><?php echo $stats['present_days']; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <h5 class="text-warning">Late</h5>
                                <h3><?php echo $stats['late_days']; ?></h3>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <h5 class="text-danger">Absent</h5>
                                <h3><?php echo $stats['absent_days']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance History -->
            <div class="col-12">
                <div class="card p-4">
                    <h3 class="mb-4">Recent Attendance History</h3>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Work Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $historyResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $row['status'] == 'Present' ? 'success' : 
                                                ($row['status'] == 'Late' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['timeIn'] ? date('h:i A', strtotime($row['timeIn'])) : '-'; ?></td>
                                    <td><?php echo $row['timeOut'] ? date('h:i A', strtotime($row['timeOut'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        if($row['timeIn'] && $row['timeOut']) {
                                            $hours = round((strtotime($row['timeOut']) - strtotime($row['timeIn'])) / 3600, 1);
                                            echo $hours . ' hrs';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
    function markAttendance(action) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const location = `${position.coords.latitude},${position.coords.longitude}`;
                submitAttendance(action, location);
            }, () => {
                submitAttendance(action, 'Location not available');
            });
        } else {
            submitAttendance(action, 'Location not available');
        }
    }

    function submitAttendance(action, location) {
        fetch('../../controller/attendanceProcessor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${action}&location=${location}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        });
    }
    </script>
</body>
</html>

<?php include("./includes/footer.php"); ?>