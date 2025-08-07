<?php
    session_start();
    include "db/conn.php";
    
    // For development, show errors.
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATE & FETCH USER DATA ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        header("Location: login.php");
        exit();
    }
    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
    $stmt->bind_param("ss", $userN, $psw);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $loggedInUser = $result->fetch_assoc();
    $loggedInUserID = $loggedInUser['user_id'];

    // --- 2. DEFINE PLAN HIERARCHY & CHECK USER'S CURRENT PLAN ---
    $plan_ranks = [
        'Free' => 0,
        '3_months' => 1,
        '6_months' => 2,
        '12_months' => 3
    ];
    
    $current_plan_name = $loggedInUser['plan_type'] ?? 'Free';
    $current_plan_rank = $plan_ranks[$current_plan_name] ?? 0;
    
    $has_active_plan = false;
    if ($current_plan_name != 'Free' && !empty($loggedInUser['plan_expiry_date'])) {
        $expiry_date = new DateTime($loggedInUser['plan_expiry_date']);
        $today = new DateTime();
        if ($expiry_date >= $today) {
            $has_active_plan = true;
        }
    }

    // Include header files after all PHP logic
    include "inc/header.php";
    include "inc/bodystart.php";
    include "inc/navbar.php";
?>
<style>
    /* Custom style to highlight the user's current plan */
    .current-plan-highlight {
        border: 3px solid #28a745; /* A green border */
        box-shadow: 0 0 15px rgba(40, 199, 111, 0.5);
        position: relative;
    }
    .current-plan-badge {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #28a745;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
    }
</style>
<!-- Banner Section -->
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

<!-- Main Plans Section -->
<section>
    <div class="plans-main">
        <div class="container">
            <!-- Current Plan Status Display -->
            <?php if ($has_active_plan): ?>
            <div class="row mb-5">
                <div class="col-md-8 mx-auto text-center">
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">Your Current Plan: <?php echo htmlspecialchars($loggedInUser['plan_type']); ?></h4>
                        <p>Your premium membership is active until <strong><?php echo date('d F, Y', strtotime($loggedInUser['plan_expiry_date'])); ?></strong>.</p>
                        <hr>
                        <p class="mb-0">You can upgrade to a higher plan at any time.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pricing Table -->
            <div class="row">
                <ul>
                    <?php
                        // --- DYNAMIC PLAN RENDERING ---
                        $all_plans = [
                            '3_months' => ['name' => '3 Months Plan', 'price' => 250, 'popular' => false],
                            '6_months' => ['name' => '6 Months Plan', 'price' => 400, 'popular' => true],
                            '12_months' => ['name' => '12 Months Plan', 'price' => 700, 'popular' => false]
                        ];

                        foreach ($all_plans as $plan_key => $plan_details):
                            $plan_rank = $plan_ranks[$plan_key];
                            $is_current_plan = ($has_active_plan && $plan_rank === $current_plan_rank);
                            $is_upgrade = ($plan_rank > $current_plan_rank);
                            $is_downgrade = ($plan_rank < $current_plan_rank);

                            $link = "pay.php?id={$loggedInUserID}&type=plan&plan={$plan_key}";
                            $button_text = "Get Started";
                            $button_class = "cta";
                            $extra_classes = "";

                            if ($has_active_plan) {
                                if ($is_current_plan) {
                                    $button_text = "Extend Plan";
                                    $extra_classes = "current-plan-highlight";
                                } elseif ($is_upgrade) {
                                    $button_text = "Upgrade Now";
                                } else { // is_downgrade
                                    $link = "#!";
                                    $button_text = "Downgrade Not Available";
                                    $button_class = "cta disabled";
                                }
                            }
                    ?>
                    <li>
                        <div class="pri-box <?php echo $plan_details['popular'] ? 'pri-box-pop' : ''; ?> <?php echo $extra_classes; ?>">
                            <?php if ($plan_details['popular']) echo '<span class="pop-pln">Most popular</span>'; ?>
                            <?php if ($is_current_plan) echo '<span class="current-plan-badge">Your Current Plan</span>'; ?>
                            
                            <h2><?php echo $plan_details['name']; ?></h2>
                            <a href="<?php echo $link; ?>" class="<?php echo $button_class; ?>"><?php echo $button_text; ?></a>
                            <span class="pri-cou"><b>₹<?php echo $plan_details['price']; ?></b></span>
                            <ol>
                                <li><i class="fa fa-check"></i> Unlimited Chat</li>
                                <li><i class="fa fa-check"></i> Send Unlimited Interests</li>
                                <li><i class="fa fa-check"></i> View Contact Details</li>
                            </ol>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php
    include "inc/copyright.php";
    include "inc/footerlink.php";
?>