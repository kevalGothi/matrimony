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
                                            <a href="user-profile.php" class="act">
                                                <i class="fa fa-male" aria-hidden="true"></i>Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a href="see-other-profile.php" class="">
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
                                <div class="col-md-12 col-lg-6 col-xl-8 db-sec-com">
                                    <h2 class="db-tit">Profiles status</h2>
                                    <div class="db-profile">
                                        <div class="img">
                                            <img src="images/profiles/12.jpg" loading="lazy" alt="">
                                        </div>
                                        <div class="edit">
                                            <a href="user-profile-edit.php" class="cta-dark" target="_blank">Edit profile</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-lg-6 col-xl-4 db-sec-com">
                                    <h2 class="db-tit">Profiles status</h2>
                                    <div class="db-pro-stat">
                                        <h6>Profile completion</h6>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-h" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#">Edid profile</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#">View profile</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#">Profile visibility settings</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="db-pro-pgog">
                                            <span>
                                                <b class="count">90</b>%
                                            </span>
                                        </div>
                                        <ul class="pro-stat-ic">
                                            <li>
                                                <span>
                                                    <i class="fa fa-heart-o like" aria-hidden="true"></i>
                                                    <b>12</b>Likes
                                                </span>
                                            </li>
                                            <li>
                                                <span>
                                                    <i class="fa fa-eye view" aria-hidden="true"></i>
                                                    <b>12</b>Views
                                                </span>
                                            </li>
                                            <li>
                                                <span>
                                                    <i class="fa fa-handshake-o inte" aria-hidden="true"></i>
                                                    <b>12</b>Interests
                                                </span>
                                            </li>
                                            <li>
                                                <span>
                                                    <i class="fa fa-hand-pointer-o clic" aria-hidden="true"></i>
                                                    <b>12</b>Clicks
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="row">
                                <div class="col-md-12 db-sec-com db-pro-stat-pg">
                                    <h2 class="db-tit">Profiles views</h2>
                                    <div class="db-pro-stat-view-filter cho-round-cor chosenini">
                                        <div>
                                            <select class="chosen-select">
                                                <option value="">Current month</option>
                                                <option value="">Jan 2024</option>
                                                <option value="">Fan 2024</option>
                                                <option value="">Mar 2024</option>
                                                <option value="">Apr 2024</option>
                                                <option value="">May 2024</option>
                                                <option value="">Jun 2024</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="chartin">
                                        <canvas id="Chart_leads"></canvas>
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

<!-- Mirrored from rn53themes.net/themes/matrimo/user-profile.html by HTTrack Website Copier/3.x [XR&CO'2014], Sat, 12 Apr 2025 05:03:34 GMT -->
</html>
