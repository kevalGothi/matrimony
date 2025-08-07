<?php
    session_start();
    include "db/conn.php";
    
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
    $loggedInUser = $result->fetch_assoc();
    $loggedInUserID = $loggedInUser['user_id']; 

    // --- 2. FETCH DYNAMIC OPTIONS FOR FILTER DROPDOWNS ---
    $religions = $conn->query("SELECT DISTINCT user_religion FROM tbl_user WHERE user_religion IS NOT NULL AND user_religion != '' ORDER BY user_religion ASC")->fetch_all(MYSQLI_ASSOC);
    $castes = $conn->query("SELECT DISTINCT user_namecast FROM tbl_user WHERE user_namecast IS NOT NULL AND user_namecast != '' ORDER BY user_namecast ASC")->fetch_all(MYSQLI_ASSOC);
    $mother_tongues = $conn->query("SELECT DISTINCT user_mother_tongue FROM tbl_user WHERE user_mother_tongue IS NOT NULL AND user_mother_tongue != '' ORDER BY user_mother_tongue ASC")->fetch_all(MYSQLI_ASSOC);
    $states = $conn->query("SELECT DISTINCT user_state FROM tbl_user WHERE user_state IS NOT NULL AND user_state != '' ORDER BY user_state ASC")->fetch_all(MYSQLI_ASSOC);
    $countries = $conn->query("SELECT DISTINCT user_country FROM tbl_user WHERE user_country IS NOT NULL AND user_country != '' ORDER BY user_country ASC")->fetch_all(MYSQLI_ASSOC);

    // --- 3. DYNAMIC SQL QUERY BUILDING FOR FILTERS ---
    $userGender = $loggedInUser['user_gender'];
    $sql = "SELECT user_id, user_name, user_jobType, user_city, user_state, user_country, user_age, user_height, user_img FROM tbl_user WHERE user_gender != ? AND user_status = 1";
    $params = [$userGender];
    $types = 's';

    $f_religion = $_GET['religion'] ?? '';
    $f_min_age = $_GET['min_age'] ?? '';
    $f_max_age = $_GET['max_age'] ?? '';
    $f_caste = $_GET['caste'] ?? '';
    $f_mother_tongue = $_GET['mother_tongue'] ?? '';
    $f_marital_status = $_GET['marital_status'] ?? '';
    $f_state = $_GET['state'] ?? '';
    $f_country = $_GET['country'] ?? '';
    
    if (!empty($f_religion)) { $sql .= " AND user_religion = ?"; $params[] = $f_religion; $types .= 's'; }
    if (!empty($f_caste)) { $sql .= " AND user_namecast = ?"; $params[] = $f_caste; $types .= 's'; }
    if (!empty($f_mother_tongue)) { $sql .= " AND user_mother_tongue = ?"; $params[] = $f_mother_tongue; $types .= 's'; }
    if (!empty($f_marital_status)) { $sql .= " AND user_maritalstatus = ?"; $params[] = $f_marital_status; $types .= 's'; }
    if (!empty($f_state)) { $sql .= " AND user_state = ?"; $params[] = $f_state; $types .= 's'; }
    if (!empty($f_country)) { $sql .= " AND user_country = ?"; $params[] = $f_country; $types .= 's'; }
    if (!empty($f_min_age)) { $sql .= " AND user_age >= ?"; $params[] = $f_min_age; $types .= 'i'; }
    if (!empty($f_max_age)) { $sql .= " AND user_age <= ?"; $params[] = $f_max_age; $types .= 'i'; }

    $filter_stmt = $conn->prepare($sql);

    // *** THIS IS THE CRITICAL FIX FOR SERVER COMPATIBILITY ***
    if (count($params) > 1) {
        $params_ref = [];
        foreach ($params as $key => $value) {
            $params_ref[$key] = &$params[$key];
        }
        array_unshift($params_ref, $types);
        call_user_func_array([$filter_stmt, 'bind_param'], $params_ref);
    } else {
        // If there are no filters, just bind the initial gender parameter in the standard way
        $filter_stmt->bind_param($types, $userGender);
    }
    
    $filter_stmt->execute();
    $findsql = $filter_stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <title>Wedding Matrimony - My Dashboard</title>
    <!-- Your standard CSS includes -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "inc/header.php"; ?>
    <?php include "inc/bodystart.php"; ?>
    <?php include "inc/navbar.php"; ?>

    <section><div class="db"><div class="container"><div class="row">
        <!-- Left Navigation & Filter Panel -->
        <div class="col-md-4 col-lg-3">

                <?php include "inc/dashboard_nav.php"; ?>
                

        </div>
        
        <!-- Right Content -->
        <div class="col-md-8 col-lg-9"><div class="row">
                            <div class="pro-fil">
                    <h4 class="fil-tit">Find Your Match</h4>
                    <form method="GET" action="see-other-profile.php">

                        <div class="form-group mb-2"><label>Marital Status:</label><select name="marital_status" class="form-control"><option value="">All</option><option value="Single" <?php if($f_marital_status == 'Single') echo 'selected'; ?>>Single</option><option value="Divorced" <?php if($f_marital_status == 'Divorced') echo 'selected'; ?>>Divorced</option><option value="Widowed" <?php if($f_marital_status == 'Widowed') echo 'selected'; ?>>Widowed</option></select></div>
                        <div class="form-group mb-2"><label>Religion:</label><select name="religion" class="form-control"><option value="">All</option><?php foreach($religions as $item): ?><option value="<?php echo htmlspecialchars($item['user_religion']); ?>" <?php if($f_religion == $item['user_religion']) echo 'selected'; ?>><?php echo htmlspecialchars($item['user_religion']); ?></option><?php endforeach; ?></select></div>
                        <div class="form-group mb-2"><label>Caste:</label><select name="caste" class="form-control"><option value="">All</option><?php foreach($castes as $item): ?><option value="<?php echo htmlspecialchars($item['user_namecast']); ?>" <?php if($f_caste == $item['user_namecast']) echo 'selected'; ?>><?php echo htmlspecialchars($item['user_namecast']); ?></option><?php endforeach; ?></select></div>
                        <!--<div class="form-group mb-2"><label>Mother Tongue:</label><select name="mother_tongue" class="form-control"><option value="">All</option><?php foreach($mother_tongues as $item): ?><option value="<?php echo htmlspecialchars($item['user_mother_tongue']); ?>" <?php if($f_mother_tongue == $item['user_mother_tongue']) echo 'selected'; ?>><?php echo htmlspecialchars($item['user_mother_tongue']); ?></option><?php endforeach; ?></select></div>-->
                        <div class="form-group mb-2"><label>Country:</label><select name="country" class="form-control"><option value="">All</option><?php foreach($countries as $item): ?><option value="<?php echo htmlspecialchars($item['user_country']); ?>" <?php if($f_country == $item['user_country']) echo 'selected'; ?>><?php echo htmlspecialchars($item['user_country']); ?></option><?php endforeach; ?></select></div>
                        <div class="form-group mb-2"><label>State:</label><select name="state" class="form-control"><option value="">All</option><?php foreach($states as $item): ?><option value="<?php echo htmlspecialchars($item['user_state']); ?>" <?php if($f_state == $item['user_state']) echo 'selected'; ?>><?php echo htmlspecialchars($item['user_state']); ?></option><?php endforeach; ?></select></div>
                        <div class="d-grid gap-2 mt-3"><button type="submit" class="btn btn-primary">Search</button><a href="see-other-profile.php" class="btn btn-secondary">Clear Filters</a></div>
                    </form>
                </div>
            <div class="short-all"><div class="short-lhs">Found <b><?php echo $findsql->num_rows; ?></b> matching profiles</div></div>
            <div class="all-list-sh"><ul>
                <?php if ($findsql->num_rows > 0): while($profile = $findsql->fetch_assoc()): ?>
                <li><div class="all-pro-box">
                    <div class="pro-img"><a href="profile-details.php?id=<?php echo $profile['user_id']; ?>"><img src="upload/<?php echo !empty($profile['user_img']) ? htmlspecialchars($profile['user_img']) : 'default-profile.png'; ?>" alt="Profile Photo"></a></div>
                    <div class="pro-detail">
                        <h4><a href="profile-details.php?id=<?php echo $profile['user_id']; ?>"><?php echo htmlspecialchars($profile['user_name']); ?></a></h4>
                        <div class="pro-bio"><span><?php echo htmlspecialchars($profile['user_age']); ?> Yrs, <?php echo htmlspecialchars($profile['user_height']); ?></span><span><?php echo htmlspecialchars($profile['user_city'] . ', ' . $profile['user_state']); ?></span><span><?php echo htmlspecialchars($profile['user_jobType']); ?></span></div>
                        <div class="links">
                            <?php
                                $is_premium = false;
                                if (!empty($loggedInUser['plan_type']) && $loggedInUser['plan_type'] != 'Free' && !empty($loggedInUser['plan_expiry_date'])) {
                                    if (new DateTime() <= new DateTime($loggedInUser['plan_expiry_date'])) { $is_premium = true; }
                                }
                                $interest_link = "user-interests.php?send_interest=" . $profile['user_id'];
                                $chat_link = $is_premium ? "open-chat.php?receiver_id=" . $profile['user_id'] : "plans.php";
                            ?>
                            <a href="<?php echo $chat_link; ?>"><span class="cta-chat"><?php echo $is_premium ? 'Chat Now' : 'Upgrade to Chat'; ?></span></a>
                            <a href="<?php echo $interest_link; ?>" class="confirm-action-btn" data-title="Send Interest?" data-text="Are you sure you want to send an interest to <?php echo htmlspecialchars(addslashes($profile['user_name'])); ?>?" data-confirm-text="Yes, send interest!"><span class="cta cta-sendint">Send Interest</span></a>
                        </div>
                    </div>
                </div></li>
                <?php endwhile; else: ?>
                    <li class="text-center p-5"><div class="alert alert-info"><h4>No profiles found.</h4><p>Your search returned no results. Please try adjusting or clearing your filters.</p></div></li>
                <?php endif; ?>
            </ul></div>
        </div></div>
    </div></div></div></section>
    <script src="js/jquery.min.js"></script><script src="js/bootstrap.min.js"></script><script src="js/custom.js"></script>
</body>
</html>