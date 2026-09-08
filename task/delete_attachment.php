<?php

session_start();

require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit();
}


/* =========================================================
   USER INFORMATION
========================================================= */

$user_id = (int) $_SESSION["user_id"];

$user_role = trim(
    strtolower(
        $_SESSION["user_role"] ?? "user"
    )
);

$is_admin = ($user_role === "admin");


/* =========================================================
   GET ATTACHMENT ID
========================================================= */

$attachment_id = (int) (
    $_POST["attachment_id"] ?? 0
);


if ($attachment_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid attachment."
    ]);

    exit();
}


/* =========================================================
   GET ATTACHMENT
========================================================= */

$sql = "
    SELECT
        id,
        user_id,
        file_path
    FROM task_attachments
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $attachment_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


if (!$result || mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => false,
        "message" => "Attachment not found."
    ]);

    exit();
}


$attachment = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/* =========================================================
   CHECK PERMISSION
========================================================= */

/*
   Admin:
   Can delete any attachment.

   Normal user:
   Can delete only an attachment
   uploaded by themselves.
*/

if (
    !$is_admin &&
    (int) $attachment["user_id"] !== $user_id
) {

    echo json_encode([
        "success" => false,
        "message" => "You cannot delete this file."
    ]);

    exit();
}


/* =========================================================
   DELETE DATABASE RECORD
========================================================= */

$delete_sql = "
    DELETE FROM task_attachments
    WHERE id = ?
";

$delete_stmt = mysqli_prepare(
    $conn,
    $delete_sql
);


if (!$delete_stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $delete_stmt,
    "i",
    $attachment_id
);


if (!mysqli_stmt_execute($delete_stmt)) {

    mysqli_stmt_close($delete_stmt);

    echo json_encode([
        "success" => false,
        "message" => "Unable to delete file."
    ]);

    exit();
}


mysqli_stmt_close($delete_stmt);


/* =========================================================
   DELETE PHYSICAL FILE
========================================================= */

$file_path = __DIR__ . "/../" . $attachment["file_path"];


if (file_exists($file_path)) {

    unlink($file_path);
}


/* =========================================================
   SUCCESS RESPONSE
========================================================= */

echo json_encode([
    "success" => true,
    "message" => "File deleted successfully."
]);

exit();

?>