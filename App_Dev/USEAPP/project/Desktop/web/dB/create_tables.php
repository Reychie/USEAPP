<?php
// Include database configuration
include('config.php');

// Create users table if not exists
$usersTable = "CREATE TABLE IF NOT EXISTS users (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    userRole ENUM('admin', 'user') DEFAULT 'user',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($usersTable) === TRUE) {
    echo "Users table created or already exists.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create attendance table if not exists
$attendanceTable = "CREATE TABLE IF NOT EXISTS attendance (
    attendanceId INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    date DATE NOT NULL,
    timeIn TIME NOT NULL,
    timeOut TIME DEFAULT NULL,
    status ENUM('Present', 'Late', 'Absent') DEFAULT 'Present',
    FOREIGN KEY (userId) REFERENCES users(userId)
)";

if ($conn->query($attendanceTable) === TRUE) {
    echo "Attendance table created or already exists.<br>";
} else {
    echo "Error creating attendance table: " . $conn->error . "<br>";
}

// Create salary table if not exists
$salaryTable = "CREATE TABLE IF NOT EXISTS salary (
    salaryId INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    basicSalary DECIMAL(10,2) NOT NULL,
    overtime DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    totalSalary DECIMAL(10,2) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(userId),
    UNIQUE KEY user_month_year (userId, month, year)
)";

if ($conn->query($salaryTable) === TRUE) {
    echo "Salary table created or already exists.<br>";
} else {
    echo "Error creating salary table: " . $conn->error . "<br>";
}

// Create payslip table if not exists
$payslipTable = "CREATE TABLE IF NOT EXISTS payslip (
    payslipId INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    salaryId INT NOT NULL,
    pdfPath VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(userId),
    FOREIGN KEY (salaryId) REFERENCES salary(salaryId),
    UNIQUE KEY user_salary (userId, salaryId)
)";

if ($conn->query($payslipTable) === TRUE) {
    echo "Payslip table created or already exists.<br>";
} else {
    echo "Error creating payslip table: " . $conn->error . "<br>";
}

// Create directory for payslip PDFs if not exists
$uploadDir = '../uploads/payslips';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo "Directory for payslip PDFs created.<br>";
    } else {
        echo "Failed to create directory for payslip PDFs.<br>";
    }
} else {
    echo "Directory for payslip PDFs already exists.<br>";
}

echo "Database setup completed.";
?> 