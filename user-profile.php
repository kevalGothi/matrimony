<?php
    session_start();
    include "db/conn.php";
    
    // For development, show all errors. For production, you should turn this off.
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // --- 1. AUTHENTICATE THE USER ---
    if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
        exit();
    }
    
    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];
    
    // Securely fetch the user's full profile data
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_phone = ? AND user_pass = ?");
    $stmt->bind_param("ss", $userN, $psw);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // If credentials are no longer valid, destroy session and redirect
        session_destroy();
        echo "<script>alert('Your session is invalid. Please log in again.'); window.location.href='login.php';</script>";
        exit();
    }
    
    $user = $result->fetch_assoc();
    $loggedInUserID = $user['user_id'];
    $loggedInUser = $user; // Alias for dashboard nav include

    // --- 2. FETCH THE USER'S PHOTO GALLERY ---
    // The query ensures the main profile picture appears first in the gallery list
    $photos_stmt = $conn->prepare("SELECT image_path FROM tbl_user_photos WHERE user_id = ? ORDER BY is_profile_picture DESC, upload_date ASC");
    $photos_stmt->bind_param("i", $loggedInUserID);
    $photos_stmt->execute();
    $photos = $photos_stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <title>My Profile - <?php echo htmlspecialchars($user['user_name']); ?></title>
    <!-- Standard CSS -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- Lightbox CSS for photo gallery popups -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bs5-lightbox/1.8.3/bs5-lightbox.min.css">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .gallery-grid img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #eee; cursor: pointer; transition: transform 0.2s; }
        .gallery-grid img:hover { transform: scale(1.05); }
        .db-pro-list ul { list-style: none; padding: 0; }
        .db-pro-list ul li { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .db-pro-list ul li:last-child { border-bottom: none; }
        .db-pro-list ul li span { font-weight: 600; color: #555; min-width: 150px; display: inline-block; }
    </style>
</head>
<body>
    <?php include "inc/header.php"; ?>
    <?php include "inc/bodystart.php"; ?>
    <?php include "inc/navbar.php"; ?>


    <section><div class="db"><div class="container"><div class="row">
        <!-- Left Dashboard Navigation -->
        <div class="col-md-4 col-lg-3">
                        <!--<div class="db-nav">-->
                        <!--    <div class="db-nav-pro">-->
                        <!--        <img src="upload/<?php echo !empty($user['user_img']) ? htmlspecialchars($user['user_img']) : 'default-profile.png'; ?>" class="img-fluid" alt="My Profile Image">-->
                        <!--    </div>-->
                        <!--    <div class="db-nav-list">-->
                        <!--        <ul>-->
                        <!--            <li><a href="user-dashboard.php"><i class="fa fa-tachometer"></i>Dashboard</a></li>-->
                        <!--            <li><a href="user-profile.php" class="act"><i class="fa fa-user"></i>Profile</a></li>-->
                        <!--            <li><a href="see-other-profile.php"><i class="fa fa-users"></i>See Others Profile</a></li>-->
                        <!--            <li><a href="user-profile-edit.php"><i class="fa fa-pencil-square-o"></i>Edit Profile</a></li>-->
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

        <!-- Right Content: Profile Details -->
        <div class="col-md-8 col-lg-9">
            <div class="db-sec-com db-pro-stat">
                <!-- Main Bio Section -->
                <div class="db-pro-stat-top">
                    <div class="db-pro-stat-top-bio">
                        <div class="db-pro-img">
                            <img src="upload/<?php echo !empty($user['user_img']) ? htmlspecialchars($user['user_img']) : 'default-profile.png'; ?>" alt="My Profile Image">
                        </div>
                        <div class="db-pro-bio">
                            <h2><?php echo htmlspecialchars($user['user_name']); ?></h2>
                            <span>Profile ID: <?php echo htmlspecialchars($user['user_gen_id'] ?? 'N/A'); ?></span>
                            <a href="user-profile-edit.php" class="cta-3">Edit Profile & Photos</a>
                        </div>
                    </div>
                </div>
                
                <!-- Photo Gallery Section -->
                <div class="card mt-4">
                    <div class="card-header"><h5>My Photo Gallery</h5></div>
                    <div class="card-body">
                        <div class="gallery-grid">
                            <?php if ($photos && $photos->num_rows > 0): ?>
                                <?php while ($photo = $photos->fetch_assoc()): ?>
                                    <a href="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" data-toggle="lightbox" data-gallery="user-gallery">
                                        <img src="upload/<?php echo htmlspecialchars($photo['image_path']); ?>" class="img-fluid" alt="User gallery photo">
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">You have not uploaded any gallery photos. <a href="user-profile-edit.php">Add some now!</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Full Profile Details Section -->
                <div class="card mt-4">
                    <div class="card-header"><h5>My Complete Profile</h5></div>
                    <div class="card-body">
                        <div class="db-pro-list"><h5>About Me</h5><hr><p><?php
                            $about_me = "I am a " . htmlspecialchars($user['user_age']) . "-year-old " . htmlspecialchars($user['user_maritalstatus']) . " " . htmlspecialchars($user['user_gender']) . " from " . htmlspecialchars($user['user_city']) . ". ";
                            if (!empty($user['user_jobType'])) { $about_me .= "I work as a " . htmlspecialchars($user['user_jobType']) . ". "; }
                            if (!empty($user['user_hobbies'])) { $about_me .= "In my free time, I enjoy " . htmlspecialchars($user['user_hobbies']) . "."; }
                            echo $about_me;
                        ?></p></div>

                        <div class="row mt-4">
                            <div class="col-lg-6">
                                <div class="db-pro-list"><h5>Basic Information</h5><hr><ul>
                                    <li><span>Age:</span> <?php echo htmlspecialchars($user['user_age'] ?? 'N/A'); ?></li>
                                    <li><span>Date of Birth:</span> <?php echo htmlspecialchars(date('d M Y', strtotime($user['user_dob'] ?? ''))); ?></li>
                                    <li><span>Height:</span> <?php echo htmlspecialchars($user['user_height'] ?? 'N/A'); ?></li>
                                    <li><span>Weight:</span> <?php echo htmlspecialchars($user['user_weight'] ?? 'N/A'); ?></li>
                                    <li><span>Marital Status:</span> <?php echo htmlspecialchars($user['user_maritalstatus'] ?? 'N/A'); ?></li>
                                    <li><span>Disability:</span> <?php echo htmlspecialchars($user['user_disability'] ?? 'None'); ?></li>
                                </ul></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="db-pro-list"><h5>Location</h5><hr><ul>
                                    <li><span>Address:</span> <?php echo htmlspecialchars($user['user_address'] ?? 'N/A'); ?></li>
                                    <li><span>City:</span> <?php echo htmlspecialchars($user['user_city'] ?? 'N/A'); ?></li>
                                    <li><span>State:</span> <?php echo htmlspecialchars($user['user_state'] ?? 'N/A'); ?></li>
                                    <li><span>Country:</span> <?php echo htmlspecialchars($user['user_country'] ?? 'N/A'); ?></li>
                                    <li><span>Current Residence:</span> <?php echo htmlspecialchars($user['user_currentResident'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                            <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Religion & Community</h5><hr><ul>
                                    <li><span>Religion:</span> <?php echo htmlspecialchars($user['user_religion'] ?? 'N/A'); ?></li>
                                    <li><span>Caste:</span> <?php echo htmlspecialchars($user['user_namecast'] ?? 'N/A'); ?></li>
                                    <li><span>Intercaste Marriage:</span> <?php echo htmlspecialchars($user['user_nameintercast'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                            <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Family Details</h5><hr><ul>
                                    <li><span>Father's Name:</span> <?php echo htmlspecialchars($user['user_fatherName'] ?? 'N/A'); ?></li>
                                    <li><span>Mother's Name:</span> <?php echo htmlspecialchars($user['user_motherName'] ?? 'N/A'); ?></li>
                                    <li><span>I stay with:</span> <?php echo htmlspecialchars($user['user_whoyoustaywith'] ?? 'N/A'); ?></li>
                                    <li><span>Native Place:</span> <?php echo htmlspecialchars($user['user_whereyoubelong'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                             <div class="col-lg-6 mt-4">
                                <div class="db-pro-list"><h5>Education & Professional</h5><hr><ul>
                                    <li><span>Highest Degree:</span> <?php echo htmlspecialchars($user['user_degree'] ?? 'N/A'); ?></li>
                                    <li><span>College:</span> <?php echo htmlspecialchars($user['user_collage'] ?? 'N/A'); ?></li>
                                    <li><span>School:</span> <?php echo htmlspecialchars($user['user_school'] ?? 'N/A'); ?></li>
                                    <li><span>Job Type:</span> <?php echo htmlspecialchars($user['user_jobType'] ?? 'N/A'); ?></li>
                                    <li><span>Company:</span> <?php echo htmlspecialchars($user['user_companyName'] ?? 'N/A'); ?></li>
                                    <li><span>Salary:</span> <?php echo htmlspecialchars($user['user_salary'] ?? 'N/A'); ?></li>
                                </ul></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div></div></div></section>

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