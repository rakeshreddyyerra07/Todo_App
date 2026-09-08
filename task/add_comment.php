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
   GET DATA
========================================================= */

$task_id = (int)($_POST["task_id"] ?? 0);

$comment = trim($_POST["comment"] ?? "");

$user_id = (int)$_SESSION["user_id"];


/* =========================================================
   VALIDATION
========================================================= */

if ($task_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid task."
    ]);

    exit();
}


if ($comment === "") {

    echo json_encode([
        "success" => false,
        "message" => "Comment cannot be empty."
    ]);

    exit();
}


/* =========================================================
   CHECK TASK
========================================================= */

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
        "message" => "Database error."
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
   INSERT COMMENT
========================================================= */

$insert_sql = "
    INSERT INTO task_comments
    (
        task_id,
        user_id,
        comment,
        created_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        NOW()
    )
";

$insert_stmt = mysqli_prepare(
    $conn,
    $insert_sql
);

if (!$insert_stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to save comment."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $insert_stmt,
    "iis",
    $task_id,
    $user_id,
    $comment
);


if (mysqli_stmt_execute($insert_stmt)) {

    echo json_encode([
        "success" => true,
        "message" => "Comment added successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Failed to add comment."
    ]);
}


mysqli_stmt_close($insert_stmt);