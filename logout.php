<?php
// logout.php - Session Termination Module for Oggentleme
session_start();

// Empty session array
$_SESSION = [];

// Invalidate session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session data from server storage
session_destroy();

// Redirect back to landing page
header("Location: index.php");
exit();