<?php

session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";
$success = "";

$token = $_GET["token"] ?? $_POST["token"] ?? "";


/* =========================================================
   CHECK RESET TOKEN
========================================================= */

if ($token === "") {

    $error = "Invalid password reset link.";

} else {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, user_id
         FROM password_resets
         WHERE token = ?
         AND expires_at > NOW()
         LIMIT 1"
    );


    if ($stmt === false) {

        $error = "Database Error: " . mysqli_error($conn);

    } else {

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $token
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);


        if (
            !$result ||
            mysqli_num_rows($result) !== 1
        ) {

            $error =
                "This password reset link is invalid or expired.";
        }


        mysqli_stmt_close($stmt);
    }
}


/* =========================================================
   RESET PASSWORD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && $error === ""
) {

    $password =
        $_POST["password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $password === ""
        || $confirm_password === ""
    ) {

        $error =
            "Please fill in all fields.";

    } elseif (strlen($password) < 6) {

        $error =
            "Password must be at least 6 characters.";

    } elseif (
        $password !== $confirm_password
    ) {

        $error =
            "Passwords do not match.";

    } else {


        /* =================================================
           GET USER ID FROM TOKEN
        ================================================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id
             FROM password_resets
             WHERE token = ?
             AND expires_at > NOW()
             LIMIT 1"
        );


        if ($stmt === false) {

            $error =
                "Database Error: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $token
            );

            mysqli_stmt_execute($stmt);

            $result =
                mysqli_stmt_get_result($stmt);


            if (
                $result &&
                mysqli_num_rows($result) === 1
            ) {

                $reset =
                    mysqli_fetch_assoc($result);


                /* =========================================
                   HASH PASSWORD
                ========================================= */

                $hashed_password =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                /* =========================================
                   UPDATE PASSWORD
                ========================================= */

                $update = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET password = ?
                     WHERE id = ?"
                );


                if ($update === false) {

                    $error =
                        "Database Error: " .
                        mysqli_error($conn);

                } else {

                    mysqli_stmt_bind_param(
                        $update,
                        "si",
                        $hashed_password,
                        $reset["user_id"]
                    );


                    if (
                        mysqli_stmt_execute(
                            $update
                        )
                    ) {


                        /* =================================
                           DELETE USED RESET TOKEN
                        ================================= */

                        $delete = mysqli_prepare(
                            $conn,
                            "DELETE FROM password_resets
                             WHERE token = ?"
                        );


                        if ($delete !== false) {

                            mysqli_stmt_bind_param(
                                $delete,
                                "s",
                                $token
                            );

                            mysqli_stmt_execute(
                                $delete
                            );

                            mysqli_stmt_close(
                                $delete
                            );
                        }


                        mysqli_stmt_close(
                            $update
                        );

                        mysqli_stmt_close(
                            $stmt
                        );


                        /* ================================
                           GO TO LOGIN
                        ================================= */

                        header(
                            "Location: login.php?reset=1"
                        );

                        exit;


                    } else {

                        $error =
                            "Unable to update password.";

                    }


                    mysqli_stmt_close(
                        $update
                    );
                }


            } else {

                $error =
                    "Invalid or expired reset link.";
            }


            mysqli_stmt_close(
                $stmt
            );
        }
    }
}

?>


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
        href="../assets/style.css"
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
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             RESET FORM
        ================================================== -->

        <?php if (
            $error === ""
            && $token !== ""
        ): ?>


            <form method="POST">


                <input
                    type="hidden"
                    name="token"
                    value="<?php
                    echo htmlspecialchars($token);
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


            <a href="login.php">

                ← Back to Login

            </a>


        </div>


    </div>


</div>


</body>

</html>