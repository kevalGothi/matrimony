<?php
// 1. Initialize the session
// You must start the session before you can destroy it.
session_start();

// 2. Unset all session variables
// This clears all the data stored in the $_SESSION array.
$_SESSION = array();

// 3. Destroy the session
// This completely ends the session on the server.
session_destroy();

// 4. Redirect to the login page
// After logging out, the user is sent back to the login screen.
header("Location: login.php");
exit(); // It's a good practice to call exit() after a header redirect to prevent further script execution.
?>