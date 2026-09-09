<?php
// login.php - Handles both Customer Logins
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Direct comparison since plain-text is currently used
        if ($password === $user['password_hash']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            $redirectUrl = ($user['role'] === 'admin') ? 'admin/dashboard.php' : 'index.html';

            echo json_encode([
                'success' => true, 
                'role' => $user['role'], 
                'redirect' => $redirectUrl
            ]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    $stmt->close();
}
$conn->close();
?>