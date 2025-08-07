<?php
    session_start();
    include "db/conn.php";
    
    // For development, show errors
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATION ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.html';</script>";
        exit();
    }

    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];

    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
    $stmt->bind_param("ss", $userN, $psw);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<script>alert('Session error. Please login again.'); window.location.href='login.html';</script>";
        exit();
    }
    
    $loggedInUser = $result->fetch_assoc();
    $loggedInUserID = $loggedInUser['user_id'];

    // --- 2. FETCH DASHBOARD DATA (COUNTS) ---

    // Count of new interests RECEIVED by the user
    $stmt_new_interests = $conn->prepare("SELECT COUNT(chat_id) AS count FROM tbl_chat WHERE chat_receiverID = ? AND interest_status = 0");
    $stmt_new_interests->bind_param("i", $loggedInUserID);
    $stmt_new_interests->execute();
    $new_interests_count = $stmt_new_interests->get_result()->fetch_assoc()['count'];

    // Count of interests SENT by the user that were accepted by others
    $stmt_sent_accepted = $conn->prepare("SELECT COUNT(chat_id) AS count FROM tbl_chat WHERE chat_senderID = ? AND interest_status = 1");
    $stmt_sent_accepted->bind_param("i", $loggedInUserID);
    $stmt_sent_accepted->execute();
    $sent_accepted_count = $stmt_sent_accepted->get_result()->fetch_assoc()['count'];

    // Total interests SENT by the user
    $stmt_sent_total = $conn->prepare("SELECT COUNT(chat_id) AS count FROM tbl_chat WHERE chat_senderID = ? AND interest_status != 9");
    $stmt_sent_total->bind_param("i", $loggedInUserID);
    $stmt_sent_total->execute();
    $sent_total_count = $stmt_sent_total->get_result()->fetch_assoc()['count'];
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
                
                        
                        <?php include "inc/dashboard_nav.php"; ?>
                    </div>

                    <!-- Right Content: Dashboard -->
                    <div class="col-md-8 col-lg-9">
                        <div class="db-sec-com db-wel">
                            <div class="db-wel-lhs">
                                <h2>Welcome, <?php echo htmlspecialchars($loggedInUser['user_name']); ?>!</h2>
                                <p>Here's a quick look at your profile activity.</p>
                            </div>
                            <div class="db-wel-rhs">
                                <a href="user-profile-edit.php" class="cta-3">Edit my profile</a>
                            </div>
                        </div>

                        <!-- Main Dashboard Statistics -->
                        <div class="db-sec-com db-pro-stat">
                            <div class="row">
                                <!-- New Interests Received -->
                                <div class="col-md-4">
                                    <a href="user-interests.php">
                                        <div class="db-pro-stat-box">
                                            <i class="fa fa-envelope-open-o" aria-hidden="true"></i>
                                            <h4><?php echo $new_interests_count; ?></h4>
                                            <p>New interests received</p>
                                        </div>
                                    </a>
                                </div>
                                <!-- Interests Sent -->
                                <div class="col-md-4">
                                     <a href="user-interests.php">
                                        <div class="db-pro-stat-box">
                                            <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
                                            <h4><?php echo $sent_total_count; ?></h4>
                                            <p>Interests you sent</p>
                                        </div>
                                    </a>
                                </div>
                                <!-- Interests Accepted -->
                                <div class="col-md-4">
                                    <a href="user-interests.php">
                                        <div class="db-pro-stat-box">
                                            <i class="fa fa-check-square-o" aria-hidden="true"></i>
                                            <h4><?php echo $sent_accepted_count; ?></h4>
                                            <p>Interests accepted</p>
                                        </div>
                                    </a>
                                </div>
                                <!-- Profile Views (Static example) -->
                                <div class="col-md-4">
                                    <a href="#!">
                                        <div class="db-pro-stat-box">
                                            <i class="fa fa-eye" aria-hidden="true"></i>
                                            <h4>0</h4>
                                            <p>Profile Views <!-- Note: Needs a view tracking system to be dynamic --></p>
                                        </div>
                                    </a>
                                </div>
                                <!-- Chat List -->
                                <div class="col-md-4">
                                    <a href="user-chat.php">
                                        <div class="db-pro-stat-box">
                                            <i class="fa fa-commenting-o" aria-hidden="true"></i>
                                            <h4>Chat</h4>
                                            <p>Your conversations</p>
                                        </div>
                                    </a>
                                </div>
                                 <!-- Plans -->
                                <div class="col-md-4">
                                    <a href="plans.php">
                                        <div class="db-pro-stat-box">
                                            <i class="fa fa-money" aria-hidden="true"></i>
                                            <h4>Plans</h4>
                                            <p>Upgrade your plan</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Account Status Section -->
                        <div class="db-sec-com db-pro-stat">
                             <div class="row">
                                <div class="col-md-12">
                                    <h4>Account Status</h4>
                                    <?php
                                        // Profile Approval Status
                                        if ($loggedInUser['user_status'] == '1') {
                                            echo "<div class='alert alert-success'>Your profile is <strong>approved</strong> and visible to others.</div>";
                                        } else {
                                            echo "<div class='alert alert-warning'>Your profile is <strong>pending admin approval</strong> and is not yet visible.</div>";
                                        }

                                        // Subscription Plan Status
                                        $plan_status_message = '';
                                        if (empty($loggedInUser['plan_type']) || $loggedInUser['plan_type'] == 'Free') {
                                            $plan_status_message = "You are on the <strong>Free Plan</strong>. <a href='plans.php' class='alert-link'>Upgrade now</a> for full access.";
                                            echo "<div class='alert alert-info'>$plan_status_message</div>";
                                        } else {
                                            $expiry_date = new DateTime($loggedInUser['plan_expiry_date']);
                                            $today = new DateTime();
                                            if ($expiry_date < $today) {
                                                $plan_status_message = "Your <strong>".htmlspecialchars($loggedInUser['plan_type'])." Plan has expired</strong>. <a href='plans.php' class='alert-link'>Renew now</a> to continue using premium features.";
                                                echo "<div class='alert alert-danger'>$plan_status_message</div>";
                                            } else {
                                                $plan_status_message = "You have an active <strong>".htmlspecialchars($loggedInUser['plan_type'])." Plan</strong>, which expires on " . $expiry_date->format('d M, Y') . ".";
                                                echo "<div class='alert alert-success'>$plan_status_message</div>";
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    include "inc/copyright.php";
?>
<?php
    include "inc/footerlink.php";
?>