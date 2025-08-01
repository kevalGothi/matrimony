<?php
    session_start();
    include "db/conn.php";
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";

    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
?>
<section>
    <div class="login" style="padding: 80px 0;">
        <div class="container">
            <div class="row">
                <div class="inn text-center" style="max-width: 600px; margin: auto;">
                    <div class="tit" style="color: #333;">
                        <img src="images/icon/trust.png" alt="Pending Approval" style="width: 100px; margin-bottom: 20px;">
                        <h2 style="font-size: 2em; margin-bottom: 15px;">Profile Submitted for Approval</h2>
                        <h5 style="margin-top: 20px; color: #555;">Thank you for completing your profile!</h5>
                        <p style="font-size: 1.1em; line-height: 1.6;">
                            Our team will review your details. This process usually takes a few hours. Once approved, your profile will become live and visible to other members.
                        </p>
                        <p style="font-size: 1.1em; line-height: 1.6; margin-top:15px;">
                            In the meantime, you can start browsing other profiles to see your potential matches.
                        </p>
                        <a href="see-other-profile.php" class="cta-4" style="margin-top: 30px; padding: 15px 30px; font-size: 1.1em;">Browse Profiles Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include "inc/footer.php"; ?>