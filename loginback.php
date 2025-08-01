<?php
session_start();
    include "db/conn.php";
    
    if(isset($_POST['signin'])){
        $email = $_POST['email'];
        $pswd = $_POST['pswd'];
        
        $sql = mysqli_query($conn,"select * from tbl_user where user_phone = '$email' and user_pass = '$pswd'");
        $fetch = mysqli_fetch_array($sql);
        
        if(mysqli_num_rows($sql) > 0){
            
                $_SESSION['username'] = $email;
                $_SESSION['password'] = $pswd;
                echo "<script>window.location.href='user-dashboard.php'</script>";
            
        }else{
            echo "<script>alert('Not User Found !!!')</script>";
        }
    }
?>