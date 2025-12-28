<?php
include("../../auth/authentication_for_user.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
include("../../dB/config.php");

// Get current user's ID
$userId = $_SESSION['authUser']['userId'];
?>

<main id="main" class="main">
  <div class="pagetitle mb-3">
    <h1>Payslip Details</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item">Payroll</li>
        <li class="breadcrumb-item active">Payslip</li>
      </ol>
    </nav>
  </div>

  <section class="section dashboard">
    <div class="row">
      <div class="col-lg-12">
        <!-- Filter Card -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                  <?php
                  $selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
                  for($m = 1; $m <= 12; $m++) {
                    $month_name = date('F', mktime(0, 0, 0, $m, 1));
                    $selected = ($m == $selected_month) ? 'selected' : '';
                    echo "<option value='$m' $selected>$month_name</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                  <?php
                  $current_year = date('Y');
                  $selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
                  for($y = $current_year; $y >= $current_year - 2; $y--) {
                    $selected = ($y == $selected_year) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Payslip Table Card -->
        <div class="card shadow-sm">
          <div class="card-body table-responsive">
            <h5 class="card-title fw-bold pb-0 mb-3">Payslip Records</h5>

            <table class="table table-striped text-center align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>Period</th>
                  <th>Basic Salary</th>
                  <th>Overtime</th>
                  <th>Deductions</th>
                  <th>Net Pay</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Get payroll records for the selected month and year
                $month = isset($_GET['month']) ? $_GET['month'] : date('m');
                $year = isset($_GET['year']) ? $_GET['year'] : date('Y');

                // Simple query with a single result
                $query = "SELECT s.*, p.pdfPath, 
                         COALESCE(p.status, 'Pending') as status 
                         FROM salary s 
                         LEFT JOIN payslip p ON s.salaryId = p.salaryId
                         WHERE s.userId = ? AND s.month = ? AND s.year = ?
                         ORDER BY s.salaryId DESC
                         LIMIT 1";

                // Add error handling for prepare statement
                $stmt = $conn->prepare($query);
                if($stmt === false) {
                    die("Error preparing statement: " . $conn->error);
                }

                $stmt->bind_param("iii", $userId, $month, $year);
                
                // Clear any previous result sets
                while($conn->more_results()) {
                    $conn->next_result();
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if($result->num_rows > 0) {
                    $row = $result->fetch_assoc(); // Get the single row
                    
                    // Format the period
                    $period = date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year']));
                    
                    // Get status badge class
                    $status = isset($row['status']) ? $row['status'] : 'Pending';
                    $statusClass = ($status === 'Paid') ? 'bg-success' : 'bg-warning';
                    
                    // Display the single row
                    ?>
                    <tr>
                        <td><?php echo $period; ?></td>
                        <td>₱<?php echo number_format($row['basicSalary'], 2); ?></td>
                        <td>₱<?php echo number_format($row['overtime'], 2); ?></td>
                        <td>₱<?php echo number_format($row['deductions'], 2); ?></td>
                        <td><strong class="text-success">₱<?php echo number_format($row['totalSalary'], 2); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                        </td>
                        <td>
                            <?php if(isset($row['pdfPath']) && !empty($row['pdfPath'])): ?>
                                <button class="btn btn-primary btn-sm" 
                                       onclick="viewPayslip('<?php echo $row['pdfPath']; ?>')">
                                  📄 View Payslip
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="generatePayslip(<?php echo $row['salaryId']; ?>)">
                                  Generate PDF
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                } else {
                ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    No payslip records found for this period
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Monthly Summary -->
        <?php if($result->num_rows > 0): ?>
        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title fw-bold pb-0 mb-3">Monthly Summary</h5>
            <?php
            // Calculate monthly totals
            $totalQuery = "SELECT 
                SUM(basicSalary) as total_basic,
                SUM(overtime) as total_overtime,
                SUM(bonus) as total_bonus,
                SUM(deductions) as total_deductions,
                SUM(totalSalary) as total_net
                FROM salary 
                WHERE userId = ? AND month = ? AND year = ?";

            $totalStmt = $conn->prepare($totalQuery);
            $totalStmt->bind_param("iii", $userId, $month, $year);
            $totalStmt->execute();
            $totals = $totalStmt->get_result()->fetch_assoc();
            ?>
            <div class="row g-3">
              <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                  <small class="d-block text-muted">Basic Salary</small>
                  <strong>₱<?php echo number_format($totals['total_basic'], 2); ?></strong>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                  <small class="d-block text-muted">Total Overtime</small>
                  <strong>₱<?php echo number_format($totals['total_overtime'], 2); ?></strong>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                  <small class="d-block text-muted">Total Deductions</small>
                  <strong>₱<?php echo number_format($totals['total_deductions'], 2); ?></strong>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 border rounded bg-success text-white">
                  <small class="d-block">Total Net Pay</small>
                  <strong>₱<?php echo number_format($totals['total_net'], 2); ?></strong>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<?php include("./includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function viewPayslip(filePath) {
  console.log('Opening payslip at path:', filePath);
  
  // Add a unique timestamp to the URL to prevent caching
  const uniqueUrl = 'viewPayslip.php?file=' + encodeURIComponent(filePath) + '&_nocache=' + new Date().getTime();
  
  // Show loading indicator
  Swal.fire({
    title: 'Loading Payslip...',
    text: 'Please wait while we access your payslip',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
      
      // Open the payslip URL - the backend will handle generation if needed
      const link = document.createElement('a');
      link.href = uniqueUrl;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      document.body.appendChild(link);
      
      // Click the link
      link.click();
      
      // Remove the element
      setTimeout(() => {
          document.body.removeChild(link);
          Swal.close();
      }, 1000);
    }
  });
}

function generatePayslip(salaryId) {
  Swal.fire({
    title: 'Generating Payslip...',
    text: 'Please wait while we generate your payslip',
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
      
      // Get the current user ID from the session
      const userId = <?php echo $userId; ?>;
      
      fetch('../../controller/payslipGenerator.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `userId=${userId}&salaryId=${salaryId}`
      })
      .then(response => response.json())
      .then(data => {
        if(data.status === 'success') {
          // Immediately open the payslip instead of showing a success message first
          if (data.pdfUrl) {
            viewPayslip(data.pdfUrl);
          } else {
            // If no URL is returned, reload the page to show the updated status
            window.location.reload();
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to generate payslip'
          });
        }
      })
      .catch(error => {
          console.error('Error generating payslip:', error);
          Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An error occurred while generating the payslip. Please try again.'
          });
      });
    }
  });
}
</script>