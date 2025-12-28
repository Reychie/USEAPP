<?php
include("../../auth/authentication_for_user.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
include("../../dB/config.php");

// Get user ID from session
$userId = $_SESSION['authUser']['userId'];

// Get the month and year filter if set
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    .history-table {
      margin-top: 2rem;
    }
    .badge {
      font-size: 0.9rem;
      padding: 0.5em 1em;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-15">
        <div class="card p-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Attendance History</h3>
            <!-- Filter Form -->
            <form method="GET" class="d-flex gap-2">
              <select name="month" class="form-select" style="width: auto;">
                <?php
                for($m = 1; $m <= 12; $m++) {
                    $selected = $m == $month ? 'selected' : '';
                    echo "<option value='$m' $selected>" . date('F', mktime(0,0,0,$m,1)) . "</option>";
                }
                ?>
              </select>
              <select name="year" class="form-select" style="width: auto;">
                <?php
                $currentYear = date('Y');
                for($y = $currentYear; $y >= $currentYear-2; $y--) {
                    $selected = $y == $year ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
              </select>
              <button type="submit" class="btn btn-primary">Filter</button>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead class="table-dark">
                <tr>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Clock In</th>
                  <th>Clock Out</th>
                  <th>Work Hours</th>
                  <th>Location</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Fetch attendance records for the selected month and year
                $query = "SELECT * FROM attendance 
                         WHERE userId = ? 
                         AND MONTH(date) = ? 
                         AND YEAR(date) = ?
                         ORDER BY date DESC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $userId, $month, $year);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Calculate work hours if both timeIn and timeOut exist
                        $workHours = '-';
                        if($row['timeIn'] && $row['timeOut']) {
                            $timeIn = new DateTime($row['timeIn']);
                            $timeOut = new DateTime($row['timeOut']);
                            $interval = $timeIn->diff($timeOut);
                            $workHours = $interval->format('%H:%I');
                        }

                        // Determine status badge color
                        $statusClass = '';
                        switch($row['status']) {
                            case 'Present':
                                $statusClass = 'bg-success';
                                break;
                            case 'Late':
                                $statusClass = 'bg-warning';
                                break;
                            case 'Absent':
                                $statusClass = 'bg-danger';
                                break;
                        }
                ?>
                <tr>
                  <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                  <td>
                    <span class="badge <?php echo $statusClass; ?>">
                      <?php echo $row['status']; ?>
                    </span>
                  </td>
                  <td><?php echo $row['timeIn'] ? date('h:i A', strtotime($row['timeIn'])) : '-'; ?></td>
                  <td><?php echo $row['timeOut'] ? date('h:i A', strtotime($row['timeOut'])) : '-'; ?></td>
                  <td><?php echo $workHours; ?></td>
                  <td><?php echo $row['location'] ?? 'Office'; ?></td>
                </tr>
                <?php
                    }
                } else {
                ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">No attendance records found for this period</td>
                </tr>
                <?php
                }
                ?>
              </tbody>
            </table>
          </div>

          <!-- Monthly Summary -->
          <div class="mt-4">
            <h4>Monthly Summary</h4>
            <div class="row g-3">
              <?php
              // Calculate monthly statistics
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
              <div class="col-md-3">
                <div class="p-3 border rounded bg-success text-white">
                  <h6>Present Days</h6>
                  <h3><?php echo $stats['present_days'] ?? 0; ?></h3>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-warning text-dark">
                  <h6>Late Days</h6>
                  <h3><?php echo $stats['late_days'] ?? 0; ?></h3>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-danger text-white">
                  <h6>Absent Days</h6>
                  <h3><?php echo $stats['absent_days'] ?? 0; ?></h3>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-info text-white">
                  <h6>Total Working Days</h6>
                  <h3><?php echo $stats['total_days'] ?? 0; ?></h3>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include("./includes/footer.php"); ?>