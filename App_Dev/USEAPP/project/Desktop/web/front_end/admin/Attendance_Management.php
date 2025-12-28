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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        #Title {
            font-size: 28px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar bg-light px-4 py-2 shadow-sm">
        <span class="navbar-brand" id="Title">Attendance Management</span>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Employee Attendance Records</h4>
            <div>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-striped text-center align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                        
                        $query = "SELECT a.*, u.firstName, u.lastName 
                                 FROM attendance a 
                                 JOIN users u ON a.userId = u.userId 
                                 WHERE a.date = ? 
                                 ORDER BY a.timeIn DESC";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $date_filter);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while($row = $result->fetch_assoc()) {
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
                            <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                            <td><?php echo $row['timeIn'] ? date('h:i A', strtotime($row['timeIn'])) : '-'; ?></td>
                            <td><?php echo $row['timeOut'] ? date('h:i A', strtotime($row['timeOut'])) : '-'; ?></td>
                            <td>
                                <button class="btn btn-sm bg-black text-white" 
                                        onclick='editAttendance(<?php echo json_encode([
                                            "id" => $row["attendanceId"],
                                            "timeIn" => $row["timeIn"],
                                            "timeOut" => $row["timeOut"]
                                        ]); ?>)'>
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteAttendance(<?php echo $row['attendanceId']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm" method="GET">
                        <div class="mb-3">
                            <label class="form-label">Select Date</label>
                            <input type="date" class="form-control" name="date" 
                                   value="<?php echo $date_filter; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Attendance Modal -->
    <div class="modal fade" id="editAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content shadow-sm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAttendanceForm" onsubmit="return updateAttendance(event)">
                        <input type="hidden" id="attendanceId">
                        <div class="mb-3">
                            <label for="timeIn" class="form-label">Time In</label>
                            <input type="time" class="form-control" id="timeIn" required>
                        </div>
                        <div class="mb-3">
                            <label for="timeOut" class="form-label">Time Out</label>
                            <input type="time" class="form-control" id="timeOut">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
    function editAttendance(data) {
        document.getElementById('attendanceId').value = data.id;
        document.getElementById('timeIn').value = data.timeIn ? data.timeIn.substring(0, 5) : '';
        document.getElementById('timeOut').value = data.timeOut ? data.timeOut.substring(0, 5) : '';
        new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
    }

    function deleteAttendance(attendanceId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('attendanceId', attendanceId);

                fetch('../../controller/attendanceProcessor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'The attendance record has been deleted.'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete attendance record'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request'
                    });
                    console.error(error);
                });
            }
        });
    }

    function updateAttendance(event) {
        event.preventDefault();
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('attendanceId', document.getElementById('attendanceId').value);
        formData.append('timeIn', document.getElementById('timeIn').value);
        formData.append('timeOut', document.getElementById('timeOut').value);

        Swal.fire({
            title: 'Updating...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                fetch('../../controller/attendanceProcessor.php', {
                    method: 'POST',
                    body: formData
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
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update attendance'
                    });
                });
            }
        });
        return false;
    }
    </script>

</body>
</html>

<?php
include("./includes/footer.php");
?>