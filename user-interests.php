<?php
    session_start();
    include "db/conn.php";
    
    // For development, show errors. You can remove this in production.
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
?>
<!doctype html>
<html lang="en">

<!-- This head section is based on your original HTML file -->
<head>
    <title>Wedding Matrimony - User Interests</title>
    <!--== META TAGS ==-->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#f6af04">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <!--== FAV ICON(BROWSER TAB ICON) ==-->
    <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
    <!--== CSS FILES ==-->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Your standard header and navbar includes -->
    <?php 
        include "inc/header.php"; // Assuming this is your header content
        include "inc/bodystart.php";
        include "inc/navbar.php"; // Assuming this is your main navigation
    ?>

<?php
    // --- AUTHENTICATION ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.html';</script>";
        exit();
    }

    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];

    $user_query = mysqli_query($conn, "SELECT * FROM tbl_user WHERE user_phone = '$userN' AND user_pass = '$psw'");
    
    if ($user_query && mysqli_num_rows($user_query) > 0) {
        $fe = mysqli_fetch_assoc($user_query);
        $loggedInUserID = $fe['user_id'];

        // --- PART 1: HANDLE ALL POSSIBLE ACTIONS ---

        // A. Handle SENDING a new interest (from see-other-profile.php)
        if (isset($_GET['send_interest'])) {
            $receiverID = (int)$_GET['send_interest'];
            $senderID = $loggedInUserID;

            $check_sql = "SELECT chat_id FROM tbl_chat WHERE ((chat_senderID = $senderID AND chat_receiverID = $receiverID) OR (chat_senderID = $receiverID AND chat_receiverID = $senderID))";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) == 0) {
                $interest_message = "I am interested in your profile.";
                $insert_sql = "INSERT INTO tbl_chat (chat_senderID, chat_receiverID, chat_message, interest_status) VALUES ('$senderID', '$receiverID', '$interest_message', '0')";
                if(mysqli_query($conn, $insert_sql)) {
                    echo "<script>alert('Interest sent successfully!'); window.location.href='user-interests.php';</script>";
                } else {
                    echo "<script>alert('Error sending interest.'); window.location.href='see-other-profile.php';</script>";
                }
            } else {
                echo "<script>alert('An interest already exists between you and this person.'); window.location.href='see-other-profile.php';</script>";
            }
            exit();
        }

        // B. Handle ACCEPTING or DENYING an interest you RECEIVED
        if (isset($_GET['action']) && ($_GET['action'] === 'accept' || $_GET['action'] === 'deny') && isset($_GET['chat_id'])) {
            $action = $_GET['action'];
            $chat_id = (int)$_GET['chat_id'];
            $new_status = ($action === 'accept') ? 1 : 2;

            $update_sql = "UPDATE tbl_chat SET interest_status = '$new_status' WHERE chat_id = '$chat_id' AND chat_receiverID = '$loggedInUserID'";
            if(mysqli_query($conn, $update_sql)) {
                echo "<script>alert('Request updated successfully!'); window.location.href='user-interests.php';</script>";
            } else {
                echo "<script>alert('Error updating request.'); window.location.href='user-interests.php';</script>";
            }
            exit();
        }

        // C. Handle CANCELING an interest you SENT
        if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['chat_id'])) {
            $chat_id = (int)$_GET['chat_id'];
            $delete_sql = "DELETE FROM tbl_chat WHERE chat_id = '$chat_id' AND chat_senderID = '$loggedInUserID' AND interest_status = 0";
            if(mysqli_query($conn, $delete_sql)) {
                echo "<script>alert('Interest canceled successfully!'); window.location.href='user-interests.php';</script>";
            } else {
                echo "<script>alert('Error canceling interest or it has already been actioned.'); window.location.href='user-interests.php';</script>";
            }
            exit();
        }


        // --- PART 2: FETCH ALL INTERESTS DATA ---

        // -- TABS 1, 2, 4: INTERESTS YOU HAVE RECEIVED --
        $new_requests_result = mysqli_query($conn, "SELECT c.*, u.* FROM tbl_chat c JOIN tbl_user u ON c.chat_senderID = u.user_id WHERE c.chat_receiverID = '$loggedInUserID' AND c.interest_status = 0 ORDER BY c.chat_date DESC");
        $accepted_requests_result = mysqli_query($conn, "SELECT c.*, u.* FROM tbl_chat c JOIN tbl_user u ON c.chat_senderID = u.user_id WHERE c.chat_receiverID = '$loggedInUserID' AND c.interest_status = 1 ORDER BY c.chat_date DESC");
        $denied_requests_result = mysqli_query($conn, "SELECT c.*, u.* FROM tbl_chat c JOIN tbl_user u ON c.chat_senderID = u.user_id WHERE c.chat_receiverID = '$loggedInUserID' AND c.interest_status = 2 ORDER BY c.chat_date DESC");

        // -- TAB 3: INTERESTS YOU HAVE SENT --
        $sent_requests_result = mysqli_query($conn, "SELECT c.*, u.* FROM tbl_chat c JOIN tbl_user u ON c.chat_receiverID = u.user_id WHERE c.chat_senderID = '$loggedInUserID' AND c.interest_status != 9 ORDER BY c.chat_date DESC");

