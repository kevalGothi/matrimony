<?php
    session_start();
    include "db/conn.php";
    
    // For development, show errors.
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATION ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.html';</script>";
        exit();
    }

    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];

    $user_query = mysqli_query($conn, "SELECT * FROM tbl_user WHERE user_phone = '$userN' AND user_pass = '$psw'");
    
    if (!$user_query || mysqli_num_rows($user_query) == 0) {
        echo "<script>alert('Session error. Please login again.'); window.location.href='login.html';</script>";
        exit();
    }
    
    $loggedInUser = mysqli_fetch_assoc($user_query);
    $loggedInUserID = $loggedInUser['user_id'];
    $message = ''; // To store success or error messages

    // --- 2. HANDLE FORM SUBMISSIONS ---

    // A. Handle Change Password
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        // Verify the current password
        if ($current_pass === $loggedInUser['user_pass']) {
            // Check if new passwords match
            if ($new_pass === $confirm_pass) {
                // Check for minimum password length
                if (strlen($new_pass) >= 6) {
                    $stmt = $conn->prepare("UPDATE tbl_user SET user_pass = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $new_pass, $loggedInUserID);
                    if ($stmt->execute()) {
                        // Update session password and show success
                        $_SESSION['password'] = $new_pass; 
                        $message = "<div class='alert alert-success'>Password updated successfully!</div>";
                    } else {
                        $message = "<div class='alert alert-danger'>Error updating password. Please try again.</div>";
                    }
                } else {
                    $message = "<div class='alert alert-danger'>New password must be at least 6 characters long.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>New password and confirm password do not match.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Incorrect current password.</div>";
        }
    }

    // B. Handle Deactivate Account
    if (isset($_POST['deactivate_account'])) {
        $deactivate_status = '2'; // 2 = Deactivated
        $stmt = $conn->prepare("UPDATE tbl_user SET user_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $deactivate_status, $loggedInUserID);
        if ($stmt->execute()) {
            // Log the user out and redirect
            session_unset();
            session_destroy();
            echo "<script>alert('Your account has been deactivated.'); window.location.href='login.php';</script>";
            exit();
        } else {
            $message = "<div class='alert alert-danger'>Error deactivating account. Please contact support.</div>";
        }
    }
?>
<!doctype html>
<html lang="en">
<head>
    <title>Wedding Matrimony - Account Settings</title>
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
                        <div class="db-nav">
                            <div class="db-nav-pro">
                                <img src="upload/<?php echo !empty($loggedInUser['user_img']) ? htmlspecialchars($loggedInUser['user_img']) : 'default-profile.png'; ?>" class="img-fluid" alt="User Profile Image">
                            </div>
                            <div class="db-nav-list">
                                <ul>
                                    <li><a href="user-dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a></li>
                                    <li><a href="user-profile.php"><i class="fa fa-user"></i>Profile</a></li>
                                    <li><a href="see-other-profile.php"><i class="fa fa-users"></i>See Others Profile</a></li>
                                    <li><a href="user-profile-edit.php"><i class="fa fa-pencil-square-o"></i>Edit Profile</a></li>
                                    <li><a href="user-interests.php"><i class="fa fa-handshake-o"></i>Interests</a></li>
                                    <li><a href="user-chat.php"><i class="fa fa-commenting-o"></i>Chat list</a></li>
                                    <li><a href="plans.php"><i class="fa fa-money"></i>Plan</a></li>
                                    <li><a href="user-setting.php" class="act"><i class="fa fa-cog"></i>Setting</a></li>
                                    <li><a href="logout.php"><i class="fa fa-sign-out"></i>Log out</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content: Settings Forms -->
                    <div class="col-md-8 col-lg-9">
                        <div class="db-sec-com">
                            <h2 class="db-tit">Account Settings</h2>
                            
                            <!-- Display Success/Error Messages Here -->
                            <?php echo $message; ?>

                            <!-- CHANGE PASSWORD FORM -->
                            <div class="db-pro-stat card">
                                <div class="card-header">
                                    <h4>Change Your Password</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <label>Current Password</label>
                                            <input type="password" class="form-control" name="current_password" placeholder="Enter your current password" required>
                                        </div>
                                        <div class="form-group">
                                            <label>New Password</label>
                                            <input type="password" class="form-control" name="new_password" placeholder="Enter a new password" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm your new password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                                    </form>
                                </div>
                            </div>

                            <!-- DEACTIVATE ACCOUNT FORM -->
                            <div class="db-pro-stat card mt-4 border-danger">
                                 <div class="card-header bg-danger text-white">
                                    <h4>Deactivate Account</h4>
                                </div>
                                <div class="card-body">
                                    <p>
                                        This is a permanent action. Once you deactivate your account, you will not be able to log in again, and your profile will be hidden.
                                    </p>
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to permanently deactivate your account? This cannot be undone.');">
                                        <button type="submit" name="deactivate_account" class="btn btn-danger">Deactivate My Account</button>
                                    </form>
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