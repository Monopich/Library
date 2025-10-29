<?php
session_start();
include('includes/config.php');

// Clear all session variables
$_SESSION = array();

// If it's set, destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear RTC and Library tokens to ensure complete logout
setcookie('access_token', '', time() - 3600, '/');
setcookie('library_token', '', time() - 3600, '/');

// Optional: Redirect to RTC logout endpoint if you want to logout from RTC too
// header('Location: https://api.rtc-bb.camai.kh/api/auth/logout');

// Redirect to login page
header("Location: index.php");
exit;
?>