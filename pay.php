<?php
    session_start();
    include "db/conn.php";
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";

    // Ensure user ID is valid
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($user_id === 0 && !isset($_SESSION['user_id'])) {
        // Redirect to login if no user context
        header("Location: login.php");
        exit();
    } elseif ($user_id === 0) {
        // Fallback to session user ID if available
        $user_id = $_SESSION['user_id'];
    }

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
             header("Location: login.php");
             exit(); // Redirect if not logged in
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

    $stmt = $conn->prepare("SELECT user_name, user_phone, user_email FROM tbl_user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_details = $stmt->get_result();
    $user_data = $user_details->fetch_assoc();
?>

<!-- Added CSS for the modern button -->
<style>
    .modern-payment-button {
        background: linear-gradient(90deg, #f6af04, #f9c548);
        border: none;
        border-radius: 8px;
        color: white;
        cursor: pointer;
        font-size: 1.2rem;
        font-weight: bold;
        padding: 15px 30px;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .modern-payment-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
</style>

<div style="text-align: center; padding: 100px;">
    <h2>Complete Your Payment</h2>
    <p style="font-size: 1.2rem;">You are about to pay <strong>₹<?php echo $amount_in_rupees; ?></strong> for: <strong><?php echo $description; ?></strong></p>

    <div style="margin-top: 30px;">
        <!-- The form is now just a container for the button -->
        <form>
            <button type="button" id="payButton" class="modern-payment-button">Pay ₹<?php echo $amount_in_rupees; ?> Securely</button>
        </form>
    </div>

    <p style="margin-top:20px;">Click the button above to proceed to our secure payment gateway.</p>
</div>

<!-- Razorpay script and initialization -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('payButton').onclick = function(e){
    e.preventDefault(); // Prevent form submission

    var options = {
        "key": "rzp_live_ryIBxXaliWjSEe", // IMPORTANT: USE YOUR LIVE KEY IN PRODUCTION
        "amount": "<?php echo $amount_in_paise; ?>",
        "currency": "INR",
        "name": "Jeevansaathi Mela",
        "description": "<?php echo $description; ?>",
        "image": "images/logo-b.png",
        "handler": function (response){
            // This function is called after the payment is successful
            // Redirect to your thank you page
            window.location.href = "<?php echo $redirect_url; ?>&payment_id=" + response.razorpay_payment_id;
        },
        "prefill": {
            "name": "<?php echo htmlspecialchars($user_data['user_name']); ?>",
            "email": "<?php echo htmlspecialchars($user_data['user_email']); ?>", // Added email prefill
            "contact": "<?php echo htmlspecialchars($user_data['user_phone']); ?>"
        },
        "notes": {
            "user_id": "<?php echo $user_id; ?>",
            "plan": "<?php echo $plan; ?>"
        },
        "theme": {
            "color": "#f6af04"
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.on('payment.failed', function (response){
            alert("Payment Failed: " + response.error.description);
            // You can handle payment failures here
    });
    rzp1.open();
};
</script>

<?php
    // Include your footer if you have one
    // include "inc/footer.php";
?>