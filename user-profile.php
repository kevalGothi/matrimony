<?php
session_start();
include "db/conn.php";

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. AUTHENTICATE THE USER ---
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
    echo "<script>alert('Your session is invalid. Please log in again.'); window.location.href='login.php';</script>";
    exit();
}
$user_live_data = $result->fetch_assoc();
$loggedInUserID = $user_live_data['user_id'];
$loggedInUser = $user_live_data; // For dashboard nav

// --- 2. PREPARE DATA FOR DISPLAY (HANDLING PENDING EDITS) ---
$display_data = $user_live_data; // Start with the live, approved data.
if (!empty($user_live_data['pending_data'])) {
    // If pending data exists, merge it on top so the user sees their unapproved edits.
    $pending_array = json_decode($user_live_data['pending_data'], true);
    $display_data = array_merge($display_data, $pending_array);
}

// --- 3. FETCH PHOTOS ---
$photos_stmt = $conn->prepare("SELECT image_path, approval_status FROM tbl_user_photos WHERE user_id = ? ORDER BY is_profile_picture DESC, upload_date ASC");
$photos_stmt->bind_param("i", $loggedInUserID);
$photos_stmt->execute();
$photos = $photos_stmt->get_result();

// --- FATAL ERROR FIX: The query for 'tbl_user_children' has been removed as it's no longer needed. ---

