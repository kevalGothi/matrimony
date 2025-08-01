<?php
session_start();
// The database connection path should be relative to this file's location.
// From /public/admin/index.php, '../db/conn.php' points to /public/db/conn.php.
include "../db/conn.php";

// --- Part 1: Authentication and Action Processing ---

// 1. Check if an admin username is set in the session.
if (!isset($_SESSION['username'])) {
    // If not, redirect to the login page immediately. No further code is executed.
    header("Location: login/");
    exit();
}

// 2. Securely verify that the admin from the session is still a valid admin in the database.
// This prevents issues if an admin is deleted but their session is still active.
$admin_email_from_session = $_SESSION['username'];
$stmt = $conn->prepare("SELECT ad_id, ad_name FROM tbl_admin WHERE ad_email = ?");
$stmt->bind_param("s", $admin_email_from_session);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // If the admin account no longer exists, destroy the session and force a new login.
    session_destroy();
    echo "<script>
            alert('Your session has expired or your account is no longer valid. Please log in again.'); 
            window.location.href='login/';
          </script>";
    exit();
}
// The admin is valid. Fetch their details for potential use.
$adminfetch = $result->fetch_assoc();
$stmt->close();


// 3. Process Approve/Reject/Delete actions if a form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id_action'])) {
    $user_id = (int)$_POST['user_id_action'];
    $stmt_action = null; // Initialize statement variable

    if (isset($_POST['approve'])) {
        $new_status = 1; // 1 = Approved
        $stmt_action = $conn->prepare("UPDATE tbl_user SET user_status = ? WHERE user_id = ?");
        $stmt_action->bind_param("ii", $new_status, $user_id);
    } elseif (isset($_POST['reject'])) {
        $new_status = 2; // 2 = Rejected
        $stmt_action = $conn->prepare("UPDATE tbl_user SET user_status = ? WHERE user_id = ?");
        $stmt_action->bind_param("ii", $new_status, $user_id);
    } elseif (isset($_POST['delete'])) {
        // PERMANENTLY DELETE USER - Use with caution
        $stmt_action = $conn->prepare("DELETE FROM tbl_user WHERE user_id = ?");
        $stmt_action->bind_param("i", $user_id);
    }
    
    // Execute the prepared statement if it was set
    if ($stmt_action) {
        $stmt_action->execute();
        $stmt_action->close();
    }
    
    // Redirect back to the dashboard to prevent form resubmission on refresh
    header("Location: index.php?update=success");
    exit();
}

