<?php
session_start();
// --- Security Check ---
if (!isset($_SESSION['admin_user'])) {
    header("Location: index.php"); // Redirect to admin login if not logged in
    exit();
}
include "../db/conn.php"; // Go up one directory to find the db connection

// --- Handle Approve/Reject Actions ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id_action'])) {
    $user_id = (int)$_POST['user_id_action'];
    $new_status = 0;

    if (isset($_POST['approve'])) {
        $new_status = 1; // Set status to Approved
    } elseif (isset($_POST['reject'])) {
        $new_status = 2; // Set status to Rejected
    }

    if ($new_status > 0) {
        $stmt_update = $conn->prepare("UPDATE tbl_user SET user_status = ? WHERE user_id = ?");
        $stmt_update->bind_param("ii", $new_status, $user_id);
        if ($stmt_update->execute()) {
            // Redirect back to the dashboard with a success message
            header("Location: dashboard.php?update=success");
            exit();
        } else {
            $error_message = "Failed to update user status.";
        }
    }
}

// --- Fetch User Details to Display ---
$user_id_to_view = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id_to_view === 0) {
    die("Error: No user ID specified.");
}

$stmt_fetch = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
$stmt_fetch->bind_param("i", $user_id_to_view);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();

if ($result->num_rows === 0) {
    die("Error: User not found.");
}
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View User Details - Admin Panel</title>
    <link rel="stylesheet" href="css/admin_style.css"> <!-- Assuming you have the CSS file from previous instructions -->
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <a href="dashboard.php" class="logo">Matrimony Admin</a>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <a href="dashboard.php" class="back-link">« Back to Dashboard</a>
        <h1>User Details: <?php echo htmlspecialchars($user['user_name']); ?> (ID: <?php echo $user['user_id']; ?>)</h1>
        
        <?php if(isset($error_message)) echo '<p class="error-message">'.$error_message.'</p>'; ?>

        <div class="user-details-grid">
            <!-- Left Column: Photo and Actions -->
            <div class="user-photo-section">
                <h3>Profile Photo</h3>
                <?php if (!empty($user['user_img'])): ?>
                    <img src="../upload/<?php echo htmlspecialchars($user['user_img']); ?>" alt="Profile Photo of <?php echo htmlspecialchars($user['user_name']); ?>">
                <?php else: ?>
                    <p>No photo has been uploaded by the user.</p>
                <?php endif; ?>

                <div class="action-box">
                    <h3>Admin Actions</h3>
                    <p>Current Profile Status: 
                        <?php 
                            if ($user['user_status'] == 1) echo '<span class="status-approved">Approved</span>';
                            elseif ($user['user_status'] == 2) echo '<span class="status-rejected">Rejected</span>';
                            else echo '<span class="status-pending">Pending Approval</span>';
                        ?>
                    </p>
                    <form method="POST" action="view_user.php?id=<?php echo $user['user_id']; ?>" onsubmit="return confirm('Are you sure you want to proceed with this action?');">
                        <input type="hidden" name="user_id_action" value="<?php echo $user['user_id']; ?>">
                        
                        <?php if ($user['user_status'] != 1): ?>
                            <button type="submit" name="approve" class="button">Approve This Profile</button>
                        <?php endif; ?>

                        <?php if ($user['user_status'] != 2): ?>
                            <button type="submit" name="reject" class="button button-danger">Reject This Profile</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Right Column: All User Details -->
            <div class="user-info-section">
                <h3>Complete User Information</h3>
                <table>
                    <?php 
                    // To display fields in a more logical order
                    $field_order = [
                        'user_id', 'user_gen_id', 'user_name', 'user_phone', 'user_email', 'user_pass', 'user_status', 'user_payment_status', 'plan_type', 'plan_expiry_date',
                        'user_gender', 'user_age', 'user_dob', 'user_religion', 'user_namecast', 'user_nameintercast', 'user_maritalstatus',
                        'user_height', 'user_weight', 'user_disability', 'user_fatherName', 'user_motherName', 'user_whoyoustaywith', 'user_whereyoubelong',
                        'user_address', 'user_city', 'user_currentResident',
                        'user_jobType', 'user_companyName', 'user_salary', 'user_degree', 'user_school', 'user_collage',
                        'user_hobbies', 'user_create_date'
                    ];

                    foreach ($field_order as $key) {
                        if (array_key_exists($key, $user)) {
                            $value = $user[$key];
                    ?>
                    <tr>
                        <th><?php echo str_replace('_', ' ', ucfirst($key)); ?></th>
                        <td>
                            <?php 
                                if ($key === 'user_status') {
                                    if ($value == 1) echo 'Approved'; elseif ($value == 2) echo 'Rejected'; else echo 'Pending';
                                } elseif ($key === 'user_payment_status') {
                                    echo ($value == 1) ? 'Paid' : 'Not Paid';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                            ?>
                        </td>
                    </tr>
                    <?php
                        }
                    } 
                    ?>
                </table>
            </div>
        </div>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> Matrimony Admin Panel. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>