<?php
    session_start();
    include "db/conn.php";

    // --- 1. AUTHENTICATION & INITIALIZATION ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
        exit();
    }

    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];

    $user_query = mysqli_query($conn, "SELECT * FROM tbl_user WHERE user_phone = '$userN' AND user_pass = '$psw'");
    
    if (!$user_query || mysqli_num_rows($user_query) == 0) {
        echo "<script>alert('Session error. Please login again.'); window.location.href='login.php';</script>";
        exit();
    }
    
    $loggedInUser = mysqli_fetch_assoc($user_query);
    $loggedInUserID = $loggedInUser['user_id'];
    $receiverID = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

    if ($receiverID === 0 || $receiverID === $loggedInUserID) {
        die("Invalid chat session.");
    }

    // --- 2. SECURITY CHECK: Verify that an ACCEPTED interest exists ---
    $auth_stmt = $conn->prepare("SELECT chat_id FROM tbl_chat WHERE interest_status = 1 AND ((chat_senderID = ? AND chat_receiverID = ?) OR (chat_senderID = ? AND chat_receiverID = ?))");
    $auth_stmt->bind_param("iiii", $loggedInUserID, $receiverID, $receiverID, $loggedInUserID);
    $auth_stmt->execute();
    $auth_result = $auth_stmt->get_result();

    if ($auth_result->num_rows === 0) {
        // This is the security block that prevents unauthorized access.
        echo "<script>alert('You are not authorized to chat with this user. An interest must be mutually accepted first.'); window.location.href='user-chat.php';</script>";
        exit();
    }

    // --- 3. HANDLE SENDING A NEW MESSAGE ---
    if (isset($_POST['send_message'])) {
        $message = trim($_POST['message']);
        if (!empty($message)) {
            // We use status '9' to indicate a chat message.
            $insert_stmt = $conn->prepare("INSERT INTO tbl_chat (chat_senderID, chat_receiverID, chat_message, interest_status) VALUES (?, ?, ?, 9)");
            $insert_stmt->bind_param("iis", $loggedInUserID, $receiverID, $message);
            if ($insert_stmt->execute()) {
                // Redirect to the same page to show the new message and prevent re-submission on refresh
                header("Location: open-chat.php?receiver_id=" . $receiverID);
                exit();
            }
        }
    }

    // --- 4. FETCH DATA FOR DISPLAY ---
    $receiver_query = mysqli_query($conn, "SELECT user_name, user_img FROM tbl_user WHERE user_id = '$receiverID'");
    $receiverUser = mysqli_fetch_assoc($receiver_query);

    // Fetch the conversation: status 1 (the "accepted" message) and 9 (all chat messages)
    $messages_query = mysqli_query($conn, "SELECT * FROM tbl_chat WHERE ((chat_senderID = '$loggedInUserID' AND chat_receiverID = '$receiverID') OR (chat_senderID = '$receiverID' AND chat_receiverID = '$loggedInUserID')) AND interest_status IN (1, 9) ORDER BY chat_date ASC");

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chat with <?php echo htmlspecialchars($receiverUser['user_name']); ?></title>
    <!-- Standard CSS -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <!-- Custom Chat Styles -->
    <style>
        body { background-color: #f0f2f5; font-family: Arial, sans-serif; }
        .chat-container { max-width: 800px; margin: 30px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 85vh; }
        .chat-header { padding: 15px; border-bottom: 1px solid #ddd; display: flex; align-items: center; background-color: #f7f7f7; }
        .chat-header .back-btn { font-size: 1.2rem; color: #333; }
        .chat-header img { width: 45px; height: 45px; border-radius: 50%; margin-right: 15px; }
        .chat-header h5 { margin: 0; font-weight: 600; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; }
        .chat-message { max-width: 70%; padding: 10px 15px; border-radius: 18px; margin-bottom: 10px; line-height: 1.4; word-wrap: break-word; }
        .message-sent { background-color: #0084ff; color: white; align-self: flex-end; }
        .message-received { background-color: #e4e6eb; color: #050505; align-self: flex-start; }
        .system-message { text-align: center; color: #888; font-size: 0.85rem; margin: 10px 0; }
        .chat-footer { padding: 15px; border-top: 1px solid #ddd; background-color: #f7f7f7; }
        .chat-footer form { display: flex; }
        .chat-footer input { flex: 1; border-radius: 18px; border: 1px solid #ccc; padding: 10px 15px; }
        .chat-footer button { margin-left: 10px; border-radius: 50%; width: 45px; height: 45px; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <a href="user-chat.php" class="back-btn me-3"><i class="fa fa-arrow-left"></i></a>
            <img src="upload/<?php echo !empty($receiverUser['user_img']) ? htmlspecialchars($receiverUser['user_img']) : 'default-profile.png'; ?>" alt="Profile">
            <h5><?php echo htmlspecialchars($receiverUser['user_name']); ?></h5>
        </div>
        <div class="chat-body" id="chat-body">
            <?php while($msg = mysqli_fetch_assoc($messages_query)): ?>
                <?php if($msg['interest_status'] == 1): ?>
                    <div class="system-message">Interest was accepted. You can now chat.</div>
                <?php else: ?>
                    <div class="chat-message <?php echo ($msg['chat_senderID'] == $loggedInUserID) ? 'message-sent' : 'message-received'; ?>">
                        <?php echo nl2br(htmlspecialchars($msg['chat_message'])); ?>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>
        <div class="chat-footer">
            <form method="POST" action="">
                <input type="text" name="message" placeholder="Type a message..." autocomplete="off" required>
                <button type="submit" name="send_message" class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
    <!-- Script to automatically scroll to the most recent message -->
    <script>
        const chatBody = document.getElementById('chat-body');
        chatBody.scrollTop = chatBody.scrollHeight;
    </script>
</body>
</html>