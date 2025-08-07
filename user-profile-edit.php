<?php
    session_start();
    include "db/conn.php";
    
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATION ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
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
        echo "<script>alert('Session error. Please login again.'); window.location.href='login.php';</script>";
        exit();
    }
    $user = $result->fetch_assoc();
    $loggedInUserID = $user['user_id'];
    $loggedInUser = $user; // Alias for the dashboard navigation include
    $messages = [];

    // --- 2. HANDLE PHOTO UPLOADS & DELETES (Immediate Actions) ---
    if (isset($_POST['upload_photos'])) {
        if (isset($_FILES['gallery_photos']) && !empty($_FILES['gallery_photos']['name'][0])) {
            $main_pic_exists_res = $conn->query("SELECT photo_id FROM tbl_user_photos WHERE user_id = {$loggedInUserID} AND is_profile_picture = 1");
            $main_pic_exists = $main_pic_exists_res->num_rows > 0;
            $upload_dir = 'upload/';
            foreach ($_FILES['gallery_photos']['name'] as $i => $name) {
                if ($_FILES['gallery_photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['gallery_photos']['tmp_name'][$i];
                    $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']) && $_FILES['gallery_photos']['size'][$i] < 5000000) { // 5MB limit
                        $new_filename = "user{$loggedInUserID}_" . uniqid('', true) . "." . $file_ext;
                        if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                            $is_main = (!$main_pic_exists && $i === 0) ? 1 : 0; // Auto-set first photo as main if none exists
                            $insert_stmt = $conn->prepare("INSERT INTO tbl_user_photos (user_id, image_path, approval_status, is_profile_picture) VALUES (?, ?, 0, ?)");
                            $insert_stmt->bind_param("isi", $loggedInUserID, $new_filename, $is_main);
                            if ($insert_stmt->execute() && $is_main) {
                                $conn->query("UPDATE tbl_user SET user_img = '{$new_filename}' WHERE user_id = {$loggedInUserID}");
                            }
                        }
                    }
                }
            }
            header("Location: user-profile-edit.php?success=uploaded");
            exit();
        }
    }
    if (isset($_GET['delete_photo'])) {
        $photo_id_to_delete = (int)$_GET['delete_photo'];
        $check_stmt = $conn->prepare("SELECT image_path, is_profile_picture FROM tbl_user_photos WHERE photo_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $photo_id_to_delete, $loggedInUserID);
        $check_stmt->execute();
        $photo_data = $check_stmt->get_result()->fetch_assoc();
        if ($photo_data) {
            $delete_stmt = $conn->prepare("DELETE FROM tbl_user_photos WHERE photo_id = ?");
            $delete_stmt->bind_param("i", $photo_id_to_delete);
            if ($delete_stmt->execute()) {
                if(file_exists('upload/' . $photo_data['image_path'])) { unlink('upload/' . $photo_data['image_path']); }
                if ($photo_data['is_profile_picture'] == 1) {
                    $new_main_photo_res = $conn->query("SELECT photo_id, image_path FROM tbl_user_photos WHERE user_id = {$loggedInUserID} AND approval_status = 1 ORDER BY upload_date ASC LIMIT 1");
                    $new_main_img_path = NULL;
                    if ($new_main_photo_res->num_rows > 0) {
                        $new_main_photo = $new_main_photo_res->fetch_assoc();
                        $new_main_img_path = $new_main_photo['image_path'];
                        $conn->query("UPDATE tbl_user_photos SET is_profile_picture = 1 WHERE photo_id = " . $new_main_photo['photo_id']);
                    }
                    $update_user_img_stmt = $conn->prepare("UPDATE tbl_user SET user_img = ? WHERE user_id = ?");
                    $update_user_img_stmt->bind_param("si", $new_main_img_path, $loggedInUserID);
                    $update_user_img_stmt->execute();
                }
                header("Location: user-profile-edit.php?success=deleted");
                exit();
            }
        }
    }
    
    // --- 3. HANDLE "SUBMIT FOR APPROVAL" (PROFILE TEXT EDITS) ---
    if (isset($_POST['update_profile_details'])) {
        $submitted_edits = [];
        $fields_to_check = ['user_name', 'user_religion', 'user_namecast', 'user_nameintercast', 'user_gender', 'user_age', 'user_phone', 'user_email', 'user_city', 'user_state', 'user_country', 'user_dob', 'user_height', 'user_weight', 'user_fatherName', 'user_motherName', 'user_address', 'user_jobType', 'user_companyName', 'user_currentResident', 'user_salary', 'user_degree', 'user_school', 'user_collage', 'user_hobbies', 'user_disability', 'user_maritalstatus', 'user_whoyoustaywith', 'user_whereyoubelong'];
        
        foreach($fields_to_check as $field){
            if (isset($_POST[$field]) && $_POST[$field] !== $user[$field]) {
                $submitted_edits[$field] = $_POST[$field];
            }
        }
        $has_kids_value = isset($_POST['user_has_kids']) ? $_POST['user_has_kids'] : null;
        if ($has_kids_value !== $user['user_has_kids']) { $submitted_edits['user_has_kids'] = $has_kids_value; }

        if (!empty($submitted_edits)) {
            $pending_edits_json = json_encode($submitted_edits);
            $update_stmt = $conn->prepare("UPDATE tbl_user SET pending_edits = ?, has_pending_edits = 1 WHERE user_id = ?");
            $update_stmt->bind_param("si", $pending_edits_json, $loggedInUserID);
            if ($update_stmt->execute()) {
                $user['has_pending_edits'] = 1;
                $messages[] = "<div class='alert alert-success'>Your profile changes have been submitted for admin approval.</div>";
            }
        } else {
            $messages[] = "<div class='alert alert-info'>No changes were detected in your profile details.</div>";
        }
    }

    // --- 4. DISPLAY MESSAGES & FETCH PHOTOS ---
    if(isset($_GET['success'])){
        if($_GET['success'] == 'deleted') $messages[] = "<div class='alert alert-success'>Photo deleted successfully.</div>";
        if($_GET['success'] == 'uploaded') $messages[] = "<div class='alert alert-info'>Photos uploaded! They will be visible after admin approval.</div>";
    }
    if (isset($user['has_pending_edits']) && $user['has_pending_edits'] == 1) {
        $messages[] = "<div class='alert alert-warning'>You have changes pending admin review. You cannot edit your details further until they are processed.</div>";
    }
    $photos_stmt = $conn->prepare("SELECT photo_id, image_path, is_profile_picture, approval_status FROM tbl_user_photos WHERE user_id = ? ORDER BY is_profile_picture DESC, upload_date ASC");
    $photos_stmt->bind_param("i", $loggedInUserID);
    $photos_stmt->execute();
    $photos = $photos_stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
    <title>Wedding Matrimony - Edit Profile & Photos</title>
    <link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/style.css">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; } .photo-item { position: relative; } .photo-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 3px solid #ddd; } .photo-item.is-main img { border-color: #28a745; } .photo-item .delete-btn { position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.8); color: white; border-radius: 50%; width: 30px; height: 30px; line-height: 30px; text-align: center; } .main-photo-indicator { position: absolute; top: 5px; left: 5px; background: #28a745; color: white; padding: 3px 8px; font-size: 12px; border-radius: 5px; font-weight: bold; }
        .status-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: bold; border-radius: 5px; }
    </style>
