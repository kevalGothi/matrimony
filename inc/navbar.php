<?php
    // The session must be started on any page that uses this navbar
    // to check the login state.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    include "db/conn.php"; // Include the database connection
?>

<!-- HEADER & MENU -->
<div class="hom-top">
    <div class="container">
        <div class="row">
            <div class="hom-nav">
                <!-- LOGO -->
                <div class="logo">
                    <a href="index.php" class="logo-brand">
                        <img src="inc/logo.png" alt="Logo" loading="lazy" class="ic-logo">
                    </a>
                </div>

                <!-- TOP MENU -->
                <div class="bl">
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="see-other-profile.php">Browse Profiles</a></li>
                        <li><a href="plans.php">Plans</a></li>
                        <li><a href="contact.php">Contact</a></li>

                        <?php if (isset($_SESSION['username']) && isset($_SESSION['password'])): ?>
                            <!-- Show DASHBOARD MENU if user is LOGGED IN -->
                            <li class="smenu-pare">
                                <span class="smenu">Dashboard</span>
                                <div class="smenu-open smenu-single">
                                    <ul>
                                        <li><a href="user-dashboard.php">My Dashboard</a></li>
                                        <li><a href="user-profile.php">My Profile</a></li>
                                        <li><a href="user-profile-edit.php">Edit Profile</a></li>
                                        <li><a href="user-interests.php">My Interests</a></li>
                                        <li><a href="user-chat.php">My Chats</a></li>
                                        <li><a href="user-setting.php">Settings</a></li>
                                        <li><a href="logout.php">Log Out</a></li>
                                    </ul>
                                </div>
                            </li>
                        <?php else: ?>
                            <!-- Show REGISTER button if user is LOGGED OUT -->
                            <li><a href="client_sign_up.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- USER PROFILE AREA (Right Side) -->
                <div class="al">
                    <?php if (isset($_SESSION['username']) && isset($_SESSION['password'])): ?>
                        <?php
                            // Fetch user data to display their profile picture and name
                            $userN_nav = $_SESSION['username'];
                            $psw_nav = $_SESSION['password'];
                            $nav_stmt = $conn->prepare("SELECT user_name, user_img FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
                            $nav_stmt->bind_param("ss", $userN_nav, $psw_nav);
                            $nav_stmt->execute();
                            $nav_result = $nav_stmt->get_result();
                            if ($nav_result->num_rows > 0) {
                                $nav_user = $nav_result->fetch_assoc();
                            }
                        ?>
                        <!-- If LOGGED IN, show their profile info -->
                        <div class="head-pro">
                            <a href="user-dashboard.php">
                                <img src="upload/<?php echo !empty($nav_user['user_img']) ? htmlspecialchars($nav_user['user_img']) : 'default-profile.png'; ?>" alt="My Profile" loading="lazy">
                                <b>My account</b><br>
                                <h4><?php echo htmlspecialchars($nav_user['user_name']); ?></h4>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- If LOGGED OUT, show a Login button -->
                         <div class="head-pro">
                            <a href="login.php" class="btn btn-primary">Login</a>
                         </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END HEADER & MENU -->