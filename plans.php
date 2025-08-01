<?php
    session_start();
    include "db/conn.php";
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";

    // --- Get User ID from SESSION ---
    $user_id = 0;
    if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
        $userN = $_SESSION['username'];
        $psw = $_SESSION['password'];

        // Securely fetch the user ID from the database using the session credentials
        $stmt = $conn->prepare("SELECT user_id FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
        $stmt->bind_param("ss", $userN, $psw);
        $stmt->execute();
        $user_res = $stmt->get_result();

        if ($user_data = $user_res->fetch_assoc()) {
            $user_id = $user_data['user_id'];
        }
    }
    
    // If we can't find a logged-in user, redirect to the login page
    if ($user_id === 0) {
        header("Location: login.php?error=session");
        exit();
    }
?>
<!-- Your existing HTML from plans.html -->
<section>
    <div class="plans-ban">
        <div class="container">
            <div class="row">
                <span class="pri">Pricing</span>
                <h1>Upgrade Your Plan</h1>
                <p>Unlock premium features to find your perfect match faster.</p>
            </div>
        </div>
    </div>
</section>
<section>
    <div class="plans-main">
        <div class="container">
            <div class="row">
                <ul>
                    <!-- Plan 1: 3 Months -->
                    <li>
                        <div class="pri-box">
                            <h2>3 Months Plan</h2>
                            <!-- The link now correctly sends the user ID to the payment page -->
                            <a href="pay.php?id=<?php echo $user_id; ?>&type=plan&plan=3_months" class="cta">Get Started</a>
                            <span class="pri-cou"><b>₹250</b></span>
                            <ol>
                                <li><i class="fa fa-check"></i> Unlimited Chat</li>
                                <li><i class="fa fa-check"></i> Send Unlimited Interests</li>
                                <li><i class="fa fa-check"></i> View Contact Details</li>
                            </ol>
                        </div>
                    </li>
                     <!-- Plan 2: 6 Months -->
                    <li>
                        <div class="pri-box pri-box-pop">
                            <span class="pop-pln">Most popular</span>
                            <h2>6 Months Plan</h2>
                            <a href="pay.php?id=<?php echo $user_id; ?>&type=plan&plan=6_months" class="cta">Get Started</a>
                            <span class="pri-cou"><b>₹400</b></span>
                             <ol>
                                <li><i class="fa fa-check"></i> Unlimited Chat</li>
                                <li><i class="fa fa-check"></i> Send Unlimited Interests</li>
                                <li><i class="fa fa-check"></i> View Contact Details</li>
                            </ol>
                        </div>
                    </li>
                     <!-- Plan 3: 12 Months -->
                    <li>
                        <div class="pri-box">
                            <h2>12 Months Plan</h2>
                            <a href="pay.php?id=<?php echo $user_id; ?>&type=plan&plan=12_months" class="cta">Get Started</a>
                            <span class="pri-cou"><b>₹700</b></span>
                             <ol>
                                <li><i class="fa fa-check"></i> Unlimited Chat</li>
                                <li><i class="fa fa-check"></i> Send Unlimited Interests</li>
                                <li><i class="fa fa-check"></i> View Contact Details</li>
                            </ol>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- Include your standard footer section -->
<?php include "inc/footer.php"; ?>