<?php
// This file is for debugging purposes only. Remove in production.
session_start();
header('Content-Type: text/plain');

echo "SESSION DEBUG OUTPUT\n";
echo "===================\n\n";

if (empty($_SESSION)) {
    echo "No session data found!\n";
} else {
    echo "Session contains data:\n\n";
    
    if (isset($_SESSION['auth'])) {
        echo "auth = " . var_export($_SESSION['auth'], true) . "\n";
    }
    
    if (isset($_SESSION['authUser'])) {
        echo "\nauthUser contents:\n";
        foreach ($_SESSION['authUser'] as $key => $value) {
            echo "  $key = $value\n";
        }
    }
    
    echo "\nComplete session dump:\n";
    print_r($_SESSION);
}

echo "\n\nTo fix session issues, try to log out and log in again.\n";
?> 