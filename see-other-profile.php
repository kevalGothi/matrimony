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
    $user_id;
    if($userN == true && $psw == true){
        $user = mysqli_query($conn,"select * from tbl_user where user_phone = '$userN' and user_pass = '$psw'");
        $fe = mysqli_fetch_array($user);
        $userID = $fe['user_id'];
        if(isset($_POST['chat_send'])){
            $senderid = $_POST['senderid'];
            $receiverid = $_POST['receiverid'];
            $chat_message = $_POST['chat_message'];
            $sqls=mysqli_query($conn,"INSERT INTO tbl_chat(chat_senderID, chat_receiverID, chat_message) values('$senderid','$receiverid','$chat_message')");
            if($sqls){
                echo "<script>alert('Send Chat')</script>";
                echo "<script>window.location.href='see-other-profile.php'</script>";
            }
        }
?>
        <!-- LOGIN -->
        <section>
            <div class="db">
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
                                            <a href="see-other-profile.php" class="act">
                                                <i class="fa fa-male" aria-hidden="true"></i>See Others Profile
                                            </a>
                                        </li>
                                        <li><a href="user-profile-edit.php"><i class="fa fa-male" aria-hidden="true"></i>Edit Profile</a></li>
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
                                            <a href="plans.php">
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
                            <div class="row">
<?php
    $UserfID = $fe['user_id'];
    $userGender = $fe['user_gender'];

    $findsql = mysqli_query($conn,"SELECT * FROM tbl_user WHERE user_gender != '$userGender' AND user_status = '1'");
    $mro = mysqli_num_rows($findsql);

?>
                        <div class="short-all">
                            <div class="short-lhs">
                                Showing <b><?php echo $mro; ?> </b> profiles
                            </div>
                            <div class="short-rhs">
                                <ul>
                                    <li>
                                        Sort by:
                                    </li>
                                    <li>
                                        <div class="form-group">
                                            <select class="chosen-select">
                                                <option value="">Most relative</option>
                                                <option value="Men">Date listed: Newest</option>
                                                <option value="Men">Date listed: Oldest</option>
                                            </select>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="sort-grid sort-grid-1">
                                            <i class="fa fa-th-large" aria-hidden="true"></i>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="sort-grid sort-grid-2 act">
                                            <i class="fa fa-bars" aria-hidden="true"></i>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="all-list-sh">
                            <ul>
<?php
    $ct = 1;
    if(true){
    foreach($findsql as $finddata){ ?>
<li>
                                    <div class="all-pro-box " data-useravil=""
                                        data-aviltxt=" ">
                                        <!--PROFILE IMAGE-->
                                        <div class="pro-img">
                                            <a href="#">
                                                <img src="upload/<?php echo $finddata['user_img'] ?>" alt="">
                                            </a>
                                        </div>
                                        <!--END PROFILE IMAGE-->

                                        <!--PROFILE NAME-->
                                        <div class="pro-detail">
                                            <h4><a href="#"><?php echo $finddata['user_name'] ?></a></h4>
                                            <div class="pro-bio">
                                                <span><?php echo $finddata['user_jobType'] ?></span>
                                                <span><?php echo $finddata['user_currentResident'] ?></span>
                                                <span><?php echo $finddata['user_age'] ?> Yeard old</span>
                                                <span>Height: <?php echo $finddata['user_height'] ?></span>
                                            </div>
<!-- Find and replace the entire <div class="links">...</div> block -->
                                        <div class="links">
    <?php
        // Check if the logged-in user has an active premium plan
        $user_id = $finddata['user_id'];
        $is_premium = false;
        if (!empty($fe['plan_type']) && !empty($fe['plan_expiry_date'])) {
            $today = new DateTime();
            $expiry = new DateTime($fe['plan_expiry_date']);
            if ($expiry >= $today) {
                $is_premium = true;
            }
        }

        // Display links based on premium status
        if ($is_premium) {
            // PREMIUM USER: Show functional links
    ?>
        <a href="open-chat.php?id=<?php echo $finddata['user_id']; ?>"><span class="cta-chat">Chat now</span></a>
        <a href="user-interests.php?send_interest=<?php echo $finddata['user_id']; ?>" onclick="return confirm('Send interest to <?php echo htmlspecialchars(addslashes($finddata['user_name'])); ?>?');"><span class="cta cta-sendint">Send interest</span></a>
        <a href="profile-details.php?id=<?php echo $finddata['user_id']; ?>&view_contact=true">View Contact</a>
    <?php
        } else {
            // FREE USER: Links redirect to the plans page
    ?>
        <a href="plans.php?id=<?php echo $finddata['user_id']; ?>" title="Upgrade to chat"><span class="cta-chat">Chat now</span></a>
        <a href="plans.php?id=<?php echo $finddata['user_id']; ?>" title="Upgrade to send interest"><span class="cta cta-sendint">Send interest</span></a>
        <a href="plans.php?id=<?php echo $finddata['user_id']; ?>" title="Upgrade to view contact">View Contact</a>
    <?php
        }
    ?>
    <a href="profile-details.php?id=<?php echo $finddata['user_id']; ?>">More details</a>
</div>
                                        </div>
                                        <!--END PROFILE NAME-->
                                        <!--SAVE-->
                                        <span class="enq-sav" data-toggle="tooltip"
                                            title="Click to save this provile."><i class="fa fa-thumbs-o-up" aria-hidden="true"></i></span>
                                        <!--END SAVE-->
                                    </div>
                                </li>

                                <!-- INTEREST POPUP -->
    <div class="modal fade" id="sendInter">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title seninter-tit">Send interest to <span class="intename2">Jolia</span></h4>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body seninter">
                    <div class="lhs">
                        <img src="images/profiles/1.jpg" alt="" class="intephoto2">
                    </div>
                    <div class="rhs">
                        <h4>Permissions: <span class="intename2">Jolia</span> Can able to view the below details</h4>
                        <ul>
                            <li>
                                <div class="chbox">
                                    <input type="checkbox" id="pro_about" checked="">
                                    <label for="pro_about">About section</label>
                                </div>
                            </li>
                            <li>
                                <div class="chbox">
                                    <input type="checkbox" id="pro_photo">
                                    <label for="pro_photo">Photo gallery</label>
                                </div>
                            </li>
                            <li>
                                <div class="chbox">
                                    <input type="checkbox" id="pro_contact">
                                    <label for="pro_contact">Contact info</label>
                                </div>
                            </li>
                            <li>
                                <div class="chbox">
                                    <input type="checkbox" id="pro_person">
                                    <label for="pro_person">Personal info</label>
                                </div>
                            </li>
                            <li>
                                <div class="chbox">
                                    <input type="checkbox" id="pro_hobbi">
                                    <label for="pro_hobbi">Hobbies</label>
                                </div>
                            </li>
                            <li>
                                <div class="chbox">
                                    <input type="checkbox" id="pro_social">
                                    <label for="pro_social">Social media</label>
                                </div>
                            </li>
                        </ul>
                        <div class="form-floating">
                            <textarea class="form-control" id="comment" name="text"
                                placeholder="Comment goes here"></textarea>
                            <label for="comment">Write some message to <span class="intename"></span></label>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary">Send interest</button>
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancel</button>
                </div>

            </div>
        </div>
    </div>
    <!-- END INTEREST POPUP -->


    <!-- END -->
<?php    } }else{
    echo "<script>alert('Your Account Not Verified By Admin')</script>";
    ?>
     
    <?php
    // echo "<h3>Your Account Not Verified !!! You Don't See Others Profile</h3>";
}
?>


                            </ul>
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
                    <h2>
                        <span>Free support:</span> +92 (8800) 68 - 8960 &nbsp;&nbsp;|&nbsp;&nbsp;
                        <span>Email:</span>
                        info@example.com
                    </h2>
                </div>
                <div class="row wed-foot-link wed-foot-link-1">
                    <div class="col-md-4">
                        <h4>Get In Touch</h4>
                        <p>Address: 3812 Lena Lane City Jackson Mississippi</p>
                        <p>Phone:
                            <a href="tel:+917904462944">+92 (8800) 68 - 8960</a>
                        </p>
                        <p>Email:
                            <a href="mailto:info@example.com">info@example.com</a>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h4>HELP &amp; SUPPORT</h4>
                        <ul>
                            <li>
                                <a href="about-us.html">About company</a>
                            </li>
                            <li>
                                <a href="#!">Contact us</a>
                            </li>
                            <li>
                                <a href="#!">Feedback</a>
                            </li>
                            <li>
                                <a href="about-us.html#faq">FAQs</a>
                            </li>
                            <li>
                                <a href="about-us.html#testimonials">Testimonials</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4 fot-soc">
                        <h4>SOCIAL MEDIA</h4>
                        <ul>
                            <li>
                                <a href="#!">
                                    <img src="images/social/1.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#!">
                                    <img src="images/social/2.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#!">
                                    <img src="images/social/3.png" alt="">
                                </a>
                            </li>
                            <li>
                                <a href="#!">
                                    <img src="images/social/5.png" alt="">
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row foot-count">
                    <p>Company name Site - Trusted by over thousands of Boys & Girls for successfull marriage.
                        <a href="sign-up.html" class="btn btn-primary btn-sm">Join us today !</a>
                    </p>
                </div>
            </div>
        </section>
        <!-- END -->
        <!-- COPYRIGHTS -->
        <section>
            <div class="cr">
                <div class="container">
                    <div class="row">
                        <p>Copyright ©
                            <span id="cry">2017-2020</span>
                            <a href="#!" target="_blank">Company.com</a>
                            All
                        rights reserved.
                        </p>
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
        <script src="js/Chart.js"></script>
        <script src="js/custom.js"></script>
        <script>
        

var xValues = "0";
    var yValues = "50";

    new Chart("Chart_leads", {
        type: "line",
        data: {
            labels: xValues,
            datasets: [{
                fill: false,
                lineTension: 0,
                backgroundColor: "#f1bb51",
                borderColor: "#fae9c8",
                data: yValues
            }]
        },
        options: {
            responsive: true,
            legend: {display: false},
            scales: {
                yAxes: [{ticks: {min: 0, max: 100}}]
            }
        }
    });
        </script>
    </body>
</html>
