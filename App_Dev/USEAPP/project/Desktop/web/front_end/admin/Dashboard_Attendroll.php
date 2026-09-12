<?php
include("../../auth/authentication.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
?>

<?php
include("../../dB/config.php");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" href="../../assets/img/favicon.png">
    <title>Dashboard_Attendroll</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        #AP {
            background-color: white;
            border-radius: 20px;
        }
        #titleAP {
            color: black;
            font-size: 30px;
            font-weight: bold;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-light p-3 mb-3" id="AP">
        <h1 class="navbar-brand mb-0" id="titleAP">DASHBOARD</h1>
    </nav>

    <div class="container">
        <div class="row g-4">
            <div class="col-md-15">
                <div class="card bg-dark text-white text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total Employees</h5>
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-people"></i>
                            <div class="ps-3">
                            <?php
                            $query = "SELECT COUNT(*) as total FROM users WHERE userRole='user'";
                            $result = mysqli_query($conn, $query);
                            $data = mysqli_fetch_assoc($result);
                            ?>
                            <h2><?php echo $data['total']; ?></h2>
                        </div>
                    </div>

                    </div>
                </div>
            </div>
            <!-- Present Card -->
            <div class="col-md-6">
                <div class="card bg-success text-white text-center">
                    <div class="card-body">
                        <h1 class="card-title">Employees Present</h1>
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-calendar-check"></i>
                            <div class="ps-3">
                            <?php
                            $today = date('Y-m-d');
                            // Count total users
                            $totalQuery = "SELECT COUNT(*) as total FROM users WHERE userRole='user'";
                            $totalResult = mysqli_query($conn, $totalQuery);
                            $totalData = mysqli_fetch_assoc($totalResult);
                            $totalUsers = $totalData['total'];

                            // Count present and late users
                            $presentQuery = "SELECT COUNT(DISTINCT userId) as present_count 
                                           FROM attendance 
                                           WHERE date = '$today' 
                                           AND (status = 'Present' OR status = 'Late')";
                            $presentResult = mysqli_query($conn, $presentQuery);
                            $presentData = mysqli_fetch_assoc($presentResult);
                            $presentUsers = $presentData['present_count'];
                            ?>
                            <h2><?php echo $presentUsers; ?> / <?php echo $totalUsers; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absent Card -->
            <div class="col-md-6">
                <div class="card bg-danger text-white text-center">
                    <div class="card-body">
                        <h1 class="card-title">Absent Employees</h1>
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-x"></i>
                            <div class="ps-3">
                            <?php
                            $today = date('Y-m-d');
                            // Count total users
                            $totalQuery = "SELECT COUNT(*) as total FROM users WHERE userRole='user'";
                            $totalResult = mysqli_query($conn, $totalQuery);
                            $totalData = mysqli_fetch_assoc($totalResult);
                            $totalUsers = $totalData['total'];

                            // Count present and late users
                            $presentQuery = "SELECT COUNT(DISTINCT userId) as present_count 
                                           FROM attendance 
                                           WHERE date = '$today' 
                                           AND (status = 'Present' OR status = 'Late')";
                            $presentResult = mysqli_query($conn, $presentQuery);
                            $presentData = mysqli_fetch_assoc($presentResult);
                            $presentUsers = $presentData['present_count'];

                            // Calculate absent users
                            $absentUsers = $totalUsers - $presentUsers;
                            ?>
                            <h2><?php echo $absentUsers; ?> / <?php echo $totalUsers; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        Attendance Overview
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $today = date('Y-m-d');
                                // Get all users and their attendance status
                                $query = "SELECT u.userId, u.firstName, u.lastName,
                                        COALESCE(a.status, 'Absent') as status,
                                        a.timeIn, a.timeOut, a.date
                                        FROM users u
                                        LEFT JOIN attendance a ON u.userId = a.userId 
                                        AND a.date = '$today'
                                        WHERE u.userRole = 'user'
                                        ORDER BY u.firstName ASC";
                                
                                $result = mysqli_query($conn, $query);
                                
                                while($row = mysqli_fetch_assoc($result)) {
                                    // Determine status badge color
                                    $statusBadgeClass = '';
                                    switch($row['status']) {
                                        case 'Present':
                                            $statusBadgeClass = 'bg-success';
                                            break;
                                        case 'Late':
                                            $statusBadgeClass = 'bg-warning';
                                            break;
                                        case 'Absent':
                                            $statusBadgeClass = 'bg-danger';
                                            break;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                                    <td><span class="badge <?php echo $statusBadgeClass; ?>"><?php echo $row['status']; ?></span></td>
                                    <td><?php echo $row['timeIn'] ? date('h:i A', strtotime($row['timeIn'])) : '-'; ?></td>
                                    <td><?php echo $row['timeOut'] ? date('h:i A', strtotime($row['timeOut'])) : '-'; ?></td>
                                    <td><?php echo $row['date'] ? date('M d, Y', strtotime($row['date'])) : date('M d, Y'); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>


<?php
include("./includes/footer.php");
?>


