<?php

include('config.php');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Only POST method allowed');
}


$json = file_get_contents('php:
$data = json_decode($json, true);


$requiredFields = ['firstName', 'lastName', 'email', 'password', 'phoneNumber', 'gender', 'birthday'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        sendResponse(false, ucfirst($field) . ' is required');
    }
}


$firstName = sanitizeInput($data['firstName']);
$lastName = sanitizeInput($data['lastName']);
$email = sanitizeInput($data['email']);
$password = sanitizeInput($data['password']);
$phoneNumber = sanitizeInput($data['phoneNumber']);
$gender = sanitizeInput($data['gender']);
$birthday = sanitizeInput($data['birthday']);
$userRole = 'user'; 


$check_query = "SELECT * FROM users WHERE email = '$email'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    sendResponse(false, 'Email already registered');
}


$insert_query = "INSERT INTO users (firstName, lastName, email, password, phoneNumber, gender, birthday, verification, userRole)
                VALUES ('$firstName', '$lastName', '$email', '$password', '$phoneNumber', '$gender', '$birthday', 'verified', '$userRole')";
                
if (mysqli_query($conn, $insert_query)) {
    $userId = mysqli_insert_id($conn);
    
    
    $user = [
        'userId' => $userId,
        'fullName' => $firstName . ' ' . $lastName,
        'email' => $email,
        'userRole' => $userRole
    ];
    
    sendResponse(true, 'Registration successful', $user);
} else {
    sendResponse(false, 'Registration failed: ' . mysqli_error($conn));
}
?> 
