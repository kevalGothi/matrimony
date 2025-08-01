<?php
// Start the session
session_start();

// Destroy the session data
session_unset();
session_destroy();

// Optionally, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
              $params["path"], 
              $params["domain"], 
              $params["secure"], 
              $params["httponly"]);
}

// Redirect to a different page, such as the login page
header("Location: login/");
exit();
?>