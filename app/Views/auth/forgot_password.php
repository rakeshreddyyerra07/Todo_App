<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Forgot Password - Todo App</title>

    <link
        rel="stylesheet"
        href="<?= base_url('assets/style.css') ?>"
    >

</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-header">

            <div class="auth-icon">
                ✉
            </div>

            <h1>
                Forgot Password?
            </h1>

            <p>
                Enter your email address to reset your password.
            </p>

        </div>


        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <?php
                echo esc($error);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo esc($success);
                ?>

            </div>


            <div style="
                background:#f5f8ff;
                border:1px solid #dce8ff;
                padding:15px;
                border-radius:10px;
                margin-bottom:20px;
                word-break:break-all;
                font-size:13px;
            ">

                <strong>
                    Your Reset Link:
                </strong>

                <br><br>

                <a
                    href="<?php echo esc($reset_link); ?>"
                >

                    <?php
                    echo esc($reset_link);
                    ?>

                </a>

            </div>

        <?php endif; ?>


        <form method="POST">
            <?= csrf_field() ?>

            <div class="form-group">

                <label class="form-label">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email address"
                    required
                    value="<?php echo esc($email); ?>"
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary"
                style="width:100%;height:48px;"
            >
                Send Reset Link
            </button>

        </form>


        <div class="auth-link">

            <a href="<?= site_url('login') ?>">
                ← Back to Login
            </a>

        </div>

    </div>

</div>

</body>

</html>
