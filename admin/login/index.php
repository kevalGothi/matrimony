<?php
session_start();

// If admin is already logged in, redirect them to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: ../index.php"); // Go up one level to the main admin folder
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Path goes up two levels to reach the main project directory
    include "../../db/conn.php";
    
    $username = $_POST['username'];
    $pass = $_POST['pass'];

    // --- DATABASE QUERY (WITHOUT HASHING) ---
    $stmt = $conn->prepare("SELECT * FROM tbl_admin WHERE ad_email = ? AND ad_pass = ?");
    $stmt->bind_param("ss", $username, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // --- SUCCESSFUL LOGIN ---
        $fetch_admin = $result->fetch_assoc();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store admin information in the session
        $_SESSION['admin_id'] = $fetch_admin['ad_id'];
        $_SESSION['admin_name'] = $fetch_admin['ad_name'];
        $_SESSION['username'] = $fetch_admin['ad_email'];
        $_SESSION['password'] = $fetch_admin['ad_pass']; // Standardized to 'password'

        // Redirect to the admin dashboard (up one level)
        header("Location: ../index.php");
        exit(); 
    } else {
        // --- FAILED LOGIN ---
        echo "<script>
                alert('Invalid credentials. Please try again.'); 
                window.history.back();
              </script>";
        exit();
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin Login Panel</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
</head>
<body>
	
	<div class="limiter">
		<div class="container-login100" style="background-image: url('images/bg-01.jpg');">
			<div class="wrap-login100">
				<!-- Form submits to itself -->
				<form class="login100-form validate-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
					<span class="login100-form-logo">
						<!-- Adjust image path if necessary -->
						<!--<img src="../../user/assets/logo1.jpg" alt="Logo" style="border-radius: 50%;">-->
					</span>

					<span class="login100-form-title p-b-34 p-t-27">
						Admin Log in
					</span>

					<div class="wrap-input100 validate-input" data-validate = "Enter username">
						<input class="input100" type="text" name="username" placeholder="Username">
						<span class="focus-input100" data-placeholder=""></span>
					</div>

					<div class="wrap-input100 validate-input" data-validate="Enter password">
						<input class="input100" type="password" name="pass" placeholder="Password">
						<span class="focus-input100" data-placeholder=""></span>
					</div>

					<div class="container-login100-form-btn">
						<button class="login100-form-btn">
							Login
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<script src="vendor/animsition/js/animsition.min.js"></script>
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<script src="js/main.js"></script>

</body>
</html>