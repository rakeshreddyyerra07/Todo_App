<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reset Password - Todo App
    </title>


    <link
        rel="stylesheet"
        href="<?= base_url('assets/style.css') ?>"
    >

</head>


<body>


<div class="auth-page">


    <div class="auth-card">


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="auth-header">


            <div class="auth-icon">
                🔐
            </div>


            <h1>
                Reset Password
            </h1>


            <p>
                Enter your new password below.
            </p>


        </div>



        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <?php
                echo esc($error);
                ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             RESET FORM
        ================================================== -->

        <?php if ($error === "" && $token !== ""): ?>


            <form method="POST">
            <?= csrf_field() ?>


                <input
                    type="hidden"
                    name="token"
                    value="<?php
                    echo esc($token);
                    ?>"
                >



                <!-- NEW PASSWORD -->

                <div class="form-group">


                    <label class="form-label">

                        New Password

                    </label>


                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter new password"
                        required
                        minlength="6"
                    >


                </div>



                <!-- CONFIRM PASSWORD -->

                <div class="form-group">


                    <label class="form-label">

                        Confirm New Password

                    </label>


                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Confirm new password"
                        required
                        minlength="6"
                    >


                </div>



                <!-- BUTTON -->

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="
                        width:100%;
                        height:48px;
                    "
                >

                    Reset Password

                </button>


            </form>


        <?php endif; ?>



        <!-- =================================================
             LOGIN LINK
        ================================================== -->

        <div class="auth-link">


            <a href="<?= site_url('login') ?>">

                ← Back to Login

            </a>


        </div>


    </div>


</div>


</body>

</html>
