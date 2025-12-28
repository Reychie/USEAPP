<?php
include("../../auth/authentication.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
include("../../dB/config.php");

// Get current month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Calculate payroll for all employees
function calculatePayroll($userId, $month, $year, $conn) {
    // Count working days
    $query = "SELECT COUNT(*) as days_worked, 
              SUM(TIMESTAMPDIFF(HOUR, timeIn, timeOut)) as total_hours
              FROM attendance 
              WHERE userId = ? 
              AND MONTH(date) = ? 
              AND YEAR(date) = ?
              AND status IN ('Present', 'Late')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $daysWorked = $data['days_worked'] ?? 0;
    $totalHours = $data['total_hours'] ?? 0;
    
    // Check if employee has worked the required minimum days (22)
    if($daysWorked < 22) {
        // If employee hasn't worked 22 days, return zero values
        return [
            'daysWorked' => $daysWorked,
            'requiredDays' => 22,
            'basicSalary' => 0,
            'overtimePay' => 0,
            'deductions' => 0,
            'netPay' => 0,
            'insufficientDays' => true
        ];
    }
    
    // Only calculate salary if employee has worked required days
    // Calculate overtime (hours worked beyond 8 hours per day)
    $regularHours = $daysWorked * 8;
    $overtimeHours = max(0, $totalHours - $regularHours);
    
    // Calculate pay
    $basicSalary = 20000.00; // Base salary per month
    $overtimeRate = 150.00;   // Overtime rate per hour
    $overtime = $overtimeHours * $overtimeRate;
    
    // Calculate deductions
    $deductions = 0;
    if ($daysWorked < 20) {
        $deductions = $basicSalary * 0.1; // 10% deduction for less than 20 days
    }
    
    $netPay = $basicSalary + $overtime - $deductions;
    
    return [
        'daysWorked' => $daysWorked,
        'requiredDays' => 22,
        'basicSalary' => $basicSalary,
        'overtimePay' => $overtime,
        'deductions' => $deductions,
        'netPay' => $netPay,
        'insufficientDays' => false
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Calculation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        #Title {
            font-size: 30px;
            font-weight: bold;
        }
        .btn-view {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar bg-light px-4 py-2 shadow-sm">
        <span class="navbar-brand" id="Title">Payroll Management</span>
    </nav>

    <div class="container mt-4">
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select">
                            <?php
                            for($m = 1; $m <= 12; $m++) {
                                $selected = $m == $month ? 'selected' : '';
                                echo "<option value='$m' $selected>" . date('F', mktime(0,0,0,$m,1)) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <?php
                            $currentYear = date('Y');
                            for($y = $currentYear; $y >= $currentYear-2; $y--) {
                                $selected = $y == $year ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-striped text-center align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Employee</th>
                            <th>Days Worked</th>
                            <th>Basic Salary</th>
                            <th>OT Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT userId, firstName, lastName FROM users WHERE userRole='user'";
                        $result = mysqli_query($conn, $query);
                        
                        while($employee = mysqli_fetch_assoc($result)) {
                            $payroll = calculatePayroll($employee['userId'], $month, $year, $conn);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['firstName'] . ' ' . $employee['lastName']); ?></td>
                            <td>
                                <?php echo $payroll['daysWorked']; ?> / <?php echo $payroll['requiredDays']; ?> days
                                <?php if($payroll['insufficientDays']): ?>
                                    <span class="badge bg-danger">Insufficient</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $payroll['insufficientDays'] ? '₱0.00' : '₱'.number_format($payroll['basicSalary'], 2); ?></td>
                            <td><?php echo $payroll['insufficientDays'] ? '₱0.00' : '₱'.number_format($payroll['overtimePay'], 2); ?></td>
                            <td><?php echo $payroll['insufficientDays'] ? '₱0.00' : '₱'.number_format($payroll['deductions'], 2); ?></td>
                            <td>
                                <?php if($payroll['insufficientDays']): ?>
                                    <strong class="text-danger">₱0.00</strong>
                                    <i class="bi bi-info-circle text-danger" 
                                       data-bs-toggle="tooltip" 
                                       title="Employee has not completed the required 22 work days"></i>
                                <?php else: ?>
                                    <strong class="text-success">₱<?php echo number_format($payroll['netPay'], 2); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!$payroll['insufficientDays']): ?>
                                    <button class="btn btn-sm bg-black text-white btn-view" 
                                            onclick="generatePayslip(<?php echo $employee['userId']; ?>)">
                                        View Payslip
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm bg-secondary text-white" 
                                            onclick="showNoPayslipMessage('<?php echo htmlspecialchars($employee['firstName'] . ' ' . $employee['lastName']); ?>', <?php echo $payroll['daysWorked']; ?>, <?php echo $payroll['requiredDays']; ?>)">
                                        No Payslip
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Button -->
        <div class="text-end mt-3">
            <a href="view_reports.php" class="btn btn-primary me-2">View Exported Reports</a>
            <button class="btn btn-success" onclick="exportPayroll()">Export Payroll Report</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
    function generatePayslip(userId) {
        console.log('Generating payslip for user ID:', userId);
        
        // Show loading indicator
        Swal.fire({
            title: 'Generating payslip...',
            html: 'Please wait while we generate the payslip.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Set a timeout to prevent hanging
        const requestTimeout = setTimeout(() => {
            console.log('Request timeout - switching to alternative method');
            // Direct access to controller with GET parameters
            window.location.href = `../../controller/payslipGenerator.php?userId=${userId}&month=${<?php echo $month; ?>}&year=${<?php echo $year; ?>}`;
        }, 15000); // 15 seconds timeout
        
        // Function to view the payslip
        function viewPayslip(url) {
            console.log('Opening payslip at URL:', url);
            
            // Add a unique timestamp to the URL to prevent caching
            const uniqueUrl = url + (url.includes('?') ? '&' : '?') + '_nocache=' + new Date().getTime();
            
            // First check if the file exists by using a HEAD request
            fetch(uniqueUrl, { 
                method: 'HEAD',
                cache: 'no-store' // Prevent browser from caching the request
            })
                .then(response => {
                    if (response.ok) {
                        // File exists, now open it in a new tab with download attribute for backup
                        // Create a temporary link to force a fresh request
                        const link = document.createElement('a');
                        link.href = uniqueUrl;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        document.body.appendChild(link);
                        link.click();
                        
                        // Remove the element
                        setTimeout(() => {
                            document.body.removeChild(link);
                        }, 100);
                        
                        // Show a message to the user in case popup is blocked
                        setTimeout(() => {
                            Swal.fire({
                                icon: 'info',
                                title: 'Payslip Opened',
                                text: 'If the payslip did not open automatically, click the button below.',
                                confirmButtonText: 'Open Payslip',
                                showCancelButton: true,
                                timer: 5000,
                                timerProgressBar: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.open(uniqueUrl, '_blank');
                                }
                            });
                        }, 2000);
                    } else {
                        throw new Error(`File not found: ${uniqueUrl}`);
                    }
                })
                .catch(error => {
                    console.error('Error checking payslip file:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'File Error',
                        text: 'The payslip file could not be accessed. Please try regenerating it.',
                        confirmButtonText: 'Regenerate',
                        showCancelButton: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `../../controller/payslipGenerator.php?userId=${userId}&month=${<?php echo $month; ?>}&year=${<?php echo $year; ?>}&regenerate=1`;
                        }
                    });
                });
        }
        
        // Request to generate the payslip
        fetch('../../controller/payslipGenerator.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${userId}&month=${<?php echo $month; ?>}&year=${<?php echo $year; ?>}`
        })
        .then(response => {
            clearTimeout(requestTimeout); // Clear timeout on success
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response is not valid JSON:', text);
                    throw new Error('Invalid server response format');
                }
            });
        })
        .then(data => {
            Swal.close();
            
            if(data.status === 'success') {
                console.log('Payslip generated successfully:', data);
                
                // Add a cache-busting parameter to prevent browser caching
                const cacheBuster = new Date().getTime();
                // Use our proxy to view the file securely
                const pdfUrl = `viewPayslip.php?file=${encodeURIComponent(data.pdfUrl)}&t=${cacheBuster}`;
                
                // View the payslip
                viewPayslip(pdfUrl);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Payslip generated successfully'
                });
            } else {
                console.error('Error from server:', data);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to generate payslip',
                    confirmButtonText: 'Try Again',
                    showCancelButton: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Try again with direct GET request
                        window.location.href = `../../controller/payslipGenerator.php?userId=${userId}&month=${<?php echo $month; ?>}&year=${<?php echo $year; ?>}`;
                    }
                });
            }
        })
        .catch(error => {
            clearTimeout(requestTimeout); // Clear timeout on error
            console.error('Error generating payslip:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while generating the payslip. Please try again.',
                confirmButtonText: 'Try Again',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Retry with fallback path
                    window.location.href = `../../controller/payslipGenerator.php?userId=${userId}&month=${<?php echo $month; ?>}&year=${<?php echo $year; ?>}`;
                }
            });
        });
    }

    function exportPayroll() {
        console.log('Exporting payroll report for month:', <?php echo $month; ?>, 'year:', <?php echo $year; ?>);
        
        // Show loading indicator
        Swal.fire({
            title: 'Generating payroll report...',
            html: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send request to generate the report
        fetch('../../controller/payrollExport.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `month=${<?php echo $month; ?>}&year=${<?php echo $year; ?>}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if(data.status === 'success') {
                Swal.close();
                
                // Create a download link and trigger it automatically
                const downloadUrl = `../../api/payslip.php?action=download_report&file=${encodeURIComponent(data.fileName)}`;
                const link = document.createElement('a');
                link.href = downloadUrl;
                
                // Set appropriate file extension based on whether it's PDF or TXT
                const isPdf = data.fileName.toLowerCase().endsWith('.pdf');
                link.download = `Payroll_Report_${<?php echo $month; ?>}_${<?php echo $year; ?>}.${isPdf ? 'pdf' : 'txt'}`;
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Show a small notification that download has started
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Report download started'
                });
            } else {
                throw new Error(data.message || 'Failed to generate report');
            }
        })
        .catch(error => {
            console.error('Error generating report:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while generating the payroll report.',
                confirmButtonText: 'OK'
            });
        });
    }

    function showNoPayslipMessage(employeeName, daysWorked, requiredDays) {
        Swal.fire({
            icon: 'info',
            title: 'No Payslip Available',
            text: `${employeeName} has worked only ${daysWorked} out of ${requiredDays} required days this month. A minimum of ${requiredDays} work days is needed to generate a payslip.`,
            confirmButtonText: 'Understand'
        });
    }
    </script>
</body>
</html>

<?php include("./includes/footer.php"); ?>