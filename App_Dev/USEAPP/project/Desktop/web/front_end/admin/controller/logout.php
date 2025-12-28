<?php

session_start();

unset($_SESSION['auth']);
unset($_SESSION['userRole']);
unset($_SESSION['authUser']);

$_SESSION['message'] = "Logout Successfully";
$_SESSION['code'] = "success";
header("Location: ../../../login_attendroll.php");
exit(0);
?>