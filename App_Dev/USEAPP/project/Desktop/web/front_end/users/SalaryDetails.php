<?php
include("../../auth/authentication_for_user.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
include("../../dB/config.php");

// Get current user's ID and current month/year
$userId = $_SESSION['authUser']['userId'];
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get salary details for the current month
$query = "SELECT s.*, 
          COUNT(a.attendanceId) as daysWorked,
          SUM(TIMESTAMPDIFF(HOUR, a.timeIn, a.timeOut)) as totalHours
          FROM salary s
          LEFT JOIN attendance a ON s.userId = a.userId 
          AND MONTH(a.date) = s.month 
          AND YEAR(a.date) = s.year
          WHERE s.userId = ? AND s.month = ? AND s.year = ?
          GROUP BY s.salaryId";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $userId, $month, $year);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();

// Calculate various components if salary record exists
if ($salary) {
    $basicSalary = $salary['basicSalary'];
    $overtimePay = $salary['overtime'];
    $bonus = $salary['bonus'] ?? 0;
    $deductions = $salary['deductions'];
    $totalSalary = $salary['totalSalary'];
} else {
    // Default values if no salary record exists
    $basicSalary = 20000.00; // Base salary
    $overtimePay = 0;
    $bonus = 0;
    $deductions = 0;
    $totalSalary = $basicSalary;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Salary Details</title>
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
    .table td, .table th {
      vertical-align: middle;
    }
    .month-selector {
      max-width: 200px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-15">
        <div class="card p-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Salary Details</h3>
            <!-- Month/Year Filter -->
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
              <button type="submit" class="btn btn-primary">View</button>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead class="table-dark">
                <tr>
                  <th>Description</th>
                  <th>Amount (PHP)</th>
                </tr>
              </thead>
              <tbody>
                <!-- Earnings -->
                <tr>
                  <td>Basic Salary</td>
                  <td>₱<?php echo number_format($basicSalary, 2); ?></td>
                </tr>
                <?php if($overtimePay > 0): ?>
                <tr>
                  <td>Overtime Pay</td>
                  <td>₱<?php echo number_format($overtimePay, 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if($bonus > 0): ?>
                <tr>
                  <td>Bonus</td>
                  <td>₱<?php echo number_format($bonus, 2); ?></td>
                </tr>
                <?php endif; ?>

                <!-- Deductions -->
                <?php if($deductions > 0): ?>
                <tr class="table-danger">
                  <td>Deductions (Absences/Late)</td>
                  <td>-₱<?php echo number_format($deductions, 2); ?></td>
                </tr>
                <?php endif; ?>

                <!-- Total -->
                <tr class="table-success">
                  <th>Total Net Pay</th>
                  <th>₱<?php echo number_format($totalSalary, 2); ?></th>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Additional Information -->
          <div class="mt-4">
            <h5>Attendance Summary</h5>
            <?php
            // Get attendance summary
            $attendanceQuery = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
            FROM attendance 
            WHERE userId = ? AND MONTH(date) = ? AND YEAR(date) = ?";
            
            $stmt = $conn->prepare($attendanceQuery);
            $stmt->bind_param("iii", $userId, $month, $year);
            $stmt->execute();
            $attendance = $stmt->get_result()->fetch_assoc();
            ?>
            <div class="row g-3">
              <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                  <small class="d-block text-muted">Working Days</small>
                  <strong><?php echo $attendance['total_days']; ?> days</strong>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-success text-white">
                  <small class="d-block">Present Days</small>
                  <strong><?php echo $attendance['present_days']; ?> days</strong>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-warning">
                  <small class="d-block">Late Days</small>
                  <strong><?php echo $attendance['late_days']; ?> days</strong>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-danger text-white">
                  <small class="d-block">Absent Days</small>
                  <strong><?php echo $attendance['absent_days']; ?> days</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="mt-4">
            <div class="alert alert-info" role="alert">
              <h6 class="alert-heading">Salary Notes:</h6>
              <ul class="mb-0">
                <li>Basic salary is fixed at ₱20,000 per month</li>
                <li>Overtime rate is ₱150 per hour</li>
                <li>Deductions apply for absences and late arrivals</li>
                <li>10% deduction applies if less than 20 days worked</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include("./includes/footer.php"); ?>