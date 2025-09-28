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

// Fetch sent interests to determine the initial button state
$sent_interests_stmt = $conn->prepare("SELECT chat_receiverID, chat_id FROM tbl_chat WHERE chat_senderID = ? AND interest_status != 9"); // Also fetch chat_id for cancellation
$sent_interests_stmt->bind_param("i", $loggedInUserID);
$sent_interests_stmt->execute();
$sent_interests_result = $sent_interests_stmt->get_result();
$sent_interests_map = [];
while ($row = $sent_interests_result->fetch_assoc()) {
    // Map receiver ID to chat ID
    $sent_interests_map[$row['chat_receiverID']] = $row['chat_id'];
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

// Use call_user_func_array for binding parameters
if (count($params) > 1) {
    $filter_stmt->bind_param($types, ...$params);
} else {
    $filter_stmt->bind_param($types, $params[0]);
}

$filter_stmt->execute();
$findsql = $filter_stmt->get_result();
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
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
        }
        .profile-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .profile-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); }
        .filter-section { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .btn-primary { background: #ff6b6b; color: white; transition: background 0.3s ease; }
        .btn-primary:hover { background: #ff4d4d; }
        .btn-secondary { background: #6b7280; color: white; transition: background 0.3s ease; }
        .btn-secondary:hover { background: #5b606d; }
        .cta-sent { background: #ef4444 !important; color: white !important; } /* Changed to red for Cancel */
        .cta-chat, .cta-sendint, .cta-sent { transition: background 0.3s ease; }
        .cta-chat:hover { background: #60a5fa !important; }
        .cta-sendint:hover { background: #65a30d !important; }
        .cta-sent:hover { background: #dc2626 !important; } /* Darker red on hover */
        img { display: block; max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <?php include "inc/header.php"; ?>
    <?php include "inc/bodystart.php"; ?>
    <?php include "inc/navbar.php"; ?>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8" style="width: fit-content;">
                <div class="lg:w-1/4">
                    <?php include "inc/dashboard_nav.php"; ?>
                </div>
                <div class="lg:w-3/4">
                    <div class="filter-section p-6 mb-8">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Find Your Match</h2>
                        <form method="GET" action="see-other-profile.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Your filter inputs are correct and remain unchanged -->
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
                    <div class="profile-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if ($findsql->num_rows > 0): ?>
                            <?php while ($profile = $findsql->fetch_assoc()): ?>
                                <div class="profile-card bg-white rounded-lg overflow-hidden shadow-md">
                                    <a href="profile-details.php?id=<?php echo $profile['user_id']; ?>">
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
                                                echo htmlspecialchars($profile['user_height']);
                                                ?>
                                            </p>
                                            <p><?php echo htmlspecialchars($profile['user_city'] . ', ' . $profile['user_state']); ?></p>
                                            <p><?php echo htmlspecialchars($profile['user_jobType']); ?></p>
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
                                            
                                            <!-- *** MODIFIED INTEREST BUTTON LOGIC *** -->
                                            <?php if (array_key_exists($profile['user_id'], $sent_interests_map)): ?>
                                                <!-- If interest is already sent, show the Cancel button -->
                                                <a href="javascript:void(0);" class="cancel-interest-btn flex-1" data-chat-id="<?php echo $sent_interests_map[$profile['user_id']]; ?>" data-receiver-id="<?php echo $profile['user_id']; ?>">
                                                    <span class="cta-sent block text-center py-2 rounded-lg">Cancel Interest</span>
                                                </a>
                                            <?php else: ?>
                                                <!-- Otherwise, show the Send Interest button -->
                                                <a href="javascript:void(0);" class="send-interest-btn flex-1" data-receiver-id="<?php echo $profile['user_id']; ?>">
                                                    <span class="cta-sendint block text-center py-2 rounded-lg" style="background: #86efac;">Send Interest</span>
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center p-8 bg-white rounded-lg shadow-md">
                                <h3 class="text-xl font-semibold text-gray-800">No profiles found</h3>
                                <p class="text-gray-600">Your search returned no results. Please try adjusting or clearing your filters.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- jQuery with local fallback -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/jquery-3.6.0.min.js"><\/script>')</script>
    
    <!-- *** UPDATED JAVASCRIPT *** -->
    <script>
        $(document).ready(function() {
            // Use event delegation to handle clicks on dynamically added/changed buttons
            var profileGrid = $('.profile-grid');

            // --- 1. HANDLE SENDING AN INTEREST ---
            profileGrid.on('click', '.send-interest-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var receiverId = button.data('receiver-id');

                button.find('span').text('Sending...');
                button.addClass('pointer-events-none');

                $.ajax({
                    url: 'api/send_interest.php',
                    type: 'POST',
                    data: { receiver_id: receiverId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // On success, transform the button to a "Cancel" button
                            var newButton = `
                                <a href="javascript:void(0);" class="cancel-interest-btn flex-1" data-chat-id="${response.chat_id}" data-receiver-id="${receiverId}">
                                    <span class="cta-sent block text-center py-2 rounded-lg">Cancel Interest</span>
                                </a>`;
                            button.replaceWith(newButton);
                        } else {
                            alert('Error: ' + response.message);
                            button.find('span').text('Send Interest');
                            button.removeClass('pointer-events-none');
                        }
                    },
                    error: function() {
                        alert('An unexpected server error occurred.');
                        button.find('span').text('Send Interest');
                        button.removeClass('pointer-events-none');
                    }
                });
            });

            // --- 2. HANDLE CANCELING AN INTEREST ---
            profileGrid.on('click', '.cancel-interest-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var chatId = button.data('chat-id');
                var receiverId = button.data('receiver-id'); // Get receiver ID for rebuilding the button

                button.find('span').text('Canceling...');
                button.addClass('pointer-events-none');

                $.ajax({
                    // Use the same API endpoint from your interest.php page
                    url: 'api/cancel_interest.php', 
                    type: 'POST',
                    data: { chat_id: chatId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                             // On success, transform the button back to a "Send Interest" button
                            var newButton = `
                                <a href="javascript:void(0);" class="send-interest-btn flex-1" data-receiver-id="${receiverId}">
                                    <span class="cta-sendint block text-center py-2 rounded-lg" style="background: #86efac;">Send Interest</span>
                                </a>`;
                            button.replaceWith(newButton);
                        } else {
                            alert('Error: ' + response.message);
                            button.find('span').text('Cancel Interest');
                            button.removeClass('pointer-events-none');
                        }
                    },
                    error: function() {
                        alert('An unexpected server error occurred.');
                        button.find('span').text('Cancel Interest');
                        button.removeClass('pointer-events-none');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
$filter_stmt->close();
$conn->close();
?>