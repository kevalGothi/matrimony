<?php
// Get the filename of the current page (e.g., "user-dashboard.php")
$current_page = basename($_SERVER['PHP_SELF']);

// --- Get User Data from Session ---
// This assumes you store user info in the session after they log in.
// We provide default values to prevent errors if the session is not set.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest';
$user_img_path = isset($_SESSION['user_img_path']) ? $_SESSION['user_img_path'] : 'images/profiles/1.jpg';
$user_join_date = isset($_SESSION['user_join_date']) ? $_SESSION['user_join_date'] : date('d, M Y');

?>

<!-- ================================================================== -->
<!-- THIS IS YOUR EXACT SIDEBAR CODE, NOW MADE DYNAMIC -->
<!-- ================================================================== -->

<!-- START -->
<div class="ud-lhs">
    <div class="ud-pro">
        <img src="<?php echo $user_img_path; ?>" alt="User Profile Picture">
        <h4><?php echo $user_name; ?></h4>
        <p><b>Join on:</b> <?php echo $user_join_date; ?></p>
        <a class="ud-cta-btn" href="user-profile.php">View my profile</a>
    </div>
    <div class="ud-menu">
        <ul>
            <li <?php if ($current_page == 'user-dashboard.php') { echo 'class="act"'; } ?>>
                <a href="user-dashboard.php"><i class="fa fa-tachometer" aria-hidden="true"></i> Dashboard</a>
            </li>
            <li <?php if ($current_page == 'user-profile.php') { echo 'class="act"'; } ?>>
                <a href="user-profile.php"><i class="fa fa-user" aria-hidden="true"></i> My Profile</a>
            </li>
            <li <?php if ($current_page == 'user-profile-edit.php') { echo 'class="act"'; } ?>>
                <a href="user-profile-edit.php"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Edit Full Profile</a>
            </li>
            <li <?php if ($current_page == 'user-interests.php') { echo 'class="act"'; } ?>>
                <a href="user-interests.php"><i class="fa fa-heart" aria-hidden="true"></i> Interests</a>
            </li>
            <li <?php if ($current_page == 'user-chat.php') { echo 'class="act"'; } ?>>
                <a href="user-chat.php"><i class="fa fa-comments-o" aria-hidden="true"></i> Chat</a>
            </li>
            <li <?php if ($current_page == 'user-plan.php') { echo 'class="act"'; } ?>>
                <a href="user-plan.php"><i class="fa fa-money" aria-hidden="true"></i> My Plan Details</a>
            </li>
            <li <?php if ($current_page == 'user-setting.php') { echo 'class="act"'; } ?>>
                <a href="user-setting.php"><i class="fa fa-cogs" aria-hidden="true"></i> Profile Settings</a>
            </li>
            <li>
                <a href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a>
            </li>
        </ul>
    </div>
</div>
<!-- END -->