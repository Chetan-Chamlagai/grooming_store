<?php
// get-user.php - API Endpoint to Fetch Logged-in User Details
session_start();
header('Content-Type: application/json');

// Include database connection (adjust path if placed inside an api/ subfolder)
require_once '../db.php';

// Verify if a user or admin session is active
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Please log in first.'
    ]);
    exit();
}

// Retrieve the active user ID from the session
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'];

// Fetch user information (excluding the sensitive password hash)
$stmt = $conn->prepare("SELECT id, full_name, email, phone_number, role, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $row
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'User record not found.'
    ]);
}

$stmt->close();
$conn->close();
?>