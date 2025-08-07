<?php
session_start();
include "../db/conn.php";

// --- 1. Authentication (Using your established session variables) ---
if (!isset($_SESSION['username']) || !isset($_SESSION['pass'])) {
    header("Location: login/");
    exit();
}
$admin_email = $_SESSION['username'];
$admin_pass = $_SESSION['pass']; // Note: Storing plain password in session is insecure for production.

$stmt = $conn->prepare("SELECT ad_id, ad_name FROM tbl_admin WHERE ad_email = ? AND ad_pass = ?");
$stmt->bind_param("ss", $admin_email, $admin_pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    session_destroy();
    echo "<script>alert('Your session is invalid or your account no longer exists. Please log in again.'); window.location.href='login/';</script>";
    exit();
}
$adminfetch = $result->fetch_assoc();
$stmt->close();


// --- 2. Fetch All Necessary Data for the Dashboard ---

// Fetch users with pending profile edits (has_pending_edits = 1)
$pending_edits_res = $conn->query("SELECT user_id, user_name, user_phone FROM tbl_user WHERE has_pending_edits = 1 ORDER BY user_id DESC");
if (!$pending_edits_res) { die("Error fetching pending edits: " . $conn->error); }

// Fetch users pending initial profile approval (status = 0)
$pending_users_res = $conn->query("SELECT user_id, user_name, user_phone, user_create_date FROM tbl_user WHERE user_status = 0 ORDER BY user_id DESC");
if (!$pending_users_res) { die("Error fetching pending users: " . $conn->error); }

// Fetch all users for the main list
$all_users_res = $conn->query("SELECT user_id, user_name, user_phone, user_gender, user_status FROM tbl_user ORDER BY user_id DESC");
if (!$all_users_res) { die("Error fetching all users: " . $conn->error); }


// Include the HTML header and template structure
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
            
            <h4 class="py-3 mb-4">Admin Dashboard: User Management</h4>

            <!-- SECTION 1: Users with Pending Profile Edits -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title text-warning">Action Required: Users with Pending Profile Edits</h5></div>
                <div class="card-datatable table-responsive">
                    <table class="table border-top">
                        <thead><tr><th>User ID</th><th>Name</th><th>Phone</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($pending_edits_res->num_rows > 0): while($user = $pending_edits_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_phone']); ?></td>
                                <td><a class="btn btn-warning btn-sm" href="manage_user.php?user_id=<?php echo $user['user_id']; ?>">Review Edits</a></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center">No users have pending profile edits.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: New Users Pending Initial Approval -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title">New Users Pending Initial Approval</h5></div>
                <div class="card-datatable table-responsive">
                    <table class="table border-top">
                        <thead><tr><th>User ID</th><th>Name</th><th>Phone</th><th>Registered On</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($pending_users_res->num_rows > 0): while($user = $pending_users_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_phone']); ?></td>
                                <td><?php echo date('d M Y', strtotime($user['user_create_date'])); ?></td>
                                <td><a class="btn btn-primary btn-sm" href="manage_user.php?user_id=<?php echo $user['user_id']; ?>">View & Approve</a></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center">No new users are pending initial approval.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 3: All Users List -->
            <div class="card">
                <div class="card-header"><h5 class="card-title">Complete User List</h5></div>
                <div class="card-datatable table-responsive">
                    <table class="datatables-products table"> <!-- Using a class for DataTables JS if you have it -->
                        <thead><tr><th>ID</th><th>Client Name</th><th>Phone Number</th><th>Gender</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if ($all_users_res->num_rows > 0): while($user = $all_users_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_gender']); ?></td>
                                <td>
                                    <?php
                                        if ($user['user_status'] == '1') echo '<span class="badge bg-label-success">Approved</span>';
                                        elseif ($user['user_status'] == '2') echo '<span class="badge bg-label-danger">Rejected</span>';
                                        else echo '<span class="badge bg-label-warning">Pending</span>';
                                    ?>
                                </td>
                                <td>
                                    <a class="btn btn-info btn-sm" href="manage_user.php?user_id=<?php echo $user['user_id']; ?>">Manage User</a>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
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