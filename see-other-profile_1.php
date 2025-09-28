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
$stmt->close();

// Fetch sent interests
$sent_interests_stmt = $conn->prepare("SELECT chat_receiverID FROM tbl_chat WHERE chat_senderID = ?");
$sent_interests_stmt->bind_param("i", $loggedInUserID);
$sent_interests_stmt->execute();
$sent_interests_result = $sent_interests_stmt->get_result();
$sent_interest_ids = [];
while ($row = $sent_interests_result->fetch_assoc()) {
    $sent_interest_ids[] = $row['chat_receiverID'];
}
$sent_interests_stmt->close();

// --- 2. FETCH DYNAMIC OPTIONS ---
$religions = $conn->query("SELECT DISTINCT user_religion FROM tbl_user WHERE user_religion IS NOT NULL AND user_religion != '' ORDER BY user_religion ASC")->fetch_all(MYSQLI_ASSOC);
$castes = $conn->query("SELECT DISTINCT user_namecast FROM tbl_user WHERE user_namecast IS NOT NULL AND user_namecast != '' ORDER BY user_namecast ASC")->fetch_all(MYSQLI_ASSOC);
$mother_tongues = $conn->query("SELECT DISTINCT user_mother_tongue FROM tbl_user WHERE user_mother_tongue IS NOT NULL AND user_mother_tongue != '' ORDER BY user_mother_tongue ASC")->fetch_all(MYSQLI_ASSOC);
$states = $conn->query("SELECT DISTINCT user_state FROM tbl_user WHERE user_state IS NOT NULL AND user_state != '' ORDER BY user_state ASC")->fetch_all(MYSQLI_ASSOC);
$countries = $conn->query("SELECT DISTINCT user_country FROM tbl_user WHERE user_country IS NOT NULL AND user_country != '' ORDER BY user_country ASC")->fetch_all(MYSQLI_ASSOC);

// --- 3. DYNAMIC SQL QUERY BUILDING ---
$userGender = $loggedInUser['user_gender'];
$sql = "SELECT user_id, user_name, user_jobType, user_city, user_state, user_country, user_dob, user_height, user_img 
        FROM tbl_user 
        WHERE user_gender != ? AND user_status = 1";
$params = [$userGender];
$types = 's';

$f_religion = filter_input(INPUT_GET, 'religion', FILTER_SANITIZE_STRING) ?? '';
$f_min_age = filter_input(INPUT_GET, 'min_age', FILTER_VALIDATE_INT) ?? '';
$f_max_age = filter_input(INPUT_GET, 'max_age', FILTER_VALIDATE_INT) ?? '';
$f_caste = filter_input(INPUT_GET, 'caste', FILTER_SANITIZE_STRING) ?? '';
$f_mother_tongue = filter_input(INPUT_GET, 'mother_tongue', FILTER_SANITIZE_STRING) ?? '';
$f_marital_status = filter_input(INPUT_GET, 'marital_status', FILTER_SANITIZE_STRING) ?? '';
$f_state = filter_input(INPUT_GET, 'state', FILTER_SANITIZE_STRING) ?? '';
$f_country = filter_input(INPUT_GET, 'country', FILTER_SANITIZE_STRING) ?? '';

if (!empty($f_religion)) { $sql .= " AND user_religion = ?"; $params[] = $f_religion; $types .= 's'; }
if (!empty($f_caste)) { $sql .= " AND user_namecast = ?"; $params[] = $f_caste; $types .= 's'; }
if (!empty($f_mother_tongue)) { $sql .= " AND user_mother_tongue = ?"; $params[] = $f_mother_tongue; $types .= 's'; }
if (!empty($f_marital_status)) { $sql .= " AND user_maritalstatus = ?"; $params[] = $f_marital_status; $types .= 's'; }
if (!empty($f_state)) { $sql .= " AND user_state = ?"; $params[] = $f_state; $types .= 's'; }
if (!empty($f_country)) { $sql .= " AND user_country = ?"; $params[] = $f_country; $types .= 's'; }

