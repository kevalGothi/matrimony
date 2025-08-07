<?php
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";

    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($user_id === 0) {
        die("An error occurred. Invalid user ID provided.");
    }
?>
<section class="plans-main" style="padding: 60px 0; background-color: #f9f9f9;">
    <div class="container">
        <div class="row">
            <div class="text-center" style="margin-bottom: 40px">
                <h1>Account Created Successfully!</h1>
                <h3>Please choose a subscription plan to continue.</h3>
                <p>Your profile will be visible to others only after payment and admin approval.</p>
            </div>
            <div class="plans-main">
                <ul>
                    <!-- Plan 1: 3 Months -->
                    <li>
                        <div class="pri-box">
                            <h2>3 Months Plan</h2>
                            <p>Basic access for 3 months.</p>
                            <a href="pay.php?id=<?php echo $user_id; ?>&plan=3_months" class="cta">Pay ₹250</a>
                            <span class="pri-cou"><b>₹250</b></span>
                            <ol>
                                <li><i class="fa fa-check"></i> See other profiles</li>
                                <li><i class="fa fa-check"></i> Get your profile approved</li>
                                <li><i class="fa fa-check"></i> Show interest in profiles</li>
                            </ol>
                        </div>
                    </li>
                    <!-- Plan 2: 6 Months -->
                    <li>
                        <div class="pri-box pri-box-pop">
                            <span class="pop-pln">Most popular</span>
                            <h2>6 Months Plan</h2>
                            <p>Best value for 6 months.</p>
                            <a href="pay.php?id=<?php echo $user_id; ?>&plan=6_months" class="cta">Pay ₹400</a>
                            <span class="pri-cou"><b>₹400</b></span>
                            <ol>
                                <li><i class="fa fa-check"></i> See other profiles</li>
                                <li><i class="fa fa-check"></i> Get your profile approved</li>
                                <li><i class="fa fa-check"></i> Show interest in profiles</li>
                            </ol>
                        </div>
                    </li>
                    <!-- Plan 3: 12 Months -->
                    <li>
                        <div class="pri-box">
                            <h2>12 Months Plan</h2>
                            <p>Full year access.</p>
                            <a href="pay.php?id=<?php echo $user_id; ?>&plan=12_months" class="cta">Pay ₹700</a>
                            <span class="pri-cou"><b>₹700</b></span>
                            <ol>
                                <li><i class="fa fa-check"></i> See other profiles</li>
                                <li><i class="fa fa-check"></i> Get your profile approved</li>
                                <li><i class="fa fa-check"></i> Show interest in profiles</li>
                            </ol>
                        </div>
                    </li>
                </ul>
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