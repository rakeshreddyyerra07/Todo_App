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

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if ($stmt === false) {

            $error = "Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {

                $user = mysqli_fetch_assoc($result);
                $user_id = (int)$user["id"];

                /* Generate token */

                $token = bin2hex(random_bytes(32));

                /*
                 * Use MySQL time.
                 * Token will expire after 1 hour.
                 */

                $delete = mysqli_prepare(
                    $conn,
                    "DELETE FROM password_resets WHERE user_id = ?"
                );

                if ($delete === false) {

                    $error =
                        "Unable to delete old reset request: "
                        . mysqli_error($conn);

                } else {

                    mysqli_stmt_bind_param(
                        $delete,
                        "i",
                        $user_id
                    );

                    mysqli_stmt_execute($delete);

                    mysqli_stmt_close($delete);
                }


                if ($error === "") {

                    /*
                     * Use DATE_ADD(NOW(), INTERVAL 1 HOUR)
                     * so PHP and MySQL time cannot conflict.
                     */

                    $insert = mysqli_prepare(
                        $conn,
                        "INSERT INTO password_resets
                        (user_id, token, expires_at)
                        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
                    );

                    if ($insert === false) {

                        $error =
                            "Unable to create reset request: "
                            . mysqli_error($conn);

                    } else {

                        mysqli_stmt_bind_param(
                            $insert,
                            "is",
                            $user_id,
                            $token
                        );

                        if (mysqli_stmt_execute($insert)) {

                            $reset_link =
                                "http://localhost/Todo_App/auth/reset_password.php?token="
                                . urlencode($token);

                            $success =
                                "Reset link created successfully.";

                        } else {

                            $error =
                                "Unable to create reset link: "
                                . mysqli_stmt_error($insert);
                        }

                        mysqli_stmt_close($insert);
                    }
                }

            } else {

                $error =
                    "No account was found with this email address.";
            }

            mysqli_stmt_close($stmt);
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

                <a href="<?php echo htmlspecialchars($reset_link); ?>">

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
