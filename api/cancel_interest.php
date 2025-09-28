<?php
// Set the content type to JSON for the AJAX response
header('Content-Type: application/json');

// Start the session to access logged-in user data
session_start();

// Include the database connection file
// Using 'require_once' is safer as it will throw a fatal error if the file is not found.
require_once '../db/conn.php'; 

// Initialize the response array
$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred.'
];

// --- 1. Authentication ---
// Check if the user is logged in. If not, send an error and exit.
if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    $response['message'] = 'Authentication required. Please login again.';
    echo json_encode($response);
    exit();
}

// --- 2. Input Validation ---
// Check if this script was accessed via a POST request and if chat_id is set.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['chat_id'])) {
    $response['message'] = 'Invalid request method or missing parameters.';
    echo json_encode($response);
    exit();
}

// Sanitize the input to ensure it's an integer
$chat_id = (int)$_POST['chat_id'];

if ($chat_id <= 0) {
    $response['message'] = 'Invalid Chat ID.';
    echo json_encode($response);
    exit();
}


try {
    // --- 3. Verify User Identity and Get ID ---
    // We must fetch the user's ID from the database to ensure they are who they say they are.
    $stmt_user = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
    $stmt_user->bind_param("ss", $_SESSION['username'], $_SESSION['password']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows === 0) {
        $response['message'] = 'Your session is invalid. Please login again.';
        echo json_encode($response);
        exit();
    }
    $loggedInUser = $result_user->fetch_assoc();
    $loggedInUserID = $loggedInUser['user_id'];

    // --- 4. Perform the Deletion ---
    // The query deletes the interest request.
    // CRITICAL: The "AND chat_senderID = ?" clause ensures that users can ONLY delete interests they have sent themselves.
    $stmt_delete = $conn->prepare("DELETE FROM tbl_chat WHERE chat_id = ? AND chat_senderID = ?");
    $stmt_delete->bind_param("ii", $chat_id, $loggedInUserID);
    $stmt_delete->execute();

    // --- 5. Check if the deletion was successful ---
    // We check if any row was actually affected by the delete query.
    if ($stmt_delete->affected_rows > 0) {
        // If one row was deleted, it was a success.
        $response['status'] = 'success';
        $response['message'] = 'Interest cancelled successfully.';
    } else {
        // If zero rows were affected, it means the chat_id didn't exist or the user didn't have permission to delete it.
        $response['message'] = 'Interest not found or you do not have permission to cancel it.';
    }

} catch (Exception $e) {
    // This will catch any unexpected database errors.
    // It's good practice to log the actual error for your own debugging.
    error_log("Error in cancel_interest.php: " . $e->getMessage());
    $response['message'] = 'A server error occurred. Please try again later.';
}

// --- 6. Send the final JSON response ---
echo json_encode($response);
exit();
?>