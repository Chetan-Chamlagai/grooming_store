<?php
// submit_inquiry.php - Processes and saves customer contact inquiries
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $subject    = trim($_POST['subject'] ?? 'General Inquiry');
    $message    = trim($_POST['message'] ?? '');

    if (!empty($first_name) && !empty($email) && !empty($message)) {
        // Let MySQL handle 'status' default ('unread') and 'created_at' (current_timestamp)
        $stmt = $conn->prepare("INSERT INTO inquiries (first_name, last_name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $subject, $message);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?inquiry=success#inquiry");
            exit();
        } else {
            $stmt->close();
            echo "<script>alert('Error saving your inquiry: " . addslashes($conn->error) . "'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}