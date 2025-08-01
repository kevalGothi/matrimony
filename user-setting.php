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

    <!-- LOGIN -->
    <section>
        <div class="db">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 col-lg-3">
                        <div class="db-nav">
                            <div class="db-nav-pro"><img src="images/profiles/12.jpg" class="img-fluid" alt=""></div>
                            <div class="db-nav-list">
                                 <ul>
                                        <li>
                                            <a href="user-dashboard.php">
                                                <i class="fa fa-tachometer" aria-hidden="true"></i>Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a href="user-profile.php">
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
                                            <a href="user-setting.php" class="act">
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
                            <div class="col-md-12 db-sec-com">
                                <h2 class="db-tit">Profile settings</h2>
                                <div class="col7 fol-set-rhs">
                                    <!--START-->
                                    <div class="ms-write-post fol-sett-sec sett-rhs-pro" style="">
                                        <div class="foll-set-tit fol-pro-abo-ico">
                                            <h4>Profile</h4>
                                        </div>
                                        <div class="fol-sett-box">
                                            <ul>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="auth-pro-sm sett-pro-wid">
                                                            <div class="auth-pro-sm-img">
                                                                <img src="images/profiles/15.jpg" alt="">
                                                            </div>
                                                            <div class="auth-pro-sm-desc">
                                                                <h5>Anna Jaslin</h5>
                                                                <p>Premium user | Illunois</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <a href="#" class="set-sig-out">Sign Out</a>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="sett-rad-left">
                                                            <h5>Profile visible</h5>
                                                            <p>You can set-up who can able to view your profile.</p>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <div class="sett-select-drop">
                                                            <select>
                                                              <option value="All users">All users</option>
                                                              <option value="Premium">Premium</option>
                                                              <option value="Free">Free</option>
                                                              <option value="Free">No-more visible(You can't visible, so no one can view your profile)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="sett-rad-left">
                                                            <h5>Who can send you Interest requests?</h5>
                                                            <p>You can set-up who can able to make Interest request here.</p>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <div class="sett-select-drop">
                                                            <select>
                                                                <option value="All users">All users</option>
                                                                <option value="Premium">Premium</option>
                                                                <option value="Free">Free</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!--END-->	
                                    <!--START-->
                                    <div class="ms-write-post fol-sett-sec sett-rhs-acc" style="">
                                        <div class="foll-set-tit fol-pro-abo-ico">
                                            <h4>Account</h4><a href="#!" class="sett-edit-btn sett-acc-edit-eve"><i class="fa fa-edit" aria-hidden="true"></i> Edit</a>
                                        </div>
                                        <div class="fol-sett-box sett-acc-view sett-two-tab">
                                            <ul>
                                                <li>
                                                    <div>Full name</div>
                                                    <div>Anna Jaslin</div>
                                                </li>
                                                <li>
                                                    <div>Mobile</div>
                                                    <div>+01 4343 53553</div>
                                                </li>
                                                <li>
                                                    <div>Email id</div>
                                                    <div>loremipsum@gmail.com</div>
                                                </li>
                                                <li>
                                                    <div>Password</div>
                                                    <div>**********</div>
                                                </li>
                                                <li>
                                                    <div>Profile type</div>
                                                    <div>Platinum</div>
                                                </li>
                                            </ul>
                                        </div>
                                    <div class="sett-acc-edit">
                                            <form class="form-com sett-pro-form">
                                                <ul>
                                                    <li>
                                                        <div class="fm-lab">Full name</div>
                                                        <div class="fm-fie"><input type="text" value="vijaya kumar"></div>
                                                    </li>
                                                    <li>
                                                        <div class="fm-lab">Email id</div>
                                                        <div class="fm-fie"><input type="text" value="vijaykumar@gmail.com"></div>
                                                    </li>
                                                    <li>
                                                        <div class="fm-lab">Password</div>
                                                        <div class="fm-fie"><input type="password" value="dfght3d34"></div>
                                                    </li>
                                                    <li>
                                                        <div class="fm-lab">Confirm password</div>
                                                        <div class="fm-fie"><input type="password" value="asg235sf"></div>
                                                    </li>
                                                    <li>
                                                        <div class="fm-lab">Profile type</div>
                                                        <div class="fm-fie">
                                                            <select>
                                                              <option value="volvo">General</option>
                                                              <option value="opel">Bloger</option>
                                                              <option value="saab">Business</option>
                                                              <option value="saab">Marketer</option>
                                                            </select>
                                                        </div>
                                                    </li>
                                                    <li><input type="submit" value="Update" class=""><input type="reset" value="Cancel" class="sett-acc-edi-can"></li>
                                                </ul>
                                            </form>
                                        </div>	
                                    </div>
                                    <!--END-->	
                                    <!--START-->
                                    <div class="ms-write-post fol-sett-sec sett-rhs-not" style="">
                                        <div class="foll-set-tit fol-pro-abo-ico">
                                            <h4>Notifications</h4>
                                        </div>
                                        <div class="fol-sett-box">
                                            <ul>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="sett-rad-left">
                                                            <h5>Interest request</h5>
                                                            <p>Interest request email notificatios</p>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <div class="checkboxes-and-radios">
                                                           <input type="checkbox" name="checkbox-cats" id="sett-not-mail" value="1" checked="">
                                                            <label for="sett-not-mail"></label>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="sett-rad-left">
                                                            <h5>Chat</h5>
                                                            <p>New chat notificatios</p>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <div class="checkboxes-and-radios">
                                                           <input type="checkbox" name="checkbox-cats" id="sett-not-fri" value="1" checked="">
                                                            <label for="sett-not-fri"></label>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="sett-rad-left">
                                                            <h5>Profile views</h5>
                                                            <p>If any one view your profile means you get the notifications at end of the day</p>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <div class="checkboxes-and-radios">
                                                           <input type="checkbox" name="checkbox-cats" id="sett-not-fol" value="1" checked>
                                                            <label for="sett-not-fol"></label>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="sett-lef">
                                                        <div class="sett-rad-left">
                                                            <h5>New profile match</h5>
                                                            <p>You get the profile match emails</p>
                                                        </div>
                                                    </div>
                                                    <div class="sett-rig">
                                                        <div class="checkboxes-and-radios">
                                                           <input type="checkbox" name="checkbox-cats" id="sett-not-mes" value="1" checked="">
                                                            <label for="sett-not-mes"></label>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!--END-->
                                   						
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END -->

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


<!-- Mirrored from rn53themes.net/themes/matrimo/user-setting.html by HTTrack Website Copier/3.x [XR&CO'2014], Sat, 12 Apr 2025 05:03:35 GMT -->
</html>