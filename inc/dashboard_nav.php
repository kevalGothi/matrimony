<?php
// This component assumes the parent page has already authenticated the user
// and defined $loggedInUser and $loggedInUserID.
if (!isset($loggedInUser) || !isset($loggedInUserID)) {
    die("Critical error: User data was not provided to the navigation component.");
}

// Fetch the main profile picture for the desktop sidebar
$profile_pic_path = !empty($loggedInUser['user_img']) ? $loggedInUser['user_img'] : 'default-profile.png';

// Get the current page filename to dynamically set the 'active' class
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- ============================================= -->
<!-- 1. DESKTOP SIDEBAR (Visible on large screens) -->
<!-- ============================================= -->
<div class="db-nav">
    <div class="db-nav-pro">
        <img src="upload/<?php echo htmlspecialchars($profile_pic_path); ?>" class="img-fluid" alt="My Profile Image">
    </div>
    <div class="db-nav-list">
        <ul>
            <li>
                <a href="user-dashboard.php" class="<?php echo ($current_page == 'user-dashboard.php') ? 'act' : ''; ?>">
                    <i class="fa fa-tachometer"></i>Dashboard
                </a>
            </li>
            <li>
                <a href="user-profile.php" class="<?php echo ($current_page == 'user-profile.php') ? 'act' : ''; ?>">
                    <i class="fa fa-user"></i>My Profile
                </a>
            </li>
            <li>
                <a href="see-other-profile.php" class="<?php echo ($current_page == 'see-other-profile.php') ? 'act' : ''; ?>">
                    <i class="fa fa-users"></i>Browse Profiles
                </a>
            </li>
            <li>
                <a href="user-profile-edit.php" class="<?php echo ($current_page == 'user-profile-edit.php') ? 'act' : ''; ?>">
                    <i class="fa fa-pencil-square-o"></i>Edit Profile & Photos
                </a>
            </li>
            <li>
                <a href="user-interests.php" class="<?php echo ($current_page == 'user-interests.php') ? 'act' : ''; ?>">
                    <i class="fa fa-handshake-o"></i>My Interests
                </a>
            </li>
            <li>
                <a href="user-chat.php" class="<?php echo in_array($current_page, ['user-chat.php', 'open-chat.php']) ? 'act' : ''; ?>">
                    <i class="fa fa-commenting-o"></i>Chat List
                </a>
            </li>
            <li>
                <a href="plans.php" class="<?php echo ($current_page == 'plans.php') ? 'act' : ''; ?>">
                    <i class="fa fa-money"></i>Membership Plans
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fa fa-sign-out"></i>Log Out
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- ============================================= -->
<!-- 2. MOBILE BOTTOM BAR (Visible on small screens) -->
<!-- ============================================= -->
<nav class="mobile-dashboard-nav">
    <a href="user-dashboard.php" class="<?php echo ($current_page == 'user-dashboard.php') ? 'active' : ''; ?>">
        <i class="fa fa-tachometer"></i>
        <span>Dashboard</span>
    </a>
    <a href="see-other-profile.php" class="<?php echo ($current_page == 'see-other-profile.php') ? 'active' : ''; ?>">
        <i class="fa fa-users"></i>
        <span>Browse</span>
    </a>
    <a href="user-interests.php" class="<?php echo ($current_page == 'user-interests.php') ? 'active' : ''; ?>">
        <i class="fa fa-handshake-o"></i>
        <span>Interests</span>
    </a>
    <a href="user-chat.php" class="<?php echo in_array($current_page, ['user-chat.php', 'open-chat.php']) ? 'active' : ''; ?>">
        <i class="fa fa-commenting-o"></i>
        <span>Chats</span>
    </a>
    <a href="user-profile.php" class="<?php echo ($current_page == 'user-profile.php') ? 'active' : ''; ?>">
        <i class="fa fa-user"></i>
        <span>Profile</span>
    </a>
</nav>