if ($f_min_age !== false && $f_min_age !== '') {
    $max_dob = (new DateTime())->sub(new DateInterval("P{$f_min_age}Y"))->format('Y-m-d');
    $sql .= " AND user_dob <= ?";
    $params[] = $max_dob;
    $types .= 's';
}
if ($f_max_age !== false && $f_max_age !== '') {
    $min_dob = (new DateTime())->sub(new DateInterval("P" . ($f_max_age + 1) . "Y"))->add(new DateInterval('P1D'))->format('Y-m-d');
    $sql .= " AND user_dob >= ?";
    $params[] = $min_dob;
    $types .= 's';
}

$filter_stmt = $conn->prepare($sql);
if (!$filter_stmt) {
    die("SQL Error: " . $conn->error);
}

if (count($params) > 1) {
    $params_ref = array_merge([$types], $params);
    $filter_stmt->bind_param(...$params_ref);
} else {
    $filter_stmt->bind_param($types, $params[0]);
}

$filter_stmt->execute();
$findsql = $filter_stmt->get_result();
$profiles = $findsql->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Matrimony - Find Your Match</title>
    <!-- Tailwind CSS with local fallback -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="css/tailwind.min.css" rel="stylesheet" media="none" onload="if(media!='all')media='all'">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Hammer.js for swipe gestures -->
    <script src="https://hammerjs.github.io/dist/hammer.min.js"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
        }
        .profile-card {
            -webkit-transition: -webkit-transform 0.3s ease, box-shadow 0.3s ease;
            -moz-transition: -moz-transform 0.3s ease, box-shadow 0.3s ease;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profile-card:hover {
            -webkit-transform: translateY(-5px);
            -moz-transform: translateY(-5px);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .filter-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: #ff6b6b;
            color: white;
            -webkit-transition: background 0.3s ease;
            -moz-transition: background 0.3s ease;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: #ff4d4d;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
            -webkit-transition: background 0.3s ease;
            -moz-transition: background 0.3s ease;
            transition: background 0.3s ease;
        }
        .btn-secondary:hover {
            background: #5b606d;
        }
        .cta-sent {
            background: #22c55e !important;
            color: white !important;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .cta-chat, .cta-sendint {
            -webkit-transition: background 0.3s ease;
            -moz-transition: background 0.3s ease;
            transition: background 0.3s ease;
        }
        .cta-chat:hover {
            background: #60a5fa !important;
        }
        .cta-sendint:hover {
            background: #65a30d !important;
        }
        img {
            display: block;
            max-width: 100%;
            height: auto;
        }
        .db-nav {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        .db-nav-pro img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }
        .db-nav-list ul {
            list-style: none;
            padding: 0;
        }
        .db-nav-list li {
            margin-bottom: 0.5rem;
        }
        .db-nav-list a {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            color: #4b5563;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s ease;
        }
        .db-nav-list a:hover {
            background: #f3f4f6;
        }
        .db-nav-list a.act {
            background: #ff6b6b;
            color: white;
        }
        .db-nav-list i {
            margin-right: 0.5rem;
        }
        .mobile-dashboard-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            z-index: 1000;
            display: flex;
        }
        .mobile-dashboard-nav a {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .mobile-dashboard-nav a.active {
            color: #ff6b6b;
        }
        .mobile-dashboard-nav i {
            display: block;
            font-size: 1.5rem;
        }
        /* Tinder-like mobile styles */
        .tinder-container {
            position: relative;
            height: calc(100vh - 5rem);
            overflow: hidden;
        }
        .tinder-card {
            position: absolute;
            top: 1rem;
            left: 5%;
            width: 90%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: transform 0.4s ease, opacity 0.4s ease;
            z-index: 10;
        }
        .tinder-card.swipe-right {
            transform: translateX(100vw) rotate(15deg);
            opacity: 0;
        }
        .tinder-card.swipe-left {
            transform: translateX(-100vw) rotate(-15deg);
            opacity: 0;
        }
        .tinder-card img {
            height: 60vh;
            object-fit: cover;
            position: relative;
        }
        .tinder-card .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
            color: white;
            padding: 1.5rem;
            text-align: center;
            z-index: 20;
            color: ffffffe0;
        }
        .tinder-card .overlay h3 {
            font-size: 1.8rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 0.5rem;
        }
        .tinder-card .overlay p {
            font-size: 1.1rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }
        .tinder-controls {
            position: fixed;
            bottom: 5rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            z-index: 1000;
        }
        .tinder-controls button {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            transition: transform 0.2s ease;
        }
        .tinder-controls button:hover {
            transform: scale(1.1);
        }
        .swipe-left-btn {
            color: #6b7280;
        }
        .swipe-right-btn {
            color: #ff6b6b;
        }
        .filter-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }
        .tinder-filter-section {
            z-index: 50;
            max-height: 80vh;
            overflow-y: auto;
        }
        @media (min-width: 1024px) {
            .tinder-container, .tinder-controls, .filter-toggle {
                display: none;
            }
            .mobile-dashboard-nav {
                display: none;
            }
            .db-nav {
                display: block;
            }
        }
        @media (max-width: 1023px) {
            .profile-grid, .db-nav, .filter-section {
                display: none;
            }
            .tinder-container {
                display: block;
            }
        }
    </style>
