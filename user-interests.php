<?php
    session_start();
    include "db/conn.php";
    
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

    // --- 2. HANDLE ACTIONS (Using redirects for a clean workflow) ---
    // A. Handle SENDING a new interest
    if (isset($_GET['send_interest'])) {
        $receiverID = (int)$_GET['send_interest'];
        $check_stmt = $conn->prepare("SELECT chat_id FROM tbl_chat WHERE (chat_senderID = ? AND chat_receiverID = ?) OR (chat_senderID = ? AND chat_receiverID = ?)");
        $check_stmt->bind_param("iiii", $loggedInUserID, $receiverID, $receiverID, $loggedInUserID);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            $interest_message = "I am interested in your profile.";
            $insert_stmt = $conn->prepare("INSERT INTO tbl_chat (chat_senderID, chat_receiverID, chat_message, interest_status) VALUES (?, ?, ?, 0)");
            $insert_stmt->bind_param("iis", $loggedInUserID, $receiverID, $interest_message);
            $insert_stmt->execute();
        }
        header("Location: user-interests.php");
        exit();
    }
    
    // B. Handle ACCEPTING or DENYING an interest
    if (isset($_GET['action']) && in_array($_GET['action'], ['accept', 'deny']) && isset($_GET['chat_id'])) {
        $chat_id = (int)$_GET['chat_id'];
        $new_status = ($_GET['action'] === 'accept') ? 1 : 2;
        $update_stmt = $conn->prepare("UPDATE tbl_chat SET interest_status = ? WHERE chat_id = ? AND chat_receiverID = ?");
        $update_stmt->bind_param("iii", $new_status, $chat_id, $loggedInUserID);
        $update_stmt->execute();
        header("Location: user-interests.php");
        exit();
    }

    // C. Handle CANCELING an interest
    if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['chat_id'])) {
        $chat_id = (int)$_GET['chat_id'];
        $delete_stmt = $conn->prepare("DELETE FROM tbl_chat WHERE chat_id = ? AND chat_senderID = ? AND interest_status = 0");
        $delete_stmt->bind_param("ii", $chat_id, $loggedInUserID);
        $delete_stmt->execute();
        header("Location: user-interests.php");
        exit();
    }
    
    // --- 3. FETCH ALL INTERESTS DATA (Using Correct and Secure Queries) ---
    $sql_received_base = "SELECT c.*, u.user_id, u.user_name, u.user_city, u.user_age, u.user_img FROM tbl_chat c JOIN tbl_user u ON c.chat_senderID = u.user_id WHERE c.chat_receiverID = ? AND c.interest_status = ?";
    
    $stmt_new = $conn->prepare($sql_received_base . " ORDER BY c.chat_date DESC");
    $status_new = 0; $stmt_new->bind_param("ii", $loggedInUserID, $status_new); $stmt_new->execute();
    $new_requests_result = $stmt_new->get_result();

    $stmt_accepted = $conn->prepare($sql_received_base . " ORDER BY c.chat_date DESC");
    $status_accepted = 1; $stmt_accepted->bind_param("ii", $loggedInUserID, $status_accepted); $stmt_accepted->execute();
    $accepted_requests_result = $stmt_accepted->get_result();

    $stmt_denied = $conn->prepare($sql_received_base . " ORDER BY c.chat_date DESC");
    $status_denied = 2; $stmt_denied->bind_param("ii", $loggedInUserID, $status_denied); $stmt_denied->execute();
    $denied_requests_result = $stmt_denied->get_result();

    $sql_sent = "SELECT c.*, u.user_id, u.user_name, u.user_img FROM tbl_chat c JOIN tbl_user u ON c.chat_receiverID = u.user_id WHERE c.chat_senderID = ? AND c.interest_status != 9 ORDER BY c.chat_date DESC";
    $stmt_sent = $conn->prepare($sql_sent);
    $stmt_sent->bind_param("i", $loggedInUserID); $stmt_sent->execute();
    $sent_requests_result = $stmt_sent->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <title>Wedding Matrimony - My Interests</title>
    <link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/style.css">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include "inc/navbar.php"; ?>
    <section><div class="db"><div class="container"><div class="row">
        <!-- Left Navigation -->
        <div class="col-md-4 col-lg-3">
            <?php include "inc/dashboard_nav.php"; ?>
        </div>
        <!-- Right Content -->
        <div class="col-md-8 col-lg-9"><div class="db-sec-com">
            <h2 class="db-tit">Interest Dashboard</h2>
            <div class="db-pro-stat"><div class="db-inte-main">
                <!-- TABS NAVIGATION -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#home">New Requests <span class="badge bg-danger"><?php echo $new_requests_result->num_rows; ?></span></a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu1">Accepted By Me</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu3">Interests Sent By Me</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#menu2">Denied By Me</a></li>
                </ul>
                <div class="tab-content">
                    <!-- TAB 1: NEW REQUESTS RECEIVED -->
                    <div id="home" class="container tab-pane active"><br><div class="db-inte-prof-list"><ul>
                        <?php if ($new_requests_result->num_rows > 0): while ($row = $new_requests_result->fetch_assoc()): ?>
                            <li>
                                <div class="db-int-pro-1"><img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile"></div>
                                <div class="db-int-pro-2">
                                    <h5><?php echo htmlspecialchars($row['user_name']); ?></h5> 
                                    <ol class="poi"><li>City: <strong><?php echo htmlspecialchars($row['user_city']); ?></strong></li><li>Age: <strong><?php echo htmlspecialchars($row['user_age']); ?></strong></li></ol>
                                    <ol class="poi poi-date"><li>Request on: <?php echo date("d M Y, h:i A", strtotime($row['chat_date'])); ?></li></ol>
                                    <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                </div>
                                <div class="db-int-pro-3">
                                    <a href="user-interests.php?action=accept&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-success btn-sm">Accept</a>
                                    <a href="user-interests.php?action=deny&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm">Deny</a>
                                </div>
                            </li>
                        <?php endwhile; else: ?><li class="text-center p-5">No new interest requests found.</li><?php endif; ?>
                    </ul></div></div>
                    <!-- TAB 2: REQUESTS YOU ACCEPTED -->
                    <div id="menu1" class="container tab-pane fade"><br><div class="db-inte-prof-list"><ul>
                        <?php if ($accepted_requests_result->num_rows > 0): while ($row = $accepted_requests_result->fetch_assoc()): ?>
                            <li>
                                <div class="db-int-pro-1"><img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile"></div>
                                <div class="db-int-pro-2">
                                    <h5><?php echo htmlspecialchars($row['user_name']); ?></h5> 
                                    <ol class="poi poi-date"><li>You accepted this interest.</li></ol>
                                    <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                </div>
                                <div class="db-int-pro-3">
                                    <a href="open-chat.php?receiver_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Chat Now</a>
                                    <a href="user-interests.php?action=deny&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm">Deny</a>
                                </div>
                            </li>
                        <?php endwhile; else: ?><li class="text-center p-5">You have not accepted any requests.</li><?php endif; ?>
                    </ul></div></div>
                    <!-- TAB 3: INTERESTS YOU SENT -->
                    <div id="menu3" class="container tab-pane fade"><br><div class="db-inte-prof-list"><ul>
                        <?php if ($sent_requests_result->num_rows > 0): while ($row = $sent_requests_result->fetch_assoc()): ?>
                        <li>
                            <div class="db-int-pro-1"><img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile"></div>
                            <div class="db-int-pro-2">
                                <h5>To: <?php echo htmlspecialchars($row['user_name']); ?></h5>
                                <ol class="poi poi-date"><li>You sent on: <?php echo date("d M Y, h:i A", strtotime($row['chat_date'])); ?></li></ol>
                                <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                            </div>
                            <div class="db-int-pro-3">
                                <?php if ($row['interest_status'] == 0): ?>
                                    <span class="btn btn-warning btn-sm disabled">Pending</span>
                                    <a href="user-interests.php?action=cancel&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm confirm-action-btn" data-title="Cancel Interest?" data-text="Are you sure you want to cancel the interest you sent?" data-confirm-text="Yes, cancel it!">Cancel</a>
                                <?php elseif ($row['interest_status'] == 1): ?>
                                    <span class="btn btn-success btn-sm disabled">Accepted</span>
                                    <a href="open-chat.php?receiver_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Chat Now</a>
                                <?php elseif ($row['interest_status'] == 2): ?>
                                    <span class="btn btn-danger btn-sm disabled">Denied by them</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endwhile; else: ?><li class="text-center p-5">You have not sent any interests yet.</li><?php endif; ?>
                    </ul></div></div>
                    <!-- TAB 4: REQUESTS YOU DENIED -->
                    <div id="menu2" class="container tab-pane fade"><br><div class="db-inte-prof-list"><ul>
                        <?php if ($denied_requests_result->num_rows > 0): while ($row = $denied_requests_result->fetch_assoc()): ?>
                        <li>
                            <div class="db-int-pro-1"><img src="upload/default-profile.png" alt="Profile"></div>
                            <div class="db-int-pro-2">
                                <h5><?php echo htmlspecialchars($row['user_name']); ?></h5>
                                <ol class="poi poi-date"><li>You denied this request.</li></ol>
                            </div>
                            <div class="db-int-pro-3"><a href="user-interests.php?action=accept&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-success btn-sm">Accept</a></div>
                        </li>
                        <?php endwhile; else: ?><li class="text-center p-5">You have not denied any requests.</li><?php endif; ?>
                    </ul></div></div>
                </div>
            </div></div>
        </div></div>
    </div></div></div></section>
    <script src="js/jquery.min.js"></script><script src="js/bootstrap.min.js"></script><script src="js/custom.js"></script> 
</body>
</html>