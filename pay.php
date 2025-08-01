<?php
    session_start();
    include "db/conn.php";
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";

    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $payment_type = isset($_GET['type']) ? $_GET['type'] : 'plan';
    $plan = isset($_GET['plan']) ? $_GET['plan'] : '';

    $amount_in_rupees = 0;
    $description = '';
    $redirect_url = '';

    if ($payment_type === 'registration') {
        $amount_in_rupees = 99;
        $description = 'One-time Registration Fee';
        $redirect_url = "thanku.php?id=$user_id&type=registration";
    } else {
        // This handles plan upgrades for logged-in users
        if (!isset($_SESSION['username'])) {
             header("Location: login.php"); exit(); // Redirect if not logged in
        }
        switch ($plan) {
            case '3_months': $amount_in_rupees = 250; break;
            case '6_months': $amount_in_rupees = 400; break;
            case '12_months': $amount_in_rupees = 700; break;
            default: die("Invalid plan selected. <a href='plans.php'>Go back</a>.");
        }
        $description = 'Plan Upgrade: '. str_replace('_', ' ', $plan);
        $redirect_url = "thanku.php?id=$user_id&type=plan&plan=$plan";
    }

    $amount_in_paise = $amount_in_rupees * 100;

    $stmt = $conn->prepare("SELECT user_name, user_phone FROM tbl_user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_details = $stmt->get_result();
    $user_data = $user_details->fetch_assoc();
?>
<div style="text-align: center; padding: 100px;">
    <h2>Complete Your Payment</h2>
    <p style="font-size: 1.2rem;">You are paying <strong>₹<?php echo $amount_in_rupees; ?></strong> for: <strong><?php echo $description; ?></strong></p>
    <div style="margin-top: 30px;">
        <form action="<?php echo $redirect_url; ?>" method="POST">
        <script
            src="https://checkout.razorpay.com/v1/checkout.js"
            data-key="rzp_live_ryIBxXaliWjSEe" // <-- IMPORTANT: USE YOUR LIVE KEY IN PRODUCTION
            data-amount="<?php echo $amount_in_paise; ?>"
            data-currency="INR"
            data-buttontext="Pay ₹<?php echo $amount_in_rupees; ?> Securely"
            data-name="Jeevansaathi Mela"
            data-description="<?php echo $description; ?>"
            data-image="images/logo-b.png" 
            data-prefill.name="<?php echo htmlspecialchars($user_data['user_name']); ?>"
            data-prefill.contact="<?php echo htmlspecialchars($user_data['user_phone']); ?>"
            data-theme.color="#f6af04"
        ></script>
        <input type="hidden" value="hidden" name="hidden">
        </form>
    </div>
    <p style="margin-top:20px;">Please click the button above to proceed.</p>
</div>
<?php include "inc/footer.php"; ?>