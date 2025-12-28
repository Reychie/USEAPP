<?php
include("../../auth/authentication.php");
include("./includes/header.php");
include("./includes/topbar.php");
include("./includes/sidebar.php");
include("../../dB/config.php");
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>User Management</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">User Management</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <button class="btn btn-primary" style="float: right; margin-top: 20px; background-color: black;">
              <a href="../../register.php" style="color: white; text-decoration: none;"><b>Add User</b></a>
            </button>
            <h5 class="card-title">Users</h5>
            <table class="table datatable">
              <thead>
                <tr>
                  <th><b>Fullname</b></th>
                  <th>Mobile Number</th>
                  <th>Gender</th>
                  <th data-type="date" data-format="YYYY/DD/MM">Birthday</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
              <?php 
                  $query = "SELECT `firstName`, `lastName`, `phoneNumber`, `gender`, `birthday`, `userId` FROM `users`";
                  $query_run = mysqli_query($conn, $query);

                  if (!$query_run) {
                      die("Query failed: " . mysqli_error($conn));
                  }
                  if (mysqli_num_rows($query_run) > 0) {
                      while ($row = mysqli_fetch_assoc($query_run)) {
                          ?>
                          <tr>
                              <td><?php echo $row['firstName'] . " " . $row['lastName']; ?></td>
                              <td><?php echo $row['phoneNumber']; ?></td>
                              <td><?php echo $row['gender']; ?></td>
                              <td><?php echo $row['birthday']; ?></td>
                              <td>
                                <a href="view_user.php?id=<?php echo $row['userId']; ?>" class="btn btn-sm btn-info">
                                  <i class="bi bi-eye-fill"></i>
                                </a>
                              </td>
                          </tr>
                          <?php
                      }
                  } else {
                      echo "<tr><td colspan='5' class='text-center'>No users found</td></tr>";
                  }
              ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include("./includes/footer.php"); ?>