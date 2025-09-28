<?php
session_start();
include "db/conn.php";

// --- 1. AUTHENTICATION ---
if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
$stmt->bind_param("ss", $_SESSION['username'], $_SESSION['password']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$loggedInUser = $result->fetch_assoc();
$loggedInUserID = $loggedInUser['user_id'];

// --- 2. PLAN VERIFICATION ---
$hasActivePlan = false;
if (!empty($loggedInUser['plan_expiry_date'])) {
    try {
        $expiryDate = new DateTime($loggedInUser['plan_expiry_date']);
        $today = new DateTime('today');
        if ($expiryDate >= $today) {
            $hasActivePlan = true;
        }
    } catch (Exception $e) {
        $hasActivePlan = false; 
    }
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 3. HANDLE ACTIONS ---
if (isset($_GET['action']) && in_array($_GET['action'], ['accept', 'deny']) && isset($_GET['chat_id'])) {
    $chat_id = (int)$_GET['chat_id'];
    $new_status = ($_GET['action'] === 'accept') ? 1 : 2;
    $update_stmt = $conn->prepare("UPDATE tbl_chat SET interest_status = ? WHERE chat_id = ? AND chat_receiverID = ?");
    $update_stmt->bind_param("iii", $new_status, $chat_id, $loggedInUserID);
    $update_stmt->execute();
    header("Location: user-interests.php");
    exit();
}

// --- 4. FETCH ALL INTERESTS DATA ---
$sql_received_base = "SELECT c.*, u.user_id, u.user_name, u.user_city, u.user_dob, u.user_img FROM tbl_chat c JOIN tbl_user u ON c.chat_senderID = u.user_id WHERE c.chat_receiverID = ? AND c.interest_status = ?";

$stmt_new = $conn->prepare($sql_received_base . " ORDER BY c.chat_date DESC");
$status_new = 0; $stmt_new->bind_param("ii", $loggedInUserID, $status_new); $stmt_new->execute();
$new_requests_result = $stmt_new->get_result();

$stmt_accepted = $conn->prepare($sql_received_base . " ORDER BY c.chat_date DESC");
$status_accepted = 1; $stmt_accepted->bind_param("ii", $loggedInUserID, $status_accepted); $stmt_accepted->execute();
$accepted_requests_result = $stmt_accepted->get_result();

$stmt_denied = $conn->prepare($sql_received_base . " ORDER BY c.chat_date DESC");
$status_denied = 2; $stmt_denied->bind_param("ii", $loggedInUserID, $status_denied); $stmt_denied->execute();
$denied_requests_result = $stmt_denied->get_result();

$sql_sent = "SELECT c.*, u.user_id, u.user_name, u.user_img, u.user_dob FROM tbl_chat c JOIN tbl_user u ON c.chat_receiverID = u.user_id WHERE c.chat_senderID = ? AND c.interest_status != 9 ORDER BY c.chat_date DESC";
$stmt_sent = $conn->prepare($sql_sent);
$stmt_sent->bind_param("i", $loggedInUserID); $stmt_sent->execute();
$sent_requests_result = $stmt_sent->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Add this for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wedding Matrimony - My Interests</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Custom CSS for mobile adjustments */
        @media (max-width: 767.98px) {
            .db-inte-prof-list ul li {
                flex-direction: column; /* Stack items vertically */
                align-items: flex-start; /* Align content to the left */
                padding: 15px; /* Add some padding */
            }
            .db-int-pro-1 {
                margin-bottom: 10px; /* Space between image and details */
            }
            .db-int-pro-2 {
                width: 100%; /* Take full width */
                margin-bottom: 15px; /* Space between details and buttons */
            }
            .db-int-pro-3 {
                width: 100%; /* Take full width */
                display: flex; /* Use flexbox for buttons */
                gap: 10px; /* Space between buttons */
                justify-content: flex-start; /* Align buttons to the left */
            }
            .db-int-pro-3 .btn {
                flex-grow: 1; /* Allow buttons to grow and fill space */
            }
            .nav-tabs {
                flex-wrap: wrap; /* Allow tabs to wrap on smaller screens */
                border-bottom: none; /* Remove default bottom border */
            }
            .nav-tabs .nav-item {
                flex-grow: 1; /* Allow nav items to take available width */
                text-align: center; /* Center tab text */
            }
            .nav-tabs .nav-link {
                border-radius: 0; /* Remove border-radius for full-width tabs */
                margin-bottom: 5px; /* Space between wrapped tabs */
            }
            .tab-content {
                padding-top: 15px; /* Add space above tab content */
            }
            .db-tit {
                text-align: center; /* Center the dashboard title on mobile */
                margin-bottom: 20px;
            }
        }
        /* Disable chat button styling */
        .chat-disabled {
            background-color: #6c757d !important;
            color: #fff !important;
            cursor: not-allowed !span;
            text-decoration: none !important;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include "inc/header.php"; ?>
    <?php include "inc/bodystart.php"; ?>
    <?php include "inc/navbar.php"; ?>
    <section>
        <div class="db">
            <div class="container">
                <div class="row">
                    <!-- Dashboard Navigation (Adjusted for mobile) -->
                    <div class="col-md-4 col-lg-3 mb-3"> <!-- Added mb-3 for spacing on small screens -->
                        <?php include "inc/dashboard_nav.php"; ?>
                    </div>
                    <!-- Main Content Area -->
                    <div class="col-md-8 col-lg-9">
                        <div class="db-sec-com">
                            <h2 class="db-tit">Interest Dashboard</h2>
                            <div class="db-pro-stat">
                                <div class="db-inte-main">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#home">New Requests <span class="badge bg-danger"><?php echo $new_requests_result->num_rows; ?></span></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#menu1">Accepted By Me</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#menu3">Interests Sent By Me</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#menu2">Denied By Me</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <!-- TAB 1: NEW REQUESTS RECEIVED -->
                                        <div id="home" class="container tab-pane active"><br>
                                            <div class="db-inte-prof-list">
                                                <ul>
                                                    <?php if ($new_requests_result->num_rows > 0): while ($row = $new_requests_result->fetch_assoc()): ?>
                                                        <li>
                                                            <div class="db-int-pro-1">
                                                                <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile">
                                                            </div>
                                                            <div class="db-int-pro-2">
                                                                <h5><?php echo htmlspecialchars($row['user_name']); ?></h5> 
                                                                <ol class="poi">
                                                                    <li>City: <strong><?php echo htmlspecialchars($row['user_city']); ?></strong></li>
                                                                    <li>Age: <strong>
                                                                        <?php
                                                                            if (!empty($row['user_dob'])) {
                                                                                echo (new DateTime($row['user_dob']))->diff(new DateTime('today'))->y;
                                                                            } else { echo 'N/A'; }
                                                                        ?>
                                                                    </strong></li>
                                                                </ol>
                                                                <ol class="poi poi-date"><li>Request on: <?php echo date("d M Y, h:i A", strtotime($row['chat_date'])); ?></li></ol>
                                                                <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                                            </div>
                                                            <div class="db-int-pro-3">
                                                                <a href="user-interests.php?action=accept&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-success btn-sm">Accept</a>
                                                                <a href="user-interests.php?action=deny&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm">Deny</a>
                                                            </div>
                                                        </li>
                                                    <?php endwhile; else: ?>
                                                        <li class="text-center p-5">No new interest requests found.</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- TAB 2: REQUESTS YOU ACCEPTED -->
                                        <div id="menu1" class="container tab-pane fade"><br>
                                            <div class="db-inte-prof-list">
                                                <ul>
                                                    <?php if ($accepted_requests_result->num_rows > 0): while ($row = $accepted_requests_result->fetch_assoc()): ?>
                                                        <li>
                                                            <div class="db-int-pro-1">
                                                                <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile">
                                                            </div>
                                                            <div class="db-int-pro-2">
                                                                <h5><?php echo htmlspecialchars($row['user_name']); ?></h5> 
                                                                <ol class="poi poi-date"><li>You accepted this interest.</li></ol>
                                                                <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                                            </div>
                                                            <div class="db-int-pro-3">
                                                                <?php if ($hasActivePlan): ?>
                                                                    <a href="open-chat.php?receiver_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Chat Now</a>
                                                                <?php else: ?>
                                                                    <span class="btn btn-secondary btn-sm chat-disabled">Upgrade to Chat</span>
                                                                <?php endif; ?>
                                                                <a href="user-interests.php?action=deny&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm">Deny</a>
                                                            </div>
                                                        </li>
                                                    <?php endwhile; else: ?>
                                                        <li class="text-center p-5">You have not accepted any requests.</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- TAB 3: INTERESTS YOU SENT -->
                                        <div id="menu3" class="container tab-pane fade"><br>
                                            <div class="db-inte-prof-list">
                                                <ul>
                                                    <?php if ($sent_requests_result->num_rows > 0): while ($row = $sent_requests_result->fetch_assoc()): ?>
                                                    <li id="interest-row-<?php echo $row['chat_id']; ?>">
                                                        <div class="db-int-pro-1">
                                                            <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile">
                                                        </div>
                                                        <div class="db-int-pro-2">
                                                            <h5>To: <?php echo htmlspecialchars($row['user_name']); ?></h5>
                                                            <ol class="poi poi-date"><li>You sent on: <?php echo date("d M Y, h:i A", strtotime($row['chat_date'])); ?></li></ol>
                                                            <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                                        </div>
                                                        <div class="db-int-pro-3">
                                                            <?php if ($row['interest_status'] == 0): ?>
                                                                <span class="btn btn-warning btn-sm disabled">Pending</span>
                                                                <a href="javascript:void(0);" class="btn btn-outline-danger btn-sm cancel-interest-btn" data-chat-id="<?php echo $row['chat_id']; ?>">Cancel</a>
                                                            <?php elseif ($row['interest_status'] == 1): ?>
                                                                <span class="btn btn-success btn-sm disabled">Accepted</span>
                                                                <?php if ($hasActivePlan): ?>
                                                                    <a href="open-chat.php?receiver_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Chat Now</a>
                                                                <?php else: ?>
                                                                    <span class="btn btn-secondary btn-sm chat-disabled">Upgrade to Chat</span>
                                                                <?php endif; ?>
                                                            <?php elseif ($row['interest_status'] == 2): ?>
                                                                <span class="btn btn-danger btn-sm disabled">Denied by them</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                    <?php endwhile; else: ?>
                                                        <li class="text-center p-5">You have not sent any interests yet.</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- TAB 4: REQUESTS YOU DENIED -->
                                        <div id="menu2" class="container tab-pane fade"><br>
                                            <div class="db-inte-prof-list">
                                                <ul>
                                                    <?php if ($denied_requests_result->num_rows > 0): while ($row = $denied_requests_result->fetch_assoc()): ?>
                                                        <li>
                                                            <div class="db-int-pro-1">
                                                                <img src="upload/default-profile.png" alt="Profile">
                                                            </div>
                                                            <div class="db-int-pro-2">
                                                                <h5><?php echo htmlspecialchars($row['user_name']); ?></h5>
                                                                <ol class="poi poi-date"><li>You denied this request.</li></ol>
                                                            </div>
                                                            <div class="db-int-pro-3">
                                                                <a href="user-interests.php?action=accept&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-success btn-sm">Accept</a>
                                                            </div>
                                                        </li>
                                                    <?php endwhile; else: ?>
                                                        <li class="text-center p-5">You have not denied any requests.</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script> 
    
    <!-- JAVASCRIPT FOR INSTANT CANCELLATION (NO POP-UP) -->
    <script>
    $(document).ready(function() {
        $('.cancel-interest-btn').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var chatId = button.data('chat-id');
            
            // The confirmation pop-up has been removed.
            // The action will now happen immediately on click.

            // Disable the button to prevent multiple clicks
            button.text('Canceling...').addClass('disabled');

            $.ajax({
                url: 'api/cancel_interest.php',
                type: 'POST',
                data: { chat_id: chatId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // On success, smoothly remove the entire list item from view
                        $('#interest-row-' + chatId).slideUp(function() { $(this).remove(); });
                    } else {
                        // If there was an error (e.g., permission denied), show an alert and re-enable the button
                        alert('Error: ' + response.message);
                        button.text('Cancel').removeClass('disabled');
                    }
                },
                error: function() {
                    // Handle server errors
                    alert('An unexpected server error occurred.');
                    button.text('Cancel').removeClass('disabled');
                }
            });
        });
    });
    </script>
</body>
</html>