?>
        <!-- USER DASHBOARD SECTION -->
        <section>
            <div class="db">
                <div class="container">
                    <div class="row">
                        <!-- Left Navigation -->
                        <div class="col-md-4 col-lg-3">
                           <div class="db-nav">
                                <div class="db-nav-pro">
                                    <img src="upload/<?php echo !empty($fe['user_img']) ? htmlspecialchars($fe['user_img']) : 'default-profile.png'; ?>" class="img-fluid" alt="User Profile Image">
                                </div>
                                <div class="db-nav-list">
                                    <ul>
                                        <li><a href="user-dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a></li>
                                        <li><a href="user-profile.php"><i class="fa fa-user"></i>Profile</a></li>
                                        <li><a href="see-other-profile.php"><i class="fa fa-users"></i>See Others Profile</a></li>
                                        <li><a href="user-profile-edit.php"><i class="fa fa-pencil-square-o"></i>Edit Profile</a></li>
                                        <li><a href="user-interests.php" class="act"><i class="fa fa-handshake-o"></i>Interests</a></li>
                                        <li><a href="user-chat.php"><i class="fa fa-commenting-o"></i>Chat list</a></li>
                                        <li><a href="plans.php"><i class="fa fa-money"></i>Plan</a></li>
                                        <li><a href="user-setting.php"><i class="fa fa-cog"></i>Setting</a></li>
                                        <li><a href="logout.php"><i class="fa fa-sign-out"></i>Log out</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Right Content -->
                       <div class="col-md-8 col-lg-9">
                            <div class="db-sec-com">
                                <h2 class="db-tit">Interest Dashboard</h2>
                                <div class="db-pro-stat">
                                    <div class="db-inte-main">
                                        <!-- TABS NAVIGATION -->
                                          <ul class="nav nav-tabs" role="tablist">
                                            <li class="nav-item">
                                              <a class="nav-link active" data-bs-toggle="tab" href="#home">New Requests <span class="badge bg-danger"><?php echo mysqli_num_rows($new_requests_result); ?></span></a>
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
                                          
                                          <!-- Tab Content Panes -->
                                          <div class="tab-content">
                                            
                                            <!-- TAB 1: NEW REQUESTS RECEIVED -->
                                            <div id="home" class="container tab-pane active"><br>
                                              <div class="db-inte-prof-list">
                                                    <ul>
                                                        <?php if ($new_requests_result && mysqli_num_rows($new_requests_result) > 0): ?>
                                                            <?php while ($row = mysqli_fetch_assoc($new_requests_result)): ?>
                                                                <li>
                                                                    <div class="db-int-pro-1"> <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile Image"></div>
                                                                    <div class="db-int-pro-2">
                                                                        <h5><?php echo htmlspecialchars($row['user_name']); ?></h5> 
                                                                        <ol class="poi">
                                                                            <li>City: <strong><?php echo htmlspecialchars($row['user_city']); ?></strong></li>
                                                                            <li>Age: <strong><?php echo htmlspecialchars($row['user_age']); ?></strong></li>
                                                                        </ol>
                                                                        <ol class="poi poi-date"><li>Request on: <?php echo date("d M Y, h:i A", strtotime($row['chat_date'])); ?></li></ol>
                                                                        <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                                                    </div>
                                                                    <div class="db-int-pro-3">
                                                                        <a href="user-interests.php?action=accept&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-success btn-sm">Accept</a>
                                                                        <a href="user-interests.php?action=deny&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm">Deny</a>
                                                                    </div>
                                                                </li>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
                                                            <li class="text-center p-5">No new interest requests found.</li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- TAB 2: REQUESTS YOU ACCEPTED -->
                                            <div id="menu1" class="container tab-pane fade"><br>
                                                <div class="db-inte-prof-list">
                                                    <ul>
                                                        <?php if ($accepted_requests_result && mysqli_num_rows($accepted_requests_result) > 0): ?>
                                                            <?php while ($row = mysqli_fetch_assoc($accepted_requests_result)): ?>
                                                                <li>
                                                                    <div class="db-int-pro-1"> <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile Image"> </div>
                                                                    <div class="db-int-pro-2">
                                                                        <h5><?php echo htmlspecialchars($row['user_name']); ?></h5> 
                                                                        <ol class="poi poi-date"><li>You accepted this interest.</li></ol>
                                                                        <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                                                    </div>
                                                                    <div class="db-int-pro-3">
                                                                        <a href="user-chat.php?receiver_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Chat Now</a>
                                                                        <a href="user-interests.php?action=deny&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm">Deny</a>
                                                                    </div>
                                                                </li>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
                                                            <li class="text-center p-5">You have not accepted any requests.</li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <!-- TAB 3: INTERESTS YOU SENT -->
                                            <div id="menu3" class="container tab-pane fade"><br>
                                                <div class="db-inte-prof-list">
                                                    <ul>
                                                        <?php if ($sent_requests_result && mysqli_num_rows($sent_requests_result) > 0): ?>
                                                            <?php while ($row = mysqli_fetch_assoc($sent_requests_result)): ?>
                                                            <li>
                                                                <div class="db-int-pro-1"> <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile Image"></div>
                                                                <div class="db-int-pro-2">
                                                                    <h5>To: <?php echo htmlspecialchars($row['user_name']); ?></h5>
                                                                    <ol class="poi poi-date"><li>You sent on: <?php echo date("d M Y, h:i A", strtotime($row['chat_date'])); ?></li></ol>
                                                                    <a href="profile-details.php?id=<?php echo $row['user_id']; ?>" class="cta-5" target="_blank">View full profile</a>
                                                                </div>
                                                                <div class="db-int-pro-3">
                                                                    <?php if ($row['interest_status'] == 0): ?>
                                                                        <span class="btn btn-warning btn-sm disabled">Pending</span>
                                                                        <a href="user-interests.php?action=cancel&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this interest?');">Cancel</a>
                                                                    <?php elseif ($row['interest_status'] == 1): ?>
                                                                        <span class="btn btn-success btn-sm disabled">Accepted</span>
                                                                        <a href="user-chat.php?receiver_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Chat Now</a>
                                                                    <?php elseif ($row['interest_status'] == 2): ?>
                                                                        <span class="btn btn-danger btn-sm disabled">Denied by them</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </li>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
                                                            <li class="text-center p-5">You have not sent any interests yet.</li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- TAB 4: REQUESTS YOU DENIED -->
                                            <div id="menu2" class="container tab-pane fade"><br>
                                                <div class="db-inte-prof-list">
                                                    <ul>
                                                        <?php if ($denied_requests_result && mysqli_num_rows($denied_requests_result) > 0): ?>
                                                            <?php while ($row = mysqli_fetch_assoc($denied_requests_result)): ?>
                                                            <li>
                                                                <div class="db-int-pro-1"> <img src="upload/<?php echo !empty($row['user_img']) ? htmlspecialchars($row['user_img']) : 'default-profile.png'; ?>" alt="Profile Image"> </div>
                                                                <div class="db-int-pro-2">
                                                                    <h5><?php echo htmlspecialchars($row['user_name']); ?></h5>
                                                                    <ol class="poi poi-date"><li>You denied this request.</li></ol>
                                                                </div>
                                                                <div class="db-int-pro-3"><a href="user-interests.php?action=accept&chat_id=<?php echo $row['chat_id']; ?>" class="btn btn-success btn-sm">Accept</a></div>
                                                            </li>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
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
<?php
    } else {
        // This case handles if the session data is stale or invalid
        echo "<div class='container p-5 text-center'><h2>Session Error</h2><p>Could not find user. Please log in again.</p><a href='logout.php' class='btn btn-primary'>Login</a></div>";
    }
?>
    <!-- FOOTER AND COPYRIGHTS -->
    <?php 
        // include "inc/footer.php"; // Assuming you have a standard footer
    ?>
    <!-- END -->

    <!-- ################################################################## -->
    <!-- # IMPORTANT: JAVASCRIPT FILES                                    # -->
    <!-- # These files are REQUIRED for the Bootstrap tabs to be clickable. # -->
    <!-- # Make sure the paths are correct for your project structure.      # -->
    <!-- ################################################################## -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/select-opt.js"></script>
    <script src="js/custom.js"></script>
</body>

</html>