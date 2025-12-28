<?php

session_start();
include(__DIR__ . "/../dB/config.php");

if(!isset($_SESSION['auth'])){
    $_SESSION['message'] = "Login to access Dashboard";
    $_SESSION['code'] = "error";
    header("Location: ../../login_attendroll.php");
    exit();
}else{
    if($_SESSION['authUser']['userRole'] != 'user'){
        $_SESSION['message'] = "You are not authorized to access this page";
        $_SESSION['code'] = "error";
        header("Location: ../../login_attendroll.php");
        exit();
    }
}

?>