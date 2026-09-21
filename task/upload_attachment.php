
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


$user_id = (int)($_SESSION["user_id"] ?? 0);

$task_id = (int)($_POST["task_id"] ?? 0);


/* =========================================================
   VALIDATE TASK ID
========================================================= */

if ($task_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid task."
    ]);

    exit();
}


/* =========================================================
   CHECK TASK EXISTS
========================================================= */

$task_sql = "
    SELECT id
    FROM tasks
    WHERE id = ?
    LIMIT 1
";

$task_stmt = mysqli_prepare(
    $conn,
    $task_sql
);

if (!$task_stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to validate task."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $task_stmt,
    "i",
    $task_id
);

mysqli_stmt_execute(
    $task_stmt
);

$task_result = mysqli_stmt_get_result(
    $task_stmt
);

if (
    !$task_result ||
    mysqli_num_rows($task_result) === 0
) {

    mysqli_stmt_close(
        $task_stmt
    );

    echo json_encode([
        "success" => false,
        "message" => "Task not found."
    ]);

    exit();
}

mysqli_stmt_close(
    $task_stmt
);


/* =========================================================
   CHECK FILE
========================================================= */

if (
    !isset($_FILES["attachment"]) ||
    !is_array($_FILES["attachment"])
) {

    echo json_encode([
        "success" => false,
        "message" => "No file selected."
    ]);

    exit();
}


$file = $_FILES["attachment"];


/* =========================================================
   CHECK UPLOAD ERROR
========================================================= */

if (
    !isset($file["error"]) ||
    $file["error"] !== UPLOAD_ERR_OK
) {

    echo json_encode([
        "success" => false,
        "message" => "File upload failed."
    ]);

    exit();
}


/* =========================================================
   CHECK TEMP FILE
========================================================= */

if (
    !isset($file["tmp_name"]) ||
    !is_uploaded_file($file["tmp_name"])
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid uploaded file."
    ]);

    exit();
}


/* =========================================================
   FILE SIZE
========================================================= */

$max_size = 10 * 1024 * 1024;

$file_size = (int)$file["size"];

if ($file_size <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "The selected file is empty."
    ]);

    exit();
}

if ($file_size > $max_size) {

    echo json_encode([
        "success" => false,
        "message" => "File size cannot exceed 10 MB."
    ]);

    exit();
}


/* =========================================================
   ORIGINAL FILE NAME
========================================================= */

$original_name = basename(
    $file["name"]
);

if ($original_name === "") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid file name."
    ]);

    exit();
}


/* =========================================================
   FILE EXTENSION
========================================================= */

$extension = strtolower(
    pathinfo(
        $original_name,
        PATHINFO_EXTENSION
    )
);


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


if (
    !in_array(
        $extension,
        $allowed_extensions,
        true
    )
) {

    echo json_encode([
        "success" => false,
        "message" => "This file type is not allowed."
    ]);

    exit();
}


/* =========================================================
   UPLOAD DIRECTORY
========================================================= */

$upload_dir =
    __DIR__ .
    "/../uploads/task_files";


/* =========================================================
   CREATE DIRECTORY IF NOT EXISTS
========================================================= */

if (!is_dir($upload_dir)) {

    if (!mkdir(
        $upload_dir,
        0777,
        true
    )) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Unable to create upload directory."
        ]);

        exit();
    }
}


/* =========================================================
   CHECK DIRECTORY
========================================================= */

if (!is_writable($upload_dir)) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Upload directory is not writable."
    ]);

    exit();
}


/* =========================================================
   GENERATE UNIQUE FILE NAME
========================================================= */

$stored_name =
    "task_" .
    $task_id .
    "_" .
    uniqid(
        "",
        true
    ) .
    "." .
    $extension;


/* =========================================================
   PHYSICAL FILE PATH
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
        "message" =>
            "Unable to save uploaded file."
    ]);

    exit();
}


/* =========================================================
   DATABASE FILE PATH
========================================================= */

$file_path =
    "uploads/task_files/" .
    $stored_name;


/* =========================================================
   DETECT MIME TYPE
========================================================= */

$file_type = "";


if (function_exists("finfo_open")) {

    $finfo = finfo_open(
        FILEINFO_MIME_TYPE
    );

    if ($finfo) {

        $detected_type =
            finfo_file(
                $finfo,
                $target_path
            );

        if ($detected_type) {

            $file_type =
                $detected_type;
        }

        finfo_close(
            $finfo
        );
    }
}


/* =========================================================
   FALLBACK MIME TYPE
========================================================= */

if ($file_type === "") {

    $file_type =
        $file["type"] ?? "";
}


/* =========================================================
   INSERT ATTACHMENT
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


$stmt = mysqli_prepare(
    $conn,
    $sql
);


/* =========================================================
   CHECK DATABASE STATEMENT
========================================================= */

if (!$stmt) {

    if (file_exists($target_path)) {

        unlink($target_path);
    }

    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


/* =========================================================
   BIND PARAMETERS
========================================================= */

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


/* =========================================================
   EXECUTE INSERT
========================================================= */

if (mysqli_stmt_execute($stmt)) {

    $attachment_id =
        mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);

    echo json_encode([

        "success" => true,

        "message" =>
            "File uploaded successfully.",

        "attachment_id" =>
            (int)$attachment_id,

        "original_name" =>
            $original_name,

        "stored_name" =>
            $stored_name,

        "file_path" =>
            $file_path

    ]);

    exit();
}


/* =========================================================
   DATABASE INSERT FAILED
========================================================= */

if (file_exists($target_path)) {

    unlink($target_path);
}


$error_message =
    mysqli_stmt_error($stmt);


mysqli_stmt_close($stmt);


echo json_encode([

    "success" => false,

    "message" =>
        "Failed to save file information: " .
        $error_message

]);


exit();
