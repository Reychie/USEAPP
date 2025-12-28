<?php
include("../../auth/authentication.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
include("../../dB/config.php");

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $email = $_POST['email'];
                $password = password_hash('123', PASSWORD_DEFAULT); // Default password
                $phoneNumber = $_POST['phoneNumber'];
                $gender = $_POST['gender'];
                $birthday = $_POST['birthday'];

                $query = "INSERT INTO users (firstName, lastName, email, password, phoneNumber, gender, birthday, userRole) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'user')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssssss", $firstName, $lastName, $email, $password, $phoneNumber, $gender, $birthday);
                $stmt->execute();
                break;

            case 'edit':
                $userId = $_POST['userId'];
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $email = $_POST['email'];
                $phoneNumber = $_POST['phoneNumber'];
                $gender = $_POST['gender'];
                $birthday = $_POST['birthday'];

                $query = "UPDATE users SET firstName=?, lastName=?, email=?, phoneNumber=?, gender=?, birthday=? 
                         WHERE userId=?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $phoneNumber, $gender, $birthday, $userId);
                $stmt->execute();
                break;

            case 'delete':
                $userId = $_POST['userId'];
                $query = "DELETE FROM users WHERE userId=?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        #Title {
            font-size: 28px;
            font-weight: bold;
        }
        .modal-header {
            background-color: #f8f9fa;
        }
        .btn-black {
            background-color: #000;
            color: #fff;
        }
        .btn-black:hover {
            background-color: #333;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar bg-light px-4 shadow-sm">
        <span class="navbar-brand mb-0 h1" id="Title">Employee Management</span>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Employee List</h4>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                + Add Employee
            </button>
        </div>

        <!-- Employee Table -->
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-striped text-center align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM users WHERE userRole='user' ORDER BY userId DESC";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                            <td><?php echo $row['userId']; ?></td>
                            <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                            <td>Software Developer</td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phoneNumber']); ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-black" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $row['userId']; ?>)">Remove</button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phoneNumber" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthday</label>
                            <input type="date" class="form-control" name="birthday" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editEmployeeForm" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="userId" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstName" id="editFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastName" id="editLastName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phoneNumber" id="editPhoneNumber" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="editGender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthday</label>
                            <input type="date" class="form-control" name="birthday" id="editBirthday" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
    function editEmployee(employee) {
        document.getElementById('editUserId').value = employee.userId;
        document.getElementById('editFirstName').value = employee.firstName;
        document.getElementById('editLastName').value = employee.lastName;
        document.getElementById('editEmail').value = employee.email;
        document.getElementById('editPhoneNumber').value = employee.phoneNumber;
        document.getElementById('editGender').value = employee.gender;
        document.getElementById('editBirthday').value = employee.birthday;
        new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
    }

    function deleteEmployee(userId) {
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
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="userId" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Show success/error messages
    <?php if (isset($_POST['action'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Operation completed successfully'
    }).then(() => {
        
    });
    <?php endif; ?>
    </script>
</body>
</html>

<?php include("./includes/footer.php"); ?>