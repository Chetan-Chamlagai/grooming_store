<?php
// admin_login_process.php - Admin Authentication Handler
session_start();

// Include database connection
require_once 'db.php';

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.html?error=invalid_request");
    exit();
}

// Retrieve input fields safely
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate that fields are not empty
if (empty($email) || empty($password)) {
    header("Location: admin_login.html?error=empty_fields");
    exit();
}

// Prepared statement to fetch matching admin record
$stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1");

if (!$stmt) {
    // If the SQL query fails, catch it so it doesn't throw a 500 error
    die("Query Preparation Failed: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Raw/unencrypted password comparison
    if ($password === $row['password_hash']) {
        // Set admin session parameters
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_name'] = $row['full_name'];
        $_SESSION['admin_email'] = $row['email'];
        $_SESSION['admin_role'] = $row['role'];

        // Redirect to dashboard
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Incorrect password
        header("Location: admin_login.html?error=invalid_credentials");
        exit();
    }
} else {
    // User not found or not an admin
    header("Location: admin_login.html?error=invalid_credentials");
    exit();
}

$stmt->close();
$conn->close();
?>