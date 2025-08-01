<?php
    session_start();
    include "db/conn.php";
    error_reporting(0);
?>
<?php 
    include "inc/header.php";
?>
<?php 
    include "inc/bodystart.php";
?>
<?php 
    include "inc/navbar.php";
?>
<?php
    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];
    if($userN == true && $psw == true){
        $user = mysqli_query($conn,"select * from tbl_user where user_phone = '$userN' and user_pass = '$psw'");
        $fe = mysqli_fetch_array($user);
        $id = $fe['user_id'];
        $genid = $fe['user_gen_id'];
?>
    <!-- REGISTER -->
    <section>
        <div class="login pro-edit-update">
            <div class="container">
                <div class="row">
                                            <div class="col-md-4 col-lg-3">
                            <div class="db-nav">
                                <div class="db-nav-pro">
                                    <img src="images/profiles/12.jpg" class="img-fluid" alt="">
                                </div>
                                <div class="db-nav-list">
                                    <ul>
                                        <li>
                                            <a href="user-dashboard.php">
                                                <i class="fa fa-tachometer" aria-hidden="true"></i>Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a href="user-profile.php" class="">
                                                <i class="fa fa-male" aria-hidden="true"></i>Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a href="see-other-profile.php" class="">
                                                <i class="fa fa-male" aria-hidden="true"></i>See Others Profile
                                            </a>
                                        </li>
                                        <li><a href="user-profile-edit.php" class="act"><i class="fa fa-male" aria-hidden="true"></i>Edit Profile</a></li>
                                        <li>
                                            <a href="user-interests.php">
                                                <i class="fa fa-handshake-o" aria-hidden="true"></i>Interests
                                            </a>
                                        </li>
                                        <li>
                                            <a href="user-chat.php">
                                                <i class="fa fa-commenting-o" aria-hidden="true"></i>Chat list
                                            </a>
                                        </li>
                                        <li>
                                            <a href="user-plan.php">
                                                <i class="fa fa-money" aria-hidden="true"></i>Plan
                                            </a>
                                        </li>
                                        <li>
                                            <a href="user-setting.php">
                                                <i class="fa fa-cog" aria-hidden="true"></i>Setting
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#">
                                                <i class="fa fa-sign-out" aria-hidden="true"></i>Log out
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <div class="col-md-8 col-lg-9">
                    <div class="inn">
                        <div class="rhs">
                                <div class="form-login">
                                    <form action="bioupdate.php?id=<?php echo $id ?>&genid=<?php echo $genid ?>" method="POST" enctype="multipart/form-data">
                                        <!--PROFILE BIO-->
                                        <div class="edit-pro-parti">
                                            <div class="form-tit">
                                                <h4>Basic info</h4>
                                                <h1>Edit my profile</h1>
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Name:</label>
                                                <input type="text" class="form-control" placeholder="Enter your full name"
                                                    name="name" value="<?php echo $fe['user_name'] ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="lb">Cast:</label>
                                                <input type="text" class="form-control" placeholder="Enter your Cast"
                                                    name="cast" value="<?php echo $fe['user_namecast'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Inter Cast:</label>
                                                <input type="text" class="form-control" placeholder="Enter your Intercast"
                                                    name="intercast" value="<?php echo $fe['user_nameintercast'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Disability Status:</label>
                                                <select type="text" class="form-control"
                                                    name="disability" value="<?php echo $fe['user_disability'] ?>">
                                                    <optin disabled selected>Select</optin>
                                                    <option value="YES">YES</option>
                                                    <option value="NO">NO</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="lb">Marital Status:</label>
                                                <select type="text" class="form-control"
                                                    name="maritalstatus" value="<?php echo $fe['user_maritalstatus'] ?>">
                                                    <optin disabled selected>Select</optin>
                                                    <option value="Single">Single</option>
                                                    <option value="Married">Married</option>
                                                    <option value="Divorced">Divorced</option>
                                                    <option value="Way To Divorced">Way To Divorced</option>
                                                    <option value="Widowed">Widowed</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Photo:</label>
                                                <input type="file" class="form-control" name="user_img" value="<?php echo $fe['user_img'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Email:</label>
                                                <input type="email" class="form-control" id="email"
                                                    placeholder="Enter email" name="email" value="<?php echo $fe['user_email'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Phone:</label>
                                                <input type="text" class="form-control" id="phone"
                                                    placeholder="Enter phone number" name="phone" value="<?php echo $fe['user_phone'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Password:</label>
                                                <input type="text" class="form-control" id="pwd"
                                                    placeholder="Enter password" name="pswd"  value="<?php echo $fe['user_pass'] ?>">
                                            </div>
                                        </div>
                                        <!--END PROFILE BIO-->
                                        <!--PROFILE BIO-->
                                        <div class="edit-pro-parti">
                                            <div class="form-tit">
                                                <h4>Basic info</h4>
                                                <h1>Advanced bio</h1>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Gender:</label>
                                                    <select class="form-select chosen-select" name="user_gender" value="<?php echo $fe['user_gender'] ?>" data-placeholder="Select your Gender">
                                                        <option value="<?php echo $fe['user_gender'] ?>" selected><?php echo $fe['user_gender'] ?></option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                      </select>
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">City:</label>
                                                    <select class="form-select chosen-select" name="user_city" data-placeholder="Select your City" value="<?php echo $fe['user_city'] ?>">
                                                        <option value="<?php echo $fe['user_city'] ?>" selected><?php echo $fe['user_city'] ?></option>
                                                        <option value="Chennai">Chennai</option>
                                                        <option value="Newyork">Newyork</option>
                                                        <option value="London">London</option>
                                                        <option value="Chicago">Chicago</option>
                                                      </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Date of birth:</label>
                                                    <input type="text" class="form-control" name="user_dob" value="<?php echo $fe['user_dob'] ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Age:</label>
                                                    <input type="number" class="form-control" name="user_age" value="<?php echo $fe['user_age'] ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Height:</label>
                                                    <input type="text" class="form-control" name="user_height" value="<?php echo $fe['user_height'] ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Weight:</label>
                                                    <input type="text" class="form-control" name="user_weight" value="<?php echo $fe['user_weight'] ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Fathers name:</label>
                                                    <input type="text" class="form-control" name="user_fatherName" value="<?php echo $fe['user_fatherName'] ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Mothers name:</label>
                                                    <input type="text" name="user_motherName" class="form-control" value="<?php echo $fe['user_motherName'] ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Address:</label>
                                                <input type="text" name="user_address" class="form-control" value="<?php echo $fe['user_address'] ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="lb">Who You Stay With:"</label>
                                                <select type="text" class="form-control"
                                                    name="whoyoustaywith" value="<?php echo $fe['user_whoyoustaywith'] ?>">
                                                    <optin disabled selected>Select</optin>
                                                    <option value="Rent">Rent</option>
                                                    <option value="Personal">Personal</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="lb">Where You Belong:</label>
                                                <input type="text" name="whereyoubelong" class="form-control" value="<?php echo $fe['user_whereyoubelong'] ?>">
                                            </div>
                                        </div>
                                        <!--END PROFILE BIO-->
                                        <!--PROFILE BIO-->
                                        <div class="edit-pro-parti">
                                            <div class="form-tit">
                                                <h4>Job details</h4>
                                                <h1>Job & Education</h1>
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Job type:</label>
                                                <select class="form-select chosen-select" value="<?php echo $fe['user_jobType'] ?>" data-placeholder="Select your Hobbies" name="user_jobType">
                                                    <option>Business</option>
                                                    <option>Employee</option>
                                                    <option>Government</option>
                                                    <option>Jobless</option>
                                                  </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="lb">Company name:</label>
                                                <input type="text" class="form-control" name="user_companyName" value="<?php echo $fe['user_companyName'] ?>">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Salary:</label>
                                                    <input type="text" class="form-control" name="user_salary" value="<?php echo $fe['user_salary'] ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">Current Resident:</label>
                                                    <input type="text" name="user_currentResident" class="form-control" value="<?php echo $fe['user_currentResident'] ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="lb">Degree:</label>
                                                <input type="text" class="form-control" name="user_degree" value="<?php echo $fe['user_degree'] ?>">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">School:</label>
                                                    <input type="text" class="form-control" name="user_school" value="<?php echo $fe['user_school'] ?>">
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label class="lb">College:</label>
                                                    <input type="text" class="form-control" name="user_collage" value="<?php echo $fe['user_collage'] ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <!--END PROFILE BIO-->
                                        <!--PROFILE BIO-->
                                        <div class="edit-pro-parti">
                                            <div class="form-tit">
                                                <h4>interests</h4>
                                                <h1>Hobbies</h1>
                                            </div>
                                            <div class="chosenini">
                                                <div class="form-group">
                                                    <select class="chosen-select" name="user_hobbies" data-placeholder="Select your Hobbies" value="<?php echo $fe['user_hobbies'] ?>">
                                                        <option></option>
                                                        <option>Modelling </option>
                                                        <option>Watching </option>
                                                        <option>movies </option>
                                                        <option>Playing </option>
                                                        <option>volleyball</option> 
                                                        <option>Hangout with family </option>
                                                        <option>Adventure travel </option>
                                                        <option>Books reading </option>
                                                        <option>Music </option>
                                                        <option>Cooking </option>
                                                        <option>Yoga</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <!--END PROFILE BIO-->
                                        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END -->
<?php
    }else{
        echo "<script>alert('Please Login Now')</script>";
        echo "<script>window.location.href='login.html'</script>";
    }
