<?php
session_start();
include "db/conn.php";

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$payment_type = isset($_GET['type']) ? $_GET['type'] : '';

if ($user_id > 0) {
    if ($payment_type === 'registration') {
        $stmt = $conn->prepare("UPDATE tbl_user SET user_payment_status = '1' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $stmt_login = $conn->prepare("SELECT user_phone, user_pass FROM tbl_user WHERE user_id = ?");
            $stmt_login->bind_param("i", $user_id);
            $stmt_login->execute();
            $user_res = $stmt_login->get_result();
            if($user_data = $user_res->fetch_assoc()) {
                $_SESSION['username'] = $user_data['user_phone'];
                $_SESSION['password'] = $user_data['user_pass'];
            }
            echo "<script>window.location.href = 'see-other-profile.php';</script>";
            exit();
        }
    } elseif ($payment_type === 'plan') {
        $plan = isset($_GET['plan']) ? $_GET['plan'] : '';
        $interval = '';
        if ($plan === '3_months') $interval = '+3 months';
        if ($plan === '6_months') $interval = '+6 months';
        if ($plan === '12_months') $interval = '+12 months';

        if (!empty($interval)) {
            $expiry_date = date('Y-m-d', strtotime($interval));
            $stmt = $conn->prepare("UPDATE tbl_user SET plan_type = ?, plan_expiry_date = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $plan, $expiry_date, $user_id);
            if ($stmt->execute()) {
                echo "<script>window.location.href = 'user-dashboard.php';</script>";
                exit();
            }
        }
    }
}
die("An error occurred. Please contact support.");
?>