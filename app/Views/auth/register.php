<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - Todo App</title>

    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-header">

            <div class="auth-icon">
                👤+
            </div>

            <h1>Create Your Account</h1>

        </div>

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <?php echo esc($error); ?>

            </div>

        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>

            <div class="form-group">

                <label class="form-label">
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Enter your full name"
                    value="<?php echo esc($name); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label class="form-label">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    value="<?php echo esc($email); ?>"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label class="form-label">
                    Password
                </label>

                <div style="position:relative;">

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Create a password"
                        required
                        style="padding-right:45px;"
                    >

                    <i
                        class="bi bi-eye"
                        id="togglePassword"
                        style="
                            position:absolute;
                            right:10px;
                            top:50%;
                            transform:translateY(-50%);
                            cursor:pointer;
                            font-size 14px;
                        "
                    ></i>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label class="form-label">
                    Confirm Password
                </label>

                <div style="position:relative;">

                    <input
                        type="password"
                        name="confirm_password"
                        id="confirmPassword"
                        class="form-control"
                        placeholder="Confirm your password"
                        required
                        style="padding-right:45px;"
                    >

                    <i
                        class="bi bi-eye"
                        id="toggleConfirmPassword"
                        style="
                            position:absolute;
                            right:10px;
                            top:50%;
                            transform:translateY(-50%);
                            cursor:pointer;
                            font-size:14px;
                        "
                    ></i>

                </div>

            </div>


            <div class="checkbox-row">

                <input
                    type="checkbox"
                    name="terms"
                    id="terms"
                >

                <label for="terms">

                    I agree to the

                    <a
                        href="#"
                        class="auth-link"
                        style="margin:0;"
                    >
                        Terms and Conditions
                    </a>

                </label>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
                style="width:100%; height:48px;"
            >
                Create Account
            </button>

        </form>


        <div class="auth-link">

            Already have an account?

            <a href="<?= site_url('login') ?>">
                Login
            </a>

        </div>

    </div>

</div>


<script>

/* =========================
   PASSWORD EYE
========================= */

document.getElementById("togglePassword").onclick = function () {

    const password =
        document.getElementById("password");

    if (password.type === "password") {

        password.type = "text";

        this.classList.remove("bi-eye");

        this.classList.add("bi-eye-slash");

    } else {

        password.type = "password";

        this.classList.remove("bi-eye-slash");

        this.classList.add("bi-eye");

    }

};


/* =========================
   CONFIRM PASSWORD EYE
========================= */

document.getElementById("toggleConfirmPassword").onclick = function () {

    const password =
        document.getElementById("confirmPassword");

    if (password.type === "password") {

        password.type = "text";

        this.classList.remove("bi-eye");

        this.classList.add("bi-eye-slash");

    } else {

        password.type = "password";

        this.classList.remove("bi-eye-slash");

        this.classList.add("bi-eye");

    }

};

</script>


</body>

</html>