</head>
<body>
    <?php include "inc/header.php"; ?>
    <?php include "inc/bodystart.php"; ?>
    <?php include "inc/navbar.php"; ?>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="lg:w-1/4">
                    <!-- Desktop Sidebar -->
                    <div class="db-nav">
                        <div class="db-nav-pro">
                            <img src="upload/<?php echo !empty($loggedInUser['user_img']) ? htmlspecialchars($loggedInUser['user_img']) : 'default-profile.png'; ?>" class="img-fluid" alt="My Profile Image">
                        </div>
                        <div class="db-nav-list">
                            <ul>
                                <li>
                                    <a href="user-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-dashboard.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-tachometer"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a href="user-profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-profile.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-user"></i>My Profile
                                    </a>
                                </li>
                                <li>
                                    <a href="see-other-profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'see-other-profile.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-users"></i>Browse Profiles
                                    </a>
                                </li>
                                <li>
                                    <a href="user-profile-edit.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-profile-edit.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-pencil-square-o"></i>Edit Profile &amp; Photos
                                    </a>
                                </li>
                                <li>
                                    <a href="user-interests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-interests.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-handshake-o"></i>My Interests
                                    </a>
                                </li>
                                <li>
                                    <a href="user-chat.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-chat.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-commenting-o"></i>Chat List
                                    </a>
                                </li>
                                <li>
                                    <a href="plans.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'plans.php' ? 'act' : ''; ?>">
                                        <i class="fa fa-money"></i>Membership Plans
                                    </a>
                                </li>
                                <li>
                                    <a href="logout.php">
                                        <i class="fa fa-sign-out"></i>Log Out
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- Mobile Bottom Bar -->
                    <nav class="mobile-dashboard-nav">
                        <a href="user-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-dashboard.php' ? 'active' : ''; ?>">
                            <i class="fa fa-tachometer"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="see-other-profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'see-other-profile.php' ? 'active' : ''; ?>">
                            <i class="fa fa-users"></i>
                            <span>Browse</span>
                        </a>
                        <a href="user-interests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-interests.php' ? 'active' : ''; ?>">
                            <i class="fa fa-handshake-o"></i>
                            <span>Interests</span>
                        </a>
                        <a href="user-chat.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-chat.php' ? 'active' : ''; ?>">
                            <i class="fa fa-commenting-o"></i>
                            <span>Chats</span>
                        </a>
                        <a href="user-profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user-profile.php' ? 'active' : ''; ?>">
                            <i class="fa fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </nav>
                </div>
                <div class="lg:w-3/4">
                    <!-- Desktop Filter and Grid -->
                    <div class="filter-section p-6 mb-8">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Find Your Match</h2>
                        <form method="GET" action="see-other-profile.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="marital_status">Marital Status</label>
                                <select id="marital_status" name="marital_status" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                    <option value="">All</option>
                                    <option value="Single" <?php if ($f_marital_status === 'Single') echo 'selected'; ?>>Single</option>
                                    <option value="Divorced" <?php if ($f_marital_status === 'Divorced') echo 'selected'; ?>>Divorced</option>
                                    <option value="Widowed" <?php if ($f_marital_status === 'Widowed') echo 'selected'; ?>>Widowed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="religion">Religion</label>
                                <select id="religion" name="religion" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                    <option value="">All</option>
                                    <?php foreach ($religions as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['user_religion']); ?>" <?php if ($f_religion === $item['user_religion']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($item['user_religion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="caste">Caste</label>
                                <select id="caste" name="caste" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                    <option value="">All</option>
                                    <?php foreach ($castes as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['user_namecast']); ?>" <?php if ($f_caste === $item['user_namecast']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($item['user_namecast']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="country">Country</label>
                                <select id="country" name="country" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                    <option value="">All</option>
                                    <?php foreach ($countries as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['user_country']); ?>" <?php if ($f_country === $item['user_country']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($item['user_country']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="state">State</label>
                                <select id="state" name="state" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                    <option value="">All</option>
                                    <?php foreach ($states as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['user_state']); ?>" <?php if ($f_state === $item['user_state']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($item['user_state']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2 flex space-x-4 mt-4">
                                <button type="submit" class="btn-primary px-6 py-2 rounded-lg">Search</button>
                                <a href="see-other-profile.php" class="btn-secondary px-6 py-2 rounded-lg">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                    <div class="mb-6">
                        <p class="text-lg text-gray-700">Found <span class="font-semibold"><?php echo $findsql->num_rows; ?></span> matching profiles</p>
                    </div>
                    <!-- Desktop Profile Grid -->
                    <div class="profile-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if ($findsql->num_rows > 0): ?>
                            <?php foreach ($profiles as $profile): ?>
                                <div class="profile-card bg-white rounded-lg overflow-hidden shadow-md">
                                    <a href="profile-details.php?id=<?php echo $profile['user_id']; ?>" class="block">
                                        <img src="upload/<?php echo !empty($profile['user_img']) ? htmlspecialchars($profile['user_img']) : 'default-profile.png'; ?>" alt="Profile Photo" class="w-full h-48 object-cover">
                                    </a>
                                    <div class="p-4">
                                        <h3 class="text-lg font-semibold text-gray-800"><a href="profile-details.php?id=<?php echo $profile['user_id']; ?>"><?php echo htmlspecialchars($profile['user_name']); ?></a></h3>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p>
                                                <?php
                                                if (!empty($profile['user_dob'])) {
                                                    $age = (new DateTime($profile['user_dob']))->diff(new DateTime('today'))->y;
                                                    echo htmlspecialchars($age) . ' Yrs, ';
                                                }
                                                echo htmlspecialchars($profile['user_height'] ?? '');
                                                ?>
                                            </p>
                                            <p><?php echo htmlspecialchars(($profile['user_city'] ?? '') . ', ' . ($profile['user_state'] ?? '')); ?></p>
                                            <p><?php echo htmlspecialchars($profile['user_jobType'] ?? ''); ?></p>
                                        </div>
                                        <div class="flex space-x-2 mt-4">
                                            <?php
                                            $is_premium = false;
                                            if (!empty($loggedInUser['plan_type']) && $loggedInUser['plan_type'] !== 'Free' && !empty($loggedInUser['plan_expiry_date'])) {
                                                if (new DateTime() <= new DateTime($loggedInUser['plan_expiry_date'])) {
                                                    $is_premium = true;
                                                }
                                            }
                                            $chat_link = $is_premium ? "open-chat.php?receiver_id=" . $profile['user_id'] : "plans.php";
                                            ?>
                                            <a href="<?php echo $chat_link; ?>" class="flex-1">
                                                <span class="cta-chat block text-center py-2 rounded-lg" style="background: #93c5fd;">
                                                    <?php echo $is_premium ? 'Chat Now' : 'Upgrade to Chat'; ?>
                                                </span>
                                            </a>
                                            <?php if (in_array($profile['user_id'], $sent_interest_ids)): ?>
                                                <a href="javascript:void(0);" class="flex-1 pointer-events-none">
                                                    <span class="cta-sent block text-center py-2 rounded-lg">Interest Sent</span>
                                                </a>
                                            <?php else: ?>
                                                <a href="javascript:void(0);" class="send-interest-btn flex-1" data-receiver-id="<?php echo $profile['user_id']; ?>">
                                                    <span class="cta-sendint block text-center py-2 rounded-lg" style="background: #86efac;">Send Interest</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center p-8 bg-white rounded-lg shadow-md">
                                <h3 class="text-xl font-semibold text-gray-800">No profiles found</h3>
                                <p class="text-gray-600">Your search returned no results. Please try adjusting or clearing your filters.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Mobile Tinder-like Interface -->
                    <div class="tinder-container">
                        <button class="filter-toggle btn-primary px-4 py-2 rounded-lg">Filters</button>
                        <div class="tinder-filter-section hidden bg-white p-6 rounded-lg shadow-md absolute top-0 left-0 right-0 z-50">
                            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Find Your Match</h2>
                            <form method="GET" action="see-other-profile.php" class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="mobile_marital_status">Marital Status</label>
                                    <select id="mobile_marital_status" name="marital_status" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                        <option value="">All</option>
                                        <option value="Single" <?php if ($f_marital_status === 'Single') echo 'selected'; ?>>Single</option>
                                        <option value="Divorced" <?php if ($f_marital_status === 'Divorced') echo 'selected'; ?>>Divorced</option>
                                        <option value="Widowed" <?php if ($f_marital_status === 'Widowed') echo 'selected'; ?>>Widowed</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="mobile_religion">Religion</label>
                                    <select id="mobile_religion" name="religion" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                        <option value="">All</option>
                                        <?php foreach ($religions as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item['user_religion']); ?>" <?php if ($f_religion === $item['user_religion']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($item['user_religion']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="mobile_caste">Caste</label>
                                    <select id="mobile_caste" name="caste" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                        <option value="">All</option>
                                        <?php foreach ($castes as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item['user_namecast']); ?>" <?php if ($f_caste === $item['user_namecast']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($item['user_namecast']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="mobile_country">Country</label>
                                    <select id="mobile_country" name="country" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                        <option value="">All</option>
                                        <?php foreach ($countries as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item['user_country']); ?>" <?php if ($f_country === $item['user_country']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($item['user_country']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="mobile_state">State</label>
                                    <select id="mobile_state" name="state" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-ff6b6b">
                                        <option value="">All</option>
                                        <?php foreach ($states as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item['user_state']); ?>" <?php if ($f_state === $item['user_state']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($item['user_state']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex space-x-4 mt-4">
                                    <button type="submit" class="btn-primary px-6 py-2 rounded-lg">Search</button>
                                    <button type="button" class="btn-secondary px-6 py-2 rounded-lg close-filter">Close</button>
                                </div>
                            </form>
                        </div>
                        <div class="tinder-profiles">
                            <?php if ($findsql->num_rows > 0): ?>
                                <?php foreach ($profiles as $index => $profile): ?>
                                    <div class="tinder-card <?php echo $index !== 0 ? 'hidden' : ''; ?>" data-index="<?php echo $index; ?>">
                                        <a href="profile-details.php?id=<?php echo $profile['user_id']; ?>" class="block profile-link">
                                            <img src="upload/<?php echo !empty($profile['user_img']) ? htmlspecialchars($profile['user_img']) : 'default-profile.png'; ?>" alt="Profile Photo" class="w-full">
                                        </a>
                                        <div class="overlay">
                                            <h3><a href="profile-details.php?id=<?php echo $profile['user_id']; ?>" class="profile-link text-white"><?php echo htmlspecialchars($profile['user_name']); ?></a></h3>
                                            <div>
                                                <p style="color:#ffffffe0">
                                                    <?php
                                                    if (!empty($profile['user_dob'])) {
                                                        $age = (new DateTime($profile['user_dob']))->diff(new DateTime('today'))->y;
                                                        echo htmlspecialchars($age) . ' Yrs, ';
                                                    }
                                                    echo htmlspecialchars($profile['user_height'] ?? '');
                                                    ?>
                                                </p>
                                                <p style="color:#ffffffe0"><?php echo htmlspecialchars(($profile['user_city'] ?? '') . ', ' . ($profile['user_state'] ?? '')); ?></p>
                                                <p style="color:#ffffffe0"><?php echo htmlspecialchars($profile['user_jobType'] ?? ''); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center p-8 bg-white rounded-lg shadow-md">
                                    <h3 class="text-xl font-semibold text-gray-800">No profiles found</h3>
                                    <p class="text-gray-600">Your search returned no results. Please try adjusting or clearing your filters.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tinder-controls">
                            <button class="swipe-left-btn"><i class="fas fa-arrow-left"></i></button>
                            <button class="swipe-right-btn"><i class="fas fa-heart"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- jQuery with local fallback -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/jquery-3.6.0.min.js"><\/script>')</script>
    <script>
        $(document).ready(function() {
            // Desktop Send Interest
            $('.profile-grid').on('click', '.send-interest-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var receiverId = button.data('receiver-id');
                button.addClass('pointer-events-none');
                button.find('span').text('Sending...');
                $.ajax({
                    url: 'api/send_interest.php',
                    type: 'POST',
                    data: { receiver_id: receiverId },
                    dataType: 'json',
                    success: function(response) {
                        console.log('AJAX Success:', response);
                        if (response.status === 'success') {
                            button.find('span').text('Interest Sent').removeClass('cta-sendint').addClass('cta-sent');
                            button.addClass('pointer-events-none');
                        } else {
                            alert('Error: ' + response.message);
                            button.removeClass('pointer-events-none');
                            button.find('span').text('Send Interest');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('An unexpected server error occurred.');
                        button.removeClass('pointer-events-none');
                        button.find('span').text('Send Interest');
                    }
                });
            });

            // Mobile Tinder-like Interface
            var profiles = <?php echo json_encode($profiles); ?>;
            var currentIndex = 0;
            var totalProfiles = profiles.length;

            function showProfile(index) {
                $('.tinder-card').addClass('hidden').css({ transform: '', opacity: 1 });
                $('.tinder-card[data-index="' + index + '"]').removeClass('hidden');
                console.log('Showing profile index:', index);
            }

            // Swipe Gestures
            $('.tinder-card').each(function() {
                var card = $(this);
                var hammer = new Hammer(this);
                hammer.get('swipe').set({ direction: Hammer.DIRECTION_HORIZONTAL, threshold: 10 });
                hammer.on('swipeleft', function(e) {
                    console.log('Swipe left on card:', card.data('index'));
                    card.addClass('swipe-left');
                    setTimeout(function() {
                        card.addClass('hidden').removeClass('swipe-left');
                        currentIndex = totalProfiles > 0 ? (currentIndex + 1) % totalProfiles : 0;
                        if (totalProfiles > 0) showProfile(currentIndex);
                    }, 400);
                });
                hammer.on('swiperight', function(e) {
                    console.log('Swipe right on card:', card.data('index'));
                    var receiverId = card.find('.send-interest-btn').data('receiver-id');
                    var button = card.find('.send-interest-btn');
                    if (!button.hasClass('pointer-events-none')) {
                        card.addClass('swipe-right');
                        $.ajax({
                            url: 'api/send_interest.php',
                            type: 'POST',
                            data: { receiver_id: receiverId },
                            dataType: 'json',
                            success: function(response) {
                                console.log('Swipe AJAX Success:', response);
                                if (response.status === 'success') {
                                    setTimeout(function() {
                                        card.addClass('hidden').removeClass('swipe-right');
                                        currentIndex = totalProfiles > 0 ? (currentIndex + 1) % totalProfiles : 0;
                                        if (totalProfiles > 0) showProfile(currentIndex);
                                    }, 400);
                                } else {
                                    alert('Error: ' + response.message);
                                    card.removeClass('swipe-right');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Swipe AJAX Error:', status, error);
                                alert('An unexpected server error occurred.');
                                card.removeClass('swipe-right');
                            }
                        });
                    } else {
                        card.addClass('swipe-right');
                        setTimeout(function() {
                            card.addClass('hidden').removeClass('swipe-right');
                            currentIndex = totalProfiles > 0 ? (currentIndex + 1) % totalProfiles : 0;
                            if (totalProfiles > 0) showProfile(currentIndex);
                        }, 400);
                    }
                });
                // Allow clicks on profile link
                card.find('.profile-link').on('click', function(e) {
                    console.log('Profile link clicked:', $(this).attr('href'));
                    window.location.href = $(this).attr('href');
                });
            });

            // Fallback Buttons
            $('.swipe-left-btn').on('click', function() {
                console.log('Left button clicked');
                var currentCard = $('.tinder-card[data-index="' + currentIndex + '"]');
                currentCard.addClass('swipe-left');
                setTimeout(function() {
                    currentCard.addClass('hidden').removeClass('swipe-left');
                    currentIndex = totalProfiles > 0 ? (currentIndex + 1) % totalProfiles : 0;
                    if (totalProfiles > 0) showProfile(currentIndex);
                }, 400);
            });

            $('.swipe-right-btn').on('click', function() {
                console.log('Right button clicked');
                var currentCard = $('.tinder-card[data-index="' + currentIndex + '"]');
                var receiverId = currentCard.find('.send-interest-btn').data('receiver-id');
                if (!currentCard.find('.send-interest-btn').hasClass('pointer-events-none')) {
                    currentCard.addClass('swipe-right');
                    $.ajax({
                        url: 'api/send_interest.php',
                        type: 'POST',
                        data: { receiver_id: receiverId },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Button AJAX Success:', response);
                            if (response.status === 'success') {
                                setTimeout(function() {
                                    currentCard.addClass('hidden').removeClass('swipe-right');
                                    currentIndex = totalProfiles > 0 ? (currentIndex + 1) % totalProfiles : 0;
                                    if (totalProfiles > 0) showProfile(currentIndex);
                                }, 400);
                            } else {
                                alert('Error: ' + response.message);
                                currentCard.removeClass('swipe-right');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Button AJAX Error:', status, error);
                            alert('An unexpected server error occurred.');
                            currentCard.removeClass('swipe-right');
                        }
                    });
                } else {
                    currentCard.addClass('swipe-right');
                    setTimeout(function() {
                        currentCard.addClass('hidden').removeClass('swipe-right');
                        currentIndex = totalProfiles > 0 ? (currentIndex + 1) % totalProfiles : 0;
                        if (totalProfiles > 0) showProfile(currentIndex);
                    }, 400);
                }
            });

            // Filter Toggle
            $('.filter-toggle').on('click', function() {
                console.log('Filter toggle clicked');
                $('.tinder-filter-section').toggleClass('hidden');
            });
            $('.close-filter').on('click', function() {
                console.log('Close filter clicked');
                $('.tinder-filter-section').addClass('hidden');
            });
        });
    </script>
</body>
</html>

<?php
$filter_stmt->close();
$conn->close();
?>
```