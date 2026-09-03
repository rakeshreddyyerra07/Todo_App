
<?php

session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";
$success = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {

        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if ($stmt === false) {

            $error =
                "Database error: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            if (!mysqli_stmt_execute($stmt)) {

                $error =
                    "Database error: " .
                    mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);

            } else {

                $result = mysqli_stmt_get_result($stmt);

                if ($result === false) {

                    $error =
                        "Unable to read account information: " .
                        mysqli_stmt_error($stmt);

                    mysqli_stmt_close($stmt);

                } elseif (mysqli_num_rows($result) !== 1) {

                    $error =
                        "No account was found with this email address.";

                    mysqli_stmt_close($stmt);

                } else {

                    $user = mysqli_fetch_assoc($result);

                    mysqli_stmt_close($stmt);

                    $user_id = (int)$user["id"];


                    /*
                    |--------------------------------------------------------------------------
                    | GENERATE SECURE RESET TOKEN
                    |--------------------------------------------------------------------------
                    */

                    try {

                        $token = bin2hex(
                            random_bytes(32)
                        );

                    } catch (Exception $e) {

                        $error =
                            "Unable to generate reset token.";

                        $token = "";
                    }


                    if ($error === "" && $token !== "") {

                        /*
                        |--------------------------------------------------------------------------
                        | SAVE TOKEN IN USERS TABLE
                        |--------------------------------------------------------------------------
                        |
                        | Your users table already contains:
                        |
                        | reset_token
                        | reset_token_expiry
                        |
                        */

                        $update = mysqli_prepare(
                            $conn,
                            "UPDATE users
                             SET
                                reset_token = ?,
                                reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                             WHERE id = ?
                             LIMIT 1"
                        );

                        if ($update === false) {

                            $error =
                                "Unable to create reset request: " .
                                mysqli_error($conn);

                        } else {

                            mysqli_stmt_bind_param(
                                $update,
                                "si",
                                $token,
                                $user_id
                            );

                            if (!mysqli_stmt_execute($update)) {

                                $error =
                                    "Unable to save reset request: " .
                                    mysqli_stmt_error($update);

                            } else {

                                /*
                                |--------------------------------------------------------------------------
                                | BUILD SERVER RESET LINK
                                |--------------------------------------------------------------------------
                                |
                                | This automatically uses your current domain.
                                | It will NOT use localhost.
                                |
                                */

                                $scheme = "http";

                                if (
                                    isset($_SERVER["HTTPS"]) &&
                                    $_SERVER["HTTPS"] !== "off"
                                ) {
                                    $scheme = "https";
                                }

                                $host =
                                    $_SERVER["HTTP_HOST"] ?? "localhost";

                                $base_path = rtrim(
                                    dirname(
                                        $_SERVER["SCRIPT_NAME"]
                                    ),
                                    "/\\"
                                );

                                $reset_link =
                                    $scheme .
                                    "://" .
                                    $host .
                                    $base_path .
                                    "/reset_password.php?token=" .
                                    urlencode($token);

                                $success =
                                    "Reset link created successfully.";
                            }

                            mysqli_stmt_close($update);
                        }
                    }
                }
            }
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

    <title>Forgot Password - Todo App</title>

    <link
        rel="stylesheet"
        href="../assets/style.css"
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
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars($success);
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
                    href="<?php echo htmlspecialchars($reset_link); ?>"
                >

                    <?php
                    echo htmlspecialchars($reset_link);
                    ?>

                </a>

            </div>

        <?php endif; ?>


        <form method="POST">

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
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
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

            <a href="login.php">
                ← Back to Login
            </a>

        </div>

    </div>

</div>

</body>

</html>

