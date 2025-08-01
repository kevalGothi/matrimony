<?php
// Include your database connection file.
// IMPORTANT: Adjust the path if your connection file is located elsewhere.
include "./db/conn.php";

// 1. --- GET AND VALIDATE USER ID ---
// Check if the 'id' parameter is set in the URL and is a number.
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // If no valid ID, stop and show an error.
    die("Error: No profile ID was specified or the ID is invalid.");
}
$user_id = (int)$_GET['id'];


// 2. --- FETCH USER DATA FROM DATABASE ---
// Use a prepared statement for security against SQL Injection.
$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ? AND user_status = 1"); // Only show approved profiles
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If no user is found with that ID, stop and show a user-friendly message.
    $profile_found = false;
} else {
    // Fetch the user's data into an associative array.
    $user = $result->fetch_assoc();
    $profile_found = true;
}
$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">

<head>
    <title><?php echo $profile_found ? htmlspecialchars($user['user_name']) . ' | Profile Details' : 'Profile Not Found'; ?></title>
    <!--== META TAGS ==-->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#f6af04">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <!--== FAV ICON(BROWSER TAB ICON) ==-->
    <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
    <!--== CSS FILES ==-->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/style.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.min.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <!-- PRELOADER -->
    <div id="preloader">
        <div class="plod">
            <span class="lod1"><img src="images/loder/1.png" alt="" loading="lazy"></span>
            <span class="lod2"><img src="images/loder/2.png" alt="" loading="lazy"></span>
            <span class="lod3"><img src="images/loder/3.png" alt="" loading="lazy"></span>
        </div>
    </div>
    <!-- ... (rest of your header, menu, etc. can remain the same) ... -->

    <!-- Your Header and Menu HTML can go here -->
    
    <!-- PROFILE SECTION -->
    <section>
        <?php if ($profile_found): ?>
        <div class="profi-pg profi-ban">
            <div class="">
                <div class="">
                    <div class="profile">
                        <div class="pg-pro-big-im">
                            <div class="s1">
                                <!-- DYNAMIC IMAGE -->
                                <img src="./upload/<?php echo htmlspecialchars($user['user_img']); ?>" loading="lazy" class="pro" alt="<?php echo htmlspecialchars($user['user_name']); ?>">
                            </div>
                            <div class="s3">
                                <a href="#!" class="cta fol cta-chat">Chat now</a>
                                <span class="cta cta-sendint" data-toggle="modal" data-target="#sendInter">Send interest</span>
                            </div>
                        </div>
                    </div>
                    <div class="profi-pg profi-bio">
                        <div class="lhs">
                            <div class="pro-pg-intro">
                                <!-- DYNAMIC NAME -->
                                <h1><?php echo htmlspecialchars($user['user_name']); ?></h1>
                                <div class="pro-info-status">
                                    <span class="stat-1">Profile ID: <?php echo htmlspecialchars($user['user_gen_id']); ?></span>
                                    <span class="stat-2"><b>Available</b> online</span>
                                </div>
                                <ul>
                                    <!-- DYNAMIC BASIC INFO -->
                                    <li><div><img src="images/icon/pro-city.png" loading="lazy" alt=""><span>City: <strong><?php echo htmlspecialchars($user['user_city']); ?></strong></span></div></li>
                                    <li><div><img src="images/icon/pro-age.png" loading="lazy" alt=""><span>Age: <strong><?php echo htmlspecialchars($user['user_age']); ?></strong></span></div></li>
                                    <li><div><img src="images/icon/pro-height.png" loading="lazy" alt=""><span>Height: <strong><?php echo htmlspecialchars($user['user_height']); ?></strong></span></div></li>
                                    <li><div><img src="images/icon/pro-job.png" loading="lazy" alt=""><span>Job: <strong><?php echo htmlspecialchars($user['user_jobType']); ?></strong></span></div></li>
                                </ul>
                            </div>
                            
                            <!-- DYNAMIC ABOUT SECTION -->
                            <div class="pr-bio-c pr-bio-abo">
                                <h3>About <?php echo htmlspecialchars(explode(' ', $user['user_name'])[0]); // Show first name ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($user['user_address'])); // Using address as 'About Me', nl2br converts newlines to <br> ?></p>
                            </div>
                            
                            <!-- DYNAMIC PHOTO GALLERY (Shows the main photo) -->
                            <div class="pr-bio-c pr-bio-gal" id="gallery">
                                <h3>Photo gallery</h3>
                                <div id="image-gallery">
                                    <div class="pro-gal-imag">
                                        <div class="img-wrapper">
                                            <a href="admin/upload/<?php echo htmlspecialchars($user['user_img']); ?>"><img src="admin/upload/<?php echo htmlspecialchars($user['user_img']); ?>" class="img-responsive" alt=""></a>
                                            <div class="img-overlay"><i class="fa fa-arrows-alt" aria-hidden="true"></i></div>
                                        </div>
                                    </div>
                                    <!-- Add more photos here if you have a separate photo table -->
                                </div>
                            </div>
                            
                            <!-- DYNAMIC PERSONAL INFORMATION -->
                            <div class="pr-bio-c pr-bio-info">
                                <h3>Personal Information</h3>
                                <ul>
                                    <li><b>Name:</b> <?php echo htmlspecialchars($user['user_name']); ?></li>
                                    <li><b>Father's Name:</b> <?php echo htmlspecialchars($user['user_fatherName']); ?></li>
                                    <li><b>Date of Birth:</b> <?php echo date('d M Y', strtotime($user['user_dob'])); ?></li>
                                    <li><b>Marital Status:</b> <?php echo htmlspecialchars($user['user_maritalstatus']); ?></li>
                                    <li><b>Religion / Caste:</b> <?php echo htmlspecialchars($user['user_religion']); ?> / <?php echo htmlspecialchars($user['user_namecast']); ?></li>
                                    <li><b>Weight:</b> <?php echo htmlspecialchars($user['user_weight']); ?></li>
                                    <li><b>Disability:</b> <?php echo htmlspecialchars($user['user_disability']); ?></li>
                                </ul>
                            </div>

                             <!-- DYNAMIC PROFESSIONAL & EDUCATION INFORMATION -->
                            <div class="pr-bio-c pr-bio-info">
                                <h3>Education & Professional Details</h3>
                                <ul>
                                    <li><b>Highest Degree:</b> <?php echo htmlspecialchars($user['user_degree']); ?></li>
                                    <li><b>College / University:</b> <?php echo htmlspecialchars($user['user_collage']); ?></li>
                                    <li><b>Profession:</b> <?php echo htmlspecialchars($user['user_jobType']); ?></li>
                                    <li><b>Company:</b> <?php echo htmlspecialchars($user['user_companyName']); ?></li>
                                    <li><b>Salary:</b> <?php echo htmlspecialchars($user['user_salary']); ?></li>
                                </ul>
                            </div>
                            
                            <!-- DYNAMIC HOBBIES -->
                            <div class="pr-bio-c pr-bio-hob">
                                <h3>Hobbies & Interests</h3>
                                <ul>
                                    <?php
                                        // Explode the comma-separated hobbies string into an array
                                        $hobbies = explode(',', $user['user_hobbies']);
                                        foreach ($hobbies as $hobby) {
                                            // Trim whitespace and display each hobby
                                            echo '<li><span>' . htmlspecialchars(trim($hobby)) . '</span></li>';
                                        }
                                    ?>
                                </ul>
                            </div>

                        </div>

                        <!-- Right Hand Side (RHS) -->
                        <div class="rhs">
                            <!-- HELP BOX -->
                            <div class="prof-rhs-help">
                                <div class="inn">
                                    <h3>Tell us your Needs</h3>
                                    <p>Tell us what kind of service or experts you are looking for.</p>
                                    <a href="sign-up.html">Register for free</a>
                                </div>
                            </div>
                            <!-- END HELP BOX -->
                            <!-- (The "Related profiles" section can remain static for now) -->
                            <div class="slid-inn pr-bio-c wedd-rel-pro">
                               <h3>Related profiles</h3>
                                <ul class="slider3">
                                    <li>
                                        <div class="wedd-rel-box">
                                            <div class="wedd-rel-img"><img src="images/profiles/1.jpg" alt=""><span class="badge badge-success">21 Years old</span></div>
                                            <div class="wedd-rel-con"><h5>Christine</h5><span>City: <b>New York City</b></span></div>
                                            <a href="profile-details.html" class="fclick"></a>
                                        </div>
                                    </li>
                                     <!-- Add more static related profiles as needed -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <!-- This section is shown if the profile was not found -->
            <div class="container" style="padding: 100px 15px; text-align: center;">
                <div class="alert alert-danger">
                    <h2>Profile Not Found</h2>
                    <p>Sorry, the profile you are looking for does not exist or is no longer available.</p>
                    <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ... (rest of your page like modals, footer, etc.) ... -->
    <!-- Your Modals, Footer, and JS scripts can go here -->

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/select-opt.js"></script>
    <script src="js/slick.js"></script>
    <script src="js/gallery.js"></script>
    <script src="js/custom.js"></script>
</body>

</html>