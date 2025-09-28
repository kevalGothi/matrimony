<?php
session_start();
include "../db/conn.php";

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. Admin Authentication ---
if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    header("Location: login/");
    exit();
}
$stmt = $conn->prepare("SELECT ad_id FROM tbl_admin WHERE ad_email = ? AND ad_pass = ?");
$stmt->bind_param("ss", $_SESSION['username'], $_SESSION['password']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    session_destroy();
    header("Location: login/");
    exit();
}
$stmt->close();

// --- 2. Get User ID & Initialize ---
if (!isset($_GET['user_id'])) { die("No user specified."); }
$user_id_to_manage = (int)$_GET['user_id'];
$message = '';

// --- 3. ACTION HANDLERS ---

// ACTION: Approve or Reject a newly uploaded photo
if (isset($_GET['photo_action']) && isset($_GET['photo_id'])) {
    $photo_id = (int)$_GET['photo_id'];
    $new_status = ($_GET['photo_action'] == 'approve') ? 1 : 2;
    $stmt = $conn->prepare("UPDATE tbl_user_photos SET approval_status = ? WHERE photo_id = ?");
    $stmt->bind_param("ii", $new_status, $photo_id);
    if ($stmt->execute()) {
        header("Location: manage_user.php?user_id=$user_id_to_manage&success=photo_status_updated");
        exit();
    }
}

