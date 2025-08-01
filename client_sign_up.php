<?php 
    // This file is located at the root: kevalgothi-os_in_website/client_sign_up.php
    include "db/conn.php";
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";

    // --- Bring in the modern PHPMailer classes ---
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // The paths must match your new folder structure
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
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
                                <form method="POST">
                                    <div class="form-group"><label class="lb">Religion:</label><select class="form-control" name="religion" required><option selected disabled>Select Religion</option><option value="Christianity">Christianity</option><option value="Hindu">Hindu</option><option value="Islam">Islam</option><option value="Sikh">Sikh</option></select></div>
                                    <div class="form-group"><label class="lb">Full Name:</label><input type="text" class="form-control" name="name" placeholder="Enter your full name" required></div>
                                    <div class="form-group"><label class="lb">Gender:</label><select class="form-control" name="gender" required><option selected disabled>Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                                    <div class="form-group"><label class="lb">Age:</label><input type="text" class="form-control" name="age" placeholder="Enter Your Age" required></div>
                                    <div class="form-group"><label class="lb">Phone:</label><input type="text" class="form-control" name="phone" placeholder="Enter phone number" required></div>
                                    <!-- Added Email Field -->
                                    <div class="form-group"><label class="lb">Email:</label><input type="email" class="form-control" name="email" placeholder="Enter your valid email" required></div>
                                    <div class="form-group"><label class="lb">Password:</label><input type="password" class="form-control" name="pswd" placeholder="Enter password" required></div>
                                    <button type="submit" name="createuser" class="btn btn-primary">Create Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    if(isset($_POST['createuser'])) {
                        // Sanitize all inputs
                        $religion = mysqli_real_escape_string($conn, $_POST['religion']);
                        $name = mysqli_real_escape_string($conn, $_POST['name']);
                        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
                        $age = mysqli_real_escape_string($conn, $_POST['age']);
                        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                        $email = mysqli_real_escape_string($conn, $_POST['email']);
                        $pswd = mysqli_real_escape_string($conn, $_POST['pswd']);
                        $genid = $name."/".rand(0000,9999).date('d');

                        $stmt_check = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_phone = ? OR user_email = ?");
                        $stmt_check->bind_param("ss", $phone, $email);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();

                        if($result_check->num_rows > 0){
                            echo "<script>alert('This phone number or email is already registered.');</script>";
                        } else {
                            $otp = rand(100000, 999999);

                            $stmt_insert = $conn->prepare("INSERT INTO tbl_user (user_gen_id, user_religion, user_name, user_gender, user_age, user_phone, user_email, user_pass, user_otp, user_status, user_payment_status, user_otp_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '0', '0', '0')");
                            $stmt_insert->bind_param("ssssssssi", $genid, $religion, $name, $gender, $age, $phone, $email, $pswd, $otp);

                            if($stmt_insert->execute()){ 
                                $last_ID = $conn->insert_id;
                                
                                $mail = new PHPMailer(true);
                                try {
                                    // IMPORTANT: CONFIGURE YOUR EMAIL SETTINGS HERE
                                    $mail->isSMTP();
                                    $mail->Host       = 'smtp.gmail.com'; // Use your mail server
                                    $mail->SMTPAuth   = true;
                                    $mail->Username   = 'creativewebhub.newsoftware@gmail.com'; // Your email address
                                    $mail->Password   = 'fmlh nhrw pucw usoj'; // Your Gmail App Password
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                    $mail->Port       = 465;

                                    $mail->setFrom('no-reply@jeevansaathimela.com', 'Jeevansaathi Mela');
                                    $mail->addAddress($email, $name);

                                    $mail->isHTML(true);
                                    $mail->Subject = 'Your OTP for Account Verification';
                                    $mail->Body    = "<h2>Welcome to Jeevansaathi Mela!</h2>
                                                      <p>Your One-Time Password (OTP) to verify your account is:</p>
                                                      <h3 style='color:#f6af04; font-size:24px;'>$otp</h3>
                                                      <p>Please enter this code on the verification page to continue.</p>";

                                    $mail->send();
                                    
                                    echo "<script>alert('Registration successful! An OTP has been sent to your email.');</script>";
                                    echo "<script>window.location.href='verify-otp.php?id=" . htmlspecialchars($last_ID, ENT_QUOTES, 'UTF-8') . "';</script>";
                                    exit();

                                } catch (Exception $e) {
                                    echo "<script>alert('Account created, but the OTP email could not be sent. Please contact support. Error: {$mail->ErrorInfo}');</script>";
                                }

                            } else {
                                echo "<script>alert('Error creating account. Please contact support.');</script>";
                            }
                        }
                    }
                ?>
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