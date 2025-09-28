<?php
// =========================================================================
//  1. PROCESS THE FORM
// =========================================================================
session_start(); 

// --- Use __DIR__ for reliable file paths ---
require_once __DIR__ . '/db/conn.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize a message variable for user feedback
$message = '';

// Check if the form has been submitted
if (isset($_POST['createuser'])) {
    // --- Get data from the form ---
    $religion = $_POST['religion'];
    $name     = $_POST['name'];
    $gender   = $_POST['gender'];
    $dob      = $_POST['dob'];
    $phone    = $_POST['phone'];
    $email    = $_POST['email'];
    $pswd     = $_POST['pswd']; // The raw, plain-text password

    // --- Check if user already exists ---
    $stmt_check = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_phone = ? OR user_email = ?");
    $stmt_check->bind_param("ss", $phone, $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $message = '<div class="alert alert-danger" role="alert">This phone number or email is already registered.</div>';
    } else {
        // --- Prepare data for insertion ---
        $genid = $name . "/" . rand(1000, 9999) . date('d');
        $otp   = rand(100000, 999999);

        // --- SQL QUERY with plain-text password ---
        $stmt_insert = $conn->prepare(
            "INSERT INTO tbl_user (
                user_gen_id, user_religion, user_name, user_gender, user_phone, 
                user_email, user_pass, user_dob, user_otp, user_otp_status, 
                user_payment_status, user_status, user_create_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '0', '0', '0', NOW())"
        );
        
        // --- BINDING with plain-text password ($pswd) ---
        // The type string is 'ssssssssi'. We are now binding the raw $pswd variable.
        $stmt_insert->bind_param("ssssssssi", $genid, $religion, $name, $gender, $phone, $email, $pswd, $dob, $otp);

        if ($stmt_insert->execute()) {
            $last_ID = $conn->insert_id;
            $mail = new PHPMailer(true);

            try {
                // PHPMailer Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'creativewebhub.newsoftware@gmail.com';
                $mail->Password   = 'fmlh nhrw pucw usoj';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                // Email content
                $mail->setFrom('no-reply@jeevansaathimela.com', 'Jeevansaathi Mela');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Account Verification';
                $mail->Body    = "<h2>Welcome to Jeevansaathi Mela!</h2><p>Your One-Time Password (OTP) is: <h3 style='color:#f6af04;'>$otp</h3></p>";

                $mail->send();
                
                // Set success message and redirect to the OTP page
                $_SESSION['message'] = '<div class="alert alert-success" role="alert">Registration successful! An OTP has been sent to your email.</div>';
                header("Location: verify-otp.php?id=" . htmlspecialchars($last_ID, ENT_QUOTES, 'UTF-8'));
                exit();

            } catch (Exception $e) {
                // Show a generic error and log the real error for the admin
                $message = '<div class="alert alert-warning" role="alert">Account created, but the OTP email could not be sent. Please contact support.</div>';
                error_log("PHPMailer Error on client_sign_up: " . $mail->ErrorInfo);
            }
        } else {
            // Show a generic error and log the real error for the admin
            $message = '<div class="alert alert-danger" role="alert">An error occurred while creating your account. Please try again.</div>';
            error_log("MySQLi Insert Error on client_sign_up: " . $stmt_insert->error);
        }
    }
}

// =========================================================================
//  2. RENDER THE PAGE
// =========================================================================
include __DIR__ . '/inc/header.php';
include __DIR__ . '/inc/bodystart.php';
include __DIR__ . '/inc/navbar.php';
?>
<section>
    <div class="login">
        <div class="container">
            <div class="row">
                <div class="inn">
                    <div class="lhs">
                        <div class="tit"><h2>Now <b>Find your life partner</b> Easy and fast.</h2></div>
                        <div class="im"><img src="images/login-couple.png" alt=""></div>
                        <div class="log-bg"> </div>
                    </div>
                    <div class="rhs">
                        <div>
                            <div class="form-tit">
                                <h4>Start for free</h4><h1>Sign up to Matrimony</h1>
                                <p>Already a member? <a href="login.php">Login</a></p>
                            </div>
                            <div class="form-login">
                                <?php
                                // Display any feedback message
                                if (!empty($message)) { echo $message; }
                                ?>
                                <form method="POST" action="client_sign_up.php">
                                    <div class="form-group"><label class="lb">Religion:</label><select class="form-control" name="religion" required><option selected disabled>Select Religion</option><option value="Christianity">Christianity</option><option value="Hindu">Hindu</option><option value="Islam">Islam</option><option value="Sikh">Sikh</option></select></div>
                                    <div class="form-group"><label class="lb">Full Name:</label><input type="text" class="form-control" name="name" placeholder="Enter your full name" required></div>
                                    <div class="form-group"><label class="lb">Gender:</label><select class="form-control" name="gender" required><option selected disabled>Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                                    <div class="form-group">
                                        <label class="lb">Date of Birth:</label>
                                        <input type="date" class="form-control" name="dob" required>
                                    </div>
                                    <div class="form-group"><label class="lb">Phone:</label><input type="text" class="form-control" name="phone" placeholder="Enter phone number" required></div>
                                    <div class="form-group"><label class="lb">Email:</label><input type="email" class="form-control" name="email" placeholder="Enter your valid email" required></div>
                                    <div class="form-group"><label class="lb">Password:</label><input type="password" class="form-control" name="pswd" placeholder="Enter password" required></div>
                                    <button type="submit" name="createuser" class="btn btn-primary">Create Account</button>
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
include __DIR__ . '/inc/copyright.php';
include __DIR__ . '/inc/footerlink.php';
?>