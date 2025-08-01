<?php
include "db/conn.php";
include "inc/header.php";
include "inc/bodystart.php";

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id === 0) {
    die("Invalid request. No user ID specified.");
}

$error_msg = '';
if (isset($_POST['verify_otp'])) {
    $submitted_otp = $_POST['otp'];
    
    $stmt = $conn->prepare("SELECT user_otp FROM tbl_user WHERE user_id = ? AND user_otp_status = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['user_otp'] == $submitted_otp) {
            // OTP is correct! Update status and redirect to payment.
            $update_stmt = $conn->prepare("UPDATE tbl_user SET user_otp_status = 1 WHERE user_id = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            
            echo "<script>alert('Email verified successfully! You will now be redirected to the payment page.');</script>";
            echo "<script>window.location.href='pay.php?id=" . htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') . "&type=registration';</script>";
            exit();
        } else {
            $error_msg = "Invalid OTP. Please try again.";
        }
    } else {
        $error_msg = "This account is already verified or does not exist.";
    }
}
?>

<section>
    <div class="login">
        <div class="container">
            <div class="row">
                <div class="inn" style="margin: auto;">
                    <div class="rhs">
                        <div>
                            <div class="form-tit">
                                <h4>Verify Your Account</h4>
                                <h1>Enter Your OTP</h1>
                                <p>An OTP has been sent to your registered email address.</p>
                            </div>
                            <div class="form-login">
                                <?php if (!empty($error_msg)) echo "<p style='color:red; text-align:center;'>$error_msg</p>"; ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="lb">One-Time Password (OTP):</label>
                                        <input type="text" class="form-control" name="otp" placeholder="Enter 6-digit OTP" required maxlength="6">
                                    </div>
                                    <button type="submit" name="verify_otp" class="btn btn-primary">Verify & Proceed</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>