?>
    <!-- FOOTER -->
    <section class="wed-hom-footer">
        <div class="container">
            <div class="row foot-supp">
                <h2><span>Free support:</span> +92 (8800) 68 - 8960 &nbsp;&nbsp;|&nbsp;&nbsp; <span>Email:</span>
                    info@example.com</h2>
            </div>
            <div class="row wed-foot-link wed-foot-link-1">
                <div class="col-md-4">
                    <h4>Get In Touch</h4>
                    <p>Address: 3812 Lena Lane City Jackson Mississippi</p>
                    <p>Phone: <a href="tel:+917904462944">+92 (8800) 68 - 8960</a></p>
                    <p>Email: <a href="mailto:info@example.com">info@example.com</a></p>
                </div>
                <div class="col-md-4">
                    <h4>HELP &amp; SUPPORT</h4>
                    <ul>
                        <li><a href="about-us.html">About company</a>
                        </li>
                        <li><a href="#!">Contact us</a>
                        </li>
                        <li><a href="#!">Feedback</a>
                        </li>
                        <li><a href="about-us.html#faq">FAQs</a>
                        </li>
                        <li><a href="about-us.html#testimonials">Testimonials</a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4 fot-soc">
                    <h4>SOCIAL MEDIA</h4>
                    <ul>
                        <li><a href="#!"><img src="images/social/1.png" alt=""></a></li>
                        <li><a href="#!"><img src="images/social/2.png" alt=""></a></li>
                        <li><a href="#!"><img src="images/social/3.png" alt=""></a></li>
                        <li><a href="#!"><img src="images/social/5.png" alt=""></a></li>
                    </ul>
                </div>
            </div>
            <div class="row foot-count">
                <p>Company name Site - Trusted by over thousands of Boys & Girls for successfull marriage. <a
                        href="sign-up.html" class="btn btn-primary btn-sm">Join us today !</a></p>
            </div>
        </div>
    </section>
    <!-- END -->
    <!-- COPYRIGHTS -->
    <section>
        <div class="cr">
            <div class="container">
                <div class="row">
                    <p>Copyright © <span id="cry">2017-2020</span> <a href="#!" target="_blank">Company.com</a> All
                        rights reserved.</p>
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
    <script src="js/custom.js"></script>
</body>
</html>