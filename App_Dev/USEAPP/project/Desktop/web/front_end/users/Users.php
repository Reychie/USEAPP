<div class="card">
            <div class="card-body">
              <h5 class="card-title">Datatables</h5>
              <p>Add lightweight datatables to your project with using the <a href="https://github.com/fiduswriter/Simple-DataTables" target="_blank">Simple DataTables</a> library. Just add <code>.datatable</code> class name to any table you wish to conver to a datatable. Check for <a href="https://fiduswriter.github.io/simple-datatables/demos/" target="_blank">more examples</a>.</p>

              <!-- Table with stripped rows -->
              <table class="table datatable">
                <thead>
                  <tr>
                    <th>
                      <b>Fullname</b>
                    </th>
                    <th>Mobile Number</th>
                    <th>Gender</th>
                    <th data-type="date" data-format="YYYY/DD/MM">Birthday</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                <?php 
                    $query = "SELECT `firstName`, `lastName`, `phoneNumber`, `gender`, `birthday` FROM `users`";
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
                                <td><button class="bi bi-eye-fill"></button></td>
                            </tr>
                            <?php
                        }
                    }
                ?>
                </tbody>

              </table>

            </div>
          </div>