<?php
session_start();

require_once '../db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.html?error=invalid_request");
    exit;
}

// Get submitted values
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate empty fields
if ($email === '' || $password === '') {
    header("Location: admin_login.html?error=empty_fields");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: admin_login.html?error=invalid_email");
    exit;
}

// Find admin by email
$sql = "SELECT id, full_name, email, password_hash, role
        FROM users
        WHERE email = ? AND role = 'admin'
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: admin_login.html?error=server_error");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Email does not exist or user is not an admin
    $stmt->close();
    $conn->close();

    header("Location: admin_login.html?error=invalid_credentials");
    exit;
}

$user = $result->fetch_assoc();

// Compare raw password
if ($password !== $user['password_hash']) {
    $stmt->close();
    $conn->close();

    header("Location: admin_login.html?error=invalid_credentials");
    exit;
}

// Login successful
session_regenerate_id(true);

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_name'] = $user['full_name'];
$_SESSION['admin_email'] = $user['email'];
$_SESSION['admin_role'] = $user['role'];

$stmt->close();
$conn->close();

// Go to dashboard
header("Location: dashboard.html");
exit;
?>
