  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <?php
    // Check if session is started, if not start it
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // For debugging - remove in production
    if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
        // Session data is missing, attempt to reconnect
        if (file_exists('../../auth/authentication_for_user.php')) {
            include_once('../../auth/authentication_for_user.php');
        } else if (file_exists('../../../auth/authentication_for_user.php')) {
            include_once('../../../auth/authentication_for_user.php');
        }
    }
    
    // Get user data
    $firstName = $_SESSION['authUser']['firstName'] ?? '';
    $lastName = $_SESSION['authUser']['lastName'] ?? '';
    $position = $_SESSION['authUser']['position'] ?? 'Employee';
    $userRole = $_SESSION['authUser']['userRole'] ?? '';
    ?>

    <div class="d-flex align-items-center justify-content-between">
      <b href="index.html" class="logo d-flex align-items-center">
        <img src="../../assets/img/ATTEND ROLL.png" alt="">
        <span class="d-none d-lg-block">ATTENDROLL</span>
      </b>
      <div class="sidebar-header">
        <button id="toggle-sidebar-btn" class="btn btn-sm btn-primary">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </div><!-- End Logo -->
    <!-- Toggle Button (place this in your main layout/header) -->

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle " href="#">
            <i class="bi bi-search"></i>
          </a>
        </li><!-- End Search Icon-->

        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="../../assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2">
              <?php 
                if (!empty($firstName) && !empty($lastName)) {
                  echo substr($firstName, 0, 1) . '. ' . $lastName;
                } else {
                  echo 'User';
                }
              ?>
            </span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6>
                <?php 
                  echo (!empty($firstName) && !empty($lastName)) 
                    ? $firstName . ' ' . $lastName 
                    : 'User Profile';
                ?>
              </h6>
              <span><?php echo $position; ?></span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.html">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.html">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="pages-faq.html">
                <i class="bi bi-question-circle"></i>
                <span>Need Help?</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="controller/logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->  