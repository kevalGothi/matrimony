<?php
    session_start();
    include "../db/conn.php";
    $username = $_SESSION['username'];
    $pass = $_SESSION['pass'];
    $adminsql = mysqli_query($conn, "select * from tbl_admin where ad_email = '$username' and ad_pass = '$pass'");
    $adminfetch = mysqli_fetch_array($adminsql);
    if($username == true & $pass == true)
    {
?>

<?php include "inc/header.php"; ?>    
    <!-- ?PROD Only: Google Tag Manager (noscript) (Default ThemeSelection: GTM-5DDHKGP, PixInvent: GTM-5J3LMKC) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5J3LMKC" height="0" width="0" style="display: none; visibility: hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar  ">
  <div class="layout-container">

    <?php include "inc/side_bar.php"; ?>

    <!-- Layout container -->
    <div class="layout-page">

<?php include "inc/top_bar.php"; ?>
      <!-- Content wrapper -->
<div class="content-wrapper">

        <!-- Content -->
<style>
    ::placeholder {
  color: red;
  opacity: 1; /* Firefox */
}

::-ms-input-placeholder { /* Edge 12 -18 */
  color: red;
}
</style>        
        <div class="container-xxl flex-grow-1 container-p-y">
<center>
<form method="POST">
        <input type="submit" class="btn btn-primary" name="yes" value="YES" style="color:#000;">
    <input type="submit" class="btn btn-danger" name="no" value="NO"  style="color:#000;">
</form>
</center>
<?php
$IDD = $_GET['id'];
if(isset($_POST['yes'])){
    $sql = mysqli_query($conn,"UPDATE tbl_user SET user_status = '1' WHERE user_id = '$IDD'");
    if($sql){
        echo "<script>alert('Successfully Delete')</script>";
    }
}elseif(isset($_POST['no'])){
    echo "<script>window.location.href='index.php';</script>";
}else{
    
}
?>
</div>
                      <!-- / Content -->

<?php include "inc/footer.php"; ?>

<?php
    }else{
        echo "<script>window.location.href='login/';</script>";
    }
?>