?>
<!doctype html>
<html lang="en">
<head>
    <title>My Profile - <?php echo htmlspecialchars($display_data['user_name']); ?></title>
    <link rel="stylesheet" href="css/bootstrap.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bs5-lightbox/1.8.3/bs5-lightbox.min.css">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .gallery-grid .photo-item { position: relative; }
        .gallery-grid img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #eee; cursor: pointer; transition: transform 0.2s; }
        .gallery-grid img:hover { transform: scale(1.05); }
        .db-pro-list ul { list-style: none; padding: 0; }
        .db-pro-list ul li { padding: 8px 0; border-bottom: 1px solid #f0f0f0; display: flex; }
        .db-pro-list ul li:last-child { border-bottom: none; }
        .db-pro-list ul li span { font-weight: 600; color: #555; width: 180px; flex-shrink: 0; }
        .status-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: bold; border-radius: 6px; }
    </style>
</head>
<body>
    <?php include "inc/header.php"; ?><?php include "inc/bodystart.php"; ?><?php include "inc/navbar.php"; ?>

    <section><div class="db"><div class="container"><div class="row">
        <div class="col-md-4 col-lg-3"><?php include "inc/dashboard_nav.php"; ?></div>
        <div class="col-md-8 col-lg-9">
            <div class="db-sec-com db-pro-stat">
                <?php if (!empty($user_live_data['has_pending_changes'])): ?>
                    <div class="alert alert-warning">
                        <strong>Pending Approval:</strong> Some of your profile details are waiting for review. The information you see below includes your latest edits.
                    </div>
                <?php endif; ?>

                <div class="db-pro-stat-top">
                    <div class="db-pro-stat-top-bio">
                        <div class="db-pro-img">
                            <img src="upload/<?php echo !empty($display_data['user_img']) ? htmlspecialchars($display_data['user_img']) : 'default-profile.png'; ?>" alt="My Profile Image">
                        </div>
                        <div class="db-pro-bio">
                            <h2><?php echo htmlspecialchars($display_data['user_name']); ?></h2>
                            <span>Profile ID: <?php echo htmlspecialchars($display_data['user_gen_id'] ?? 'N/A'); ?></span>
                            <a href="user-profile-edit.php" class="cta-3">Edit Profile & Photos</a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header"><h5>My Photo Gallery</h5></div>
                    <div class="card-body">
                        <div class="gallery-grid">
                            <?php if ($photos && $photos->num_rows > 0): while ($photo = $photos->fetch_assoc()): ?>
                                <div class="photo-item">
                                    <a href="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" data-bs-toggle="lightbox" data-gallery="user-gallery">
                                        <img src="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" class="img-fluid" alt="User gallery photo">
                                    </a>
                                    <?php if($photo['approval_status'] == 0): ?><div class="status-overlay">Pending</div><?php endif; ?>
                                    <?php if($photo['approval_status'] == 2): ?><div class="status-overlay" style="background:rgba(220,53,69,0.7);">Rejected</div><?php endif; ?>
                                </div>
                            <?php endwhile; else: ?>
                                <p class="text-muted">You have not uploaded any gallery photos. <a href="user-profile-edit.php">Add some now!</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header"><h5>My Complete Profile</h5></div>
                    <div class="card-body">
                        <div class="db-pro-list">
                            <h5>About Me</h5><hr>
                            <p><?php
                                $age = !empty($display_data['user_dob']) ? (new DateTime($display_data['user_dob']))->diff(new DateTime('today'))->y : 'an unknown age';
                                $about_me = "I am a {$age}-year-old " . htmlspecialchars($display_data['user_maritalstatus']) . " from " . htmlspecialchars($display_data['user_city']) . ". ";
                                if (!empty($display_data['user_jobType'])) { $about_me .= "I work as a " . htmlspecialchars($display_data['user_jobType']) . ". "; }
                                if (!empty($display_data['user_hobbies'])) { $about_me .= "In my free time, I enjoy " . htmlspecialchars($display_data['user_hobbies']) . "."; }
                                echo $about_me;
                            ?></p>
                        </div>

                        <div class="row mt-4" style="overflow: scroll;">
                            <div class="col-lg-6">
                                <div class="db-pro-list"><h5>Basic Information</h5><hr><ul>
                                    <li><span>Age:</span> <?php echo $age; ?> Years</li>
                                    <li><span>Date of Birth:</span> <?php echo htmlspecialchars(date('d M Y', strtotime($display_data['user_dob'] ?? ''))); ?></li>
                                    <li><span>Height / Weight:</span> <?php echo htmlspecialchars($display_data['user_height'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($display_data['user_weight'] ?? 'N/A'); ?></li>
                                    <li><span>Marital Status:</span> <?php echo htmlspecialchars($display_data['user_maritalstatus'] ?? 'N/A'); ?></li>
                                    <li><span>Disability:</span> <?php echo htmlspecialchars($display_data['user_disability'] ?? 'None'); ?></li>
                                </ul></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="db-pro-list"><h5>Location</h5><hr><ul>
                                    <li><span>Address:</span> <?php echo htmlspecialchars($display_data['user_address'] ?? 'N/A'); ?></li>
                                    <li><span>City / State:</span> <?php echo htmlspecialchars($display_data['user_city'] ?? 'N/A'); ?>, <?php echo htmlspecialchars($display_data['user_state'] ?? 'N/A'); ?></li>
                                    <li><span>Country:</span> <?php echo htmlspecialchars($display_data['user_country'] ?? 'N/A'); ?></li>
                                    <li><span>Current Residence:</span> <?php echo htmlspecialchars($display_data['user_currentResident'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                            <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Religion & Community</h5><hr><ul>
                                    <li><span>Religion:</span> <?php echo htmlspecialchars($display_data['user_religion'] ?? 'N/A'); ?></li>
                                    <li><span>Mother Tongue:</span> <?php echo htmlspecialchars($display_data['user_mother_tongue'] ?? 'N/A'); ?></li>
                                    <li><span>Caste:</span> <?php echo htmlspecialchars($display_data['user_namecast'] ?? 'N/A'); ?></li>
                                    <li><span>Marry Other Castes?</span> <?php echo htmlspecialchars($display_data['user_nameintercast'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                            <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Family Details</h5><hr><ul>
                                    <li><span>Father's Name:</span> <?php echo htmlspecialchars($display_data['user_fatherName'] ?? 'N/A'); ?></li>
                                    <li><span>Mother's Name:</span> <?php echo htmlspecialchars($display_data['user_motherName'] ?? 'N/A'); ?></li>
                                    <li><span>Lives With:</span> <?php echo htmlspecialchars($display_data['user_whoyoustaywith'] ?? 'N/A'); ?></li>
                                    <li><span>Native Place:</span> <?php echo htmlspecialchars($display_data['user_whereyoubelong'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                             <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Education & Career</h5><hr><ul>
                                    <li><span>Highest Degree:</span> <?php echo htmlspecialchars($display_data['user_degree'] ?? 'N/A'); ?></li>
                                    <li><span>College / School:</span> <?php echo htmlspecialchars($display_data['user_collage'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($display_data['user_school'] ?? 'N/A'); ?></li>
                                    <li><span>Profession:</span> <?php echo htmlspecialchars($display_data['user_jobType'] ?? 'N/A'); ?></li>
                                    <li><span>Company:</span> <?php echo htmlspecialchars($display_data['user_companyName'] ?? 'N/A'); ?></li>
                                    <li><span>Annual Salary:</span> <?php echo htmlspecialchars($display_data['user_salary'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                            
                            <!-- UPGRADED: Children Details Section -->
                            <?php if (!empty($display_data['user_has_kids']) && $display_data['user_has_kids'] == 'Yes'): ?>
                            <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Children Details</h5><hr><ul>
                                    <li><span>Has Children:</span> Yes</li>
                                    <li>
                                        <span>Count:</span>
                                        <?php 
                                            echo htmlspecialchars($display_data['user_children_count'] ?? '0');
                                            echo " (Boys: " . htmlspecialchars($display_data['user_boys_count'] ?? '0') . ", ";
                                            echo "Girls: " . htmlspecialchars($display_data['user_girls_count'] ?? '0') . ")";
                                        ?>
                                    </li>
                                    <?php if (!empty($display_data['user_children_names'])): ?>
                                        <li><span>Names:</span> <?php echo nl2br(htmlspecialchars($display_data['user_children_names'])); ?></li>
                                    <?php endif; ?>
                                </ul></div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div></div></div></section>

    <?php include "inc/copyright.php"; ?><?php include "inc/footerlink.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/bs5-lightbox@1.8.3/dist/index.bundle.min.js"></script>
</body>
</html>