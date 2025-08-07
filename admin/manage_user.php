<?php
session_start();
include "../db/conn.php";

// --- 1. Admin Authentication ---
if (!isset($_SESSION['username']) || !isset($_SESSION['pass'])) {
    header("Location: login/");
    exit();
}
$admin_email = $_SESSION['username'];
$admin_pass = $_SESSION['pass'];
$stmt = $conn->prepare("SELECT ad_id FROM tbl_admin WHERE ad_email = ? AND ad_pass = ?");
$stmt->bind_param("ss", $admin_email, $admin_pass);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    session_destroy();
    header("Location: login/");
    exit();
}
$stmt->close();

// --- 2. Get User ID from URL ---
if (!isset($_GET['user_id'])) { die("No user specified."); }
$user_id_to_manage = (int)$_GET['user_id'];
$message = ''; // For feedback messages

// --- 3. ACTION HANDLERS ---

// ACTION: Approve or Reject a newly uploaded photo
if (isset($_GET['photo_action']) && isset($_GET['photo_id'])) {
    $photo_id = (int)$_GET['photo_id'];
    $action = $_GET['photo_action'];
    $new_status = ($action == 'approve') ? 1 : 2; // 1=Approved, 2=Rejected

    $stmt = $conn->prepare("UPDATE tbl_user_photos SET approval_status = ? WHERE photo_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $new_status, $photo_id, $user_id_to_manage);
    if ($stmt->execute()) {
        header("Location: manage_user.php?user_id=$user_id_to_manage&success=photo_status_updated");
        exit();
    }
}

// ACTION: Update user's main status (Approve/Reject Profile)
if (isset($_POST['update_status'])) {
    $new_status = (int)$_POST['user_status'];
    $stmt = $conn->prepare("UPDATE tbl_user SET user_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $new_status, $user_id_to_manage);
    if($stmt->execute()){ $message = "<div class='alert alert-success'>User status updated successfully!</div>"; }
}

// ACTION: Approve pending text edits
if (isset($_POST['approve_edits'])) {
    $user_res = $conn->query("SELECT pending_edits FROM tbl_user WHERE user_id = $user_id_to_manage");
    $pending_edits_json = $user_res->fetch_assoc()['pending_edits'];
    $pending_edits = json_decode($pending_edits_json, true);
    if (!empty($pending_edits)) {
        $sql_parts = [];
        foreach ($pending_edits as $column => $value) {
            $sql_parts[] = "`" . $conn->real_escape_string($column) . "` = '" . $conn->real_escape_string($value) . "'";
        }
        $update_query = "UPDATE tbl_user SET " . implode(', ', $sql_parts) . ", pending_edits = NULL, has_pending_edits = 0 WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user_id_to_manage);
        if ($stmt->execute()) { $message = "<div class='alert alert-success'>Pending edits have been approved and applied.</div>"; }
    }
}

// ACTION: Reject pending text edits
if (isset($_POST['reject_edits'])) {
    $stmt = $conn->prepare("UPDATE tbl_user SET pending_edits = NULL, has_pending_edits = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id_to_manage);
    if($stmt->execute()){ $message = "<div class='alert alert-warning'>Pending edits have been rejected and cleared.</div>"; }
}

// ACTION: Set a new "Hero" image from approved photos
if (isset($_GET['set_main_photo'])) {
    $photo_id_to_set = (int)$_GET['set_main_photo'];
    $photo_path_res = $conn->query("SELECT image_path FROM tbl_user_photos WHERE photo_id = $photo_id_to_set AND user_id = $user_id_to_manage");
    if ($photo_path_res->num_rows > 0) {
        $photo_path = $photo_path_res->fetch_assoc()['image_path'];
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE tbl_user_photos SET is_profile_picture = 0 WHERE user_id = $user_id_to_manage");
            $conn->query("UPDATE tbl_user_photos SET is_profile_picture = 1 WHERE photo_id = $photo_id_to_set");
            $update_main_stmt = $conn->prepare("UPDATE tbl_user SET user_img = ? WHERE user_id = ?");
            $update_main_stmt->bind_param("si", $photo_path, $user_id_to_manage);
            $update_main_stmt->execute();
            $conn->commit();
            header("Location: manage_user.php?user_id=$user_id_to_manage&success=photo_set");
            exit();
        } catch (Exception $e) { $conn->rollback(); die("Database error: " . $e->getMessage()); }
    }
}

// ACTION: Delete any gallery photo
if (isset($_GET['delete_photo'])) {
    $photo_id_to_delete = (int)$_GET['delete_photo'];
    $check_stmt = $conn->prepare("SELECT image_path, is_profile_picture FROM tbl_user_photos WHERE photo_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $photo_id_to_delete, $user_id_to_manage);
    $check_stmt->execute();
    $photo_data = $check_stmt->get_result()->fetch_assoc();
    if ($photo_data) {
        $delete_stmt = $conn->prepare("DELETE FROM tbl_user_photos WHERE photo_id = ?");
        $delete_stmt->bind_param("i", $photo_id_to_delete);
        if ($delete_stmt->execute()) {
            if(file_exists('../upload/' . $photo_data['image_path'])) { unlink('../upload/' . $photo_data['image_path']); }
            if ($photo_data['is_profile_picture'] == 1) {
                $new_main_photo_res = $conn->query("SELECT photo_id, image_path FROM tbl_user_photos WHERE user_id = {$user_id_to_manage} AND approval_status = 1 ORDER BY upload_date ASC LIMIT 1");
                $new_main_img_path = NULL;
                if ($new_main_photo_res->num_rows > 0) {
                    $new_main_photo = $new_main_photo_res->fetch_assoc();
                    $new_main_img_path = $new_main_photo['image_path'];
                    $conn->query("UPDATE tbl_user_photos SET is_profile_picture = 1 WHERE photo_id = " . $new_main_photo['photo_id']);
                }
                $update_user_img_stmt = $conn->prepare("UPDATE tbl_user SET user_img = ? WHERE user_id = ?");
                $update_user_img_stmt->bind_param("si", $new_main_img_path, $user_id_to_manage);
                $update_user_img_stmt->execute();
            }
            header("Location: manage_user.php?user_id=$user_id_to_manage&success=photo_deleted");
            exit();
        }
    }
}

// ACTION: Admin directly edits and saves profile data
if (isset($_POST['admin_update_details'])) {
    $sql = "UPDATE tbl_user SET user_name=?, user_religion=?, user_namecast=?, user_nameintercast=?, user_gender=?, user_age=?, user_phone=?, user_email=?, user_city=?, user_state=?, user_country=?, user_dob=?, user_height=?, user_weight=?, user_fatherName=?, user_motherName=?, user_address=?, user_jobType=?, user_companyName=?, user_currentResident=?, user_salary=?, user_degree=?, user_school=?, user_collage=?, user_hobbies=?, user_disability=?, user_maritalstatus=?, user_has_kids=?, user_whoyoustaywith=?, user_whereyoubelong=? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssisssssssssssssssssssssssi", 
        $_POST['user_name'], $_POST['user_religion'], $_POST['user_namecast'], $_POST['user_nameintercast'], $_POST['user_gender'], $_POST['user_age'], $_POST['user_phone'], $_POST['user_email'], $_POST['user_city'], $_POST['user_state'], $_POST['user_country'], $_POST['user_dob'], $_POST['user_height'], $_POST['user_weight'], $_POST['user_fatherName'], $_POST['user_motherName'], $_POST['user_address'], $_POST['user_jobType'], $_POST['user_companyName'], $_POST['user_currentResident'], $_POST['user_salary'], $_POST['user_degree'], $_POST['user_school'], $_POST['user_collage'], $_POST['user_hobbies'], $_POST['user_disability'], $_POST['user_maritalstatus'], $_POST['user_has_kids'], $_POST['user_whoyoustaywith'], $_POST['user_whereyoubelong'], $user_id_to_manage
    );
    if ($stmt->execute()) { $message = "<div class='alert alert-success'>Profile details were successfully updated by admin.</div>"; } 
    else { $message = "<div class='alert alert-danger'>Error updating profile: " . $stmt->error . "</div>"; }
}

// --- 4. DATA FETCHING for Display (re-fetch after potential updates) ---
$user_res = $conn->query("SELECT * FROM tbl_user WHERE user_id = $user_id_to_manage");
if ($user_res->num_rows === 0) { die("User not found."); }
$user = $user_res->fetch_assoc();
$pending_edits = json_decode($user['pending_edits'], true) ?: [];
$pending_photos_res = $conn->query("SELECT * FROM tbl_user_photos WHERE user_id = $user_id_to_manage AND approval_status = 0");
$approved_photos_res = $conn->query("SELECT * FROM tbl_user_photos WHERE user_id = $user_id_to_manage AND approval_status = 1 ORDER BY is_profile_picture DESC, upload_date ASC");

if(isset($_GET['success'])){
    if($_GET['success'] == 'photo_set') $message = "<div class='alert alert-success'>User's main profile photo has been updated.</div>";
    if($_GET['success'] == 'photo_deleted') $message = "<div class='alert alert-success'>Photo has been permanently deleted.</div>";
    if($_GET['success'] == 'photo_status_updated') $message = "<div class='alert alert-success'>Photo status has been updated.</div>";
}

include "inc/header.php"; 
?>
<style>
    .gallery-admin-grid { display: flex; flex-wrap: wrap; gap: 20px; } .photo-admin-item { border: 2px solid #ddd; padding: 10px; border-radius: 8px; text-align: center; position: relative; } .photo-admin-item.is-main { border-color: #28a745; background-color: #f0fff0; } .photo-admin-item img { width: 200px; height: 200px; object-fit: cover; margin-bottom: 10px; } .photo-admin-item .delete-btn { position: absolute; top: 0px; right: 0px; background-color: rgba(255, 0, 0, 0.8); color: white; border: none; border-radius: 0 5px 0 50%; width: 30px; height: 30px; line-height: 30px; }
</style>
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <?php include "inc/side_bar.php"; ?>
    <div class="layout-page">
      <?php include "inc/top_bar.php"; ?>
      <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <a href="index.php" class="btn btn-secondary mb-4">« Back to Dashboard</a>
            <h4 class="py-3 mb-4">Manage User: <?php echo htmlspecialchars($user['user_name']); ?></h4>
            <?php echo $message; ?>

            <!-- PENDING PHOTOS APPROVAL -->
            <?php if ($pending_photos_res->num_rows > 0): ?>
            <div class="card mb-4 card-action">
                <div class="card-header bg-info text-white"><h5 class="card-title mb-0">Action Required: New Photos Pending Approval</h5></div>
                <div class="card-body">
                    <div class="gallery-admin-grid">
                        <?php while ($photo = $pending_photos_res->fetch_assoc()): ?>
                        <div class="photo-admin-item">
                            <img src="../upload/<?php echo htmlspecialchars($photo['image_path']); ?>" alt="Pending Photo">
                            <div class="mt-2">
                                <a href="?user_id=<?php echo $user_id_to_manage; ?>&photo_id=<?php echo $photo['photo_id']; ?>&photo_action=approve" class="btn btn-sm btn-success">Approve</a>
                                <a href="?user_id=<?php echo $user_id_to_manage; ?>&delete_photo=<?php echo $photo['photo_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to permanently delete this photo?');">Delete</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- PENDING EDITS APPROVAL -->
            <?php if (isset($user['has_pending_edits']) && $user['has_pending_edits']): ?>
            <div class="card mb-4 card-action">
                <div class="card-header bg-warning text-white"><h5 class="card-title mb-0">Review Pending Profile Edits</h5></div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="table-dark"><tr><th>Field</th><th>Current Value</th><th>Proposed New Value</th></tr></thead>
                        <tbody><?php foreach($pending_edits as $field => $value): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $field))); ?></strong></td>
                                <td><?php echo htmlspecialchars($user[$field] ?? 'N/A'); ?></td>
                                <td><strong class="text-primary"><?php echo htmlspecialchars($value); ?></strong></td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table>
                    <form method="POST" class="mt-3"><button type="submit" name="approve_edits" class="btn btn-success">Approve Changes</button> <button type="submit" name="reject_edits" class="btn btn-danger">Reject Changes</button></form>
                </div>
            </div>
            <?php endif; ?>

            <!-- MANAGE APPROVED PHOTOS -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title">Manage Approved Photos & Select Hero Image</h5></div>
                <div class="card-body">
                    <div class="gallery-admin-grid">
                        <?php if ($approved_photos_res->num_rows > 0): while ($photo = $approved_photos_res->fetch_assoc()): ?>
                        <div class="photo-admin-item <?php if ($photo['is_profile_picture']) echo 'is-main'; ?>">
                            <a href="?user_id=<?php echo $user_id_to_manage; ?>&delete_photo=<?php echo $photo['photo_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure?');" title="Delete Photo"><i class="fa fa-times"></i></a>
                            <img src="../upload/<?php echo htmlspecialchars($photo['image_path']); ?>" alt="User Photo">
                            <?php if ($photo['is_profile_picture']): ?>
                                <span class="btn btn-success disabled">Current Main Photo</span>
                            <?php else: ?>
                                <a href="?user_id=<?php echo $user_id_to_manage; ?>&set_main_photo=<?php echo $photo['photo_id']; ?>" class="btn btn-primary">Set as Main</a>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; else: ?><p>This user has no approved photos.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DIRECT EDIT FORM -->
            <div class="card">
                <div class="card-header"><h5 class="card-title">Directly Edit User Profile</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 form-group mb-3"><label>Name:</label><input type="text" name="user_name" class="form-control" value="<?php echo htmlspecialchars($user['user_name']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Email:</label><input type="email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($user['user_email']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Phone:</label><input type="text" name="user_phone" class="form-control" value="<?php echo htmlspecialchars($user['user_phone']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Age:</label><input type="number" name="user_age" class="form-control" value="<?php echo htmlspecialchars($user['user_age']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Date of Birth:</label><input type="date" name="user_dob" class="form-control" value="<?php echo htmlspecialchars($user['user_dob']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Gender:</label><select name="user_gender" class="form-control"><option value="Male" <?php if($user['user_gender'] == 'Male') echo 'selected'; ?>>Male</option><option value="Female" <?php if($user['user_gender'] == 'Female') echo 'selected'; ?>>Female</option></select></div>
                            <div class="col-md-4 form-group mb-3"><label>Height:</label><input type="text" name="user_height" class="form-control" value="<?php echo htmlspecialchars($user['user_height']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Weight:</label><input type="text" name="user_weight" class="form-control" value="<?php echo htmlspecialchars($user['user_weight']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Marital Status:</label><select name="user_maritalstatus" class="form-control"><option value="Single" <?php if($user['user_maritalstatus'] == 'Single') echo 'selected'; ?>>Single</option><option value="Divorced" <?php if($user['user_maritalstatus'] == 'Divorced') echo 'selected'; ?>>Divorced</option><option value="Widowed" <?php if($user['user_maritalstatus'] == 'Widowed') echo 'selected'; ?>>Widowed</option></select></div>
                            <div class="col-md-4 form-group mb-3"><label>Has Children:</label><select name="user_has_kids" class="form-control"><option value="">Not Specified</option><option value="No" <?php if(isset($user['user_has_kids']) && $user['user_has_kids'] == 'No') echo 'selected'; ?>>No</option><option value="Yes" <?php if(isset($user['user_has_kids']) && $user['user_has_kids'] == 'Yes') echo 'selected'; ?>>Yes</option></select></div>
                            <div class="col-md-4 form-group mb-3"><label>Disability:</label><input type="text" name="user_disability" class="form-control" value="<?php echo htmlspecialchars($user['user_disability']); ?>" placeholder="e.g., None, Physical"></div>
                            <div class="col-md-12 form-group mb-3"><label>Address:</label><input type="text" name="user_address" class="form-control" value="<?php echo htmlspecialchars(trim($user['user_address'])); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>City:</label><input type="text" name="user_city" class="form-control" value="<?php echo htmlspecialchars($user['user_city']); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>State:</label><input type="text" name="user_state" class="form-control" value="<?php echo htmlspecialchars(isset($user['user_state']) ? $user['user_state'] : ''); ?>"></div>
                            <div class="col-md-4 form-group mb-3"><label>Country:</label><input type="text" name="user_country" class="form-control" value="<?php echo htmlspecialchars(isset($user['user_country']) ? $user['user_country'] : ''); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Current Residence:</label><input type="text" name="user_currentResident" class="form-control" value="<?php echo htmlspecialchars($user['user_currentResident']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Religion:</label><input type="text" name="user_religion" class="form-control" value="<?php echo htmlspecialchars($user['user_religion']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Caste:</label><input type="text" name="user_namecast" class="form-control" value="<?php echo htmlspecialchars($user['user_namecast']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Intercaste Marriage:</label><select name="user_nameintercast" class="form-control"><option value="Yes" <?php if($user['user_nameintercast'] == 'Yes') echo 'selected'; ?>>Yes</option><option value="No" <?php if($user['user_nameintercast'] == 'No') echo 'selected'; ?>>No</option></select></div>
                            <div class="col-md-6 form-group mb-3"><label>Father's Name:</label><input type="text" name="user_fatherName" class="form-control" value="<?php echo htmlspecialchars($user['user_fatherName']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Mother's Name:</label><input type="text" name="user_motherName" class="form-control" value="<?php echo htmlspecialchars($user['user_motherName']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Who You Stay With:</label><input type="text" name="user_whoyoustaywith" class="form-control" value="<?php echo htmlspecialchars($user['user_whoyoustaywith']); ?>" placeholder="e.g., Family, Alone"></div>
                            <div class="col-md-6 form-group mb-3"><label>Where You Belong (Native Place):</label><input type="text" name="user_whereyoubelong" class="form-control" value="<?php echo htmlspecialchars($user['user_whereyoubelong']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Highest Degree:</label><input type="text" name="user_degree" class="form-control" value="<?php echo htmlspecialchars($user['user_degree']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>College:</label><input type="text" name="user_collage" class="form-control" value="<?php echo htmlspecialchars($user['user_collage']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>School:</label><input type="text" name="user_school" class="form-control" value="<?php echo htmlspecialchars($user['user_school']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Job Type:</label><input type="text" name="user_jobType" class="form-control" value="<?php echo htmlspecialchars($user['user_jobType']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Company Name:</label><input type="text" name="user_companyName" class="form-control" value="<?php echo htmlspecialchars($user['user_companyName']); ?>"></div>
                            <div class="col-md-6 form-group mb-3"><label>Salary:</label><input type="text" name="user_salary" class="form-control" value="<?php echo htmlspecialchars($user['user_salary']); ?>"></div>
                            <div class="col-md-12 form-group mb-3"><label>Hobbies:</label><input type="text" name="user_hobbies" class="form-control" value="<?php echo htmlspecialchars($user['user_hobbies']); ?>"></div>
                        </div>
                        <hr>
                        <button type="submit" name="admin_update_details" class="btn btn-danger">Save Direct Changes</button>
                    </form>
                </div>
            </div>
            
        </div>
        <?php include "inc/footer.php"; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>