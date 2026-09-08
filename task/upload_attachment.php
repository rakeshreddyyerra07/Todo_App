
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


$user_id = (int)$_SESSION["user_id"];

$task_id = (int)($_POST["task_id"] ?? 0);


/* =========================================================
   VALIDATE TASK
========================================================= */

if ($task_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid task."
    ]);

    exit();
}


$task_sql = "
    SELECT id
    FROM tasks
    WHERE id = ?
    LIMIT 1
";

$task_stmt = mysqli_prepare($conn, $task_sql);

if (!$task_stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error while checking task."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $task_stmt,
    "i",
    $task_id
);

mysqli_stmt_execute($task_stmt);

$task_result = mysqli_stmt_get_result($task_stmt);


if (!$task_result || mysqli_num_rows($task_result) === 0) {

    mysqli_stmt_close($task_stmt);

    echo json_encode([
        "success" => false,
        "message" => "Task not found."
    ]);

    exit();
}


mysqli_stmt_close($task_stmt);


/* =========================================================
   CHECK FILE
========================================================= */

if (!isset($_FILES["attachment"])) {

    echo json_encode([
        "success" => false,
        "message" => "No file selected."
    ]);

    exit();
}


$file = $_FILES["attachment"];


if ($file["error"] !== UPLOAD_ERR_OK) {

    echo json_encode([
        "success" => false,
        "message" => "File upload failed."
    ]);

    exit();
}


/* =========================================================
   FILE SIZE
========================================================= */

$max_size = 10 * 1024 * 1024;

if ($file["size"] > $max_size) {

    echo json_encode([
        "success" => false,
        "message" => "File size cannot exceed 10 MB."
    ]);

    exit();
}


/* =========================================================
   ALLOWED FILE TYPES
========================================================= */

$allowed_extensions = [
    "jpg",
    "jpeg",
    "png",
    "gif",
    "webp",
    "pdf",
    "doc",
    "docx",
    "xls",
    "xlsx",
    "txt",
    "zip"
];


$original_name = basename($file["name"]);

$extension = strtolower(
    pathinfo(
        $original_name,
        PATHINFO_EXTENSION
    )
);


if (!in_array($extension, $allowed_extensions, true)) {

    echo json_encode([
        "success" => false,
        "message" => "This file type is not allowed."
    ]);

    exit();
}


/* =========================================================
   UPLOAD DIRECTORY
========================================================= */

$upload_dir = __DIR__ . "/../uploads/task_files";


if (!is_dir($upload_dir)) {

    if (!mkdir($upload_dir, 0777, true)) {

        echo json_encode([
            "success" => false,
            "message" => "Unable to create upload directory."
        ]);

        exit();
    }
}


/* =========================================================
   CHECK DIRECTORY WRITABLE
========================================================= */

if (!is_writable($upload_dir)) {

    echo json_encode([
        "success" => false,
        "message" => "Upload directory is not writable."
    ]);

    exit();
}


/* =========================================================
   UNIQUE FILE NAME
========================================================= */

$stored_name =
    "task_" .
    $task_id .
    "_" .
    uniqid("", true) .
    "." .
    $extension;


/* =========================================================
   TARGET PATH
========================================================= */

$target_path =
    $upload_dir .
    DIRECTORY_SEPARATOR .
    $stored_name;


/* =========================================================
   MOVE FILE
========================================================= */

if (!move_uploaded_file(
    $file["tmp_name"],
    $target_path
)) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to save uploaded file."
    ]);

    exit();
}


/* =========================================================
   DATABASE PATH
========================================================= */

/*
   Store path relative to the application root.
*/

$file_path =
    "/uploads/task_files/" .
    $stored_name;


$file_type =
    $file["type"] ?? "";


$file_size =
    (int)$file["size"];


/* =========================================================
   INSERT DATABASE
========================================================= */

$sql = "
    INSERT INTO task_attachments
    (
        task_id,
        user_id,
        original_name,
        stored_name,
        file_path,
        file_type,
        file_size,
        uploaded_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        NOW()
    )
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    if (file_exists($target_path)) {

        unlink($target_path);
    }

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $stmt,
    "iissssi",
    $task_id,
    $user_id,
    $original_name,
    $stored_name,
    $file_path,
    $file_type,
    $file_size
);


if (mysqli_stmt_execute($stmt)) {

    echo json_encode([
        "success" => true,
        "message" => "File uploaded successfully.",
        "file_path" => $file_path,
        "stored_name" => $stored_name
    ]);

} else {

    if (file_exists($target_path)) {

        unlink($target_path);
    }

    echo json_encode([
        "success" => false,
        "message" => "Failed to save file information."
    ]);
}


mysqli_stmt_close($stmt);
