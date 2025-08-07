<?php
    session_start();
    include "db/conn.php";
    
    // For development, show errors. For production, turn this off.
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATE THE VIEWER ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('You must be logged in to view profiles.'); window.location.href='login.php';</script>";
        exit();
    }
    $viewer_stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
    $viewer_stmt->bind_param("ss", $_SESSION['username'], $_SESSION['password']);
    $viewer_stmt->execute();
    $viewer = $viewer_stmt->get_result()->fetch_assoc();
    if(!$viewer) {
        session_destroy();
        echo "<script>alert('Your session is invalid. Please log in again.'); window.location.href='login.php';</script>";
        exit();
    }

    // --- 2. GET & VALIDATE THE PROFILE ID FROM URL ---
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        header("Location: see-other-profile.php?error=invalid_id");
        exit();
    }
    $user_id_to_view = (int)$_GET['id'];

    // --- 3. FETCH THE TARGET PROFILE'S DATA (Only if user is approved) ---
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ? AND user_status = 1");
    $stmt->bind_param("i", $user_id_to_view);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile_found = ($result->num_rows > 0);
    $user = $profile_found ? $result->fetch_assoc() : null;

    // --- 4. FETCH ONLY APPROVED PHOTOS FOR THE GALLERY ---
    $photos = null;
    if ($profile_found) {
        $photos_stmt = $conn->prepare("SELECT image_path FROM tbl_user_photos WHERE user_id = ? AND approval_status = 1 ORDER BY is_profile_picture DESC, upload_date ASC");
        $photos_stmt->bind_param("i", $user_id_to_view);
        $photos_stmt->execute();
        $photos = $photos_stmt->get_result();
    }
?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo $profile_found ? htmlspecialchars($user['user_name']) . ' | Profile Details' : 'Profile Not Found'; ?></title>
    <!-- Your standard META and CSS links -->
    <link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bs5-lightbox/1.8.3/bs5-lightbox.min.css">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; }
        .gallery-grid img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; cursor: pointer; transition: transform 0.2s; }
        .gallery-grid img:hover { transform: scale(1.05); } .details-list dt { font-weight: 600; }
        .main-profile-img { width: 100%; height: auto; max-height: 500px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>
    <div class="container my-4 my-md-5">
        <?php if ($profile_found): ?>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card sticky-top" style="top: 80px;">
                    <img src="upload/<?php echo !empty($user['user_img']) ? htmlspecialchars($user['user_img']) : 'default-profile.png'; ?>" class="main-profile-img" alt="Profile photo">
                    <div class="card-body text-center">
                        <h4 class="card-title"><?php echo htmlspecialchars($user['user_name']); ?></h4>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($user['user_age']); ?> Years, <?php echo htmlspecialchars($user['user_jobType'] ?? 'N/A'); ?></p>
                        <p class="card-text"><?php echo htmlspecialchars($user['user_city'] . ', ' . $user['user_country']); ?></p>
                        <?php
                            // --- ** UPDATED BUTTON LOGIC ** ---
                            if ($viewer['user_id'] != $user['user_id']) {
                                // Check if the VIEWING user has a premium plan
                                $is_premium = false;
                                if (!empty($viewer['plan_type']) && $viewer['plan_type'] != 'Free' && !empty($viewer['plan_expiry_date'])) {
                                    if (new DateTime() <= new DateTime($viewer['plan_expiry_date'])) { $is_premium = true; }
                                }
                                
                                // Sending interest is ALWAYS free.
                                $interest_link = "user-interests.php?send_interest=" . $user['user_id'];
                                $interest_text = "Send Interest";

                                // Chatting is a PREMIUM feature.
                                $chat_link = $is_premium ? "open-chat.php?receiver_id=" . $user['user_id'] : "plans.php";
                                $chat_text = $is_premium ? "Chat Now" : "Upgrade to Chat";
                        ?>
                            <div class="d-grid gap-2">
                                <a href="<?php echo $chat_link; ?>" class="btn btn-secondary"><i class="fa fa-comments"></i> <?php echo $chat_text; ?></a>
                                
                                <!-- This button is now always functional and always uses the confirmation pop-up -->
                                <a href="<?php echo $interest_link; ?>" 
                                   class="btn btn-primary confirm-action-btn"
                                   data-title="Send Interest?"
                                   data-text="Are you sure you want to send an interest to <?php echo htmlspecialchars(addslashes($user['user_name'])); ?>?"
                                   data-confirm-text="Yes, send interest!">
                                   <i class="fa fa-heart"></i> <?php echo $interest_text; ?>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5>About <?php echo htmlspecialchars(explode(' ', $user['user_name'])[0]); ?></h5></div>
                    <div class="card-body"><p>I am a <?php echo htmlspecialchars($user['user_age']); ?>-year-old <?php echo htmlspecialchars($user['user_maritalstatus']); ?> <?php echo htmlspecialchars($user['user_gender']); ?> from <?php echo htmlspecialchars($user['user_city']); ?>. Currently, I work as a <?php echo htmlspecialchars($user['user_jobType']); ?>. My hobbies include <?php echo htmlspecialchars($user['user_hobbies']); ?>.</p></div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><h5>Photo Gallery</h5></div>
                    <div class="card-body">
                        <div class="gallery-grid">
                            <?php if ($photos && $photos->num_rows > 0): while ($photo = $photos->fetch_assoc()): ?>
                                <a href="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" data-toggle="lightbox" data-gallery="profile-gallery">
                                    <img src="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" class="img-fluid" alt="Gallery photo">
                                </a>
                            <?php endwhile; else: ?>
                                <p class="text-muted">This user has no approved photos in their gallery.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Complete Profile Details</h5></div>
                    <div class="card-body">
                        <dl class="row details-list">
                            <dt class="col-sm-4">Age / Height</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_age']); ?> Years / <?php echo htmlspecialchars($user['user_height']); ?></dd>
                            <dt class="col-sm-4">Marital Status</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_maritalstatus']); ?></dd>
                            <?php if (isset($user['user_maritalstatus']) && ($user['user_maritalstatus'] == 'Divorced' || $user['user_maritalstatus'] == 'Widowed')): ?>
                                <dt class="col-sm-4">Has Children</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_has_kids'] ?? 'Not specified'); ?></dd>
                            <?php endif; ?>
                            <dt class="col-sm-4">Religion / Caste</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_religion']); ?> / <?php echo htmlspecialchars($user['user_namecast']); ?></dd>
                            <dt class="col-sm-4">Location</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_city'] . ', ' . $user['user_state'] . ', ' . $user['user_country']); ?></dd>
                            <dt class="col-sm-4">Highest Education</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_degree']); ?></dd>
                            <dt class="col-sm-4">Profession</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_jobType']); ?></dd>
                            <dt class="col-sm-4">Annual Income</dt><dd class="col-sm-8"><?php echo htmlspecialchars($user['user_salary']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="alert alert-danger" role="alert">
                    <h2 class="alert-heading">Profile Not Found</h2>
                    <p>Sorry, the profile you are looking for does not exist, is awaiting approval, or is no longer available.</p><hr>
                    <a href="see-other-profile.php" class="btn btn-primary">Browse Other Profiles</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/jquery.min.js"></script><script src="js/bootstrap.min.js"></script><script src="js/custom.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bs5-lightbox/1.8.3/index.bundle.min.js"></script>
</body>
</html>