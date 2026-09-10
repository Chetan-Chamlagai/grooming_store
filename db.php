<?php
// db.php - Database Connection Configuration for XAMPP MySQL
$host = 'localhost';
$db   = 'grooming_store';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
               