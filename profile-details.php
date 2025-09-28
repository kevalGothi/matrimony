<?php
    session_start();
    include "db/conn.php";
    
    // For debugging
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
    $viewer_result = $viewer_stmt->get_result();
    if($viewer_result->num_rows === 0) {
        session_destroy();
        echo "<script>alert('Your session is invalid. Please log in again.'); window.location.href='login.php';</script>";
        exit();
    }
    $viewer = $viewer_result->fetch_assoc();
    $viewer_id = $viewer['user_id'];

    // --- 2. GET PROFILE ID FROM URL ---
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        header("Location: see-other-profile.php?error=invalid_id");
        exit();
    }
    $user_id_to_view = (int)$_GET['id'];

    // --- 3. FETCH PROFILE DATA & HANDLE PENDING EDITS ---
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ? AND user_status = 1");
    $stmt->bind_param("i", $user_id_to_view);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile_found = ($result->num_rows > 0);
    
    if ($profile_found) {
        $user_live_data = $result->fetch_assoc();
        $display_data = $user_live_data; // Default: everyone sees the live, approved data.
        
        // If the profile has pending changes AND the person viewing is the profile owner...
        if (!empty($user_live_data['pending_data']) && $viewer_id == $user_id_to_view) {
            // ...then merge the pending data on top for the owner to see.
            $pending_array = json_decode($user_live_data['pending_data'], true);
            $display_data = array_merge($display_data, $pending_array);
        }
    } else {
        $display_data = null;
    }

    // --- 4. FETCH PHOTOS ---
    $photos = null;
    if ($profile_found) {
        $photos_stmt = $conn->prepare("SELECT image_path FROM tbl_user_photos WHERE user_id = ? AND approval_status = 1 ORDER BY is_profile_picture DESC");
        $photos_stmt->bind_param("i", $user_id_to_view);
        $photos_stmt->execute();
        $photos = $photos_stmt->get_result();
    }
    // --- FATAL ERROR FIX: The query for 'tbl_user_children' has been removed. ---

    // --- 5. INTEREST CHECK ---
    $interest_already_sent = false;
    if ($profile_found && $viewer_id != $user_id_to_view) {
        $check_interest_stmt = $conn->prepare("SELECT chat_id FROM tbl_chat WHERE (chat_senderID = ? AND chat_receiverID = ?) OR (chat_senderID = ? AND chat_receiverID = ?)");
        $check_interest_stmt->bind_param("iiii", $viewer_id, $user_id_to_view, $user_id_to_view, $viewer_id);
        $check_interest_stmt->execute();
        if ($check_interest_stmt->get_result()->num_rows > 0) {
            $interest_already_sent = true;
        }
        $check_interest_stmt->close();
    }