</head>
<body>
    <?php include "inc/navbar.php"; ?>
    <section><div class="db"><div class="container"><div class="row">
        <!-- Left Navigation -->
        <div class="col-md-4 col-lg-3">

                        <!--<div class="db-nav">-->
                        <!--    <div class="db-nav-pro">-->
                        <!--        <img src="upload/<?php echo !empty($user['user_img']) ? htmlspecialchars($user['user_img']) : 'default-profile.png'; ?>" class="img-fluid" alt="My Profile Image">-->
                        <!--    </div>-->
                        <!--    <div class="db-nav-list">-->
                        <!--        <ul>-->
                        <!--            <li><a href="user-dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a></li>-->
                        <!--            <li><a href="user-profile.php"><i class="fa fa-user"></i>Profile</a></li>-->
                        <!--            <li><a href="see-other-profile.php"><i class="fa fa-users"></i>See Others Profile</a></li>-->
                        <!--            <li><a href="user-profile-edit.php"  class="act"><i class="fa fa-pencil-square-o"></i>Edit Profile</a></li>-->
                        <!--            <li><a href="user-interests.php"><i class="fa fa-handshake-o"></i>Interests</a></li>-->
                        <!--            <li><a href="user-chat.php"><i class="fa fa-commenting-o"></i>Chat list</a></li>-->
                        <!--            <li><a href="plans.php"><i class="fa fa-money"></i>Plan</a></li>-->
                        <!--            <li><a href="user-setting.php"><i class="fa fa-cog"></i>Setting</a></li>-->
                        <!--            <li><a href="logout.php"><i class="fa fa-sign-out"></i>Log out</a></li>-->
                        <!--        </ul>-->
                        <!--    </div>-->
                        <!--</div>-->
                        
                        <?php include "inc/dashboard_nav.php"; ?>
                    
        </div>
        <!-- Right Content -->
        <div class="col-md-8 col-lg-9"><div class="db-sec-com">
            <h2 class="db-tit">Edit Profile & Photos</h2>
            <?php foreach ($messages as $msg) { echo $msg; } ?>

            <!-- PART 1: PHOTO MANAGEMENT -->
            <div class="card mb-4">
                <div class="card-header"><h5>My Photo Gallery</h5></div>
                <div class="card-body">
                    <form method="POST" action="user-profile-edit.php" enctype="multipart/form-data" class="mb-4">
                        <div class="form-group"><label><h6>Add New Photos</h6></label><input type="file" name="gallery_photos[]" class="form-control" multiple required></div>
                        <button type="submit" name="upload_photos" class="btn btn-primary">Upload Photos</button>
                    </form>
                    <hr><h6>My Current Photos</h6><p>Photos marked "Pending" are waiting for admin review and are not visible to other users.</p>
                    <div class="gallery-grid">
                        <?php if ($photos->num_rows > 0): while ($photo = $photos->fetch_assoc()): ?>
                            <div class="photo-item <?php if ($photo['is_profile_picture']) echo 'is-main'; ?>">
                                <?php if ($photo['is_profile_picture']): ?><span class="main-photo-indicator">MAIN</span><?php endif; ?>
                                <img src="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" alt="User Photo">
                                <?php if ($photo['approval_status'] == 0): ?>
                                    <div class="status-overlay">Pending<br>Approval</div>
                                <?php elseif ($photo['approval_status'] == 2): ?>
                                     <div class="status-overlay" style="background: rgba(255,0,0,0.6);">Rejected</div>
                                <?php endif; ?>
                                <a href="?delete_photo=<?php echo $photo['photo_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure?');" title="Delete Photo"><i class="fa fa-trash"></i></a>
                            </div>
                        <?php endwhile; else: ?><p>Your photo gallery is empty.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PART 2: PROFILE DETAILS EDITING -->
            <form method="POST" action="user-profile-edit.php">
                <?php $is_disabled = (isset($user['has_pending_edits']) && $user['has_pending_edits'] == 1); ?>
                <fieldset <?php if ($is_disabled) echo 'disabled style="opacity:0.6;"'; ?>>
                    <div class="card">
                        <div class="card-header"><h5>My Profile Details</h5></div>
                        <div class="card-body"><div class="row">
                            <div class="col-md-6 form-group"><label>Name:</label><input type="text" name="user_name" class="form-control" value="<?php echo htmlspecialchars($user['user_name']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Email:</label><input type="email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($user['user_email']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Phone:</label><input type="text" name="user_phone" class="form-control" value="<?php echo htmlspecialchars($user['user_phone']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Age:</label><input type="number" name="user_age" class="form-control" value="<?php echo htmlspecialchars($user['user_age']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Date of Birth:</label><input type="date" name="user_dob" class="form-control" value="<?php echo htmlspecialchars($user['user_dob']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Gender:</label><select name="user_gender" class="form-control"><option value="Male" <?php if($user['user_gender'] == 'Male') echo 'selected'; ?>>Male</option><option value="Female" <?php if($user['user_gender'] == 'Female') echo 'selected'; ?>>Female</option></select></div>
                            <div class="col-md-6 form-group"><label>Height:</label><input type="text" name="user_height" class="form-control" value="<?php echo htmlspecialchars($user['user_height']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Weight:</label><input type="text" name="user_weight" class="form-control" value="<?php echo htmlspecialchars($user['user_weight']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Marital Status:</label><select name="user_maritalstatus" id="maritalStatusSelect" class="form-control"><option value="Single" <?php if($user['user_maritalstatus'] == 'Single') echo 'selected'; ?>>Single</option><option value="Divorced" <?php if($user['user_maritalstatus'] == 'Divorced') echo 'selected'; ?>>Divorced</option><option value="Widowed" <?php if($user['user_maritalstatus'] == 'Widowed') echo 'selected'; ?>>Widowed</option></select></div>
                            <div class="col-md-6 form-group" id="kidsQuestionDiv" style="display: none;"><label>Do you have children?</label><select name="user_has_kids" class="form-control"><option value="No" <?php if(isset($user['user_has_kids']) && $user['user_has_kids'] == 'No') echo 'selected'; ?>>No</option><option value="Yes" <?php if(isset($user['user_has_kids']) && $user['user_has_kids'] == 'Yes') echo 'selected'; ?>>Yes</option></select></div>
                            <div class="col-md-6 form-group"><label>Disability:</label><input type="text" name="user_disability" class="form-control" value="<?php echo htmlspecialchars($user['user_disability']); ?>" placeholder="e.g., None, Physical"></div>
                            <div class="col-md-12 form-group"><label>Address:</label><input type="text" name="user_address" class="form-control" value="<?php echo htmlspecialchars(trim($user['user_address'])); ?>"></div>
                            <div class="col-md-4 form-group"><label>City:</label><input type="text" name="user_city" class="form-control" value="<?php echo htmlspecialchars($user['user_city']); ?>"></div>
                            <div class="col-md-4 form-group"><label>State:</label><input type="text" name="user_state" class="form-control" value="<?php echo htmlspecialchars(isset($user['user_state']) ? $user['user_state'] : ''); ?>"></div>
                            <div class="col-md-4 form-group"><label>Country:</label><input type="text" name="user_country" class="form-control" value="<?php echo htmlspecialchars(isset($user['user_country']) ? $user['user_country'] : ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Current Residence:</label><input type="text" name="user_currentResident" class="form-control" value="<?php echo htmlspecialchars($user['user_currentResident']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Religion:</label><input type="text" name="user_religion" class="form-control" value="<?php echo htmlspecialchars($user['user_religion']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Caste:</label><input type="text" name="user_namecast" class="form-control" value="<?php echo htmlspecialchars($user['user_namecast']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Intercaste Marriage:</label><select name="user_nameintercast" class="form-control"><option value="Yes" <?php if($user['user_nameintercast'] == 'Yes') echo 'selected'; ?>>Yes</option><option value="No" <?php if($user['user_nameintercast'] == 'No') echo 'selected'; ?>>No</option></select></div>
                            <div class="col-md-6 form-group"><label>Father's Name:</label><input type="text" name="user_fatherName" class="form-control" value="<?php echo htmlspecialchars($user['user_fatherName']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Mother's Name:</label><input type="text" name="user_motherName" class="form-control" value="<?php echo htmlspecialchars($user['user_motherName']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Who You Stay With:</label><input type="text" name="user_whoyoustaywith" class="form-control" value="<?php echo htmlspecialchars($user['user_whoyoustaywith']); ?>" placeholder="e.g., Family, Alone"></div>
                            <div class="col-md-6 form-group"><label>Where You Belong (Native Place):</label><input type="text" name="user_whereyoubelong" class="form-control" value="<?php echo htmlspecialchars($user['user_whereyoubelong']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Highest Degree:</label><input type="text" name="user_degree" class="form-control" value="<?php echo htmlspecialchars($user['user_degree']); ?>"></div>
                            <div class="col-md-6 form-group"><label>College:</label><input type="text" name="user_collage" class="form-control" value="<?php echo htmlspecialchars($user['user_collage']); ?>"></div>
                            <div class="col-md-6 form-group"><label>School:</label><input type="text" name="user_school" class="form-control" value="<?php echo htmlspecialchars($user['user_school']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Job Type:</label><input type="text" name="user_jobType" class="form-control" value="<?php echo htmlspecialchars($user['user_jobType']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Company Name:</label><input type="text" name="user_companyName" class="form-control" value="<?php echo htmlspecialchars($user['user_companyName']); ?>"></div>
                            <div class="col-md-6 form-group"><label>Salary:</label><input type="text" name="user_salary" class="form-control" value="<?php echo htmlspecialchars($user['user_salary']); ?>"></div>
                            <div class="col-md-12 form-group"><label>Hobbies:</label><input type="text" name="user_hobbies" class="form-control" value="<?php echo htmlspecialchars($user['user_hobbies']); ?>"></div>
                        </div></div>
                        <div class="card-footer">
                             <button type="submit" name="update_profile_details" class="btn btn-primary">Submit Details for Approval</button>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div></div>
    </div></div></div></section>
    
    <script>
        $(document).ready(function() {
            function toggleKidsQuestion() {
                var selectedStatus = $('#maritalStatusSelect').val();
                if (selectedStatus === 'Divorced' || selectedStatus === 'Widowed') {
                    $('#kidsQuestionDiv').slideDown();
                } else {
                    $('#kidsQuestionDiv').slideUp();
                }
            }
            toggleKidsQuestion();
            $('#maritalStatusSelect').on('change', toggleKidsQuestion);
        });
    </script>
    
        <!-- FOOTER -->
    <section class="wed-hom-footer">
        <div class="container">
            <div class="row foot-supp">
                <h2><span>Call us Now:</span> <a href="tel:919727410836" style="color:#ffffff;">+91 97274 10836</a> &nbsp;&nbsp;|&nbsp;&nbsp; <span>Email:</span>
                    <a href="mailto:info@jeevansathimela.com" style="color:#ffffff;">info@jeevansathimela.com</a></h2>
            </div>
            <div class="row wed-foot-link wed-foot-link-1">
                <div class="col-md-6">
                    <h4>Get In Touch</h4>
                    <p>Address: 13-14, Bakor Nagar, New VIP Road, Vadodara, Gujarat - 390019</p>
                    <p>Phone: <a href="tel:919727410836">+91 97274 10836</a></p>
                    <p>Email: <a href="mailto:info@jeevansathimela.com">info@jeevansathimela.com</a></p>
                </div>
                <div class="col-md-6">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a>
                        </li>
                        <li><a href="login.php">Login</a>
                        </li>
                        <li><a href="client_sign_up.php">Register</a>
                        </li>
                        <li><a href="contact.php">Contact us</a>
                        </li>
                    </ul>
                </div>
            </div>
          
        </div>
    </section>
    <!-- END -->

    <!-- COPYRIGHTS -->
    <section>
        <div class="cr">
            <div class="container">
                <div class="row">

                    <p> Copyright © 2025 <a href="https://www.taniyawebfix.com/">Taniya Webfix Private Limited</a>. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- END -->
    
    
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/select-opt.js"></script>
    <script src="js/slick.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>