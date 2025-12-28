  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
        <a class="nav-link " href="Dashboard_Attendroll.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="Attendance_Management.php">
          <i class="bi bi-kanban"></i>
          <span>Attendance Management</span>
        </a>
      </li><!-- End F.A.Q Page Nav -->

      <!-- Payroll Nav -->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#payroll-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-currency-dollar"></i><span>Payroll</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="payroll-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="Payroll_Calculation.php">
              <i class="bi bi-circle"></i><span>Payroll Calculation</span>
            </a>
          </li>
          <li>
            <a href="view_reports.php">
              <i class="bi bi-circle"></i><span>Reports</span>
            </a>
          </li>
        </ul>
      </li><!-- End Payroll Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="Employee_Management.php">
          <i class="bi bi-person"></i>
          <span>Employee Management</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="controller/logout.php">
          <i class="bi bi-box-arrow-right"></i>
          <span>Sign Out</span>
        </a>
      </li>

    </ul>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">