// ACTION: Update user's main status
if (isset($_POST['update_status'])) {
    $new_status = (int)$_POST['user_status'];
    $stmt = $conn->prepare("UPDATE tbl_user SET user_status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $new_status, $user_id_to_manage);
    if($stmt->execute()){ $message = "<div class='alert alert-success'>User status updated successfully!</div>"; }
}

// ACTION: Approve pending text edits
if (isset($_POST['approve_edits'])) {
    $user_res = $conn->query("SELECT pending_data FROM tbl_user WHERE user_id = $user_id_to_manage");
    $pending_json = $user_res->fetch_assoc()['pending_data'];
    $pending_edits = json_decode($pending_json, true);
    if (!empty($pending_edits)) {
        $sql_parts = []; $params = []; $types = '';
        foreach ($pending_edits as $column => $value) {
            $sql_parts[] = "`{$column}` = ?"; $params[] = $value; $types .= 's';
        }
        $sql = "UPDATE tbl_user SET " . implode(', ', $sql_parts) . ", pending_data = NULL, has_pending_changes = 0 WHERE user_id = ?";
        $params[] = $user_id_to_manage; $types .= 'i';
        $stmt = $conn->prepare($sql);
        $bind_params = []; $bind_params[] = $types;
        foreach ($params as $key => &$value) { $bind_params[] = &$value; }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        if ($stmt->execute()) { $message = "<div class='alert alert-success'>Pending edits have been approved.</div>"; }
    }
}

// ACTION: Reject pending text edits
if (isset($_POST['reject_edits'])) {
    $stmt = $conn->prepare("UPDATE tbl_user SET pending_data = NULL, has_pending_changes = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id_to_manage);
    if($stmt->execute()){ $message = "<div class='alert alert-warning'>Pending edits have been rejected.</div>"; }
}

// =================================================================
// ===           PHOTO MANAGEMENT LOGIC (RESTORED)             ===
// =================================================================

// ACTION: Set a new "Hero" image from approved photos
if (isset($_GET['set_main_photo'])) {
    $photo_id_to_set = (int)$_GET['set_main_photo'];
    $photo_path_res = $conn->query("SELECT image_path FROM tbl_user_photos WHERE photo_id = $photo_id_to_set AND user_id = $user_id_to_manage AND approval_status = 1");
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
        if(file_exists('../upload/' . $photo_data['image_path'])) { unlink('../upload/' . $photo_data['image_path']); }
        $delete_stmt = $conn->prepare("DELETE FROM tbl_user_photos WHERE photo_id = ?");
        $delete_stmt->bind_param("i", $photo_id_to_delete);
        $delete_stmt->execute();
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

// =================================================================
// ===               END OF PHOTO MANAGEMENT LOGIC               ===
// =================================================================

// ACTION: Admin directly edits and saves profile data
if (isset($_POST['admin_update_details'])) {
    $sql = "UPDATE tbl_user SET user_name=?, user_religion=?, user_namecast=?, user_nameintercast=?, user_mother_tongue=?, user_gender=?, user_phone=?, user_email=?, user_city=?, user_state=?, user_country=?, user_dob=?, user_height=?, user_weight=?, user_fatherName=?, user_motherName=?, user_address=?, user_jobType=?, user_companyName=?, user_currentResident=?, user_salary=?, user_degree=?, user_school=?, user_collage=?, user_hobbies=?, user_disability=?, user_maritalstatus=?, user_whoyoustaywith=?, user_whereyoubelong=?, user_has_kids=?, user_children_count=?, user_boys_count=?, user_girls_count=?, user_children_names=? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssssssssssssssiiis", 
        $_POST['user_name'], $_POST['user_religion'], $_POST['user_namecast'], $_POST['user_nameintercast'], $_POST['user_mother_tongue'], $_POST['user_gender'], $_POST['user_phone'], $_POST['user_email'], $_POST['user_city'], $_POST['user_state'], $_POST['user_country'], $_POST['user_dob'], $_POST['user_height'], $_POST['user_weight'], $_POST['user_fatherName'], $_POST['user_motherName'], $_POST['user_address'], $_POST['user_jobType'], $_POST['user_companyName'], $_POST['user_currentResident'], $_POST['user_salary'], $_POST['user_degree'], $_POST['user_school'], $_POST['user_collage'], $_POST['user_hobbies'], $_POST['user_disability'], $_POST['user_maritalstatus'], $_POST['user_whoyoustaywith'], $_POST['user_whereyoubelong'], $_POST['user_has_kids'], $_POST['user_children_count'], $_POST['user_boys_count'], $_POST['user_girls_count'], $_POST['user_children_names'], $user_id_to_manage
    );
    if ($stmt->execute()) { $message = "<div class='alert alert-success'>Profile details were successfully updated by admin.</div>"; } 
    else { $message = "<div class='alert alert-danger'>Error updating profile: " . $stmt->error . "</div>"; }
}

// --- 4. DATA FETCHING for Display ---
$user_res = $conn->query("SELECT * FROM tbl_user WHERE user_id = $user_id_to_manage");
if ($user_res->num_rows === 0) { die("User not found."); }
$user = $user_res->fetch_assoc();
$pending_edits = json_decode($user['pending_data'], true) ?: [];
$pending_photos_res = $conn->query("SELECT * FROM tbl_user_photos WHERE user_id = $user_id_to_manage AND approval_status = 0");
$approved_photos_res = $conn->query("SELECT * FROM tbl_user_photos WHERE user_id = $user_id_to_manage AND approval_status = 1 ORDER BY is_profile_picture DESC");

if(isset($_GET['success'])){
    if($_GET['success'] == 'photo_set') $message = "<div class='alert alert-success'>User's main photo has been updated.</div>";
    if($_GET['success'] == 'photo_deleted') $message = "<div class='alert alert-success'>Photo has been permanently deleted.</div>";
    if($_GET['success'] == 'photo_status_updated') $message = "<div class='alert alert-success'>Photo status has been updated.</div>";
}

include "inc/header.php"; 
?>
<style>
    .gallery-admin-grid { display: flex; flex-wrap: wrap; gap: 20px; } .photo-admin-item { border: 2px solid #ddd; padding: 10px; border-radius: 8px; text-align: center; position: relative; } .photo-admin-item.is-main { border-color: #28a745; background-color: #f0fff0; } .photo-admin-item img { width: 200px; height: 200px; object-fit: cover; margin-bottom: 10px; border-radius: 5px; } .photo-admin-item .delete-btn { position: absolute; top: 0px; right: 0px; background-color: rgba(255, 0, 0, 0.8); color: white; border: none; border-radius: 0 5px 0 50%; width: 30px; height: 30px; line-height: 30px; }
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
            <div class="card mb-4">
                <div class="card-header bg-info text-white"><h5 class="card-title mb-0">Pending Photos</h5></div>
                <div class="card-body">
                    <div class="gallery-admin-grid">
                        <?php while ($photo = $pending_photos_res->fetch_assoc()): ?>
                        <div class="photo-admin-item">
                            <img src="../upload/<?php echo htmlspecialchars($photo['image_path']); ?>" alt="Pending">
                            <div class="mt-2"><a href="?user_id=<?php echo $user_id_to_manage; ?>&photo_id=<?php echo $photo['photo_id']; ?>&photo_action=approve" class="btn btn-sm btn-success">Approve</a> <a href="?user_id=<?php echo $user_id_to_manage; ?>&delete_photo=<?php echo $photo['photo_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- PENDING EDITS APPROVAL -->
            <?php if ($user['has_pending_changes']): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-white"><h5 class="card-title mb-0">Review Pending Edits</h5></div>
                <div class="card-body">
                    <table class="table table-bordered"><thead><tr><th>Field</th><th>Current</th><th>New</th></tr></thead>
                        <tbody><?php foreach($pending_edits as $field => $value): ?><tr>
                                <td><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $field))); ?></strong></td>
                                <td><?php echo htmlspecialchars($user[$field] ?? 'N/A'); ?></td>
                                <td><strong class="text-primary"><?php echo htmlspecialchars($value); ?></strong></td>
                        </tr><?php endforeach; ?></tbody>
                    </table>
                    <form method="POST" class="mt-3"><button type="submit" name="approve_edits" class="btn btn-success">Approve</button> <button type="submit" name="reject_edits" class="btn btn-danger">Reject</button></form>
                </div>
            </div>
            <?php endif; ?>

            <!-- MANAGE APPROVED PHOTOS -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title">Approved Photos</h5></div>
                <div class="card-body">
                    <div class="gallery-admin-grid">
                        <?php if ($approved_photos_res->num_rows > 0): while ($photo = $approved_photos_res->fetch_assoc()): ?>
                        <div class="photo-admin-item <?php if ($photo['is_profile_picture']) echo 'is-main'; ?>">
                            <a href="?user_id=<?php echo $user_id_to_manage; ?>&delete_photo=<?php echo $photo['photo_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure?');" title="Delete"><i class="fa fa-times"></i></a>
                            <img src="../upload/<?php echo htmlspecialchars($photo['image_path']); ?>" alt="Approved">
                            <?php if ($photo['is_profile_picture']): ?><span class="btn btn-success disabled btn-sm mt-2">Main</span>
                            <?php else: ?><a href="?user_id=<?php echo $user_id_to_manage; ?>&set_main_photo=<?php echo $photo['photo_id']; ?>" class="btn btn-primary btn-sm mt-2">Set as Main</a><?php endif; ?>
                        </div>
                        <?php endwhile; else: ?><p>No approved photos.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CHILDREN DETAILS -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title">Children's Details (Live Data)</h5></div>
                <div class="card-body">
                    <?php if (!empty($user['user_has_kids']) && $user['user_has_kids'] == 'Yes'): ?>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Has Children:</strong> Yes</li>
                            <li class="list-group-item"><strong>Count:</strong> <?php echo htmlspecialchars($user['user_children_count'] ?? 0); ?> (Boys: <?php echo htmlspecialchars($user['user_boys_count'] ?? 0); ?>, Girls: <?php echo htmlspecialchars($user['user_girls_count'] ?? 0); ?>)</li>
                            <?php if (!empty($user['user_children_names'])): ?><li class="list-group-item"><strong>Names:</strong> <?php echo nl2br(htmlspecialchars($user['user_children_names'])); ?></li><?php endif; ?>
                        </ul>
                    <?php else: ?><p>This user has not specified having any children.</p><?php endif; ?>
                </div>
            </div>

            <!-- DIRECT EDIT FORM -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Directly Edit User Profile</h5>
                    <form method="POST"><select name="user_status" class="form-select-sm"><option value="1" <?php if($user['user_status'] == 1) echo 'selected'; ?>>Approved</option><option value="0" <?php if($user['user_status'] == 0) echo 'selected'; ?>>Pending</option><option value="2" <?php if($user['user_status'] == 2) echo 'selected'; ?>>Rejected</option></select><button type="submit" name="update_status" class="btn btn-sm btn-primary ms-2">Update Status</button></form>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label>Name:</label><input type="text" name="user_name" class="form-control" value="<?php echo htmlspecialchars($user['user_name']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Email:</label><input type="email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($user['user_email']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Phone:</label><input type="text" name="user_phone" class="form-control" value="<?php echo htmlspecialchars($user['user_phone']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Date of Birth:</label><input type="date" name="user_dob" class="form-control" value="<?php echo htmlspecialchars($user['user_dob']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Gender:</label><input type="text" name="user_gender" class="form-control" value="<?php echo htmlspecialchars($user['user_gender']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Height:</label><input type="text" name="user_height" class="form-control" value="<?php echo htmlspecialchars($user['user_height']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Weight:</label><input type="text" name="user_weight" class="form-control" value="<?php echo htmlspecialchars($user['user_weight']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Marital Status:</label><input type="text" name="user_maritalstatus" class="form-control" value="<?php echo htmlspecialchars($user['user_maritalstatus']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Religion:</label><input type="text" name="user_religion" class="form-control" value="<?php echo htmlspecialchars($user['user_religion']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Mother Tongue:</label><input type="text" name="user_mother_tongue" class="form-control" value="<?php echo htmlspecialchars($user['user_mother_tongue']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Caste:</label><input type="text" name="user_namecast" class="form-control" value="<?php echo htmlspecialchars($user['user_namecast']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Intercaste Marriage:</label><input type="text" name="user_nameintercast" class="form-control" value="<?php echo htmlspecialchars($user['user_nameintercast']); ?>"></div>
                            <div class="col-md-12 mb-3"><label>Address:</label><input type="text" name="user_address" class="form-control" value="<?php echo htmlspecialchars($user['user_address']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>City:</label><input type="text" name="user_city" class="form-control" value="<?php echo htmlspecialchars($user['user_city']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>State:</label><input type="text" name="user_state" class="form-control" value="<?php echo htmlspecialchars($user['user_state']); ?>"></div>
                            <div class="col-md-4 mb-3"><label>Country:</label><input type="text" name="user_country" class="form-control" value="<?php echo htmlspecialchars($user['user_country']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Father's Name:</label><input type="text" name="user_fatherName" class="form-control" value="<?php echo htmlspecialchars($user['user_fatherName']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Mother's Name:</label><input type="text" name="user_motherName" class="form-control" value="<?php echo htmlspecialchars($user['user_motherName']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Current Residence:</label><input type="text" name="user_currentResident" class="form-control" value="<?php echo htmlspecialchars($user['user_currentResident']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Native Place:</label><input type="text" name="user_whereyoubelong" class="form-control" value="<?php echo htmlspecialchars($user['user_whereyoubelong']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Lives With:</label><input type="text" name="user_whoyoustaywith" class="form-control" value="<?php echo htmlspecialchars($user['user_whoyoustaywith']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Highest Degree:</label><input type="text" name="user_degree" class="form-control" value="<?php echo htmlspecialchars($user['user_degree']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>College:</label><input type="text" name="user_collage" class="form-control" value="<?php echo htmlspecialchars($user['user_collage']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>School:</label><input type="text" name="user_school" class="form-control" value="<?php echo htmlspecialchars($user['user_school']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Profession:</label><input type="text" name="user_jobType" class="form-control" value="<?php echo htmlspecialchars($user['user_jobType']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Company Name:</label><input type="text" name="user_companyName" class="form-control" value="<?php echo htmlspecialchars($user['user_companyName']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Annual Salary:</label><input type="text" name="user_salary" class="form-control" value="<?php echo htmlspecialchars($user['user_salary']); ?>"></div>
                            <div class="col-md-6 mb-3"><label>Disability:</label><input type="text" name="user_disability" class="form-control" value="<?php echo htmlspecialchars($user['user_disability']); ?>"></div>
                            <div class="col-md-12 mb-3"><label>Hobbies:</label><input type="text" name="user_hobbies" class="form-control" value="<?php echo htmlspecialchars($user['user_hobbies']); ?>"></div>
                            <hr class="my-3"><h6 class="text-primary">Children Details (for Admin Edit)</h6>
                            <div class="col-md-3 mb-3"><label>Has Children?</label><select name="user_has_kids" class="form-select"><option value="No" <?php if(($user['user_has_kids'] ?? 'No') == 'No') echo 'selected'; ?>>No</option><option value="Yes" <?php if(($user['user_has_kids'] ?? '') == 'Yes') echo 'selected'; ?>>Yes</option></select></div>
                            <div class="col-md-3 mb-3"><label>Total Children:</label><input type="number" name="user_children_count" class="form-control" value="<?php echo htmlspecialchars($user['user_children_count'] ?? '0'); ?>"></div>
                            <div class="col-md-3 mb-3"><label>Boys:</label><input type="number" name="user_boys_count" class="form-control" value="<?php echo htmlspecialchars($user['user_boys_count'] ?? '0'); ?>"></div>
                            <div class="col-md-3 mb-3"><label>Girls:</label><input type="number" name="user_girls_count" class="form-control" value="<?php echo htmlspecialchars($user['user_girls_count'] ?? '0'); ?>"></div>
                            <div class="col-md-12 mb-3"><label>Children Names:</label><textarea name="user_children_names" class="form-control" rows="2"><?php echo htmlspecialchars($user['user_children_names'] ?? ''); ?></textarea></div>
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