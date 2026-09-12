<?php

$servername = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'localhost');
$username = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
$passwordEnv = getenv('DB_PASSWORD');
if ($passwordEnv === false) {
    $passwordEnv = getenv('MYSQL_PASSWORD');
}
$password = ($passwordEnv === false) ? '' : $passwordEnv;
$database = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'it322');

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
