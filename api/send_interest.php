<?php
session_start();
// The path to your database connection file may need to be adjusted.
// This assumes 'conn.php' is inside a 'db' folder in the main directory.
include "../db/conn.php"; 

// We will return JSON responses to the AJAX call
header('Content-Type: application/json');

// --- 1. AUTHENTICATION ---
// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    // Return an error message in JSON format and stop the script
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed. Please login.']);
    exit();
}

$userN = $_SESSION['username'];
$psw = $_SESSION['password'];
$stmt = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
$stmt->bind_param("ss", $userN, $psw);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Session error. Please login again.']);
    exit();
}
$loggedInUser = $result->fetch_assoc();
$loggedInUserID = $loggedInUser['user_id'];

// --- 2. VALIDATE INPUT AND PROCESS THE INTEREST ---
// Check if the receiver's ID was sent via POST request
if (!isset($_POST['receiver_id']) || !filter_var($_POST['receiver_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid profile ID.']);
    exit();
}
$receiverID = (int)$_POST['receiver_id'];

// A user cannot send an interest to themselves
if ($receiverID === $loggedInUserID) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot send an interest to yourself.']);
    exit();
}

// Check if an interest or chat already exists to prevent duplicates
$check_stmt = $conn->prepare("SELECT chat_id FROM tbl_chat WHERE (chat_senderID = ? AND chat_receiverID = ?) OR (chat_senderID = ? AND chat_receiverID = ?)");
$check_stmt->bind_param("iiii", $loggedInUserID, $receiverID, $receiverID, $loggedInUserID);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'An interest has already been sent or received.']);
    exit();
}

// If all checks pass, insert the new interest into the database
$interest_message = "I am interested in your profile.";
// The interest_status '0' means 'Pending'
$insert_stmt = $conn->prepare("INSERT INTO tbl_chat (chat_senderID, chat_receiverID, chat_message, interest_status) VALUES (?, ?, ?, 0)");
$insert_stmt->bind_param("iis", $loggedInUserID, $receiverID, $interest_message);

if ($insert_stmt->execute()) {
    // Send a success response
    echo json_encode(['status' => 'success', 'message' => 'Interest sent successfully!']);
} else {
    // Send a failure response if the database query fails
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again.']);
}

$insert_stmt->close();
$conn->close();
exit();