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
   TASK ID
========================================================= */

$task_id = (int)($_GET["task_id"] ?? 0);


if ($task_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid task."
    ]);

    exit();
}


/* =========================================================
   GET COMMENTS
========================================================= */

$sql = "
    SELECT
        tc.id,
        tc.task_id,
        tc.user_id,
        tc.comment,
        tc.created_at,
        u.name AS user_name

    FROM task_comments tc

    LEFT JOIN users u
        ON u.id = tc.user_id

    WHERE tc.task_id = ?

    ORDER BY tc.id DESC
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
    $task_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


$comments = [];


if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $comments[] = [

            "id" => (int)$row["id"],

            "task_id" => (int)$row["task_id"],

            "user_id" => (int)$row["user_id"],

            "user_name" => $row["user_name"] ?: "User",

            "comment" => $row["comment"],

            "created_at" => date(
                "d M Y, h:i A",
                strtotime($row["created_at"])
            )

        ];
    }
}


mysqli_stmt_close($stmt);


echo json_encode([
    "success" => true,
    "comments" => $comments
]);