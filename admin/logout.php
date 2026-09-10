<?php
// admin/logout.php - Admin Session Destruction & Redirection Script

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if configured
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session storage on the server
session_destroy();

// Redirect back to the admin login page
header("Location: login.php");
exit();