?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo $profile_found ? htmlspecialchars($display_data['user_name']) . ' | Profile Details' : 'Profile Not Found'; ?></title>
    <link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bs5-lightbox/1.8.3/bs5-lightbox.min.css">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; }
        .gallery-grid img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; cursor: pointer; }
        .details-list dt { font-weight: 600; color: #555; }
        .main-profile-img { width: 100%; height: auto; max-height: 500px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>    
    <?php include "inc/header.php"; ?><?php include "inc/bodystart.php"; ?><?php include "inc/navbar.php"; ?>
    <div class="container my-4 my-md-5">
        
        <?php if ($profile_found): ?>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card sticky-top" style="top: 80px;">
                    <img src="upload/<?php echo !empty($display_data['user_img']) ? htmlspecialchars($display_data['user_img']) : 'default-profile.png'; ?>" class="main-profile-img" alt="Profile photo">
                    <div class="card-body text-center">
                        <h4 class="card-title"><?php echo htmlspecialchars($display_data['user_name']); ?></h4>
                        <p class="card-text text-muted">
                            <?php 
                                if (!empty($display_data['user_dob'])) {
                                    echo (new DateTime($display_data['user_dob']))->diff(new DateTime('today'))->y . " Years, ";
                                }
                                echo htmlspecialchars($display_data['user_jobType'] ?? 'N/A'); 
                            ?>
                        </p>
                        <p class="card-text"><?php echo htmlspecialchars($display_data['user_city'] . ', ' . $display_data['user_country']); ?></p>
                        
                        <?php if ($viewer_id != $display_data['user_id']) {
                            $is_premium = (!empty($viewer['plan_type']) && $viewer['plan_type'] != 'Free' && !empty($viewer['plan_expiry_date']) && new DateTime() <= new DateTime($viewer['plan_expiry_date']));
                            $chat_link = $is_premium ? "open-chat.php?receiver_id=" . $display_data['user_id'] : "plans.php";
                            $chat_text = $is_premium ? "Chat Now" : "Upgrade to Chat";
                        ?>
                            <div class="d-grid gap-2">
                                <a href="<?php echo $chat_link; ?>" class="btn btn-secondary"><i class="fa fa-comments"></i> <?php echo $chat_text; ?></a>
                                <?php
                                if ($interest_already_sent) {
                                    echo '<a href="javascript:void(0);" class="btn btn-success disabled"><i class="fa fa-check"></i> Interest Sent</a>';
                                } else {
                                    echo '<a href="javascript:void(0);" class="btn btn-primary send-interest-btn" data-receiver-id="' . $display_data['user_id'] . '"><i class="fa fa-heart"></i> Send Interest</a>';
                                }
                                ?>
                            </div>
                        <?php } else { ?>
                            <a href="user-profile-edit.php" class="btn btn-primary"><i class="fa fa-pencil"></i> Edit My Profile</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                 <div class="card mb-4">
                    <div class="card-header"><h5>About <?php echo htmlspecialchars(explode(' ', $display_data['user_name'])[0]); ?></h5></div>
                    <div class="card-body"><p>
                        I am a <strong><?php if (!empty($display_data['user_dob'])) { echo (new DateTime($display_data['user_dob']))->diff(new DateTime('today'))->y; } ?></strong>-year-old
                        <strong><?php echo htmlspecialchars($display_data['user_maritalstatus'] ?? 'person'); ?></strong> from <strong><?php echo htmlspecialchars($display_data['user_city'] ?? 'their city'); ?></strong>.
                        <?php if (!empty($display_data['user_jobType'])): ?>Currently, I work as a <strong><?php echo htmlspecialchars($display_data['user_jobType']); ?></strong>.<?php endif; ?>
                        <?php if (!empty($display_data['user_hobbies'])): ?> In my free time, my hobbies include <strong><?php echo htmlspecialchars($display_data['user_hobbies']); ?></strong>.<?php endif; ?>
                    </p></div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><h5>Photo Gallery</h5></div>
                    <div class="card-body">
                        <div class="gallery-grid">
                            <?php if ($photos && $photos->num_rows > 0): while ($photo = $photos->fetch_assoc()): ?>
                                <a href="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" data-bs-toggle="lightbox" data-gallery="profile-gallery">
                                    <img src="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" class="img-fluid" alt="Gallery photo">
                                </a>
                            <?php endwhile; else: ?><p class="text-muted">This user has no approved photos in their gallery.</p><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Complete Profile Details</h5></div>
                    <div class="card-body">
                        <dl class="row details-list">
                            <dt class="col-sm-4">Age / Height</dt>
                            <dd class="col-sm-8"><?php if (!empty($display_data['user_dob'])) { echo (new DateTime($display_data['user_dob']))->diff(new DateTime('today'))->y . " Years / "; } echo htmlspecialchars($display_data['user_height'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-4">Marital Status</dt><dd class="col-sm-8"><?php echo htmlspecialchars($display_data['user_maritalstatus'] ?? 'N/A'); ?></dd>
                            
                            <!-- === UPGRADED CHILDREN DETAILS SECTION START === -->
                            <?php if (!empty($display_data['user_has_kids']) && $display_data['user_has_kids'] == 'Yes'): ?>
                                <dt class="col-sm-4">Children</dt>
                                <dd class="col-sm-8">
                                    <?php echo htmlspecialchars($display_data['user_children_count'] ?? 'Yes'); ?>
                                </dd>
                            <?php endif; ?>
                            <!-- === CHILDREN DETAILS SECTION END === -->

                            <dt class="col-sm-4">Religion / Caste</dt><dd class="col-sm-8"><?php echo htmlspecialchars($display_data['user_religion'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($display_data['user_namecast'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-4">Location</dt><dd class="col-sm-8"><?php echo htmlspecialchars(($display_data['user_city'] ?? '') . ', ' . ($display_data['user_state'] ?? '') . ', ' . ($display_data['user_country'] ?? '')); ?></dd>
                            <dt class="col-sm-4">Highest Education</dt><dd class="col-sm-8"><?php echo htmlspecialchars($display_data['user_degree'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-4">Profession</dt><dd class="col-sm-8"><?php echo htmlspecialchars($display_data['user_jobType'] ?? 'N/A'); ?></dd>
                            <dt class="col-sm-4">Annual Income</dt><dd class="col-sm-8"><?php echo htmlspecialchars($display_data['user_salary'] ?? 'N/A'); ?></dd>
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
    
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/custom.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/bs5-lightbox@1.8.3/dist/index.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.send-interest-btn').on('click', function(e) {
                e.preventDefault(); 
                var button = $(this);
                var receiverId = button.data('receiver-id');
                button.addClass('disabled').css('pointer-events', 'none').html('<i class="fa fa-spinner fa-spin"></i> Sending...');
                $.ajax({
                    url: 'api/send_interest.php',
                    type: 'POST',
                    data: { receiver_id: receiverId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            button.removeClass('btn-primary').addClass('btn-success').html('<i class="fa fa-check"></i> Interest Sent');
                        } else {
                            alert('Error: ' + response.message);
                            button.removeClass('disabled').css('pointer-events', 'auto').html('<i class="fa fa-heart"></i> Send Interest');
                        }
                    },
                    error: function() {
                        alert('An unexpected server error occurred.');
                        button.removeClass('disabled').css('pointer-events', 'auto').html('<i class="fa fa-heart"></i> Send Interest');
                    }
                });
            });
        });
    </script>
</body>
</html>