<?php
include("../../auth/authentication.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
?>

<!-- Include SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

<main id="main" class="main">
    <div class="pagetitle mb-3">
        <h1>Payroll Reports</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Payroll</li>
                <li class="breadcrumb-item active">Reports</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-body table-responsive">
                        <h5 class="card-title fw-bold pb-0 mb-3">Exported Payroll Reports</h5>
                        
                        <?php
                        // Path to reports directory - try multiple possible locations
                        $reportsPaths = [
                            '../../uploads/reports',
                            '../../../uploads/reports',
                            dirname(dirname(dirname(__FILE__))) . '/uploads/reports'
                        ];
                        
                        $reportsDir = null;
                        foreach ($reportsPaths as $path) {
                            if (file_exists($path)) {
                                $reportsDir = $path;
                                break;
                            }
                        }
                        
                        // If no reports directory found
                        if (!$reportsDir) {
                            echo '<div class="alert alert-warning">
                                <h4 class="alert-heading mb-2">Reports Directory Not Found</h4>
                                <p>The system could not locate the reports directory.</p>
                                <p class="mb-2">Paths checked:</p>
                                <ul>';
                            foreach ($reportsPaths as $path) {
                                echo '<li>' . $path . '</li>';
                            }
                            echo '</ul>
                                <p class="mt-2">To generate reports, please go to the <a href="Payroll_Calculation.php">Payroll</a> page and click the "Export Payroll Report" button.</p>
                                </div>';
                        } else {
                            // Get all files in the directory
                            $files = glob($reportsDir . '/*.*');
                            
                            if (empty($files)) {
                                echo '<div class="alert alert-info">
                                    <h4 class="alert-heading mb-2">No Reports Found</h4>
                                    <p>No payroll reports have been generated yet.</p>
                                    <p class="mt-3 mb-1">To generate reports:</p>
                                    <ol class="ms-4 mt-2">
                                        <li>Go to the <a href="Payroll_Calculation.php">Payroll</a> page</li>
                                        <li>Select the month and year</li>
                                        <li>Click the "Export Payroll Report" button</li>
                                    </ol>
                                    </div>';
                            } else {
                                // Sort files by modification time, newest first
                                usort($files, function($a, $b) {
                                    return filemtime($b) - filemtime($a);
                                });
                                
                                echo '<div class="table-responsive">
                                    <table class="table table-striped text-center align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Report Name</th>
                                            <th>Date Generated</th>
                                            <th>Size</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                                
                                foreach ($files as $file) {
                                    $filename = basename($file);
                                    $fileDate = date('Y-m-d H:i:s', filemtime($file));
                                    $fileSize = round(filesize($file) / 1024, 2) . ' KB';
                                    
                                    // Extract month and year from filename if possible
                                    $displayName = $filename;
                                    if (preg_match('/Payroll_Report_(\d+)_(\d+)_/', $filename, $matches)) {
                                        $month = $matches[1];
                                        $year = $matches[2];
                                        
                                        // Convert month number to name
                                        $monthNames = [
                                            '1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April',
                                            '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August',
                                            '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                                        ];
                                        
                                        $monthName = $monthNames[$month] ?? $month;
                                        $displayName = "Payroll Report - $monthName $year";
                                    }
                                    
                                    echo '<tr>
                                        <td>' . htmlspecialchars($displayName) . '</td>
                                        <td>' . $fileDate . '</td>
                                        <td>' . $fileSize . '</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="../../api/payslip.php?action=download_report&file=' . urlencode($filename) . '" class="btn btn-sm btn-primary">Download</a>
                                                <a href="../../api/payslip.php?action=view_report&file=' . urlencode($filename) . '" class="btn btn-sm btn-info" target="_blank">View</a>
                                                <button type="button" onclick="deleteReport(\'' . $filename . '\')" class="btn btn-sm btn-danger">Delete</button>
                                            </div>
                                        </td>
                                    </tr>';
                                }
                                
                                echo '</tbody>
                                    </table>
                                    </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include("./includes/footer.php"); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add active class to the payroll nav item
    document.querySelector('a[href="Payroll_Calculation.php"]').parentElement.classList.add('active');
});

function deleteReport(filename) {
    // Use SweetAlert2 for confirmation instead of basic confirm
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to recover this report!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the report',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send AJAX request to delete the file
            fetch('../../api/payslip.php?action=delete_report&file=' + encodeURIComponent(filename), {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === true || data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Report deleted successfully',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload(); // Reload the page to refresh the list
                    });
                } else {
                    console.error('Error deleting report:', data);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete report'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the report: ' + error.message
                });
            });
        }
    });
}
</script> 