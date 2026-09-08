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


$task_id = (int)($_GET["task_id"] ?? 0);


if ($task_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid task."
    ]);

    exit();
}


/* =========================================================
   GET FILES
========================================================= */

$sql = "
    SELECT
        id,
        task_id,
        user_id,
        original_name,
        file_path,
        file_type,
        file_size,
        uploaded_at

    FROM task_attachments

    WHERE task_id = ?

    ORDER BY id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


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
    $task_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


$attachments = [];


if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $is_image = false;

        $extension = strtolower(
            pathinfo(
                $row["original_name"],
                PATHINFO_EXTENSION
            )
        );


        if (in_array(
            $extension,
            [
                "jpg",
                "jpeg",
                "png",
                "gif",
                "webp"
            ],
            true
        )) {

            $is_image = true;
        }


        $attachments[] = [

            "id" => (int)$row["id"],

            "task_id" => (int)$row["task_id"],

            "user_id" => (int)$row["user_id"],

            "original_name" => $row["original_name"],

            "file_path" => $row["file_path"],

            "file_type" => $row["file_type"],

            "file_size" => (int)$row["file_size"],

            "is_image" => $is_image,

            "uploaded_at" => date(
                "d M Y, h:i A",
                strtotime($row["uploaded_at"])
            )
        ];
    }
}


mysqli_stmt_close($stmt);


echo json_encode([
    "success" => true,
    "attachments" => $attachments
]);