// --- Part 2: HTML Structure and Page Content ---
// Your template includes start here.
include "inc/header.php"; 
?>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">
    <?php include "inc/side_bar.php"; ?>
    <!-- Layout container -->
    <div class="layout-page">
      <?php include "inc/top_bar.php"; ?>
      <!-- Content wrapper -->
      <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <?php
            // Check if we are viewing a single user or the main dashboard
            if (isset($_GET['view_id'])) {
                // --- DETAIL VIEW MODE ---
                $user_id_to_view = (int)$_GET['view_id'];
                $stmt_view = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
                $stmt_view->bind_param("i", $user_id_to_view);
                $stmt_view->execute();
                $result_view = $stmt_view->get_result();
                $user = $result_view->fetch_assoc();
                $stmt_view->close();

                if ($user) {
            ?>
                <a href="index.php" class="btn btn-secondary mb-3">« Back to Dashboard</a>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Full Details for <?php echo htmlspecialchars($user['user_name']); ?></h5>
                        <div>
                            Current Status: 
                            <?php 
                                if ($user['user_status'] == 1) echo '<span class="badge bg-label-success">Approved</span>';
                                elseif ($user['user_status'] == 2) echo '<span class="badge bg-label-danger">Rejected</span>';
                                else echo '<span class="badge bg-label-warning">Pending</span>';
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h6>Profile Photo</h6>
                                <?php if (!empty($user['user_img'])): ?>
                                    <img src="../upload/<?php echo htmlspecialchars($user['user_img']); ?>" alt="Profile Photo" class="img-fluid rounded mb-3" style="max-height: 300px;">
                                <?php else: ?>
                                    <p class="text-muted">No photo uploaded.</p>
                                <?php endif; ?>

                                <!-- Admin Action Buttons -->
                                <div class="mt-4">
                                    <form method="POST" action="index.php" onsubmit="return confirm('Are you sure you want to proceed with this action?');">
                                        <input type="hidden" name="user_id_action" value="<?php echo $user['user_id']; ?>">
                                        <?php if ($user['user_status'] != 1): // Show approve button if not already approved ?>
                                            <button type="submit" name="approve" class="btn btn-success w-100 mb-2">Approve Profile</button>
                                        <?php endif; ?>
                                        <?php if ($user['user_status'] != 2): // Show reject button if not already rejected ?>
                                            <button type="submit" name="reject" class="btn btn-warning w-100 mb-2">Reject Profile</button>
                                        <?php endif; ?>
                                        <button type="submit" name="delete" class="btn btn-danger w-100" onclick="return confirm('WARNING: This will permanently delete the user and their data. This cannot be undone. Are you absolutely sure?');">Delete User Permanently</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6>All User Information</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <?php foreach ($user as $key => $value): ?>
                                        <tr>
                                            <th style="width: 200px;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                } else {
                    echo "<div class='alert alert-danger'>User not found. They may have been deleted. <a href='index.php'>Return to dashboard</a>.</div>";
                }

            } else {
                // --- DASHBOARD MODE (DEFAULT) ---
            ?>
                <!-- Pending Users List -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="card-title">Users Pending Approval</h5></div>
                    <div class="card-datatable table-responsive">
                        <table class="table border-top">
                            <thead><tr><th>ID</th><th>Client Name</th><th>Phone Number</th><th>Registered On</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php 
                                // SECURELY fetch pending users
                                $sql_pending = "SELECT user_id, user_name, user_phone, user_create_date FROM tbl_user WHERE user_status = 0 AND user_payment_status = 1 ORDER BY user_id DESC";
                                $result_pending = $conn->query($sql_pending);
                                if ($result_pending->num_rows > 0) {
                                    while($data = $result_pending->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $data['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($data['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($data['user_phone']); ?></td>
                                    <td><?php echo date('d-M-Y', strtotime($data['user_create_date'])); ?></td>
                                    <td><a class="btn btn-primary btn-sm" href="index.php?view_id=<?php echo $data['user_id']; ?>">View & Approve</a></td>
                                </tr>
                                <?php } } else { echo "<tr><td colspan='5' class='text-center'>No new users are currently pending approval.</td></tr>"; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- All Users List -->
                <div class="card">
                    <div class="card-header"><h5 class="card-title">Complete Client List</h5></div>
                    <div class="card-datatable table-responsive">
                        <table class="datatables-products table">
                            <thead class="border-top"><tr><th>ID</th><th>Client Name</th><th>Phone Number</th><th>Gender</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php 
                                // SECURELY fetch all users
                                $sql_all = "SELECT user_id, user_name, user_phone, user_gender, user_status FROM tbl_user ORDER BY user_id DESC";
                                $result_all = $conn->query($sql_all);
                                while($data = $result_all->fetch_assoc()){ ?>
                                <tr>
                                    <td><?php echo $data['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($data['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($data['user_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($data['user_gender']); ?></td>
                                    <td>
                                        <?php
                                            if ($data['user_status'] == '1') echo '<span class="badge bg-label-success me-1">Approved</span>';
                                            elseif ($data['user_status'] == '2') echo '<span class="badge bg-label-danger me-1">Rejected</span>';
                                            else echo '<span class="badge bg-label-warning me-1">Pending</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-info btn-sm" href="index.php?view_id=<?php echo $data['user_id']; ?>">View Details</a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php
            } // End of the main if/else block for modes
            ?>
        </div>
        <!-- / Content -->
        <?php include "inc/footer.php"; ?>
        <div class="content-backdrop fade"></div>
      </div>
      <!-- / Content wrapper -->
    </div>
    <!-- / Layout page -->
  </div>
  <!-- Overlay -->
  <div class="layout-overlay layout-menu-toggle"></div>
</div>
<!-- / Layout wrapper -->
</body>
</html>