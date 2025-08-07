<?php
include "db/conn.php";
include "inc/header.php";
include "inc/bodystart.php";

$message = '';
$token_is_valid = false;

if (isset($_GET["token"])) {
    $token = $_GET["token"];
    $token_hash = hash("sha256", $token);

    // Find the user with this token hash
    $stmt = $conn->prepare("SELECT user_id, reset_token_expires_at FROM tbl_user WHERE reset_token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if the token has expired
        if (strtotime($user["reset_token_expires_at"]) > time()) {
            $token_is_valid = true;
        } else {
            $message = "<div class='alert alert-danger'>Password reset link has expired. Please request a new one.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Invalid password reset link.</div>";
    }
} else {
    $message = "<div class='alert alert-danger'>No reset token provided.</div>";
}

// Handle the form submission to set the new password
if (isset($_POST["set_new_password"])) {
    $password = $_POST["password"];
    $password_confirm = $_POST["password_confirmation"];

    if ($password === $password_confirm) {
        if (strlen($password) >= 6) {
            $user_id = $user["user_id"];
            
            // Update the password and clear the reset token fields
            $update_stmt = $conn->prepare("UPDATE tbl_user SET user_pass = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?");
            $update_stmt->bind_param("si", $password, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "<div class='alert alert-success'>Your password has been reset successfully! You can now <a href='login.php' class='alert-link'>log in</a> with your new password.</div>";
                $token_is_valid = false; // Hide the form after success
            } else {
                 $message = "<div class='alert alert-danger'>Error updating password. Please try again.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Password must be at least 6 characters long.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    }
}
?>

<section>
    <div class="login">
        <div class="container">
            <div class="row">
                <div class="inn">
                     <div class="lhs">
                        <div class="tit"><h2><b>Create a new password</b><br>to secure your account.</h2></div>
                        <div class="im"><img src="images/login-couple.png" alt=""></div>
                    </div>
                    <div class="rhs">
                        <div>
                            <div class="form-tit">
                                <h1>Set Your New Password</h1>
                            </div>
                            <?php echo $message; ?>

                            <?php if ($token_is_valid): ?>
                                <div class="form-login">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label class="lb">New Password:</label>
                                            <input type="password" class="form-control" name="password" placeholder="Enter new password" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="lb">Confirm New Password:</label>
                                            <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password" required>
                                        </div>
                                        <button type="submit" name="set_new_password" class="btn btn-primary">Reset Password</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
