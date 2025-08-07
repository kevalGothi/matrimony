<?php
// --- Bring in the modern PHPMailer classes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// The paths must match your folder structure
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

include "db/conn.php";
include "inc/header.php"; // Assuming this has your HTML head
include "inc/bodystart.php";
// No navbar needed for this simple page

$message = '';

if (isset($_POST["send_reset_link"])) {
    $email = $conn->real_escape_string($_POST["email"]);

    // Check if the email exists
    $stmt = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // --- Generate a secure token ---
        $token = bin2hex(random_bytes(32)); // A 64-character token
        $token_hash = hash("sha256", $token);

        // --- Set an expiry date (e.g., 1 hour from now) ---
        $expiry = date("Y-m-d H:i:s", time() + 60 * 60);

        // --- Update the user's record with the token and expiry ---
        $update_stmt = $conn->prepare("UPDATE tbl_user SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_email = ?");
        $update_stmt->bind_param("sss", $token_hash, $expiry, $email);
        $update_stmt->execute();

        // --- Send the email using PHPMailer ---
        // IMPORTANT: Assumes your PHPMailer setup from client_sign_up.php is correct
        $mail = new PHPMailer(true);
        try {
            // SMTP CONFIGURATION (use your own details)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'creativewebhub.newsoftware@gmail.com'; // Your email
            $mail->Password   = 'fmlh nhrw pucw usoj'; // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('no-reply@jeevansaathimela.com', 'Jeevansaathi Mela Support');
            $mail->addAddress($email);

            $reset_link = "https://jeevansathimela.com/reset-password.php?token=$token"; // <-- IMPORTANT: CHANGE THIS URL

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "<h2>Password Reset Request</h2>
                              <p>A request has been made to reset the password for your account.</p>
                              <p>Please click the link below to set a new password. This link is valid for 1 hour.</p>
                              <p><a href='$reset_link' style='padding:10px 15px; background-color:#f6af04; color:white; text-decoration:none; border-radius:5px;'>Reset My Password</a></p>
                              <p>If you did not request a password reset, please ignore this email.</p>";

            $mail->send();
            $message = "<div class='alert alert-success'>A password reset link has been sent to your email address.</div>";

        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>The instruction email could not be sent. Please contact support. Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>No account found with that email address.</div>";
    }
}
?>
<section>
    <div class="login">
        <div class="container">
            <div class="row">
                <div class="inn">
                    <div class="lhs">
                        <div class="tit"><h2><b>Forgot your password?</b><br>No problem.</h2></div>
                        <div class="im"><img src="images/login-couple.png" alt=""></div>
                    </div>
                    <div class="rhs">
                        <div>
                            <div class="form-tit">
                                <h1>Reset Password</h1>
                                <p>Enter your email address and we will send you a link to reset your password.</p>
                            </div>
                            <?php echo $message; ?>
                            <div class="form-login">
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="lb">Email Address:</label>
                                        <input type="email" class="form-control" name="email" placeholder="Enter your registered email" required>
                                    </div>
                                    <button type="submit" name="send_reset_link" class="btn btn-primary">Send Reset Link</button>
                                </form>
                                <div class="mt-3">
                                    <a href="login.php">Back to Login</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
