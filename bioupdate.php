<?PHP
    include "db/conn.php";
    $id = $_GET['id'];
    $genid = $_GET['genid'];

    if(isset($_POST['submit'])){
        $name = $_POST['name'];
        $cast = $_POST['cast'];
        $intercast = $_POST['intercast'];
        $disability = $_POST['disability'];
        $maritalstatus = $_POST['maritalstatus'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $pswd = $_POST['pswd'];
        $user_gender = $_POST['user_gender'];
        $user_city = $_POST['user_city'];
        $user_dob = $_POST['user_dob'];
        $user_age = $_POST['user_age'];
        $user_height = $_POST['user_height'];
        $user_weight = $_POST['user_weight'];
        $user_fatherName = $_POST['user_fatherName'];
        $user_motherName = $_POST['user_motherName'];
        $user_address = $_POST['user_address'];
        $whoyoustaywith = $_POST['whoyoustaywith'];
        $whereyoubelong = $_POST['whereyoubelong'];
        $user_jobType = $_POST['user_jobType'];
        $user_companyName = $_POST['user_companyName'];
        $user_salary = $_POST['user_salary'];
        $user_currentResident = $_POST['user_currentResident'];
        $user_degree = $_POST['user_degree'];
        $user_school = $_POST['user_school'];
        $user_collage = $_POST['user_collage'];
        $user_hobbies = $_POST['user_hobbies'];

        $user_img = $_FILES['user_img']['name'];
        $user_imgtmp = $_FILES['user_img']['tmp_name'];

        $uplod = "upload/";
        move_uploaded_file($user_imgtmp , $uplod.$user_img);

        $sql = mysqli_query($conn,"UPDATE tbl_user SET user_name = '$name' , user_namecast = '$cast' , user_nameintercast = '$intercast' , user_gender = '$user_gender' , user_age = '$user_age' , user_phone = '$phone' , user_email = '$email' , user_pass = '$pswd' , user_status = '0' , user_city = '$user_city' , user_dob = '$user_dob' , user_height = '$user_height' , user_weight = '$user_weight' , user_fatherName = '$user_fatherName' , user_motherName = '$user_motherName' , user_address = ' $user_address' , user_jobType = '$user_jobType' , user_companyName = '$user_companyName' , user_currentResident = '$user_currentResident' , user_salary = '$user_salary' , user_degree = '$user_degree' , user_school = '$user_school' , user_collage = '$user_collage' , user_hobbies = '$user_hobbies' , user_img = '$user_img' , user_disability = '$disability' , user_maritalstatus = '$maritalstatus' , user_whoyoustaywith = '$whoyoustaywith' , user_whereyoubelong = '$whereyoubelong' WHERE user_id = '$id'");

       if($sql){
            echo "<script>alert('Your profile has been submitted for admin approval!')</script>";
            echo "<script>window.location.href='pending-approval.php'</script>";
        }
    }
?>