<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = $_POST['category'];
        $photo = trim($_POST['photo']);
        $stock = intval($_POST['stock_quantity']);

        $stmt = $conn->prepare("INSERT INTO products (title, description, price, category, photo, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", $title, $description, $price, $category, $photo, $stock);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = intval($_POST['product_id']);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: admin_dashboard.php");
exit();
?>