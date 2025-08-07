<?php 
    include "db/conn.php";
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";
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
                                    <div class="form-group"><label class="lb">Password:</label><input type="password" class="form-control" name="pswd" placeholder="Enter password" required></div>
                                    <button type="submit" name="createuser" class="btn btn-primary">Create Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    if(isset($_POST['createuser'])) {
                        $religion = $_POST['religion'];
                        $name = $_POST['name'];
                        $gender = $_POST['gender'];
                        $age = $_POST['age'];
                        $phone = $_POST['phone'];
                        $pswd = $_POST['pswd'];
                        $genid = $name."/".rand(0000,9999).date('d');

                        // Use prepared statement to check if phone exists
                        $stmt_check = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_phone = ?");
                        $stmt_check->bind_param("s", $phone);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();

                        if($result_check->num_rows > 0){
                            echo "<script>alert('This phone number is already registered.');</script>";
                        } else {
                            // Use prepared statement to insert user securely
                            $stmt_insert = $conn->prepare("INSERT INTO tbl_user (user_gen_id, user_religion, user_name, user_gender, user_age, user_phone, user_pass, user_status, user_payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, '0', '0')");
                            $stmt_insert->bind_param("sssssss", $genid, $religion, $name, $gender, $age, $phone, $pswd);

                            if($stmt_insert->execute()){ 
                                $last_ID = $conn->insert_id;
                                echo "<script>window.location.href='pay.php?id=" . htmlspecialchars($last_ID, ENT_QUOTES, 'UTF-8') . "&type=registration';</script>";
                                exit();
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