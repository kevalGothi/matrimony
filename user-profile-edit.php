<?php
session_start();
include "db/conn.php";

// For debugging. You can comment these out later.
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. AUTHENTICATION ---
if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
    exit();
}
$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
$stmt->bind_param("ss", $_SESSION['username'], $_SESSION['password']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    echo "<script>alert('Session error. Please login again.'); window.location.href='login.php';</script>";
    exit();
}
$user_live_data = $result->fetch_assoc();
$loggedInUserID = $user_live_data['user_id'];
$loggedInUser = $user_live_data;
$messages = [];

// =================================================================
// ===           PHOTO MANAGEMENT LOGIC (RESTORED)             ===
// =================================================================
// ACTION: Handle Photo Uploads
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
                        // All new photos are pending (status 0) and not the main picture initially.
                        $insert_stmt = $conn->prepare("INSERT INTO tbl_user_photos (user_id, image_path, approval_status, is_profile_picture) VALUES (?, ?, 0, 0)");
                        $insert_stmt->bind_param("is", $loggedInUserID, $new_filename);
                        $insert_stmt->execute();
                    }
                }
            }
        }
        header("Location: user-profile-edit.php?success=uploaded");
        exit();
    }
}
// ACTION: Handle Photo Deletes
if (isset($_GET['delete_photo'])) {
    $photo_id_to_delete = (int)$_GET['delete_photo'];
    $check_stmt = $conn->prepare("SELECT image_path, is_profile_picture FROM tbl_user_photos WHERE photo_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $photo_id_to_delete, $loggedInUserID);
    $check_stmt->execute();
    $photo_data = $check_stmt->get_result()->fetch_assoc();
    if ($photo_data) {
        if(file_exists('upload/' . $photo_data['image_path'])) { unlink('upload/' . $photo_data['image_path']); }
        $delete_stmt = $conn->prepare("DELETE FROM tbl_user_photos WHERE photo_id = ?");
        $delete_stmt->bind_param("i", $photo_id_to_delete);
        $delete_stmt->execute();

        // If the deleted photo was the main profile picture, assign a new one
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
// =================================================================
// ===               END OF PHOTO MANAGEMENT LOGIC               ===
// =================================================================

// --- 3. UNIFIED PROFILE DETAILS FORM HANDLER ---
if (isset($_POST['update_profile_details'])) {
    $editable_fields = [
        'user_name', 'user_religion', 'user_namecast', 'user_nameintercast', 'user_mother_tongue', 'user_gender', 'user_phone', 'user_email',
        'user_city', 'user_state', 'user_country', 'user_dob', 'user_height', 'user_weight', 'user_fatherName', 'user_motherName',
        'user_address', 'user_jobType', 'user_companyName', 'user_currentResident', 'user_salary', 'user_degree', 'user_school',
        'user_collage', 'user_hobbies', 'user_disability', 'user_maritalstatus', 'user_whoyoustaywith', 'user_whereyoubelong'
    ];
    if (isset($_POST['user_maritalstatus']) && in_array($_POST['user_maritalstatus'], ['Divorced', 'Widowed'])) {
        $editable_fields[] = 'user_has_kids';
        if (isset($_POST['user_has_kids']) && $_POST['user_has_kids'] === 'Yes') {
            $editable_fields = array_merge($editable_fields, ['user_children_count', 'user_boys_count', 'user_girls_count', 'user_children_names']);
        }
    }
    $pending_changes = [];
    foreach ($editable_fields as $field) {
        if (isset($_POST[$field])) { $pending_changes[$field] = $_POST[$field]; }
    }
    $marital_status = $_POST['user_maritalstatus'] ?? 'Single';
    $has_kids_answer = $_POST['user_has_kids'] ?? 'No';
    if ($marital_status === 'Single' || $has_kids_answer === 'No') {
        $pending_changes['user_has_kids'] = 'No';
        $pending_changes['user_children_count'] = 0;
        $pending_changes['user_boys_count'] = 0;
        $pending_changes['user_girls_count'] = 0;
        $pending_changes['user_children_names'] = '';
    }
    if (!empty($pending_changes)) {
        $pending_json = json_encode($pending_changes);
        $sql = "UPDATE tbl_user SET pending_data = ?, has_pending_changes = 1 WHERE user_id = ?";
        $update_stmt = $conn->prepare($sql);
        $update_stmt->bind_param("si", $pending_json, $loggedInUserID);
        if ($update_stmt->execute()) {
            header("Location: user-profile-edit.php?success=pending");
            exit();
        }
    }
}

// --- 4. PREPARE DATA FOR DISPLAY ---
if(isset($_GET['success'])){
    if($_GET['success'] == 'pending') $messages[] = "<div class='alert alert-success'>Your changes are saved and waiting for approval.</div>";
    if($_GET['success'] == 'deleted') $messages[] = "<div class='alert alert-success'>Photo deleted successfully.</div>";
    if($_GET['success'] == 'uploaded') $messages[] = "<div class='alert alert-info'>Photos uploaded! They will be visible after approval.</div>";
}
$display_data = $user_live_data;
if (!empty($user_live_data['pending_data'])) {
    $pending_array = json_decode($user_live_data['pending_data'], true);
    $display_data = array_merge($display_data, $pending_array);
}
$photos = $conn->query("SELECT * FROM tbl_user_photos WHERE user_id = {$loggedInUserID} ORDER BY is_profile_picture DESC, upload_date ASC");
?>
<!doctype html>
<html lang="en">
<head>
    <title>Wedding Matrimony - Edit My Profile</title>
    <link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/style.css">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; } .photo-item { position: relative; } .photo-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #eee; } .photo-item.is-main img { border-color: #28a745; } .photo-item .delete-btn { position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.8); color: white; border-radius: 50%; width: 30px; height: 30px; line-height: 30px; text-align: center; } .main-photo-indicator { position: absolute; top: 5px; left: 5px; background: #28a745; color: white; padding: 3px 8px; font-size: 12px; border-radius: 5px; font-weight: bold; } .status-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: bold; border-radius: 6px; }
        #hasKidsDiv, #childrenDetailsDiv { display: none; }
    </style>
</head>
<body>
    <?php include "inc/header.php"; ?><?php include "inc/bodystart.php"; ?><?php include "inc/navbar.php"; ?>

    <section><div class="db"><div class="container"><div class="row">
        <div class="col-md-4 col-lg-3"><?php include "inc/dashboard_nav.php"; ?></div>
        <div class="col-md-8 col-lg-9"><div class="db-sec-com">
            <h2 class="db-tit">Edit Profile & Photos</h2>
            <?php foreach ($messages as $msg) { echo $msg; } ?>

            <!-- Photo Management Section -->
            <div class="card mb-4">
                <div class="card-header"><h5>My Photo Gallery</h5></div>
                <div class="card-body">
                    <form method="POST" action="user-profile-edit.php" enctype="multipart/form-data" class="mb-4">
                        <div class="form-group"><label><h6>Add New Photos (up to 5MB each)</h6></label><input type="file" name="gallery_photos[]" class="form-control" multiple required></div>
                        <button type="submit" name="upload_photos" class="btn btn-primary mt-2">Upload Photos</button>
                    </form>
                    <hr><h6>My Current Photos</h6>
                    <div class="gallery-grid">
                        <?php if ($photos && $photos->num_rows > 0): while ($photo = $photos->fetch_assoc()): ?>
                            <div class="photo-item <?php if ($photo['is_profile_picture']) echo 'is-main'; ?>">
                                <?php if ($photo['is_profile_picture']): ?><span class="main-photo-indicator">MAIN</span><?php endif; ?>
                                <img src="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" alt="User Photo">
                                <?php if($photo['approval_status'] == 0): ?><div class="status-overlay">Pending</div><?php endif; ?>
                                <?php if($photo['approval_status'] == 2): ?><div class="status-overlay" style="background:rgba(220,53,69,0.7);">Rejected</div><?php endif; ?>
                                <a href="?delete_photo=<?php echo $photo['photo_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this photo?');" title="Delete Photo"><i class="fa fa-trash"></i></a>
                            </div>
                        <?php endwhile; else: ?><p class="text-muted">Your gallery is empty.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Unified Form -->
            <form method="POST" action="user-profile-edit.php">
                <div class="card">
                    <div class="card-header"><h5>My Profile Details</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Name:</label><input type="text" name="user_name" class="form-control" value="<?php echo htmlspecialchars($display_data['user_name'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Email:</label><input type="email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($display_data['user_email'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Phone:</label><input type="text" name="user_phone" class="form-control" value="<?php echo htmlspecialchars($display_data['user_phone'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Age:</label><p class="form-control-plaintext"><?php if (!empty($display_data['user_dob'])) { echo (new DateTime($display_data['user_dob']))->diff(new DateTime('today'))->y . " Years"; } else { echo "N/A"; }?></p></div>
                            <div class="col-md-6 form-group"><label>Date of Birth:</label><input type="date" name="user_dob" class="form-control" value="<?php echo htmlspecialchars($display_data['user_dob'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Gender:</label><select name="user_gender" class="form-control"><option value="Male" <?php if(($display_data['user_gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option><option value="Female" <?php if(($display_data['user_gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option></select></div>
                            <div class="col-md-6 form-group"><label>Height:</label><input type="text" name="user_height" class="form-control" value="<?php echo htmlspecialchars($display_data['user_height'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Weight:</label><input type="text" name="user_weight" class="form-control" value="<?php echo htmlspecialchars($display_data['user_weight'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group">
                                <label>Marital Status:</label>
                                <select name="user_maritalstatus" id="maritalStatusSelect" class="form-control">
                                    <option value="Single" <?php if(($display_data['user_maritalstatus'] ?? 'Single') == 'Single') echo 'selected'; ?>>Single</option>
                                    <option value="Divorced" <?php if(($display_data['user_maritalstatus'] ?? '') == 'Divorced') echo 'selected'; ?>>Divorced</option>
                                    <option value="Widowed" <?php if(($display_data['user_maritalstatus'] ?? '') == 'Widowed') echo 'selected'; ?>>Widowed</option>
                                </select>
                            </div>
                            <div id="hasKidsDiv" class="col-md-6 form-group">
                                <label>Do you have children?</label>
                                <select name="user_has_kids" id="hasKidsSelect" class="form-control">
                                    <option value="No" <?php if(($display_data['user_has_kids'] ?? 'No') == 'No') echo 'selected'; ?>>No</option>
                                    <option value="Yes" <?php if(($display_data['user_has_kids'] ?? '') == 'Yes') echo 'selected'; ?>>Yes</option>
                                </select>
                            </div>
                            <div id="childrenDetailsDiv" class="col-md-12">
                                <div class="p-3 bg-light rounded mt-2">
                                    <h6 class="mb-3">Children's Details:</h6>
                                    <div class="row">
                                        <div class="col-md-4 form-group"><label>Number of Children:</label><input type="number" min="0" name="user_children_count" class="form-control" value="<?php echo htmlspecialchars($display_data['user_children_count'] ?? '0'); ?>"></div>
                                        <div class="col-md-4 form-group"><label>Number of Boys:</label><input type="number" min="0" name="user_boys_count" class="form-control" value="<?php echo htmlspecialchars($display_data['user_boys_count'] ?? '0'); ?>"></div>
                                        <div class="col-md-4 form-group"><label>Number of Girls:</label><input type="number" min="0" name="user_girls_count" class="form-control" value="<?php echo htmlspecialchars($display_data['user_girls_count'] ?? '0'); ?>"></div>
                                        <div class="col-md-12 form-group mt-2"><label>Names (optional):</label><textarea name="user_children_names" class="form-control" rows="2"><?php echo htmlspecialchars($display_data['user_children_names'] ?? ''); ?></textarea></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12"><hr class="my-4"></div>
                            <div class="col-md-6 form-group"><label>Religion:</label><input type="text" name="user_religion" class="form-control" value="<?php echo htmlspecialchars($display_data['user_religion'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Mother Tongue:</label><input type="text" name="user_mother_tongue" class="form-control" value="<?php echo htmlspecialchars($display_data['user_mother_tongue'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Caste:</label><input type="text" name="user_namecast" class="form-control" value="<?php echo htmlspecialchars($display_data['user_namecast'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Marry Other Castes?:</label><select name="user_nameintercast" class="form-control"><option value="Yes" <?php if(($display_data['user_nameintercast'] ?? '') == 'Yes') echo 'selected'; ?>>Yes</option><option value="No" <?php if(($display_data['user_nameintercast'] ?? '') == 'No') echo 'selected'; ?>>No</option></select></div>
                            <div class="col-md-12 form-group"><label>Address:</label><input type="text" name="user_address" class="form-control" value="<?php echo htmlspecialchars(trim($display_data['user_address'] ?? '')); ?>"></div>
                            <div class="col-md-4 form-group"><label>City:</label><input type="text" name="user_city" class="form-control" value="<?php echo htmlspecialchars($display_data['user_city'] ?? ''); ?>"></div>
                            <div class="col-md-4 form-group"><label>State:</label><input type="text" name="user_state" class="form-control" value="<?php echo htmlspecialchars($display_data['user_state'] ?? ''); ?>"></div>
                            <div class="col-md-4 form-group"><label>Country:</label><input type="text" name="user_country" class="form-control" value="<?php echo htmlspecialchars($display_data['user_country'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Father's Name:</label><input type="text" name="user_fatherName" class="form-control" value="<?php echo htmlspecialchars($display_data['user_fatherName'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Mother's Name:</label><input type="text" name="user_motherName" class="form-control" value="<?php echo htmlspecialchars($display_data['user_motherName'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Current Residence:</label><input type="text" name="user_currentResident" class="form-control" value="<?php echo htmlspecialchars($display_data['user_currentResident'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Native Place:</label><input type="text" name="user_whereyoubelong" class="form-control" value="<?php echo htmlspecialchars($display_data['user_whereyoubelong'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Lives With:</label><input type="text" name="user_whoyoustaywith" class="form-control" value="<?php echo htmlspecialchars($display_data['user_whoyoustaywith'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Highest Degree:</label><input type="text" name="user_degree" class="form-control" value="<?php echo htmlspecialchars($display_data['user_degree'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>College:</label><input type="text" name="user_collage" class="form-control" value="<?php echo htmlspecialchars($display_data['user_collage'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>School:</label><input type="text" name="user_school" class="form-control" value="<?php echo htmlspecialchars($display_data['user_school'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Profession:</label><input type="text" name="user_jobType" class="form-control" value="<?php echo htmlspecialchars($display_data['user_jobType'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Company Name:</label><input type="text" name="user_companyName" class="form-control" value="<?php echo htmlspecialchars($display_data['user_companyName'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Annual Salary:</label><input type="text" name="user_salary" class="form-control" value="<?php echo htmlspecialchars($display_data['user_salary'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label>Disability:</label><input type="text" name="user_disability" class="form-control" value="<?php echo htmlspecialchars($display_data['user_disability'] ?? ''); ?>"></div>
                            <div class="col-md-12 form-group"><label>Hobbies:</label><input type="text" name="user_hobbies" class="form-control" value="<?php echo htmlspecialchars($display_data['user_hobbies'] ?? ''); ?>"></div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                         <button type="submit" name="update_profile_details" class="btn btn-primary btn-lg">Save All Details for Review</button>
                    </div>
                </div>
            </form>
        </div></div>
    </div></div></div></section>

    <?php include "inc/copyright.php"; ?><?php include "inc/footerlink.php"; ?>

    <script>
    $(document).ready(function() {
        function manageChildrenVisibility() {
            let maritalStatus = $('#maritalStatusSelect').val();
            if (maritalStatus === 'Divorced' || maritalStatus === 'Widowed') {
                $('#hasKidsDiv').slideDown();
            } else {
                $('#hasKidsDiv').slideUp();
            }
            let hasKidsAnswer = $('#hasKidsSelect').val();
            if ((maritalStatus === 'Divorced' || maritalStatus === 'Widowed') && hasKidsAnswer === 'Yes') {
                $('#childrenDetailsDiv').slideDown();
            } else {
                $('#childrenDetailsDiv').slideUp();
            }
        }
        manageChildrenVisibility();
        $('#maritalStatusSelect, #hasKidsSelect').on('change', manageChildrenVisibility);
    });
    </script>
</body>
</html>