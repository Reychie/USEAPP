<?php
session_start();
include("../dB/config.php");

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $login_query = "SELECT `userId`, `firstName`, `lastName`, `email`, `password`, `phoneNumber`, `gender`, `birthday`, `verification`, `userRole` FROM `users` WHERE email = '$email' AND password = '$password' LIMIT 1;";

    $login_query_run = mysqli_query($conn, $login_query);

    if($login_query_run){
        if(mysqli_num_rows($login_query_run) > 0){
            $data = mysqli_fetch_assoc($login_query_run);

            $userId = $data['userId'];
            $firstName = $data['firstName'];
            $lastName = $data['lastName'];
            $fullName = $firstName . ' ' . $lastName;
            $emailAddress = $data['email'];
            $verification = $data['verification'];
            $userRole = $data['userRole'];
            
            // Set position based on user role
            $position = ($userRole == 'admin') ? 'Administrator' : 'Employee';

            // Debug logging
            error_log("User login: ID=$userId, Name=$fullName, Email=$emailAddress, Role=$userRole");

            $_SESSION['auth'] = true;
            $_SESSION['authUser'] = [
                'userId' => $userId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'fullName'=> $fullName,
                'userEmail' => $emailAddress,
                'userRole' => $userRole,
                'position' => $position
            ];

            // Debug logging of session
            error_log("Session set: " . print_r($_SESSION, true));

            if($userRole == 'admin'){
                header("Location: ../front_end/admin/Dashboard_Attendroll.php");
            }else if($userRole == 'user'){
                header("Location: ../front_end/users/Dashboard.php");
            }else{
                header("Location: ../login_attendroll.php");
            }
            exit();
        }
        else{
            $_SESSION['message'] = "Invalid Credentials";
            $_SESSION['code'] = "error";
            header("Location: ../login_attendroll.php");
        }
    }
    else{
        $_SESSION['message'] = "There is something wrong in the code".mysqli_error($conn);
        $_SESSION['code'] = "error";
        header("Location: ../login_attendroll.php");
    }
}
else{  
    $_SESSION['message'] = "There is something wrong in the code".mysqli_error($conn);
    $_SESSION['code'] = "error";
    header("Location: ../login_attendroll.php"); 
}
?>