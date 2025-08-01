<?php

session_start();


// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Include your database connection file
    include "../db/conn.php";
    
    // It's generally better to let PHP display errors during development
    // and handle them gracefully in production, rather than using error_reporting(0).
    
    $username = $_POST['username'];
    $pass = $_POST['pass'];

    // --- SECURE DATABASE QUERY ---
    // Step 1: Use a prepared statement to prevent SQL Injection.
    // The query looks for a user with a matching email and password.
    $stmt = $conn->prepare("SELECT * FROM tbl_admin WHERE ad_email = ? AND ad_pass = ?");
    
    // Step 2: Bind the user-provided variables to the query placeholders.
    // "ss" means both variables are treated as strings.
    $stmt->bind_param("ss", $username, $pass);

    // Step 3: Execute the prepared statement.
    $stmt->execute();

    // Step 4: Get the result of the query.
    $result = $stmt->get_result();

    // Check if the query returned exactly one matching user.
    if ($result->num_rows > 0) {
        // --- SUCCESSFUL LOGIN ---
        $fetch_admin = $result->fetch_assoc();
        
        // Store user information in the session.
        // It's better to store a non-sensitive identifier like a user ID or email.
        $_SESSION['username'] = $fetch_admin['ad_email'];
        $_SESSION['pass'] = $fetch_admin['ad_pass'];
        // IMPORTANT: Never store the password in the session.
        // The line `$_SESSION['pass'] = $pass;` has been correctly removed.

        // Redirect the user to the admin dashboard.
        header("Location: ./index.php");
        
        // Always call exit() after a header redirect to stop script execution.
        exit(); 
    } else {
        // --- FAILED LOGIN ---
        // If no user was found, show an alert and send the user back to the login page.
        echo "<script>
                alert('Invalid credentials. Please try again.'); 
                window.history.back();
              </script>";
        // Stop the script to prevent the rest of the HTML from loading.
        exit();
    }
    
    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

