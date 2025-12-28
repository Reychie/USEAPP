<?php

include('config.php');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Only POST method allowed');
}


$json = file_get_contents('php://input');
$data = json_decode($json, true);


if (!isset($data['email']) || !isset($data['password'])) {
    sendResponse(false, 'Email and password are required');
}

$email = sanitizeInput($data['email']);
$password = sanitizeInput($data['password']);


$login_query = "SELECT `userId`, `firstName`, `lastName`, `email`, `password`, `phoneNumber`, `gender`, `birthday`, `verification`, `userRole` FROM `users` WHERE email = '$email' AND password = '$password' LIMIT 1;";
$login_query_run = mysqli_query($conn, $login_query);

if ($login_query_run) {
    if (mysqli_num_rows($login_query_run) > 0) {
        $userData = mysqli_fetch_assoc($login_query_run);
        
        $userId = $userData['userId'];
        $fullName = $userData['firstName'] . ' ' . $userData['lastName'];
        $emailAddress = $userData['email'];
        $verification = $userData['verification'];
        $userRole = $userData['userRole'];

        
        $user = [
            'userId' => $userId,
            'fullName' => $fullName,
            'email' => $emailAddress,
            'userRole' => $userRole
        ];

        sendResponse(true, 'Login successful', $user);
    } else {
        sendResponse(false, 'Invalid credentials');
    }
} else {
    sendResponse(false, 'Database error: ' . mysqli_error($conn));
}
?> 
