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
        <div class="login">
            <div class="container">
                <div class="row">

                    <div class="inn">
                        <div class="lhs">
                            <div class="tit">
                                <h2>Now <b>Find <br> your life partner</b> Easy and fast.</h2>
                            </div>
                            <div class="im">
                                <img src="images/login-couple.png" alt="">
                            </div>
                            <div class="log-bg">&nbsp;</div>
                        </div>
                        <div class="rhs">
                            <div>
                                <div class="form-tit">
                                    <h4>Start for free</h4>
                                    <h1>Sign in to Matrimony</h1>
                                    <p>Not a member? <a href="client_sign_up.php">Sign up now</a></p>
                                </div>
                                <div class="form-login">
                                    <form action="loginback.php" method="POST">
                                        <div class="form-group">
                                            <label class="lb">Phone Number:</label>
                                            <input type="text" class="form-control" id="email"
                                                placeholder="Enter Phone Number" name="email">
                                        </div>
                                        <div class="form-group">
                                            <label class="lb">Password:</label>
                                            <input type="password" class="form-control" id="pwd"
                                                placeholder="Enter password" name="pswd">
                                        </div>
                                        <div class="form-group form-check">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" name="agree"> Remember
                                                me
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-primary" name="signin">Sign in</button>
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
    include "inc/copyright.php";
?>
<?php
    include "inc/footerlink.php";
?>