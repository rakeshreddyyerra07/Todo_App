
<?php

session_start();

require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| ONE-TIME ADMIN PASSWORD RESET
|--------------------------------------------------------------------------
| This file resets ONLY user ID 1.
|
| Temporary password:
| Admin@12345
|
| DELETE THIS FILE IMMEDIATELY AFTER SUCCESSFUL RESET.
|--------------------------------------------------------------------------
*/

$user_id = 1;
$new_password = "Admin@12345";


/* =========================================================
   CHECK DATABASE CONNECTION
========================================================= */

if (!isset($conn) || !$conn) {
    die("Database connection failed.");
}


/* =========================================================
   GET USER ID 1
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if ($stmt === false) {
    die(
        "Database error while preparing user query: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die(
        "Unable to find administrator: " .
        htmlspecialchars($error)
    );
}


/* =========================================================
   GET RESULT
========================================================= */

$result = mysqli_stmt_get_result($stmt);

if ($result === false) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die(
        "Unable to read user information: " .
        htmlspecialchars($error)
    );
}


/* =========================================================
   CHECK USER EXISTS
========================================================= */

if (mysqli_num_rows($result) !== 1) {

    mysqli_stmt_close($stmt);

    die("User ID 1 was not found.");
}


/* =========================================================
   GET USER DATA
========================================================= */

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   NORMALIZE ROLE
========================================================= */

$user_role = strtolower(
    trim(
        (string)($user["role"] ?? "")
    )
);


/* =========================================================
   SAFETY CHECK — MUST BE ADMIN
========================================================= */

if ($user_role !== "admin") {

    die(
        "Safety check failed. " .
        "User ID 1 role found in database: [" .
        htmlspecialchars((string)$user["role"]) .
        "]. " .
        "No password was changed."
    );
}


/* =========================================================
   CREATE SECURE PASSWORD HASH
========================================================= */

$hashed_password = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);

if ($hashed_password === false) {
    die("Unable to create secure password hash.");
}


/* =========================================================
   UPDATE ADMIN PASSWORD
========================================================= */

$update = mysqli_prepare(
    $conn,
    "UPDATE users
     SET password = ?
     WHERE id = ?
     AND LOWER(TRIM(role)) = 'admin'
     LIMIT 1"
);

if ($update === false) {

    die(
        "Unable to prepare password update: " .
        htmlspecialchars(mysqli_error($conn))
    );
}


/* =========================================================
   BIND PARAMETERS
========================================================= */

mysqli_stmt_bind_param(
    $update,
    "si",
    $hashed_password,
    $user_id
);


/* =========================================================
   EXECUTE UPDATE
========================================================= */

if (!mysqli_stmt_execute($update)) {

    $error = mysqli_stmt_error($update);

    mysqli_stmt_close($update);

    die(
        "Password reset failed: " .
        htmlspecialchars($error)
    );
}


/* =========================================================
   CHECK WHETHER PASSWORD WAS UPDATED
========================================================= */

$affected_rows = mysqli_stmt_affected_rows($update);

mysqli_stmt_close($update);


/*
|--------------------------------------------------------------------------
| If affected rows is 0, MySQL may report that the new value is the same
| or the update did not affect a row. Because password_hash() generates a
| new hash, normally this should be 1.
|--------------------------------------------------------------------------
*/

if ($affected_rows < 1) {

    die(
        "Password reset did not update the database. " .
        "Please verify that user ID 1 exists and has role = admin."
    );
}


/* =========================================================
   SUCCESS PAGE
========================================================= */

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Password Reset</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .card {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.10);
        }

        h1 {
            margin-top: 0;
            color: #198754;
            font-size: 26px;
        }

        .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .details p {
            margin: 10px 0;
            word-break: break-word;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            padding: 15px;
            border-radius: 8px;
            color: #664d03;
            margin-top: 20px;
        }

        code {
            background: #eee;
            padding: 3px 6px;
            border-radius: 4px;
            word-break: break-word;
        }

        .password {
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            margin-top: 5px;
        }

        .success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="card">

    <h1>
        ✓ Password Reset Successful
    </h1>

    <div class="success">
        The administrator password has been successfully changed.
    </div>

    <div class="details">

        <p>
            <strong>Name:</strong><br>
            <?php echo htmlspecialchars($user["name"]); ?>
        </p>

        <p>
            <strong>Email:</strong><br>
            <?php echo htmlspecialchars($user["email"]); ?>
        </p>

        <p>
            <strong>User ID:</strong><br>
            <?php echo (int)$user["id"]; ?>
        </p>

        <p>
            <strong>Role:</strong><br>
            <?php echo htmlspecialchars($user_role); ?>
        </p>

    </div>

    <p>
        <strong>Login email:</strong><br>

        <?php echo htmlspecialchars($user["email"]); ?>
    </p>

    <p>
        <strong>Temporary password:</strong><br>

        <code class="password">
            <?php echo htmlspecialchars($new_password); ?>
        </code>
    </p>

    <div class="warning">

        <strong>IMPORTANT</strong>

        <br><br>

        Delete this file immediately after confirming that you can log in.

        <br><br>

        <code>auth/reset_admin.php</code>

    </div>

</div>

</body>

</html>

