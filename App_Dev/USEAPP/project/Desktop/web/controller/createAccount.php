<?php 
include("../dB/config.php");
session_start();

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST["register"])) {
    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $cpassword = $_POST["cpassword"];
    $phoneNumber = $_POST["phoneNumber"];
    $gender = $_POST["gender"];
    $userRole = $_POST["role"];
    $birthday = $_POST["birthday"];
    
    // Validate password
    if($password != $cpassword){
        $_SESSION["status"] = "Password do not match";
        $_SESSION["status_code"] = "error";
        header("location: ../register.php");
        exit(0);
    }

    // Check if email exists 
    $checkQuery = "SELECT * FROM `users` WHERE email = '$email'";
    $result = mysqli_query($conn, $checkQuery);

    if(mysqli_num_rows($result) > 0){
        $_SESSION["status"] = "Email already exists";
        $_SESSION["status_code"] = "error";
        header("location: ../register.php");
        exit(0);
    }

    // Insert user data
    $query = "INSERT INTO `users`(`firstName`, `lastName`, `email`, `password`, `phoneNumber`, `gender`, `birthday`, `userRole`) VALUES 
    ('$firstName','$lastName','$email','$password','$phoneNumber','$gender','$birthday','$userRole')";

    if (mysqli_query($conn, $query)){
        $_SESSION["status"] = "Registration Success";
        $_SESSION["status_code"] = "success";    
        header("location: ../login_attendroll.php");
        exit(0);
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>