<?php
    session_start();
    include "db/conn.php";
    
    // For development, show errors. For production, turn this off.
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATION ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
        exit();
    }
    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
    $stmt->bind_param("ss", $userN, $psw);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        session_destroy();
        echo "<script>alert('Session error. Please login again.'); window.location.href='login.php';</script>";
        exit();
    }
    $loggedInUser = $result->fetch_assoc();
    $loggedInUserID = $loggedInUser['user_id'];

    // --- 2. FETCH CONVERSATIONS ---
    // This query finds all users with whom the logged-in user has a mutually accepted interest (status=1).
    // It groups them to ensure each person only appears once in the chat list.
    $conversations_stmt = $conn->prepare("
        SELECT
            other_user.user_id,
            other_user.user_name,
            other_user.user_img
        FROM tbl_chat
        JOIN tbl_user AS other_user ON
            (CASE
                WHEN tbl_chat.chat_senderID = ? THEN tbl_chat.chat_receiverID
                ELSE tbl_chat.chat_senderID
            END) = other_user.user_id
        WHERE
            (tbl_chat.chat_senderID = ? OR tbl_chat.chat_receiverID = ?)
            AND tbl_chat.interest_status = 1
        GROUP BY
            other_user.user_id
        ORDER BY
            MAX(tbl_chat.chat_date) DESC
    ");
    $conversations_stmt->bind_param("iii", $loggedInUserID, $loggedInUserID, $loggedInUserID);
    $conversations_stmt->execute();
    $conversations_result = $conversations_stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <title>Wedding Matrimony - My Dashboard</title>
    <!-- Your standard CSS includes -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "inc/header.php"; ?>
    <?php include "inc/bodystart.php"; ?>
    <?php include "inc/navbar.php"; ?>

    <section>
        <div class="db">
            <div class="container">
                <div class="row">
                    <!-- Left Navigation -->
                    <div class="col-md-4 col-lg-3">
                        <?php 
                            // This single line replaces your hardcoded sidebar
                            include "inc/dashboard_nav.php"; 
                        ?>
                    </div>

                    <!-- Right Content: Chat List -->
                    <div class="col-md-8 col-lg-9">
                        <div class="db-sec-com">
                            <h2 class="db-tit">My Chat List</h2>
                            <div class="db-pro-stat">
                                <div class="db-inte-prof-list">
                                    <ul>
                                        <?php if ($conversations_result && $conversations_result->num_rows > 0): ?>
                                            <?php while ($row = $conversations_result->fetch_assoc()): ?>
                                                <!-- Each list item is a link to the specific chat window -->
                                                <a href="open-chat.php?receiver_id=<?php echo $row['user_id']; ?>" style="color: inherit; text-decoration: none;">
                                                    <li style="cursor: pointer;">
                                                        <div class="db-int-pro-1"> 
                                                            <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile Image">
                                                        </div>
                                                        <div class="db-int-pro-2">
                                                            <h5><?php echo htmlspecialchars($row['user_name']); ?></h5>
                                                            <ol class="poi poi-date"><li>Click to open conversation</li></ol>
                                                        </div>
                                                        <div class="db-int-pro-3">
                                                            <span class="btn btn-primary btn-sm">Open Chat</span>
                                                        </div>
                                                    </li>
                                                </a>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <li class="text-center p-5">
                                                You have no active chats. Go to your interests and accept a request, or wait for someone to accept yours to begin chatting.
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JS Includes -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>