<?php

session_start();

/* Keep AJAX responses clean even if a PHP warning is emitted. */
ob_start();

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");
    exit();

}


/* =========================================================
   USER ROLE / PERMISSIONS
========================================================= */

$user_role = trim(strtolower($_SESSION["user_role"] ?? "user"));

$is_admin = ($user_role === "admin");

$is_user = ($user_role === "user");

$display_role = ucfirst($user_role);


/* =========================================================
   AJAX: UPDATE TASK FIELD
   (PRIORITY / PROGRESS / COMPLETION / DESCRIPTION)
   BOTH ADMIN AND USER ARE ALLOWED
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "update_task_field"
) {

    header("Content-Type: application/json");

    if (!($is_admin || $is_user)) {
        echo json_encode([
            "success" => false,
            "message" => "You do not have permission to edit this task."
        ]);
        exit();
    }

    $task_id = (int)($_POST["task_id"] ?? 0);
    $field   = $_POST["field"] ?? "";
    $value   = $_POST["value"] ?? "";

    $allowed_fields = [
        "priority",
        "progress",
        "is_completed",
        "description"
    ];

    if ($task_id <= 0 || !in_array($field, $allowed_fields, true)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid request."
        ]);
        exit();
    }

    $bind_type  = "s";
    $bind_value = $value;

    if ($field === "priority") {

        $allowed_priority = ["High", "Medium", "Low"];

        if (!in_array($value, $allowed_priority, true)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid priority value."
            ]);
            exit();
        }

    } elseif ($field === "progress") {

        $allowed_progress = ["Todo", "In Progress", "Pending", "Review", "Done"];

        if (!in_array($value, $allowed_progress, true)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid progress value."
            ]);
            exit();
        }

    } elseif ($field === "is_completed") {

        $bind_value = ((int)$value === 1) ? 1 : 0;
        $bind_type  = "i";

    } elseif ($field === "description") {

        $bind_value = trim($value);

    }

    $update_sql = "
        UPDATE tasks
        SET
            {$field} = ?,
            editedDate = NOW()
        WHERE id = ?
    ";

    $update_stmt = mysqli_prepare($conn, $update_sql);

    if (!$update_stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Database error."
        ]);
        exit();
    }

    mysqli_stmt_bind_param(
        $update_stmt,
        $bind_type . "i",
        $bind_value,
        $task_id
    );

    $update_ok = mysqli_stmt_execute($update_stmt);

    mysqli_stmt_close($update_stmt);

    if (!$update_ok) {
        echo json_encode([
            "success" => false,
            "message" => "Update failed."
        ]);
        exit();
    }

    $edited_display = "";

    $edited_stmt = mysqli_prepare(
        $conn,
        "SELECT editedDate FROM tasks WHERE id = ?"
    );

    if ($edited_stmt) {

        mysqli_stmt_bind_param($edited_stmt, "i", $task_id);
        mysqli_stmt_execute($edited_stmt);

        $edited_result = mysqli_stmt_get_result($edited_stmt);
        $edited_row = $edited_result ? mysqli_fetch_assoc($edited_result) : null;

        if ($edited_row) {
            $edited_display = formatTaskDate($edited_row["editedDate"]);
        }

        mysqli_stmt_close($edited_stmt);

    }

    echo json_encode([
        "success" => true,
        "field"   => $field,
        "value"   => $bind_value,
        "edited"  => $edited_display
    ]);

    exit();

}


/* =========================================================
   BOARD / CREATE BOARD
========================================================= */

$selected_board_id = isset($_GET["board_id"]) ? (int)$_GET["board_id"] : 0;

$board_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create_board") {

    /* Detect AJAX before doing any database work so every AJAX path returns JSON. */
    $is_ajax_board_request =
        (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
         strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") ||
        (strpos($_SERVER["HTTP_ACCEPT"] ?? "", "application/json") !== false);

    if (!($is_admin || $is_user)) {
        $board_error = "You do not have permission to create a board.";
    } else {
        $board_name = trim($_POST["board_name"] ?? "");
        $board_description = trim($_POST["board_description"] ?? "");

        if ($board_name === "") {
            $board_error = "Board name is required.";
        } else {
            $board_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO boards (name, description, created_by) VALUES (?, ?, ?)"
            );

            if (!$board_stmt) {
                $board_error = "Database Error: " . mysqli_error($conn);
            } else {
                $created_by = (int)$_SESSION["user_id"];

                mysqli_stmt_bind_param(
                    $board_stmt,
                    "ssi",
                    $board_name,
                    $board_description,
                    $created_by
                );

                if (mysqli_stmt_execute($board_stmt)) {
                    $new_board_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($board_stmt);

                    if ($is_ajax_board_request) {
                        /* Clean any unexpected buffered output before sending JSON. */
                        while (ob_get_level() > 0) {
                            ob_end_clean();
                        }

                        header("Content-Type: application/json; charset=UTF-8");

                        $json = json_encode([
                            "success" => true,
                            "board" => [
                                "id" => (int)$new_board_id,
                                "name" => $board_name,
                                "description" => $board_description
                            ]
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

                        echo ($json !== false)
                            ? $json
                            : '{"success":false,"message":"Unable to create a valid JSON response."}';
                        exit();
                    }

                    header("Location: index.php?board_id=" . (int)$new_board_id);
                    exit();
                }

                $board_error = "Database Error: " . mysqli_stmt_error($board_stmt);
                mysqli_stmt_close($board_stmt);
            }
        }
    }

    /* AJAX errors must return JSON too; otherwise fetch receives the full HTML page. */
    if ($is_ajax_board_request) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => false,
            "message" => $board_error !== "" ? $board_error : "Unable to create board."
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit();
    }
}


/* =========================================================
   BOARD / DELETE BOARD
   A board can be deleted only when it has no tasks.
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_board") {

    $delete_board_id = (int)($_POST["board_id"] ?? 0);
    $is_ajax_delete_request =
        (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") ||
        (strpos($_SERVER["HTTP_ACCEPT"] ?? "", "application/json") !== false);

    $delete_response = ["success" => false];

    if (!($is_admin || $is_user)) {
        $delete_response["message"] = "You do not have permission to delete a board.";
    }
    elseif ($delete_board_id <= 0) {
        $delete_response["message"] = "Invalid board.";
    }
    else {

        /* Do not silently delete tasks belonging to the board. */
        $task_count_stmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) AS task_count FROM tasks WHERE board_id = ?"
        );

        if (!$task_count_stmt) {
            $delete_response["message"] = "Database Error: " . mysqli_error($conn);
        }
        else {
            mysqli_stmt_bind_param($task_count_stmt, "i", $delete_board_id);
            mysqli_stmt_execute($task_count_stmt);
            $task_count_result = mysqli_stmt_get_result($task_count_stmt);
            $task_count_row = $task_count_result ? mysqli_fetch_assoc($task_count_result) : null;
            $task_count = (int)($task_count_row["task_count"] ?? 0);
            mysqli_stmt_close($task_count_stmt);

            if ($task_count > 0) {
                $delete_response["message"] =
                    "This board cannot be deleted because it contains " .
                    $task_count .
                    " task" . ($task_count === 1 ? "" : "s") .
                    ". Move or delete the tasks first.";
            }
            else {
                $delete_stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM boards WHERE id = ?"
                );

                if (!$delete_stmt) {
                    $delete_response["message"] = "Database Error: " . mysqli_error($conn);
                }
                else {
                    mysqli_stmt_bind_param($delete_stmt, "i", $delete_board_id);

                    if (mysqli_stmt_execute($delete_stmt) && mysqli_stmt_affected_rows($delete_stmt) > 0) {
                        $delete_response["success"] = true;
                        $delete_response["board_id"] = $delete_board_id;
                    }
                    else {
                        $delete_response["message"] =
                            mysqli_stmt_error($delete_stmt) ?: "Board not found or could not be deleted.";
                    }

                    mysqli_stmt_close($delete_stmt);
                }
            }
        }
    }

    if ($is_ajax_delete_request) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($delete_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    if (!empty($delete_response["success"])) {
        header("Location: index.php");
        exit();
    }

    $board_error = $delete_response["message"] ?? "Unable to delete board.";
}


/* =========================================================
   LOAD BOARDS
========================================================= */

$boards = [];

$board_result = mysqli_query(
    $conn,
    "SELECT id, name, description FROM boards ORDER BY id ASC"
);

if (!$board_result) {
    die("Database Error: " . mysqli_error($conn));
}

while ($board_row = mysqli_fetch_assoc($board_result)) {
    $boards[] = $board_row;
}

if ($selected_board_id === 0 && !empty($boards)) {
    $selected_board_id = (int)$boards[0]["id"];
}

$selected_board = null;

foreach ($boards as $board_row) {
    if ((int)$board_row["id"] === $selected_board_id) {
        $selected_board = $board_row;
        break;
    }
}

if ($selected_board === null && !empty($boards)) {
    $selected_board_id = (int)$boards[0]["id"];
    $selected_board = $boards[0];
}


/* =========================================================
   SEARCH / FILTER VALUES
========================================================= */

$search = $_GET["search"] ?? "";

$progress_filter = $_GET["progress"] ?? "all";


/* =========================================================
   BUILD QUERY
========================================================= */

$where = [];

$params = [];

$types = "";


/* =========================================================
   SEARCH TASK
========================================================= */

if ($search !== "") {

    $where[] = "task LIKE ?";

    $params[] = "%" . $search . "%";

    $types .= "s";

}


/* =========================================================
   PROGRESS FILTER
========================================================= */

if ($selected_board_id > 0) {

    $where[] = "board_id = ?";

    $params[] = $selected_board_id;

    $types .= "i";

}


/* =========================================================
   PROGRESS FILTER
========================================================= */

if (
    $progress_filter !== "" &&
    $progress_filter !== "all"
) {

    $where[] = "progress = ?";

    $params[] = $progress_filter;

    $types .= "s";

}


/* =========================================================
   WHERE SQL
========================================================= */

$where_sql = "";

if (!empty($where)) {

    $where_sql = "WHERE " . implode(" AND ", $where);

}


/* =========================================================
   GET TASKS
========================================================= */

$sql = "
    SELECT
        id,
        board_id,
        task,
        description,
        status,
        priority,
        progress,
        is_completed,
        addedDate,
        editedDate
    FROM tasks
    $where_sql
    ORDER BY id DESC
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database Error: " . mysqli_error($conn));

}


/* =========================================================
   BIND PARAMETERS
========================================================= */

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}


/* =========================================================
   EXECUTE
========================================================= */

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (!$result) {

    die("Database Error: " . mysqli_error($conn));

}


/* =========================================================
   SEPARATE TASKS
========================================================= */

$todo_tasks = [];

$in_progress_tasks = [];

$pending_tasks = [];

$review_tasks = [];

$done_tasks = [];


while ($row = mysqli_fetch_assoc($result)) {

    if ($row["progress"] === "Todo") {

        $todo_tasks[] = $row;

    }

    elseif ($row["progress"] === "In Progress") {

        $in_progress_tasks[] = $row;

    }

    elseif ($row["progress"] === "Pending") {

        $pending_tasks[] = $row;

    }

    elseif ($row["progress"] === "Review") {

        $review_tasks[] = $row;

    }

    elseif ($row["progress"] === "Done") {

        $done_tasks[] = $row;

    }

}


/* =========================================================
   PRIORITY CLASS
========================================================= */

function getPriorityClass($priority)
{

    switch ($priority) {

        case "High":

            return "priority-high";


        case "Medium":

            return "priority-medium";


        case "Low":

            return "priority-low";


        default:

            return "priority-low";

    }

}


/* =========================================================
   PROGRESS CLASS
========================================================= */

function getProgressClass($progress)
{

    switch ($progress) {

        case "Todo":

            return "progress-todo";


        case "In Progress":

            return "progress-progress";


        case "Pending":

            return "progress-pending";


        case "Review":

            return "progress-review";


        case "Done":

            return "progress-done";


        default:

            return "progress-todo";

    }

}


/* =========================================================
   DATE FORMAT
========================================================= */

function formatTaskDate($date)
{

    if (empty($date)) {

        return "";

    }


    $timestamp = strtotime($date);


    if (!$timestamp) {

        return "";

    }


    return date(
        "d M Y, h:i:s A",
        $timestamp
    );

}


/* =========================================================
   DRAG AND DROP STATUS UPDATE
   BOTH ADMIN AND USER ARE ALLOWED
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task_id = (int)($_POST["task_id"] ?? 0);

    $new_progress = $_POST["progress"] ?? "";


    $allowed_progress = [
        "Todo",
        "In Progress",
        "Pending",
        "Review",
        "Done"
    ];


    $is_ajax_progress_request =
        (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") ||
        (strpos($_SERVER["HTTP_ACCEPT"] ?? "", "application/json") !== false);

    $progress_response = ["success" => false];

    if (
        $task_id > 0 &&
        in_array(
            $new_progress,
            $allowed_progress,
            true
        )
    ) {

        $update_sql = "
            UPDATE tasks
            SET
                progress = ?,
                editedDate = NOW()
            WHERE id = ?
        ";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        if ($update_stmt) {
            mysqli_stmt_bind_param(
                $update_stmt,
                "si",
                $new_progress,
                $task_id
            );

            $update_ok = mysqli_stmt_execute($update_stmt);

            if ($update_ok) {
                $progress_response = [
                    "success" => true,
                    "task_id" => $task_id,
                    "progress" => $new_progress
                ];
            } else {
                /*
                 * IMPORTANT: read the statement error BEFORE closing the
                 * prepared statement. Calling mysqli_stmt_error() after
                 * mysqli_stmt_close() can lose the real MySQL error.
                 */
                $mysql_error = mysqli_stmt_error($update_stmt);
                $progress_response["message"] = "MySQL error while updating task progress: " . $mysql_error;
                $progress_response["mysql_error"] = $mysql_error;
            }

            mysqli_stmt_close($update_stmt);
        } else {
            $mysql_error = mysqli_error($conn);
            $progress_response["message"] = "MySQL prepare error while updating task progress: " . $mysql_error;
            $progress_response["mysql_error"] = $mysql_error;
        }
    } else {
        $progress_response["message"] = "Invalid task or progress value.";
    }

    if ($is_ajax_progress_request) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode($progress_response);
        exit();
    }

    if (!empty($progress_response["success"])) {
        $redirect_board_id = (int)($_POST["board_id"] ?? 0);
        header(
            "Location: index.php" .
            ($redirect_board_id > 0 ? "?board_id=" . $redirect_board_id : "")
        );
        exit();
    }

    header("Location: index.php");
    exit();

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

<title>Task Board - TODO APP</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<!-- Existing Style -->

<link
    rel="stylesheet"
    href="../assets/style.css"
>


<style>

    * {
        box-sizing: border-box;
    }


    body {
        margin: 0;
        background: #f5f7fb;
        color: #172b4d;
    }


    /* =====================================================
       NAVBAR
    ===================================================== */

    .navbar {
        min-height: 61px;
    }


    .navbar-brand {
        font-weight: 800;
        font-size: 25px;
        color: #172b4d !important;
        letter-spacing: 0.3px;
    }


    .navbar-brand::first-letter {
        color: #0d6efd;
    }


    .welcome-text {
        color: #52627a;
        font-size: 14px;
    }


    /* =====================================================
       USER ROLE BADGE
    ===================================================== */

    .role-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 65px;
        height: 32px;
        padding: 0 12px;
        border-radius: 20px;
        background: #e8f1ff;
        color: #1261b5;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }


    .logout-btn {
        background: #fff0f1;
        color: #dc3545;
        border: none;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 10px;
    }


    .logout-btn:hover {
        background: #ffe0e3;
        color: #dc3545;
    }


    /* =====================================================
       MAIN
    ===================================================== */

    .main-container {
        padding-top: 30px;
    }


    .board-heading {
        font-size: 23px;
        font-weight: 800;
        color: #172b4d;
        margin-bottom: 5px;
    }


    .board-subtitle {
        font-size: 13px;
        color: #61708a;
    }


    /* =====================================================
       TOP TOOLBAR
    ===================================================== */

    .board-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
    }


    .board-title-area {
        flex: 1;
    }


    .board-controls {
        display: flex;
        align-items: center;
        gap: 12px;
    }


    /* =====================================================
       SEARCH
    ===================================================== */

    .search-box {
        position: relative;
        width: 500px;
        display: flex;
        align-items: center;
        gap: 8px;
    }


    .search-input-wrapper {
        position: relative;
        flex: 1;
    }


    .search-box input {
        height: 48px;
        width: 100%;
        border: 1px solid #d6deea;
        border-radius: 8px;
        padding-left: 45px;
        padding-right: 15px;
        font-size: 14px;
        color: #172b4d;
        background: #ffffff;
    }


    .search-box input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
        outline: none;
    }


    .search-icon {
        position: absolute;
        left: 17px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 17px;
        z-index: 2;
        color: #5f6f87;
    }


    .search-btn,
    .clear-search-btn {
        height: 48px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        padding: 0 17px;
        white-space: nowrap;
    }


    .search-btn {
        background: #1473e6;
        border-color: #1473e6;
    }


    .search-btn:hover {
        background: #0967d5;
        border-color: #0967d5;
    }


    .clear-search-btn {
        background: #ffffff;
        border: 1px solid #d6deea;
        color: #52627a;
    }


    .clear-search-btn:hover {
        background: #f1f3f5;
        border-color: #c5cedb;
        color: #172b4d;
    }


    .task-filter {
        width: 163px;
    }


    .task-filter select {
        height: 48px;
        border: 1px solid #d6deea;
        border-radius: 8px;
        font-size: 14px;
        color: #172b4d;
        background-color: #ffffff;
        padding-left: 17px;
    }


    .task-filter select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
    }


    .add-board-btn {
        height: 48px;
        padding: 0 20px;
        border-radius: 8px;
        font-weight: 700;
    }


    /* =====================================================
       BOARD COLUMN CARDS
    ===================================================== */

    .board-card-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 2px;
    }

    /* BOARD CARDS USE THE SAME VISUAL LANGUAGE AS TASK CARDS */
    .board-card {
        position: relative;
        background: #ffffff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 0;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        border: 1px solid #dbe1e9;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        color: #172b4d;
        text-decoration: none;
        min-height: 76px;
    }

    .board-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 13px rgba(0,0,0,0.10);
    }

    .board-card.active {
        box-shadow: 0 0 0 2px rgba(105,65,165,0.18), 0 5px 13px rgba(0,0,0,0.10);
        border-color: #6941a5;
    }

    .board-card-name {
        font-weight: 800;
        font-size: 14px;
        color: #172b4d;
        padding-right: 36px;
        word-break: break-word;
        line-height: 1.4;
    }

    .board-card-description {
        font-size: 13px;
        color: #52627a;
        margin-top: 5px;
        padding-right: 8px;
        word-break: break-word;
        line-height: 1.4;
    }

    .board-card-menu {
        position: absolute;
        top: 9px;
        right: 9px;
        z-index: 5;
    }

    .board-card-menu-button {
        width: 30px;
        height: 30px;
        border: 1px solid #d6deea;
        border-radius: 7px;
        background: #ffffff;
        color: #52627a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
    }

    .board-card-menu-button:hover {
        background: #f3f6fa;
        color: #172b4d;
        border-color: #bfc9d8;
    }

    .board-card-menu-content {
        position: absolute;
        top: 35px;
        right: 0;
        min-width: 120px;
        background: #ffffff;
        border: 1px solid #dbe1e9;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.14);
        padding: 5px;
        display: none;
    }

    .board-card-menu-content.show {
        display: block;
    }

    .board-card-menu-delete {
        width: 100%;
        border: 0;
        background: transparent;
        color: #dc3545;
        text-align: left;
        padding: 8px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }

    .board-card-menu-delete:hover {
        background: #fff1f1;
    }

    .task-board-name-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 7px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #f1edfb;
        color: #6941a5;
        font-size: 11px;
        font-weight: 700;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    .board-empty {
        color: #6b7280;
        background: rgba(255,255,255,0.7);
        border: 1px dashed #cfd7e3;
        border-radius: 10px;
        padding: 14px;
        font-size: 13px;
        line-height: 1.5;
    }

    .add-task-btn {
        height: 48px;
        padding: 0 20px;
        border-radius: 8px;
        font-weight: 700;
        background: #1473e6;
        border-color: #1473e6;
    }


    .add-task-btn:hover {
        background: #0967d5;
        border-color: #0967d5;
    }


    /* =====================================================
       BOARD
    ===================================================== */

    .board-wrapper {
        width: 100%;
        height: calc(100vh - 205px);
        max-height: calc(100vh - 205px);
        overflow-x: auto;
        overflow-y: auto;
        overscroll-behavior: contain;
        overscroll-behavior-x: contain;
        overscroll-behavior-y: contain;
        padding: 0 0 15px 0;
        -webkit-overflow-scrolling: touch;
        scrollbar-gutter: stable both-edges;
        touch-action: pan-x pan-y;
    }


    .task-board {
        display: grid;
        grid-template-columns: repeat(6, minmax(360px, 360px));
        gap: 18px;
        width: max-content;
        min-width: max-content;
        min-height: calc(100% + 80px);
        align-items: start;
        padding-bottom: 20px;
    }


    /* =====================================================
       COLUMNS
    ===================================================== */

    .task-column {
        width: 360px;
        min-width: 360px;
        max-width: 360px;
        border-radius: 9px;
        padding: 14px;
        height: calc(100vh - 225px);
        display: flex;
        flex-direction: column;
        transition: 0.2s;
        border: 1px solid #dbe2ec;
    }


    .task-column:nth-child(1) {
        background: #eef3f9;
    }


    .task-column:nth-child(2) {
        background: #eef4fb;
    }


    .task-column:nth-child(3) {
        background: #fff8e9;
    }


    .task-column:nth-child(4) {
        background: #eef9f2;
    }


    .task-column.drag-over {
        box-shadow: inset 0 0 0 2px #0d6efd;
    }


    .column-header {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 9px;
        margin-bottom: 13px;
        padding: 7px 5px;
        flex-shrink: 0;
    }


    .column-title {
        font-weight: 800;
        font-size: 14px;
        letter-spacing: 0.2px;
    }


    .task-column:nth-child(1) .column-title {
        color: #172b4d;
    }


    .task-column:nth-child(2) .column-title {
        color: #1261b5;
    }


    .task-column:nth-child(3) .column-title {
        color: #9b6900;
    }


    .task-column:nth-child(4) .column-title {
        color: #098443;
    }


    .task-column:nth-child(5) {
        background: #f4f0fb;
    }


    .task-column:nth-child(5) .column-title {
        color: #6941a5;
    }


    /* BOARD is the 6th column and must stay after DONE. */
    .task-column.board-task-column {
        background: #f7f3ff;
    }


    .task-column.board-task-column .column-title {
        color: #6941a5;
    }


    .column-count {
        font-size: 14px;
        color: #61708a;
        font-weight: 500;
    }


    /* =====================================================
       ADMIN COLUMN PLUS BUTTON
    ===================================================== */

    .column-add-btn {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border: 1px solid #d6deea;
        border-radius: 50%;
        background: #ffffff;
        color: #1473e6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        margin-left: auto;
        transition:
            background 0.2s ease,
            color 0.2s ease,
            border-color 0.2s ease,
            transform 0.2s ease;
    }


    .column-add-btn:hover {
        background: #1473e6;
        color: #ffffff;
        border-color: #1473e6;
        transform: scale(1.08);
    }


    .column-add-btn:active {
        transform: scale(0.96);
    }


    .task-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 4px;
        scrollbar-gutter: stable;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }


    /* =====================================================
       TASK CARD
    ===================================================== */

    .task-card {
        position: relative;
        background: #ffffff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: grab;
        touch-action: pan-x pan-y;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        border: 1px solid #dbe1e9;
        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease,
            opacity 0.2s ease;
    }


    .task-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 13px rgba(0,0,0,0.10);
    }


    .task-card:active {
        cursor: grabbing;
    }


    .task-card.dragging {
        opacity: 0.45;
        transform: rotate(2deg);
    }


    .task-card.touch-dragging {
        opacity: 0.85;
        transform: scale(1.03);
        z-index: 9999;
        box-shadow: 0 15px 35px rgba(0,0,0,0.22);
    }


    .task-card.touch-source {
        opacity: 0.35;
    }


    .task-card.touch-ready {
        box-shadow: 0 0 0 2px #0d6efd;
    }


    /* =====================================================
       PRIORITY LEFT BORDER
    ===================================================== */

    .priority-high {
        border-left: 4px solid #dc3545;
    }


    .priority-medium {
        border-left: 4px solid #ffb900;
    }


    .priority-low {
        border-left: 4px solid #22a866;
    }


    /* =====================================================
       TASK CONTENT
    ===================================================== */

    .task-title {
        font-weight: 800;
        font-size: 14px;
        color: #172b4d;
        padding-right: 28px;
        word-break: break-word;
        line-height: 1.4;
    }


    .task-description {
        font-size: 13px;
        color: #52627a;
        margin-top: 5px;
        word-break: break-word;
        line-height: 1.4;
    }


    .task-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 12px;
    }


    .priority-badge,
    .progress-badge,
    .completion-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }


    .priority-badge {
        background: #d7f5e7;
        color: #087944;
    }


    .priority-high .priority-badge {
        background: #f8d7da;
        color: #a61e2b;
    }


    .priority-medium .priority-badge {
        background: #fff0b8;
        color: #8a6200;
    }


    .priority-low .priority-badge {
        background: #d7f5e7;
        color: #087944;
    }


    .priority-badge i {
        display: none;
    }


    /* =====================================================
       PROGRESS BADGES
    ===================================================== */

    .progress-todo {
        background: #6c757d;
        color: #ffffff;
    }


    .progress-progress {
        background: #1473e6;
        color: #ffffff;
    }


    .progress-review {
        background: #f1c400;
        color: #1e1e1e;
    }


    .progress-done {
        background: #098443;
        color: #ffffff;
    }


    /* =====================================================
       COMPLETION BADGES
    ===================================================== */

    .completion-badge.completed {
        background: #d6eadd;
        color: #246044;
    }


    .completion-badge.incomplete {
        background: #f8d7da;
        color: #a42835;
    }


    .completion-badge i {
        margin-right: 4px;
    }


    /* =====================================================
       DATE
    ===================================================== */

    .task-date {
        font-size: 11px;
        color: #65748b;
        margin-top: 12px;
    }


    .task-date i {
        margin-right: 3px;
    }


    /* =====================================================
       THREE DOT MENU
    ===================================================== */

    .task-menu {
        position: absolute;
        top: 10px;
        right: 10px;
    }


    .task-menu-button {
        border: 0;
        background: transparent;
        color: #6a7a91;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        font-size: 19px;
        line-height: 1;
    }


    .task-menu-button:hover {
        background: #f1f3f5;
        color: #172b4d;
    }


    .task-menu-content {
        display: none;
        position: absolute;
        right: 0;
        top: 31px;
        min-width: 130px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        padding: 5px;
        z-index: 10000;
        border: 1px solid #e1e6ed;
    }


    .task-menu-content.show {
        display: block;
    }


    .task-menu-content a {
        display: block;
        text-decoration: none;
        padding: 8px 10px;
        color: #212529;
        font-size: 13px;
        border-radius: 6px;
    }


    .task-menu-content a:hover {
        background: #f1f3f5;
    }


    .task-menu-content .delete-link {
        color: #dc3545;
    }


    /* =====================================================
       TASK DETAILS MODAL
    ===================================================== */

    .task-details-modal .modal-dialog {
        max-width: 760px;
    }


    .task-details-modal .modal-content {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 15px 45px rgba(0,0,0,0.20);
    }


    .task-details-modal .modal-header {
        padding: 18px 22px;
        background: #ffffff;
        border-bottom: 1px solid #e8edf3;
    }


    .task-details-modal .modal-title {
        font-size: 19px;
        font-weight: 800;
        color: #172b4d;
    }


    .task-details-modal .modal-header .btn-close {
        margin: 0;
        padding: 8px;
    }


    .task-details-modal .modal-body {
        padding: 22px;
        background: #ffffff;
        max-height: 75vh;
        overflow-y: auto;
    }


    .task-detail-title {
        font-size: 20px;
        font-weight: 800;
        color: #172b4d;
        margin-bottom: 8px;
        word-break: break-word;
    }


    .task-detail-description {
        font-size: 14px;
        line-height: 1.6;
        color: #52627a;
        background: #f7f9fc;
        border: 1px solid #e5eaf1;
        border-radius: 9px;
        padding: 13px 14px;
        margin-bottom: 20px;
        white-space: pre-wrap;
        word-break: break-word;
    }


    .task-detail-description.empty {
        color: #8a96a8;
        font-style: italic;
    }


    .task-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }


    .task-detail-item {
        border: 1px solid #e2e7ee;
        background: #ffffff;
        border-radius: 9px;
        padding: 13px 14px;
        min-width: 0;
    }


    .task-detail-label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
    }


    .task-detail-value {
        display: block;
        font-size: 14px;
        font-weight: 700;
        color: #172b4d;
        word-break: break-word;
    }


    .task-detail-value.status-active {
        color: #198754;
    }


    .task-detail-value.status-inactive {
        color: #dc3545;
    }


    .task-detail-value.complete {
        color: #198754;
    }


    .task-detail-value.incomplete {
        color: #dc3545;
    }


    /* =====================================================
       COMMENTS
    ===================================================== */

    .task-comments-section h6,
    .task-attachments-section h6 {
        color: #172b4d;
        font-size: 15px;
    }


    .task-comments-list {
        max-height: 300px;
        overflow-y: auto;
        padding-right: 3px;
    }


    .task-comment-item {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
        background: #f8fafc;
    }


    .task-comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }


    .task-comment-user {
        font-weight: 600;
        font-size: 14px;
        color: #172b4d;
    }


    .task-comment-date {
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap;
    }


    .task-comment-text {
        font-size: 14px;
        color: #52627a;
        white-space: pre-wrap;
        word-break: break-word;
    }


    #taskCommentInput {
        resize: vertical;
        border: 1px solid #d6deea;
        border-radius: 8px;
        font-size: 14px;
    }


    #taskCommentInput:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
    }


    #addCommentButton {
        border-radius: 8px;
        font-weight: 700;
    }


    /* =====================================================
       ATTACHMENTS
    ===================================================== */

    .task-attachments-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }


    .task-attachment-item {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px;
        background: #fff;
    }


    .task-attachment-preview {
        display: block;
        width: 100%;
        max-height: 220px;
        object-fit: contain;
        border-radius: 8px;
        background: #f8fafc;
        margin-bottom: 8px;
    }


    .task-attachment-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }


    .task-attachment-name {
        font-size: 13px;
        font-weight: 600;
        color: #172b4d;
        word-break: break-word;
    }


    .task-attachment-date {
        color: #6b7280;
        font-size: 11px;
        margin-top: 2px;
    }


    .task-attachment-actions {
        display: flex;
        gap: 5px;
        flex-shrink: 0;
    }


    .task-attachment-actions a,
    .task-attachment-actions button {
        border: 0;
        background: transparent;
        padding: 5px 8px;
        border-radius: 6px;
        text-decoration: none;
        color: #52627a;
    }


    .task-attachment-actions a:hover,
    .task-attachment-actions button:hover {
        background: #f1f5f9;
    }


    .attachment-file-icon {
        width: 55px;
        height: 55px;
        border-radius: 9px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }


    .attachment-file-icon i {
        font-size: 28px;
        color: #1473e6;
    }


    /* =====================================================
       MODAL FOOTER
    ===================================================== */

    .task-details-modal .modal-footer {
        padding: 14px 22px;
        border-top: 1px solid #e8edf3;
        background: #ffffff;
    }


    .task-details-close-btn {
        min-width: 90px;
        border-radius: 8px;
        font-weight: 700;
    }


    /* =====================================================
       TRELLO-STYLE CARD VIEW
    ===================================================== */

    .trello-card-dialog {
        max-width: 900px;
    }


    .trello-card-header {
        align-items: flex-start;
        padding: 20px 24px 16px;
    }


    .trello-card-header-text {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }


    .trello-card-eyebrow {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #61708a;
    }


    .trello-card-modal-title {
        display: none;
    }


    .trello-card-body {
        padding: 22px 24px 26px;
        background: #ffffff;
        max-height: 75vh;
        overflow-y: auto;
    }


    .trello-card-layout {
        display: grid;
        grid-template-columns: 1fr 230px;
        gap: 28px;
        align-items: start;
    }


    .trello-card-main {
        min-width: 0;
    }


    .trello-card-title-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 22px;
    }


    .trello-card-title-icon {
        font-size: 20px;
        color: #7b8aa3;
        margin-top: 3px;
        flex-shrink: 0;
    }


    .trello-card-title-row .task-detail-title {
        margin-bottom: 0;
    }


    .trello-card-section {
        margin-bottom: 26px;
    }


    .trello-card-section:last-child {
        margin-bottom: 0;
    }


    .trello-card-section-heading {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 14px;
        color: #172b4d;
        margin-bottom: 10px;
    }


    .trello-card-section-heading-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }


    .trello-card-section-heading-row .trello-card-section-heading {
        margin-bottom: 0;
    }


    .trello-comment-composer {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }


    .trello-comment-composer-input {
        flex: 1;
        min-width: 0;
    }


    .trello-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #dfe6f0;
        color: #52627a;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
    }


    /* =====================================================
       SIDEBAR
    ===================================================== */

    .trello-card-sidebar {
        border-left: 1px solid #e5eaf1;
        padding-left: 20px;
    }


    .trello-sidebar-label {
        font-size: 11px;
        font-weight: 800;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 12px;
    }


    .trello-sidebar-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 14px;
    }


    .trello-sidebar-item-label {
        font-size: 11px;
        color: #8a96a8;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 5px;
    }


    .trello-sidebar-divider {
        height: 1px;
        background: #e5eaf1;
        margin: 6px 0 16px;
    }


    .trello-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        background: #e8f1ff;
        color: #1261b5;
    }


    .trello-chip-date {
        background: #f1f3f5;
        color: #495057;
        font-weight: 600;
        font-size: 12.5px;
    }


    .trello-chip.complete {
        background: #d6eadd;
        color: #246044;
    }


    .trello-chip.incomplete {
        background: #f8d7da;
        color: #a42835;
    }


    .trello-chip.status-active {
        background: #d6eadd;
        color: #246044;
    }


    .trello-chip.status-inactive {
        background: #f8d7da;
        color: #a42835;
    }


    /* =====================================================
       INLINE FIELD EDITING (VIEW CARD)
    ===================================================== */

    .task-detail-field {
        display: flex;
        align-items: center;
        gap: 8px;
    }


    .progress-pending {
        background: #fff3cd;
        color: #8a6d1d;
    }


    .task-details-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }


    .task-details-edit-btn,
    .task-details-back-btn {
        min-height: 38px;
        border-radius: 8px;
        font-weight: 700;
        padding: 7px 13px;
    }


    .task-details-edit-footer {
        justify-content: flex-end;
    }


    .task-details-footer-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        width: 100%;
    }


    .task-details-save-btn,
    .task-details-cancel-btn {
        min-width: 96px;
        min-height: 42px;
        border-radius: 9px;
        font-weight: 700;
    }


    .task-field-edit-btn {
        border: 1px solid #e1e6ed;
        background: #ffffff;
        color: #6a7a91;
        width: 26px;
        height: 26px;
        border-radius: 6px;
        font-size: 12px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }


    .task-field-edit-btn:hover {
        background: #f1f3f5;
        color: #172b4d;
    }


    .task-field-edit-btn.text-edit-btn {
        width: auto;
        padding: 4px 10px;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
    }


    .task-detail-select {
        font-size: 13px;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #d7dee8;
        color: #172b4d;
        background: #ffffff;
        max-width: 100%;
    }


    .task-field-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin: -14px 0 20px;
    }


    @media (max-width: 767px) {

        .trello-card-layout {
            grid-template-columns: 1fr;
        }


        .trello-card-sidebar {
            border-left: none;
            border-top: 1px solid #e5eaf1;
            padding-left: 0;
            padding-top: 18px;
        }

    }


    /* =====================================================
       DRAG MESSAGES
    ===================================================== */

    .drag-hint {
        position: fixed;
        left: 50%;
        bottom: 25px;
        transform: translateX(-50%);
        background: #212529;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 30px;
        font-size: 13px;
        z-index: 20000;
        display: none;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }


    .drag-success {
        position: fixed;
        left: 50%;
        bottom: 25px;
        transform: translateX(-50%);
        background: #198754;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 30px;
        font-size: 13px;
        z-index: 20000;
        display: none;
    }


    .drop-indicator {
        height: 5px;
        border-radius: 5px;
        background: #0d6efd;
        margin: 5px 0 10px;
        display: none;
    }


    /* =====================================================
       RESPONSIVE
    ===================================================== */

    @media (max-width: 1250px) {

        .search-box {
            width: 430px;
        }

    }


    @media (max-width: 1100px) {

        .task-board {
            /* Keep all six columns in one horizontal row so the
               outer board can be scrolled left/right on touch. */
            grid-template-columns: repeat(6, minmax(360px, 360px));
            width: max-content;
            min-width: max-content;
        }


        .search-box {
            width: 400px;
        }

    }


    @media (max-width: 950px) {

        .board-toolbar {
            flex-direction: column;
            align-items: stretch;
        }


        .board-title-area {
            width: 100%;
        }


        .board-controls {
            width: 100%;
            flex-wrap: wrap;
        }


        .search-box {
            flex: 1;
            width: auto;
            min-width: 400px;
        }

    }


    @media (max-width: 767px) {

        .navbar-brand {
            font-size: 21px;
        }


        .welcome-text {
            display: none;
        }


        .main-container {
            padding-top: 20px;
        }


        .board-toolbar {
            flex-direction: column;
            align-items: stretch;
        }


        .board-title-area {
            width: 100%;
        }


        .board-controls {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }


        .search-box {
            width: 100%;
            min-width: 0;
            display: grid;
            grid-template-columns: 1fr auto auto;
        }


        .search-input-wrapper {
            min-width: 0;
        }


        .search-btn,
        .clear-search-btn {
            padding: 0 13px;
        }


        .task-filter {
            width: 100%;
        }


        .add-task-btn,
        .add-board-btn {
            width: 100%;
        }


        .task-board {
            display: flex;
            gap: 14px;
            width: max-content;
            min-width: max-content;
        }


        .task-column {
            width: 340px;
            min-width: 340px;
            max-width: 340px;
            height: calc(100vh - 220px);
        }


        .board-wrapper {
            height: calc(100vh - 180px);
            max-height: calc(100vh - 180px);
            overflow-x: auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }


        .task-card {
            padding: 14px;
        }


        .task-details-modal .modal-dialog {
            margin: 10px;
        }


        .task-details-modal .modal-body {
            padding: 17px;
        }


        .task-detail-grid {
            grid-template-columns: 1fr;
        }


        .task-comment-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 3px;
        }


        .task-attachment-info {
            align-items: flex-start;
            flex-direction: column;
        }


        .task-attachment-actions {
            width: 100%;
        }

    }


    @media (max-width: 500px) {

        .search-box {
            grid-template-columns: 1fr;
        }


        .search-btn,
        .clear-search-btn {
            width: 100%;
        }

    }


    @media (max-width: 380px) {

        .task-column {
            width: 310px;
            min-width: 310px;
            max-width: 310px;
        }

    }



    /* =====================================================
       CARD DETAILS UI - POLISHED LAYOUT
       Additive styling only: existing board/card functionality
       and existing element IDs are preserved.
    ===================================================== */

    .task-details-modal .modal-dialog.trello-card-dialog {
        width: calc(100% - 32px);
        max-width: 1140px;
        max-height: 92vh;
        margin: 1rem auto;
    }

    .task-details-modal .trello-card-content {
        height: 92vh;
        max-height: 92vh;
        border: 1px solid #e1e7ef;
        border-radius: 12px;
        background: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 18px 55px rgba(23, 43, 77, .18);
    }

    .task-details-modal .trello-card-header {
        min-height: 67px;
        padding: 14px 22px;
        flex: 0 0 auto;
        border-bottom: 1px solid #e5eaf1;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .task-details-modal .trello-card-eyebrow {
        font-size: 11px;
        line-height: 1;
        color: #60718b;
        letter-spacing: .45px;
    }

    .task-details-modal .trello-card-eyebrow i {
        color: #5f6f87;
        font-size: 12px;
    }

    .task-details-modal .trello-card-header .btn-close {
        width: 26px;
        height: 26px;
        padding: 4px;
        opacity: .62;
        background-size: 18px;
    }

    .task-details-modal .trello-card-header .btn-close:hover {
        opacity: 1;
    }

    .task-details-modal .trello-card-body {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 28px 26px 30px;
        background: #fff;
        scrollbar-width: thin;
    }

    .task-details-modal .trello-card-layout {
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 40px;
        align-items: stretch;
        min-height: 100%;
    }

    .task-details-modal .trello-card-main {
        min-width: 0;
        padding-right: 1px;
    }

    .task-details-modal .trello-card-title-row {
        gap: 13px;
        margin: 0 0 25px;
    }

    .task-details-modal .trello-card-title-icon {
        margin-top: 4px;
        color: #6c7d96;
        font-size: 21px;
    }

    .task-details-modal .task-detail-title {
        margin: 0;
        font-size: 24px;
        line-height: 1.25;
        font-weight: 800;
        color: #132b4f;
    }

    .task-details-modal .trello-card-section {
        margin-bottom: 29px;
    }

    .task-details-modal .trello-card-section-heading,
    .task-details-modal .trello-card-section-heading-row .trello-card-section-heading {
        margin-bottom: 12px;
        font-size: 15px;
        line-height: 1.2;
        color: #18355d;
    }

    .task-details-modal .trello-card-section-heading i {
        font-size: 16px;
        color: #46698f;
    }

    .task-details-modal .task-detail-description {
        margin-bottom: 0;
        min-height: 62px;
        padding: 16px 17px;
        border: 1px solid #dce4ee;
        border-radius: 11px;
        background: #f7f9fc;
        color: #4f6584;
        font-size: 15px;
        line-height: 1.6;
    }

    .task-details-modal .trello-comment-composer {
        gap: 12px;
    }

    .task-details-modal .trello-avatar {
        width: 42px;
        height: 42px;
        background: #e5ebf4;
        color: #5a6f8b;
        font-size: 17px;
    }

    .task-details-modal #taskCommentInput {
        min-height: 60px;
        padding: 11px 14px;
        border-color: #ccd8e7;
        border-radius: 10px;
        color: #233b5d;
        background: #fff;
        font-size: 15px;
        box-shadow: none;
    }

    .task-details-modal #taskCommentInput:focus {
        border-color: #6da2e8;
        box-shadow: 0 0 0 3px rgba(13,110,253,.08);
    }

    .task-details-modal #addCommentButton {
        min-width: 96px;
        min-height: 42px;
        padding: 8px 15px;
        border-radius: 10px;
        background: #2f80ed;
        border-color: #2f80ed;
        box-shadow: 0 7px 16px rgba(47,128,237,.18);
    }

    .task-details-modal #addCommentButton:hover {
        background: #246fd1;
        border-color: #246fd1;
    }

    .task-details-modal .task-comments-list {
        max-height: none;
        overflow: visible;
        padding: 0;
    }

    .task-details-modal .task-comment-item {
        padding: 14px 15px;
        margin-bottom: 10px;
        border-color: #dfe6ef;
        border-radius: 11px;
        background: #f8fafc;
    }

    .task-details-modal .task-comment-user {
        font-size: 14px;
        color: #18355d;
    }

    .task-details-modal .task-comment-date {
        font-size: 12px;
        color: #74839a;
    }

    .task-details-modal .task-comment-text {
        font-size: 14px;
        line-height: 1.55;
        color: #4f6584;
    }

    .task-details-modal .trello-card-section-heading-row {
        margin-bottom: 12px;
    }

    .task-details-modal .trello-card-section-heading-row > label {
        margin-left: auto;
        padding: 4px 9px !important;
        border: 0 !important;
        background: transparent !important;
        color: #526b89 !important;
        font-weight: 600;
        box-shadow: none !important;
    }

    .task-details-modal .trello-card-section-heading-row > label:hover {
        color: #1473e6 !important;
        background: #f1f5f9 !important;
    }

    .task-details-modal .task-attachments-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .task-details-modal .task-attachment-item {
        min-width: 0;
        padding: 10px;
        border-color: #dfe6ef;
        border-radius: 10px;
        background: #fff;
    }

    .task-details-modal .task-attachment-preview {
        max-height: 145px;
        margin-bottom: 9px;
        border: 1px solid #edf1f6;
        background: #f7f9fc;
    }

    .task-details-modal .attachment-file-icon {
        width: 52px;
        height: 52px;
        margin-bottom: 8px;
    }

    .task-details-modal .task-attachment-name {
        font-size: 13px;
    }

    .task-details-modal .trello-card-sidebar {
        border-left: 1px solid #dfe6ef;
        padding-left: 25px;
        min-width: 0;
    }

    .task-details-modal .trello-sidebar-label {
        margin-bottom: 20px;
        font-size: 12px;
        color: #687a94;
        letter-spacing: .35px;
    }

    .task-details-modal .trello-sidebar-item {
        gap: 7px;
        margin-bottom: 18px;
    }

    .task-details-modal .trello-sidebar-item-label {
        font-size: 12px;
        color: #8794a8;
        letter-spacing: .25px;
    }

    .task-details-modal .trello-chip {
        min-height: 34px;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 14px;
        background: #e9f2ff;
        color: #1261b5;
    }

    .task-details-modal .trello-chip-date {
        width: fit-content;
        max-width: 100%;
        background: #f0f2f4;
        color: #566272;
        font-size: 12.5px;
        white-space: normal;
        text-align: left;
    }

    .task-details-modal .trello-chip.complete,
    .task-details-modal .trello-chip.status-active {
        background: #d9eee2;
        color: #246044;
    }

    .task-details-modal .trello-chip.incomplete,
    .task-details-modal .trello-chip.status-inactive {
        background: #f9e0e3;
        color: #a42835;
    }

    .task-details-modal .trello-sidebar-divider {
        margin: 6px 0 20px;
        background: #dfe6ef;
    }

    .task-details-modal .modal-footer {
        flex: 0 0 auto;
        min-height: 66px;
        padding: 10px 14px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .task-details-modal .task-details-close-btn {
        min-width: 112px;
        min-height: 43px;
        border: 0;
        border-radius: 9px;
        background: #687583;
        font-size: 14px;
        font-weight: 700;
    }

    .task-details-modal .task-details-close-btn:hover {
        background: #566270;
    }

    @media (max-width: 767px) {
        .task-details-modal .modal-dialog.trello-card-dialog {
            width: calc(100% - 16px);
            margin: .5rem auto;
            max-height: 96vh;
        }

        .task-details-modal .trello-card-content {
            height: 96vh;
            max-height: 96vh;
        }

        .task-details-modal .trello-card-body {
            padding: 22px 16px 24px;
        }

        .task-details-modal .trello-card-layout {
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .task-details-modal .trello-card-sidebar {
            border-left: 0;
            border-top: 1px solid #dfe6ef;
            padding-left: 0;
            padding-top: 22px;
        }

        .task-details-modal .task-attachments-list {
            grid-template-columns: 1fr;
        }

        .task-details-modal .task-detail-title {
            font-size: 21px;
        }
    }


</style>

</head>

<body>

<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg bg-white border-bottom">

<div class="container-fluid px-3 px-md-4">

    <a
        class="navbar-brand"
        href="index.php"
    >
        ☑ TODO APP
    </a>


    <div class="d-flex align-items-center">

        <span class="welcome-text me-3">

            Welcome,

            <strong>
                <?= htmlspecialchars($_SESSION["user_name"] ?? "User") ?>
            </strong>

        </span>


        <span class="role-badge me-3">

            <?= htmlspecialchars($display_role) ?>

        </span>


        <a
            href="../auth/logout.php"
            class="btn logout-btn"
        >
            Logout
        </a>

    </div>

</div>

</nav>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="container-fluid px-3 px-md-4 main-container">


<div class="board-toolbar">


    <div class="board-title-area">

        <div class="board-heading">
            Task Board
        </div>


        <div class="board-subtitle">
            Drag and drop tasks between columns to update status
        </div>

    </div>


    <div class="board-controls">


        <form
            method="GET"
            class="search-box"
        >

            <div class="search-input-wrapper">

                <i class="bi bi-search search-icon"></i>


                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search tasks..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <input
                    type="hidden"
                    name="progress"
                    value="<?= htmlspecialchars($progress_filter) ?>"
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary search-btn"
            >
                <i class="bi bi-search"></i>
                Search
            </button>


            <a
                href="?progress=<?= urlencode($progress_filter) ?>"
                class="btn clear-search-btn"
            >
                <i class="bi bi-x-lg"></i>
                Clear
            </a>

        </form>


        <div class="task-filter">

            <form
                method="GET"
                id="progressFilterForm"
            >

                <input
                    type="hidden"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select
                    name="progress"
                    class="form-select"
                    onchange="document.getElementById('progressFilterForm').submit();"
                >

                    <option
                        value="all"
                        <?= $progress_filter === "all" ? "selected" : "" ?>
                    >
                        All Tasks
                    </option>


                    <option
                        value="Todo"
                        <?= $progress_filter === "Todo" ? "selected" : "" ?>
                    >
                        TODO
                    </option>


                    <option
                        value="In Progress"
                        <?= $progress_filter === "In Progress" ? "selected" : "" ?>
                    >
                        IN PROGRESS
                    </option>


                    <option
                        value="Pending"
                        <?= $progress_filter === "Pending" ? "selected" : "" ?>
                    >
                        PENDING
                    </option>


                    <option
                        value="Review"
                        <?= $progress_filter === "Review" ? "selected" : "" ?>
                    >
                        REVIEW
                    </option>


                    <option
                        value="Done"
                        <?= $progress_filter === "Done" ? "selected" : "" ?>
                    >
                        DONE
                    </option>

                </select>

            </form>

        </div>


        <?php if ($is_admin || $is_user): ?>

            <button
                type="button"
                class="btn btn-primary add-board-btn"
                id="addBoardBtn"
            >
                + Add Board
            </button>

        <?php endif; ?>


    </div>

</div>


<!-- =========================================================
     BOARD
========================================================= -->

<div class="board-wrapper">

<div class="task-board">


<!-- =========================================================
     TODO
========================================================= -->

<div
    class="task-column"
    data-progress="Todo"
>

    <div class="column-header">

        <span class="column-title">
            TODO
        </span>


        <span class="column-count">
            <?= count($todo_tasks) ?>
        </span>


        <?php if ($is_admin): ?>

            <a
                href="add.php?progress=Todo"
                class="column-add-btn"
                title="Add task to TODO"
                aria-label="Add task to TODO"
            >
                <i class="bi bi-plus"></i>
            </a>

        <?php endif; ?>

    </div>


    <div class="task-list">

        <?php foreach ($todo_tasks as $task): ?>

            <div
                class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                draggable="true"
                data-task-id="<?= (int)$task["id"] ?>"
                data-task-title="<?= htmlspecialchars($task["task"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-description="<?= htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                data-task-board-name="<?= htmlspecialchars($selected_board["name"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                data-task-priority="<?= htmlspecialchars($task["priority"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-progress="<?= htmlspecialchars($task["progress"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-completed="<?= ((int)$task["is_completed"] === 1) ? "Complete" : "Incomplete" ?>"
                data-task-status="<?= ((int)$task["status"] === 1) ? "Active" : "Inactive" ?>"
                data-task-added="<?= htmlspecialchars(formatTaskDate($task["addedDate"]), ENT_QUOTES, 'UTF-8') ?>"
                data-task-edited="<?= htmlspecialchars(formatTaskDate($task["editedDate"]), ENT_QUOTES, 'UTF-8') ?>"
            >


                <div class="task-title">

                    <?= htmlspecialchars($task["task"]) ?>

                </div>

                <?php if ($selected_board): ?>
                    <div class="task-board-name-badge">
                        <i class="bi bi-kanban"></i>
                        <?= htmlspecialchars($selected_board["name"]) ?>
                    </div>
                <?php endif; ?>


                <?php if (!empty($task["description"])): ?>

                    <div class="task-description">

                        <?= nl2br(htmlspecialchars($task["description"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-meta">

                    <span class="priority-badge">
                        <?= htmlspecialchars($task["priority"]) ?>
                    </span>


                    <span
                        class="progress-badge <?= htmlspecialchars(getProgressClass($task["progress"])) ?>"
                    >
                        <?= htmlspecialchars($task["progress"]) ?>
                    </span>


                    <?php if ((int)$task["is_completed"] === 1): ?>

                        <span class="completion-badge completed">

                            <i class="bi bi-check-circle-fill"></i>

                            Completed

                        </span>

                    <?php else: ?>

                        <span class="completion-badge incomplete">

                            <i class="bi bi-circle"></i>

                            Incomplete

                        </span>

                    <?php endif; ?>

                </div>


                <?php if (!empty($task["editedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["editedDate"])) ?>

                    </div>

                <?php elseif (!empty($task["addedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["addedDate"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-menu">

                    <button
                        type="button"
                        class="task-menu-button"
                        aria-label="Task menu"
                    >
                        ⋮
                    </button>


                    <div class="task-menu-content">


                        <a
                            href="view.php?id=<?= (int)$task["id"] ?>"
                            class="view-link"
                        >
                            View
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                Delete
                            </a>

                        <?php endif; ?>

                    </div>

                </div>


            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- =========================================================
     IN PROGRESS
========================================================= -->

<div
    class="task-column"
    data-progress="In Progress"
>

    <div class="column-header">

        <span class="column-title">
            IN PROGRESS
        </span>


        <span class="column-count">
            <?= count($in_progress_tasks) ?>
        </span>


        <?php if ($is_admin): ?>

            <a
                href="add.php?progress=In%20Progress"
                class="column-add-btn"
                title="Add task to IN PROGRESS"
                aria-label="Add task to IN PROGRESS"
            >
                <i class="bi bi-plus"></i>
            </a>

        <?php endif; ?>

    </div>


    <div class="task-list">

        <?php foreach ($in_progress_tasks as $task): ?>

            <div
                class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                draggable="true"
                data-task-id="<?= (int)$task["id"] ?>"
                data-task-title="<?= htmlspecialchars($task["task"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-description="<?= htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                data-task-priority="<?= htmlspecialchars($task["priority"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-progress="<?= htmlspecialchars($task["progress"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-completed="<?= ((int)$task["is_completed"] === 1) ? "Complete" : "Incomplete" ?>"
                data-task-status="<?= ((int)$task["status"] === 1) ? "Active" : "Inactive" ?>"
                data-task-added="<?= htmlspecialchars(formatTaskDate($task["addedDate"]), ENT_QUOTES, 'UTF-8') ?>"
                data-task-edited="<?= htmlspecialchars(formatTaskDate($task["editedDate"]), ENT_QUOTES, 'UTF-8') ?>"
            >

                <div class="task-title">

                    <?= htmlspecialchars($task["task"]) ?>

                </div>

                <?php if ($selected_board): ?>
                    <div class="task-board-name-badge">
                        <i class="bi bi-kanban"></i>
                        <?= htmlspecialchars($selected_board["name"]) ?>
                    </div>
                <?php endif; ?>


                <?php if (!empty($task["description"])): ?>

                    <div class="task-description">

                        <?= nl2br(htmlspecialchars($task["description"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-meta">

                    <span class="priority-badge">

                        <?= htmlspecialchars($task["priority"]) ?>

                    </span>


                    <span
                        class="progress-badge <?= htmlspecialchars(getProgressClass($task["progress"])) ?>"
                    >

                        <?= htmlspecialchars($task["progress"]) ?>

                    </span>


                    <?php if ((int)$task["is_completed"] === 1): ?>

                        <span class="completion-badge completed">

                            <i class="bi bi-check-circle-fill"></i>

                            Completed

                        </span>

                    <?php else: ?>

                        <span class="completion-badge incomplete">

                            <i class="bi bi-circle"></i>

                            Incomplete

                        </span>

                    <?php endif; ?>

                </div>


                <?php if (!empty($task["editedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["editedDate"])) ?>

                    </div>

                <?php elseif (!empty($task["addedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["addedDate"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-menu">

                    <button
                        type="button"
                        class="task-menu-button"
                        aria-label="Task menu"
                    >
                        ⋮
                    </button>


                    <div class="task-menu-content">


                        <a
                            href="view.php?id=<?= (int)$task["id"] ?>"
                            class="view-link"
                        >
                            View
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                Delete
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- =========================================================
     PENDING
========================================================= -->

<div
    class="task-column"
    data-progress="Pending"
>

    <div class="column-header">

        <span class="column-title">
            PENDING
        </span>

        <span class="column-count">
            <?= count($pending_tasks) ?>
        </span>

        <?php if ($is_admin): ?>

            <a
                href="add.php?progress=Pending"
                class="column-add-btn"
                title="Add task to PENDING"
                aria-label="Add task to PENDING"
            >
                <i class="bi bi-plus"></i>
            </a>

        <?php endif; ?>

    </div>


    <div class="task-list">

        <?php foreach ($pending_tasks as $task): ?>

            <div
                class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                draggable="true"
                data-task-id="<?= (int)$task["id"] ?>"
                data-task-title="<?= htmlspecialchars($task["task"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-description="<?= htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                data-task-priority="<?= htmlspecialchars($task["priority"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-progress="<?= htmlspecialchars($task["progress"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-completed="<?= ((int)$task["is_completed"] === 1) ? "Complete" : "Incomplete" ?>"
                data-task-status="<?= ((int)$task["status"] === 1) ? "Active" : "Inactive" ?>"
                data-task-added="<?= htmlspecialchars(formatTaskDate($task["addedDate"]), ENT_QUOTES, 'UTF-8') ?>"
                data-task-edited="<?= htmlspecialchars(formatTaskDate($task["editedDate"]), ENT_QUOTES, 'UTF-8') ?>"
            >

                <div class="task-title">
                    <?= htmlspecialchars($task["task"]) ?>
                </div>

                <?php if ($selected_board): ?>
                    <div class="task-board-name-badge">
                        <i class="bi bi-kanban"></i>
                        <?= htmlspecialchars($selected_board["name"]) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($task["description"])): ?>
                    <div class="task-description">
                        <?= nl2br(htmlspecialchars($task["description"])) ?>
                    </div>
                <?php endif; ?>

                <div class="task-meta">
                    <span class="priority-badge">
                        <?= htmlspecialchars($task["priority"]) ?>
                    </span>

                    <span
                        class="progress-badge <?= htmlspecialchars(getProgressClass($task["progress"])) ?>"
                    >
                        <?= htmlspecialchars($task["progress"]) ?>
                    </span>

                    <?php if ((int)$task["is_completed"] === 1): ?>
                        <span class="completion-badge completed">
                            <i class="bi bi-check-circle-fill"></i>
                            Completed
                        </span>
                    <?php else: ?>
                        <span class="completion-badge incomplete">
                            <i class="bi bi-circle"></i>
                            Incomplete
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($task["editedDate"])): ?>
                    <div class="task-date">
                        <i class="bi bi-clock"></i>
                        <?= htmlspecialchars(formatTaskDate($task["editedDate"])) ?>
                    </div>
                <?php elseif (!empty($task["addedDate"])): ?>
                    <div class="task-date">
                        <i class="bi bi-clock"></i>
                        <?= htmlspecialchars(formatTaskDate($task["addedDate"])) ?>
                    </div>
                <?php endif; ?>

                <div class="task-menu">
                    <button
                        type="button"
                        class="task-menu-button"
                        aria-label="Task menu"
                    >
                        ⋮
                    </button>

                    <div class="task-menu-content">
                        <a
                            href="view.php?id=<?= (int)$task["id"] ?>"
                            class="view-link"
                        >
                            View
                        </a>


                        <?php if ($is_admin): ?>
                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                Delete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- =========================================================
     REVIEW
========================================================= -->

<div
    class="task-column"
    data-progress="Review"
>

    <div class="column-header">

        <span class="column-title">
            REVIEW
        </span>


        <span class="column-count">
            <?= count($review_tasks) ?>
        </span>


        <?php if ($is_admin): ?>

            <a
                href="add.php?progress=Review"
                class="column-add-btn"
                title="Add task to REVIEW"
                aria-label="Add task to REVIEW"
            >
                <i class="bi bi-plus"></i>
            </a>

        <?php endif; ?>

    </div>


    <div class="task-list">

        <?php foreach ($review_tasks as $task): ?>

            <div
                class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                draggable="true"
                data-task-id="<?= (int)$task["id"] ?>"
                data-task-title="<?= htmlspecialchars($task["task"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-description="<?= htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                data-task-priority="<?= htmlspecialchars($task["priority"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-progress="<?= htmlspecialchars($task["progress"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-completed="<?= ((int)$task["is_completed"] === 1) ? "Complete" : "Incomplete" ?>"
                data-task-status="<?= ((int)$task["status"] === 1) ? "Active" : "Inactive" ?>"
                data-task-added="<?= htmlspecialchars(formatTaskDate($task["addedDate"]), ENT_QUOTES, 'UTF-8') ?>"
                data-task-edited="<?= htmlspecialchars(formatTaskDate($task["editedDate"]), ENT_QUOTES, 'UTF-8') ?>"
            >

                <div class="task-title">

                    <?= htmlspecialchars($task["task"]) ?>

                </div>

                <?php if ($selected_board): ?>
                    <div class="task-board-name-badge">
                        <i class="bi bi-kanban"></i>
                        <?= htmlspecialchars($selected_board["name"]) ?>
                    </div>
                <?php endif; ?>


                <?php if (!empty($task["description"])): ?>

                    <div class="task-description">

                        <?= nl2br(htmlspecialchars($task["description"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-meta">

                    <span class="priority-badge">

                        <?= htmlspecialchars($task["priority"]) ?>

                    </span>


                    <span
                        class="progress-badge <?= htmlspecialchars(getProgressClass($task["progress"])) ?>"
                    >

                        <?= htmlspecialchars($task["progress"]) ?>

                    </span>


                    <?php if ((int)$task["is_completed"] === 1): ?>

                        <span class="completion-badge completed">

                            <i class="bi bi-check-circle-fill"></i>

                            Completed

                        </span>

                    <?php else: ?>

                        <span class="completion-badge incomplete">

                            <i class="bi bi-circle"></i>

                            Incomplete

                        </span>

                    <?php endif; ?>

                </div>


                <?php if (!empty($task["editedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["editedDate"])) ?>

                    </div>

                <?php elseif (!empty($task["addedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["addedDate"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-menu">

                    <button
                        type="button"
                        class="task-menu-button"
                        aria-label="Task menu"
                    >
                        ⋮
                    </button>


                    <div class="task-menu-content">


                        <a
                            href="view.php?id=<?= (int)$task["id"] ?>"
                            class="view-link"
                        >
                            View
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                Delete
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- =========================================================
     DONE
========================================================= -->

<div
    class="task-column"
    data-progress="Done"
>

    <div class="column-header">

        <span class="column-title">
            DONE
        </span>


        <span class="column-count">
            <?= count($done_tasks) ?>
        </span>


        <?php if ($is_admin): ?>

            <a
                href="add.php?progress=Done"
                class="column-add-btn"
                title="Add task to DONE"
                aria-label="Add task to DONE"
            >
                <i class="bi bi-plus"></i>
            </a>

        <?php endif; ?>

    </div>


    <div class="task-list">

        <?php foreach ($done_tasks as $task): ?>

            <div
                class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                draggable="true"
                data-task-id="<?= (int)$task["id"] ?>"
                data-task-title="<?= htmlspecialchars($task["task"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-description="<?= htmlspecialchars($task["description"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                data-task-priority="<?= htmlspecialchars($task["priority"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-progress="<?= htmlspecialchars($task["progress"], ENT_QUOTES, 'UTF-8') ?>"
                data-task-completed="<?= ((int)$task["is_completed"] === 1) ? "Complete" : "Incomplete" ?>"
                data-task-status="<?= ((int)$task["status"] === 1) ? "Active" : "Inactive" ?>"
                data-task-added="<?= htmlspecialchars(formatTaskDate($task["addedDate"]), ENT_QUOTES, 'UTF-8') ?>"
                data-task-edited="<?= htmlspecialchars(formatTaskDate($task["editedDate"]), ENT_QUOTES, 'UTF-8') ?>"
            >

                <div class="task-title">

                    <?= htmlspecialchars($task["task"]) ?>

                </div>

                <?php if ($selected_board): ?>
                    <div class="task-board-name-badge">
                        <i class="bi bi-kanban"></i>
                        <?= htmlspecialchars($selected_board["name"]) ?>
                    </div>
                <?php endif; ?>


                <?php if (!empty($task["description"])): ?>

                    <div class="task-description">

                        <?= nl2br(htmlspecialchars($task["description"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-meta">

                    <span class="priority-badge">

                        <?= htmlspecialchars($task["priority"]) ?>

                    </span>


                    <span
                        class="progress-badge <?= htmlspecialchars(getProgressClass($task["progress"])) ?>"
                    >

                        <?= htmlspecialchars($task["progress"]) ?>

                    </span>


                    <?php if ((int)$task["is_completed"] === 1): ?>

                        <span class="completion-badge completed">

                            <i class="bi bi-check-circle-fill"></i>

                            Completed

                        </span>

                    <?php else: ?>

                        <span class="completion-badge incomplete">

                            <i class="bi bi-circle"></i>

                            Incomplete

                        </span>

                    <?php endif; ?>

                </div>


                <?php if (!empty($task["editedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["editedDate"])) ?>

                    </div>

                <?php elseif (!empty($task["addedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= htmlspecialchars(formatTaskDate($task["addedDate"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-menu">

                    <button
                        type="button"
                        class="task-menu-button"
                        aria-label="Task menu"
                    >
                        ⋮
                    </button>


                    <div class="task-menu-content">


                        <a
                            href="view.php?id=<?= (int)$task["id"] ?>"
                            class="view-link"
                        >
                            View
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                Delete
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- =========================================================
     BOARD COLUMN
========================================================= -->

<div
    class="task-column board-task-column"
    data-progress="Board"
>

    <div class="column-header">

        <span class="column-title">BOARD</span>

        <span class="column-count"><?= count($boards) ?></span>

        <?php if ($is_admin || $is_user): ?>
            <button
                type="button"
                class="column-add-btn"
                id="boardColumnAddBtn"
                title="Add board"
                aria-label="Add board"
            >
                <i class="bi bi-plus"></i>
            </button>
        <?php endif; ?>

    </div>

    <div class="board-card-list">

        <?php foreach ($boards as $board): ?>

            <div
                class="board-card <?= ((int)$board["id"] === $selected_board_id) ? "active" : "" ?>"
                data-board-id="<?= (int)$board["id"] ?>"
                role="button"
                tabindex="0"
            >

                <div class="board-card-name">
                    <?= htmlspecialchars($board["name"]) ?>
                </div>

                <?php if (!empty($board["description"])): ?>
                    <div class="board-card-description">
                        <?= htmlspecialchars($board["description"]) ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_admin || $is_user): ?>
                    <div class="board-card-menu">
                        <button
                            type="button"
                            class="board-card-menu-button"
                            aria-label="Board menu"
                            title="Board menu"
                        >
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                        <div class="board-card-menu-content">
                            <button
                                type="button"
                                class="board-card-menu-delete"
                                data-board-id="<?= (int)$board["id"] ?>"
                            >
                                <i class="bi bi-trash me-1"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

        <?php if (empty($boards)): ?>
            <div class="board-empty">
                No boards yet. Use <strong>+ Add Board</strong> to create your first board.
            </div>
        <?php endif; ?>

    </div>

</div>

</div>

</div>


<!-- =========================================================
     TASK DETAILS MODAL (TRELLO-STYLE CARD VIEW)
========================================================= -->

<div
    class="modal fade task-details-modal"
    id="taskDetailsModal"
    tabindex="-1"
    aria-labelledby="taskDetailsModalLabel"
    aria-hidden="true"
>

<div class="modal-dialog modal-dialog-centered modal-lg trello-card-dialog">

    <div class="modal-content trello-card-content">


        <!-- MODAL HEADER -->

        <div class="modal-header trello-card-header">

            <div class="trello-card-header-text">

                <span class="trello-card-eyebrow">
                    <i class="bi bi-kanban"></i>
                    Card Details
                </span>

                <h5
                    class="modal-title trello-card-modal-title"
                    id="taskDetailsModalLabel"
                >
                    Task Details
                </h5>

            </div>


            <div class="task-details-header-actions">

                <button
                    type="button"
                    class="btn btn-primary task-details-edit-btn"
                    id="taskDetailsEditBtn"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </button>

                <button
                    type="button"
                    class="btn btn-outline-secondary task-details-back-btn"
                    data-bs-dismiss="modal"
                    aria-label="Back"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>

            </div>

        </div>


        <!-- MODAL BODY -->

        <div class="modal-body trello-card-body">

            <div class="trello-card-layout">


                <!-- =========================================
                     MAIN COLUMN
                ========================================= -->

                <div class="trello-card-main">


                    <!-- TASK NAME -->

                    <div class="trello-card-title-row">

                        <i class="bi bi-card-heading trello-card-title-icon"></i>

                        <div
                            class="task-detail-title"
                            id="modalTaskTitle"
                        >
                            —
                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="trello-card-section">

                        <div class="trello-card-section-heading-row">

                            <div class="trello-card-section-heading">
                                <i class="bi bi-text-paragraph"></i>
                                Description
                            </div>

                        </div>

                        <div
                            class="task-detail-description empty"
                            id="modalTaskDescription"
                        >
                            No description provided.
                        </div>

                        <textarea
                            class="form-control d-none"
                            id="modalTaskDescriptionInput"
                            rows="4"
                            placeholder="Enter task description"
                        ></textarea>

                    </div>


                    <!-- =================================================
                         COMMENTS / ACTIVITY
                    ================================================== -->

                    <div class="trello-card-section">

                        <div class="trello-card-section-heading">
                            <i class="bi bi-chat-left-text"></i>
                            Activity
                        </div>


                        <div class="trello-comment-composer">

                            <div class="trello-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="trello-comment-composer-input">

                                <textarea
                                    id="taskCommentInput"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Write a comment..."
                                ></textarea>


                                <div class="d-flex justify-content-end mt-2">

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        id="addCommentButton"
                                    >

                                        <i class="bi bi-send"></i>

                                        Save

                                    </button>

                                </div>

                            </div>

                        </div>


                        <div
                            id="taskCommentsList"
                            class="task-comments-list mt-3"
                        >

                            <div class="text-muted small">

                                Loading comments...

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         FILES / IMAGES
                    ================================================== -->

                    <div class="trello-card-section">

                        <div class="trello-card-section-heading-row">

                            <div class="trello-card-section-heading">
                                <i class="bi bi-paperclip"></i>
                                Attachments
                            </div>


                            <label
                                for="taskAttachmentInput"
                                class="btn btn-outline-secondary btn-sm"
                                style="cursor:pointer;"
                            >

                                <i class="bi bi-upload"></i>

                                Add

                            </label>


                            <input
                                type="file"
                                id="taskAttachmentInput"
                                hidden
                            >

                        </div>


                        <div
                            id="taskAttachmentsList"
                            class="task-attachments-list mt-2"
                        >

                            <div class="text-muted small">

                                Loading files...

                            </div>

                        </div>

                    </div>


                </div>


                <!-- =========================================
                     SIDEBAR COLUMN
                ========================================= -->

                <div class="trello-card-sidebar">

                    <div class="trello-sidebar-label">
                        About this card
                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Priority
                        </span>

                        <div class="task-detail-field">

                            <span
                                class="task-detail-value trello-chip"
                                id="modalTaskPriority"
                            >
                                —
                            </span>

                            <select
                                class="task-detail-select d-none"
                                id="modalTaskPrioritySelect"
                                data-field="priority"
                            >
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>

                        </div>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Progress
                        </span>

                        <div class="task-detail-field">

                            <div class="task-detail-field">

                                <span
                                    class="task-detail-value trello-chip"
                                    id="modalTaskProgress"
                                >
                                    —
                                </span>

                                <span
                                    class="task-progress-board-name d-block mt-2 small text-muted"
                                    id="modalTaskProgressBoardName"
                                >
                                    <i class="bi bi-kanban me-1"></i>
                                    Board: —
                                </span>

                            </div>

                            <select
                                class="task-detail-select d-none"
                                id="modalTaskProgressSelect"
                                data-field="progress"
                            >
                                <option value="Todo">Todo</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending">Pending</option>
                                <option value="Review">Review</option>
                                <option value="Done">Done</option>
                            </select>

                        </div>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Board
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskBoardName"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Completion
                        </span>

                        <div class="task-detail-field">

                            <span
                                class="task-detail-value trello-chip"
                                id="modalTaskCompleted"
                            >
                                —
                            </span>

                            <select
                                class="task-detail-select d-none"
                                id="modalTaskCompletedSelect"
                                data-field="is_completed"
                            >
                                <option value="1">Completed</option>
                                <option value="0">Incomplete</option>
                            </select>

                        </div>

                    </div>


                    <div class="trello-sidebar-divider"></div>


                    <div class="trello-sidebar-label">
                        Dates
                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            <i class="bi bi-plus-circle"></i>
                            Added
                        </span>

                        <span
                            class="task-detail-value trello-chip trello-chip-date"
                            id="modalTaskAdded"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            <i class="bi bi-pencil"></i>
                            Last edited
                        </span>

                        <span
                            class="task-detail-value trello-chip trello-chip-date"
                            id="modalTaskEdited"
                        >
                            —
                        </span>

                    </div>


                </div>


            </div>

        </div>


        <!-- MODAL FOOTER -->

        <div class="modal-footer task-details-edit-footer">

            <div class="task-details-footer-actions">

                <button
                    type="button"
                    class="btn btn-secondary task-details-cancel-btn d-none"
                    id="taskDetailsCancelBtn"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-primary task-details-save-btn d-none"
                    id="taskDetailsSaveBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Save
                </button>

            </div>

        </div>


    </div>

</div>

</div>


<!-- =========================================================
     ADD BOARD MODAL
========================================================= -->

<div
    class="modal fade"
    id="addBoardModal"
    tabindex="-1"
    aria-labelledby="addBoardModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBoardModalLabel">Add Board</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="index.php" id="addBoardForm">
                <div class="modal-body">
                    <?php if ($board_error !== ""): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($board_error) ?>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="action" value="create_board">

                    <div class="mb-3">
                        <label class="form-label">Board Name</label>
                        <input
                            type="text"
                            name="board_name"
                            class="form-control"
                            maxlength="255"
                            placeholder="Enter board name"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea
                            name="board_description"
                            class="form-control"
                            rows="4"
                            placeholder="Enter board description"
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Add Board
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =========================================================
     ADD TASK MODAL
========================================================= -->

<div
    class="modal fade"
    id="addTaskModal"
    tabindex="-1"
    aria-labelledby="addTaskModalLabel"
    aria-hidden="true"
>

<div class="modal-dialog modal-dialog-centered">

    <div class="modal-content">

        <div class="modal-header">

            <h5
                class="modal-title"
                id="addTaskModalLabel"
            >
                Add Task
            </h5>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
            ></button>

        </div>

        <div class="modal-body">

            <div
                id="addTaskError"
                class="alert alert-danger d-none"
            ></div>

            <form id="addTaskForm">

                <input
                    type="hidden"
                    name="board_id"
                    id="addTaskBoardId"
                    value="<?= (int)$selected_board_id ?>"
                >

                <div class="mb-3">

                    <label class="form-label">
                        Task
                    </label>

                    <input
                        type="text"
                        name="task"
                        id="addTaskTitle"
                        class="form-control"
                        placeholder="Enter task"
                        maxlength="255"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Task Description
                    </label>

                    <textarea
                        name="description"
                        id="addTaskDescription"
                        class="form-control"
                        rows="4"
                        placeholder="Enter task description"
                    ></textarea>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Priority
                    </label>

                    <select
                        name="priority"
                        id="addTaskPriority"
                        class="form-select"
                    >
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Task Progress
                    </label>

                    <select
                        name="progress"
                        id="addTaskProgress"
                        class="form-select"
                    >
                        <option value="Todo">Todo</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                        <option value="Review">Review</option>
                        <option value="Done">Done</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        id="addTaskStatus"
                        class="form-select"
                    >
                        <option value="1" selected>Active</option>
                        <option value="2">Inactive</option>
                    </select>

                </div>

            </form>

        </div>

        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn btn-primary"
                id="addTaskSubmitBtn"
            >
                Add Task
            </button>

        </div>

    </div>

</div>

</div>




</div>

</div>


<!-- =========================================================
     DRAG HINT
========================================================= -->

<div
    id="dragHint"
    class="drag-hint"
>
    Move task to another column
</div>


<!-- =========================================================
     DRAG SUCCESS
========================================================= -->

<div
    id="dragSuccess"
    class="drag-success"
>
    Task status updated
</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

"use strict";


/* =========================================================
   GLOBAL VARIABLES
========================================================= */

let draggedCard = null;

let touchDraggedCard = null;

let touchStartX = 0;

let touchStartY = 0;

let touchCurrentX = 0;

let touchCurrentY = 0;

let touchLongPressTimer = null;

let isTouchDragging = false;

let originalParent = null;

let originalNextSibling = null;

let suppressCardClick = false;

let currentTaskId = 0;

const TOUCH_LONG_PRESS = 300;

const TOUCH_MOVE_THRESHOLD = 10;

/* =========================================================
   OUTER BOARD TOUCH SCROLL STATE
   Allows direct finger swiping on the six-column board
   to scroll horizontally and vertically.
========================================================= */

let boardTouchScrolling = false;

let boardTouchStartX = 0;

let boardTouchStartY = 0;

let boardTouchLastX = 0;

let boardTouchLastY = 0;

let boardTouchMoved = false;

let boardTouchStartedInList = false;


/* =========================================================
   SHARED TOUCH SCROLL HELPER
   Horizontal movement always scrolls the outer six-column
   board. Vertical movement scrolls whichever column's task
   list the touch is over (so cards keep appearing/disappearing
   correctly), falling back to the outer board if the touch
   is not over any task list.
========================================================= */

function performBoardTouchScroll(originElement, deltaX, deltaY)
{

    const board =
        document.querySelector(
            ".board-wrapper"
        );

    if (!board) {

        return;

    }


    if (
        board.scrollWidth >
        board.clientWidth
    ) {

        board.scrollLeft -=
            deltaX;

    }


    const list =
        originElement ?
            originElement.closest(
                ".task-list"
            ) :
            null;


    if (
        list &&
        list.scrollHeight >
        list.clientHeight
    ) {

        list.scrollTop -=
            deltaY;

    }
    else if (
        board.scrollHeight >
        board.clientHeight
    ) {

        board.scrollTop -=
            deltaY;

    }

}


/* =========================================================
   TASK DETAILS MODAL
========================================================= */

const taskDetailsModalElement =
    document.getElementById(
        "taskDetailsModal"
    );


let taskDetailsModal = null;


if (taskDetailsModalElement) {

    taskDetailsModal =
        new bootstrap.Modal(
            taskDetailsModalElement
        );

}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHtml(value)
{

    const div =
        document.createElement("div");

    div.textContent =
        value ?? "";

    return div.innerHTML;

}


/* =========================================================
   ATTRIBUTE ESCAPE
========================================================= */

function escapeAttribute(value)
{

    return escapeHtml(value)
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


/* =========================================================
   SET MODAL VALUE
========================================================= */

function setModalValue(
    elementId,
    value
)
{

    const element =
        document.getElementById(
            elementId
        );


    if (!element) {

        return;

    }


    element.textContent =
        value || "—";

}


/* =========================================================
   LOAD COMMENTS
========================================================= */

function loadTaskComments(taskId)
{

    const commentsList =
        document.getElementById(
            "taskCommentsList"
        );


    if (!commentsList) {

        return;

    }


    commentsList.innerHTML = `
        <div class="text-muted small">
            Loading comments...
        </div>
    `;


    fetch(
        "get_comments.php?task_id=" +
        encodeURIComponent(taskId),
        {
            method: "GET",
            cache: "no-store"
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            commentsList.innerHTML = `
                <div class="text-danger small">
                    ${escapeHtml(
                        data.message ||
                        "Unable to load comments."
                    )}
                </div>
            `;

            return;

        }


        if (
            !data.comments ||
            data.comments.length === 0
        ) {

            commentsList.innerHTML = `
                <div class="text-muted small">
                    No comments yet.
                </div>
            `;

            return;

        }


        commentsList.innerHTML =
            data.comments.map(
                function(comment) {

                    return `
                        <div class="task-comment-item">

                            <div class="task-comment-header">

                                <span class="task-comment-user">

                                    <i class="bi bi-person-circle"></i>

                                    ${escapeHtml(
                                        comment.user_name ||
                                        "User"
                                    )}

                                </span>


                                <span class="task-comment-date">

                                    ${escapeHtml(
                                        comment.created_at ||
                                        ""
                                    )}

                                </span>

                            </div>


                            <div class="task-comment-text">

                                ${escapeHtml(
                                    comment.comment ||
                                    ""
                                )}

                            </div>

                        </div>
                    `;

                }
            ).join("");

    })
    .catch(function(error) {

        console.error(
            "Comments error:",
            error
        );


        commentsList.innerHTML = `
            <div class="text-danger small">
                Unable to load comments.
            </div>
        `;

    });

}


/* =========================================================
   ADD COMMENT
========================================================= */

function addTaskComment()
{

    const input =
        document.getElementById(
            "taskCommentInput"
        );


    const button =
        document.getElementById(
            "addCommentButton"
        );


    if (!input) {

        return;

    }


    const comment =
        input.value.trim();


    if (currentTaskId <= 0) {

        alert("Invalid task.");

        return;

    }


    if (comment === "") {

        alert("Please enter a comment.");

        input.focus();

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "task_id",
        currentTaskId
    );


    formData.append(
        "comment",
        comment
    );


    if (button) {

        button.disabled = true;

        button.innerHTML = `
            <span
                class="spinner-border spinner-border-sm"
            ></span>

            Adding...
        `;

    }


    fetch(
        "add_comment.php",
        {
            method: "POST",
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            alert(
                data.message ||
                "Unable to add comment."
            );

            return;

        }


        input.value = "";

        loadTaskComments(
            currentTaskId
        );

    })
    .catch(function(error) {

        console.error(
            "Add comment error:",
            error
        );


        alert(
            "Unable to add comment."
        );

    })
    .finally(function() {

        if (button) {

            button.disabled = false;

            button.innerHTML = `
                <i class="bi bi-send"></i>
                Add Comment
            `;

        }

    });

}


/* =========================================================
   LOAD ATTACHMENTS
========================================================= */

function loadTaskAttachments(taskId)
{

    const attachmentsList =
        document.getElementById(
            "taskAttachmentsList"
        );


    if (!attachmentsList) {

        return;

    }


    attachmentsList.innerHTML = `
        <div class="text-muted small">
            Loading files...
        </div>
    `;


    fetch(
        "get_attachments.php?task_id=" +
        encodeURIComponent(taskId),
        {
            method: "GET",
            cache: "no-store"
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            attachmentsList.innerHTML = `
                <div class="text-danger small">
                    ${escapeHtml(
                        data.message ||
                        "Unable to load files."
                    )}
                </div>
            `;

            return;

        }


        if (
            !data.attachments ||
            data.attachments.length === 0
        ) {

            attachmentsList.innerHTML = `
                <div class="text-muted small">
                    No files uploaded yet.
                </div>
            `;

            return;

        }


        attachmentsList.innerHTML =
            data.attachments.map(
                function(file) {

                    const fileUrl =
                        "../" +
                        file.file_path;


                    let preview = "";


                    if (file.is_image) {

                        preview = `
                            <a
                                href="${escapeAttribute(fileUrl)}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >

                                <img
                                    src="${escapeAttribute(fileUrl)}"
                                    class="task-attachment-preview"
                                    alt="${escapeAttribute(
                                        file.original_name
                                    )}"
                                >

                            </a>
                        `;

                    }
                    else {

                        preview = `
                            <div class="attachment-file-icon">

                                <i class="bi bi-file-earmark-text"></i>

                            </div>
                        `;

                    }


                    return `
                        <div
                            class="task-attachment-item"
                        >

                            ${preview}


                            <div
                                class="task-attachment-info"
                            >

                                <div>

                                    <div
                                        class="task-attachment-name"
                                    >
                                        ${escapeHtml(
                                            file.original_name
                                        )}
                                    </div>


                                    <div
                                        class="task-attachment-date"
                                    >
                                        ${escapeHtml(
                                            file.uploaded_at ||
                                            ""
                                        )}
                                    </div>

                                </div>


                                <div
                                    class="task-attachment-actions"
                                >

                                    <a
                                        href="${escapeAttribute(fileUrl)}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Open"
                                    >

                                        <i
                                            class="bi bi-box-arrow-up-right"
                                        ></i>

                                    </a>


                                    <button
                                        type="button"
                                        title="Delete"
                                        onclick="deleteTaskAttachment(${Number(file.id)})"
                                    >

                                        <i
                                            class="bi bi-trash text-danger"
                                        ></i>

                                    </button>

                                </div>

                            </div>

                        </div>
                    `;

                }
            ).join("");

    })
    .catch(function(error) {

        console.error(
            "Attachments error:",
            error
        );


        attachmentsList.innerHTML = `
            <div class="text-danger small">
                Unable to load files.
            </div>
        `;

    });

}


/* =========================================================
   UPLOAD ATTACHMENT
========================================================= */

function uploadTaskAttachment(file)
{

    if (!file) {

        return;

    }


    if (currentTaskId <= 0) {

        alert("Invalid task.");

        return;

    }


    const maxSize =
        10 * 1024 * 1024;


    if (file.size > maxSize) {

        alert(
            "File size cannot exceed 10 MB."
        );

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "task_id",
        currentTaskId
    );


    formData.append(
        "attachment",
        file
    );


    const attachmentsList =
        document.getElementById(
            "taskAttachmentsList"
        );


    if (attachmentsList) {

        attachmentsList.innerHTML = `
            <div class="text-primary small">

                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>

                Uploading file...

            </div>
        `;

    }


    fetch(
        "upload_attachment.php",
        {
            method: "POST",
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            alert(
                data.message ||
                "Upload failed."
            );


            loadTaskAttachments(
                currentTaskId
            );

            return;

        }


        loadTaskAttachments(
            currentTaskId
        );

    })
    .catch(function(error) {

        console.error(
            "Upload error:",
            error
        );


        alert(
            "Unable to upload file."
        );


        loadTaskAttachments(
            currentTaskId
        );

    });

}


/* =========================================================
   DELETE ATTACHMENT
========================================================= */

function deleteTaskAttachment(
    attachmentId
)
{

    if (!attachmentId) {

        return;

    }


    if (
        !confirm(
            "Are you sure you want to delete this file?"
        )
    ) {

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "attachment_id",
        attachmentId
    );


    fetch(
        "delete_attachment.php",
        {
            method: "POST",
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            alert(
                data.message ||
                "Unable to delete file."
            );

            return;

        }


        loadTaskAttachments(
            currentTaskId
        );

    })
    .catch(function(error) {

        console.error(
            "Delete attachment error:",
            error
        );


        alert(
            "Unable to delete file."
        );

    });

}


/* =========================================================
   OPEN TASK DETAILS
========================================================= */

function openTaskDetails(card)
{

    if (!card || !taskDetailsModal) {

        return;

    }


    const taskId =
        card.dataset.taskId || "—";


    const taskTitle =
        card.dataset.taskTitle || "—";


    const taskDescription =
        card.dataset.taskDescription || "";


    const taskPriority =
        card.dataset.taskPriority || "—";


    const taskProgress =
        card.dataset.taskProgress || "—";


    const taskBoardName =
        card.dataset.taskBoardName || "—";


    const taskCompleted =
        card.dataset.taskCompleted || "—";


    const taskAdded =
        card.dataset.taskAdded || "—";


    const taskEdited =
        card.dataset.taskEdited || "—";


    /* =====================================================
       SAVE CURRENT TASK ID
    ===================================================== */

    currentTaskId =
        Number(taskId) || 0;


    /* =====================================================
       BASIC VALUES
    ===================================================== */

    setModalValue(
        "modalTaskTitle",
        taskTitle
    );


    setModalValue(
        "modalTaskPriority",
        taskPriority
    );


    setModalValue(
        "modalTaskProgress",
        taskProgress
    );


    setModalValue(
        "modalTaskBoardName",
        taskBoardName
    );


    const progressBoardNameElement =
        document.getElementById(
            "modalTaskProgressBoardName"
        );

    if (progressBoardNameElement) {
        progressBoardNameElement.innerHTML =
            '<i class="bi bi-kanban me-1"></i>Board: ' +
            escapeHtml(taskBoardName) +
            '';
    }


    setModalValue(
        "modalTaskCompleted",
        taskCompleted === "Complete" ? "Completed" : "Incomplete"
    );


    setModalValue(
        "modalTaskAdded",
        taskAdded
    );


    setModalValue(
        "modalTaskEdited",
        taskEdited
    );


    /* =====================================================
       DESCRIPTION
    ===================================================== */

    const descriptionElement =
        document.getElementById(
            "modalTaskDescription"
        );


    if (descriptionElement) {

        if (
            taskDescription.trim() !== ""
        ) {

            descriptionElement.textContent =
                taskDescription;

            descriptionElement.classList.remove(
                "empty"
            );

        }
        else {

            descriptionElement.textContent =
                "No description provided.";

            descriptionElement.classList.add(
                "empty"
            );

        }

    }


    /* =====================================================
       COMPLETE / INCOMPLETE COLOR
    ===================================================== */

    const completedElement =
        document.getElementById(
            "modalTaskCompleted"
        );


    if (completedElement) {

        completedElement.classList.remove(
            "complete",
            "incomplete"
        );


        if (
            taskCompleted === "Complete"
        ) {

            completedElement.classList.add(
                "complete"
            );

        }
        else {

            completedElement.classList.add(
                "incomplete"
            );

        }

    }


    /* =====================================================
       PRE-FILL EDIT CONTROLS + RESET TO VIEW MODE
    ===================================================== */

    const prioritySelect =
        document.getElementById(
            "modalTaskPrioritySelect"
        );

    if (prioritySelect) {
        prioritySelect.value = taskPriority;
    }


    const progressSelect =
        document.getElementById(
            "modalTaskProgressSelect"
        );

    if (progressSelect) {
        progressSelect.value = taskProgress;
    }


    const completedSelect =
        document.getElementById(
            "modalTaskCompletedSelect"
        );

    if (completedSelect) {
        completedSelect.value =
            (taskCompleted === "Complete") ? "1" : "0";
    }


    const descriptionInput =
        document.getElementById(
            "modalTaskDescriptionInput"
        );

    if (descriptionInput) {
        descriptionInput.value = taskDescription;
    }


    taskDetailsEditSnapshot = null;

    resetTaskFieldEditModes();


    /* =====================================================
       CLEAR COMMENT INPUT
    ===================================================== */

    const commentInput =
        document.getElementById(
            "taskCommentInput"
        );


    if (commentInput) {

        commentInput.value = "";

    }


    /* =====================================================
       LOAD COMMENTS
    ===================================================== */

    loadTaskComments(
        currentTaskId
    );


    /* =====================================================
       LOAD ATTACHMENTS
    ===================================================== */

    loadTaskAttachments(
        currentTaskId
    );


    /* =====================================================
       SHOW MODAL
    ===================================================== */

    taskDetailsModal.show();

}


/* =========================================================
   RESET INLINE EDIT MODES (VIEW CARD)
========================================================= */

function resetTaskFieldEditModes()
{

    const editBtn = document.getElementById("taskDetailsEditBtn");
    const saveBtn = document.getElementById("taskDetailsSaveBtn");
    const cancelBtn = document.getElementById("taskDetailsCancelBtn");

    document.querySelectorAll(".task-detail-select").forEach(function(select) {
        select.classList.add("d-none");
    });

    document.querySelectorAll(".task-detail-field .task-detail-value").forEach(function(chip) {
        chip.classList.remove("d-none");
    });

    const descriptionView = document.getElementById("modalTaskDescription");
    const descriptionInput = document.getElementById("modalTaskDescriptionInput");

    if (descriptionView) descriptionView.classList.remove("d-none");
    if (descriptionInput) descriptionInput.classList.add("d-none");

    if (editBtn) editBtn.classList.remove("d-none");
    if (saveBtn) saveBtn.classList.add("d-none");
    if (cancelBtn) cancelBtn.classList.add("d-none");

}


let taskDetailsEditSnapshot = null;


function enterTaskDetailsEditMode()
{
    if (currentTaskId <= 0) return;

    const prioritySelect = document.getElementById("modalTaskPrioritySelect");
    const progressSelect = document.getElementById("modalTaskProgressSelect");
    const completedSelect = document.getElementById("modalTaskCompletedSelect");
    const descriptionInput = document.getElementById("modalTaskDescriptionInput");

    taskDetailsEditSnapshot = {
        priority: prioritySelect ? prioritySelect.value : "",
        progress: progressSelect ? progressSelect.value : "",
        is_completed: completedSelect ? completedSelect.value : "0",
        description: descriptionInput ? descriptionInput.value : ""
    };

    document.querySelectorAll(".task-detail-select").forEach(function(select) {
        select.classList.remove("d-none");
    });

    document.querySelectorAll(".task-detail-field .task-detail-value").forEach(function(chip) {
        chip.classList.add("d-none");
    });

    const descriptionView = document.getElementById("modalTaskDescription");
    if (descriptionView) descriptionView.classList.add("d-none");

    if (descriptionInput) {
        descriptionInput.classList.remove("d-none");
        descriptionInput.focus();
    }

    const editBtn = document.getElementById("taskDetailsEditBtn");
    const saveBtn = document.getElementById("taskDetailsSaveBtn");
    const cancelBtn = document.getElementById("taskDetailsCancelBtn");

    if (editBtn) editBtn.classList.add("d-none");
    if (saveBtn) saveBtn.classList.remove("d-none");
    if (cancelBtn) cancelBtn.classList.remove("d-none");
}


function cancelTaskDetailsEdit()
{
    if (taskDetailsEditSnapshot) {
        const prioritySelect = document.getElementById("modalTaskPrioritySelect");
        const progressSelect = document.getElementById("modalTaskProgressSelect");
        const completedSelect = document.getElementById("modalTaskCompletedSelect");
        const descriptionInput = document.getElementById("modalTaskDescriptionInput");

        if (prioritySelect) prioritySelect.value = taskDetailsEditSnapshot.priority;
        if (progressSelect) progressSelect.value = taskDetailsEditSnapshot.progress;
        if (completedSelect) completedSelect.value = taskDetailsEditSnapshot.is_completed;
        if (descriptionInput) descriptionInput.value = taskDetailsEditSnapshot.description;
    }

    resetTaskFieldEditModes();
    taskDetailsEditSnapshot = null;
}


function saveTaskDetailsEdit()
{
    if (currentTaskId <= 0) return;

    const prioritySelect = document.getElementById("modalTaskPrioritySelect");
    const progressSelect = document.getElementById("modalTaskProgressSelect");
    const completedSelect = document.getElementById("modalTaskCompletedSelect");
    const descriptionInput = document.getElementById("modalTaskDescriptionInput");
    const saveBtn = document.getElementById("taskDetailsSaveBtn");
    const cancelBtn = document.getElementById("taskDetailsCancelBtn");

    const values = {
        priority: prioritySelect ? prioritySelect.value : "",
        progress: progressSelect ? progressSelect.value : "",
        is_completed: completedSelect ? completedSelect.value : "0",
        description: descriptionInput ? descriptionInput.value : ""
    };

    const fields = ["priority", "progress", "is_completed", "description"];
    const originalHtml = saveBtn ? saveBtn.innerHTML : "";

    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    }
    if (cancelBtn) cancelBtn.disabled = true;

    let chain = Promise.resolve();
    const saved = {};

    fields.forEach(function(field) {
        chain = chain.then(function() {
            return new Promise(function(resolve, reject) {
                saveTaskField(currentTaskId, field, values[field], function(data) {
                    saved[field] = data;
                    resolve();
                });
            });
        });
    });

    chain.then(function() {
        setModalValue("modalTaskPriority", values.priority);
        setModalValue("modalTaskProgress", values.progress);
        setModalValue("modalTaskCompleted", values.is_completed === "1" ? "Completed" : "Incomplete");
        const editedDisplay =
            saved.description?.edited ||
            saved.is_completed?.edited ||
            saved.progress?.edited ||
            saved.priority?.edited ||
            document.getElementById("modalTaskEdited")?.textContent ||
            "";

        setModalValue("modalTaskEdited", editedDisplay);

        const descriptionView = document.getElementById("modalTaskDescription");
        if (descriptionView) {
            if (values.description.trim() !== "") {
                descriptionView.textContent = values.description;
                descriptionView.classList.remove("empty");
            } else {
                descriptionView.textContent = "No description provided.";
                descriptionView.classList.add("empty");
            }
        }

        updateCardAfterFieldChange(currentTaskId, "priority", values.priority, null);
        updateCardAfterFieldChange(currentTaskId, "progress", values.progress, null);
        updateCardAfterFieldChange(currentTaskId, "is_completed", values.is_completed, null);
        updateCardAfterFieldChange(currentTaskId, "description", values.description, editedDisplay);

        resetTaskFieldEditModes();
        taskDetailsEditSnapshot = null;
    }).catch(function(error) {
        console.error("Task details save error:", error);
        alert(error.message || "Could not save changes. Please try again.");
    }).finally(function() {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
        }
        if (cancelBtn) cancelBtn.disabled = false;
    });
}


document.addEventListener("click", function(event) {
    if (event.target.closest("#taskDetailsEditBtn")) {
        event.preventDefault();
        enterTaskDetailsEditMode();
        return;
    }

    if (event.target.closest("#taskDetailsCancelBtn")) {
        event.preventDefault();
        cancelTaskDetailsEdit();
        return;
    }

    if (event.target.closest("#taskDetailsSaveBtn")) {
        event.preventDefault();
        saveTaskDetailsEdit();
        return;
    }
});

/* =========================================================
   SAVE TASK FIELD (AJAX)
========================================================= */

function saveTaskField(taskId, field, value, onSuccess)
{

    if (!taskId) {
        return Promise.reject(new Error("Invalid task."));
    }

    const formData =
        new FormData();

    formData.append("action", "update_task_field");
    formData.append("task_id", taskId);
    formData.append("field", field);
    formData.append("value", value);

    return fetch(
        "index.php",
        {
            method: "POST",
            body: formData
        }
    )
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data || !data.success) {
                    throw new Error(
                        (data && data.message) ?
                            data.message :
                            "Could not save changes. Please try again."
                    );
                }

                if (typeof onSuccess === "function") {
                    onSuccess(data);
                }

                return data;
            });
        })
        .catch(function(error) {
            console.error("Save task field error:", error);
            throw error;
        });

}


/* =========================================================
   PRIORITY / PROGRESS BADGE HELPERS (JS MIRROR OF PHP)
========================================================= */

function getPriorityBadgeClass(priority)
{

    switch (priority) {

        case "High":
            return "priority-high";

        case "Medium":
            return "priority-medium";

        case "Low":
            return "priority-low";

        default:
            return "priority-low";

    }

}


function getProgressBadgeClass(progress)
{

    switch (progress) {

        case "Todo":
            return "progress-todo";

        case "In Progress":
            return "progress-progress";

        case "Pending":
            return "progress-pending";

        case "Review":
            return "progress-review";

        case "Done":
            return "progress-done";

        default:
            return "progress-todo";

    }

}


/* =========================================================
   UPDATE BOARD CARD AFTER A FIELD IS SAVED
========================================================= */

function updateCardAfterFieldChange(taskId, field, value, editedDisplay)
{

    const card =
        document.querySelector(
            '.task-card[data-task-id="' + taskId + '"]'
        );

    if (!card) {
        return;
    }


    if (editedDisplay) {

        card.dataset.taskEdited = editedDisplay;

        const dateElement =
            card.querySelector(".task-date");

        if (dateElement) {

            dateElement.innerHTML =
                '<i class="bi bi-clock"></i> ' +
                escapeHtml(editedDisplay);

        }

    }


    if (field === "priority") {

        card.dataset.taskPriority = value;

        card.classList.remove(
            "priority-high",
            "priority-medium",
            "priority-low"
        );

        card.classList.add(
            getPriorityBadgeClass(value)
        );

        const badge =
            card.querySelector(".priority-badge");

        if (badge) {
            badge.textContent = value;
        }

    }
    else if (field === "progress") {

        card.dataset.taskProgress = value;

        const badge =
            card.querySelector(".progress-badge");

        if (badge) {

            badge.classList.remove(
                "progress-todo",
                "progress-progress",
                "progress-review",
                "progress-done"
            );

            badge.classList.add(
                getProgressBadgeClass(value)
            );

            badge.textContent = value;

        }


        const targetList =
            document.querySelector(
                '.task-column[data-progress="' +
                value +
                '"] .task-list'
            );

        const sourceColumn =
            card.closest(".task-column");

        if (targetList && targetList !== card.parentElement) {

            const previousColumn =
                card.closest(".task-column");

            targetList.appendChild(card);

            updateColumnCount(previousColumn);
            updateColumnCount(
                card.closest(".task-column")
            );

        }

    }
    else if (field === "is_completed") {

        const isComplete =
            (String(value) === "1");

        card.dataset.taskCompleted =
            isComplete ? "Complete" : "Incomplete";

        const badge =
            card.querySelector(".completion-badge");

        if (badge) {

            badge.classList.remove(
                "completed",
                "incomplete"
            );

            badge.classList.add(
                isComplete ? "completed" : "incomplete"
            );

            badge.innerHTML =
                isComplete ?
                    '<i class="bi bi-check-circle-fill"></i> Completed' :
                    '<i class="bi bi-circle"></i> Incomplete';

        }

    }
    else if (field === "description") {

        card.dataset.taskDescription = value;

        let descriptionElement =
            card.querySelector(".task-description");

        if (value.trim() !== "") {

            if (!descriptionElement) {

                descriptionElement =
                    document.createElement("div");

                descriptionElement.className =
                    "task-description";

                const titleElement =
                    card.querySelector(".task-title");

                if (titleElement) {

                    titleElement.insertAdjacentElement(
                        "afterend",
                        descriptionElement
                    );

                }

            }

            descriptionElement.innerHTML =
                escapeHtml(value).replace(/\n/g, "<br>");

        }
        else if (descriptionElement) {

            descriptionElement.remove();

        }

    }

}


/* =========================================================
   UPDATE COLUMN TASK COUNT
========================================================= */

function updateColumnCount(column)
{

    if (!column) {
        return;
    }

    const countElement =
        column.querySelector(".column-count");

    if (!countElement) {
        return;
    }

    const taskList =
        column.querySelector(".task-list");

    if (!taskList) {
        return;
    }

    countElement.textContent =
        taskList.querySelectorAll(".task-card").length;

}


/* =========================================================
   TASK CARD CLICK
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (!card) {

            return;

        }


        if (
            event.target.closest(
                ".task-menu"
            ) ||
            event.target.closest("a") ||
            event.target.closest("button")
        ) {

            return;

        }


        if (
            draggedCard ||
            isTouchDragging ||
            suppressCardClick
        ) {

            return;

        }


        openTaskDetails(card);

    }
);


/* =========================================================
   DELETE TASK (NO PAGE NAVIGATION)
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const deleteLink =
            event.target.closest(
                ".delete-link"
            );

        if (!deleteLink) {

            return;

        }


        event.preventDefault();


        const confirmed =
            confirm(
                "Are you sure you want to delete this task?"
            );

        if (!confirmed) {

            return;

        }


        const card =
            deleteLink.closest(
                ".task-card"
            );

        const deleteUrl =
            deleteLink.getAttribute(
                "href"
            );


        fetch(
            deleteUrl,
            {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin"
            }
        )
            .then(function() {

                document
                    .querySelectorAll(
                        ".task-menu-content.show"
                    )
                    .forEach(
                        function(item) {

                            item.classList.remove(
                                "show"
                            );

                        }
                    );


                if (card) {

                    const column =
                        card.closest(
                            ".task-column"
                        );

                    card.remove();

                    updateColumnCount(
                        column
                    );

                }

            })
            .catch(function() {

                alert(
                    "Could not delete the task. Please try again."
                );

            });

    }
);


/* =========================================================
   ADD COMMENT BUTTON
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        if (
            event.target.closest(
                "#addCommentButton"
            )
        ) {

            event.preventDefault();

            addTaskComment();

        }

    }
);


/* =========================================================
   BOARD CARD KEYBOARD SELECT
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        const boardCard = event.target.closest(".board-card");

        if (!boardCard || event.target.closest(".board-card-add")) {
            return;
        }

        event.preventDefault();

        const boardId = Number(boardCard.dataset.boardId || 0);

        if (boardId > 0) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("board_id", boardId);
            window.location.href = currentUrl.toString();
        }

    }
);


/* =========================================================
   CTRL + ENTER ADD COMMENT
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.target &&
            event.target.id ===
            "taskCommentInput" &&
            event.ctrlKey &&
            event.key === "Enter"
        ) {

            event.preventDefault();

            addTaskComment();

        }

    }
);


/* =========================================================
   FILE INPUT CHANGE
========================================================= */

document.addEventListener(
    "change",
    function(event) {

        if (
            event.target.id !==
            "taskAttachmentInput"
        ) {

            return;

        }


        const file =
            event.target.files[0];


        if (file) {

            uploadTaskAttachment(
                file
            );

        }


        event.target.value = "";

    }
);


/* =========================================================
   THREE DOT MENU
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const menuButton =
            event.target.closest(
                ".task-menu-button"
            );


        if (menuButton) {

            event.preventDefault();

            event.stopPropagation();


            const menu =
                menuButton
                    .closest(".task-menu")
                    ?.querySelector(
                        ".task-menu-content"
                    );


            document
                .querySelectorAll(
                    ".task-menu-content.show"
                )
                .forEach(
                    function(item) {

                        if (item !== menu) {

                            item.classList.remove(
                                "show"
                            );

                        }

                    }
                );


            if (menu) {

                menu.classList.toggle(
                    "show"
                );

            }


            return;

        }


        if (
            !event.target.closest(
                ".task-menu-content"
            )
        ) {

            document
                .querySelectorAll(
                    ".task-menu-content.show"
                )
                .forEach(
                    function(item) {

                        item.classList.remove(
                            "show"
                        );

                    }
                );

        }

    }
);


/* =========================================================
   SHOW MESSAGE
========================================================= */

function showMessage(
    element,
    duration
)
{

    if (!element) {

        return;

    }


    element.style.display =
        "block";


    clearTimeout(
        element.hideTimer
    );


    element.hideTimer =
        setTimeout(
            function() {

                element.style.display =
                    "none";

            },
            duration || 1500
        );

}


/* =========================================================
   DESKTOP DRAG START
========================================================= */

document.addEventListener(
    "dragstart",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (!card) {

            return;

        }


        suppressCardClick =
            true;


        draggedCard =
            card;


        card.classList.add(
            "dragging"
        );


        if (event.dataTransfer) {

            event.dataTransfer.effectAllowed =
                "move";


            event.dataTransfer.setData(
                "text/plain",
                card.dataset.taskId
            );

        }


        showMessage(
            document.getElementById(
                "dragHint"
            ),
            3000
        );

    }
);


/* =========================================================
   DESKTOP DRAG END
========================================================= */

document.addEventListener(
    "dragend",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (card) {

            card.classList.remove(
                "dragging"
            );

        }


        clearColumnHighlights();


        draggedCard =
            null;


        setTimeout(
            function() {

                suppressCardClick =
                    false;

            },
            250
        );

    }
);


/* =========================================================
   GET TASK BEFORE POSITION
========================================================= */

function getTaskBefore(
    container,
    y
)
{

    const cards = [
        ...container.querySelectorAll(
            ".task-card:not(.dragging):not(.touch-dragging)"
        )
    ];


    let closest = null;

    let closestOffset =
        Number.NEGATIVE_INFINITY;


    cards.forEach(
        function(card) {

            const box =
                card.getBoundingClientRect();


            const offset =
                y -
                box.top -
                box.height / 2;


            if (
                offset < 0 &&
                offset > closestOffset
            ) {

                closestOffset =
                    offset;

                closest =
                    card;

            }

        }
    );


    return closest;

}


/* =========================================================
   SHOW DROP POSITION
========================================================= */

function showDropPosition(
    container,
    y
)
{

    document
        .querySelectorAll(
            ".drop-indicator"
        )
        .forEach(
            function(indicator) {

                indicator.remove();

            }
        );


    const before =
        getTaskBefore(
            container,
            y
        );


    const indicator =
        document.createElement(
            "div"
        );


    indicator.className =
        "drop-indicator";


    indicator.style.display =
        "block";


    if (before) {

        before.parentNode.insertBefore(
            indicator,
            before
        );

    }
    else {

        container.appendChild(
            indicator
        );

    }

}


/* =========================================================
   CLEAR COLUMN HIGHLIGHTS
========================================================= */

function clearColumnHighlights()
{

    document
        .querySelectorAll(
            ".task-column"
        )
        .forEach(
            function(column) {

                column.classList.remove(
                    "drag-over"
                );

            }
        );


    document
        .querySelectorAll(
            ".drop-indicator"
        )
        .forEach(
            function(indicator) {

                indicator.remove();

            }
        );

}


/* =========================================================
   GET COLUMN FROM POINT
========================================================= */

function getColumnFromPoint(
    x,
    y
)
{

    const element =
        document.elementFromPoint(
            x,
            y
        );


    if (!element) {

        return null;

    }


    return element.closest(
        ".task-column"
    );

}


/* =========================================================
   DRAG AUTO-SCROLL HELPERS
   Horizontal board scroll + vertical column scroll.
========================================================= */

function autoScrollWhileDragging(x, y, activeColumn)
{

    const board =
        document.querySelector(
            ".board-wrapper"
        );


    /* =====================================================
       OUTER BOARD VERTICAL SCROLL
       Keeps the whole six-column board independently scrollable
       in addition to each column's own task-list scroll.
    ===================================================== */

    if (board) {

        const boardRect =
            board.getBoundingClientRect();

        const outerEdgeSize = 55;
        const outerMaxSpeed = 12;

        if (board.scrollHeight > board.clientHeight) {

            if (y >= boardRect.bottom - outerEdgeSize) {

                const distance =
                    Math.max(0, y - (boardRect.bottom - outerEdgeSize));

                board.scrollTop += Math.min(
                    outerMaxSpeed,
                    3 + (distance / outerEdgeSize) * 9
                );

            }
            else if (y <= boardRect.top + outerEdgeSize) {

                const distance =
                    Math.max(0, (boardRect.top + outerEdgeSize) - y);

                board.scrollTop -= Math.min(
                    outerMaxSpeed,
                    3 + (distance / outerEdgeSize) * 9
                );

            }

        }

    }


    /* =====================================================
       HORIZONTAL BOARD SCROLL
    ===================================================== */

    if (board) {

        const rect =
            board.getBoundingClientRect();

        const edgeSize = 70;
        const maxSpeed = 18;

        /* Only scroll horizontally when the board actually has overflow. */
        if (board.scrollWidth > board.clientWidth && x >= rect.right - edgeSize) {

            const distance =
                Math.max(0, x - (rect.right - edgeSize));

            const speed =
                Math.min(
                    maxSpeed,
                    5 + (distance / edgeSize) * 13
                );

            board.scrollLeft += speed;

        }
        else if (board.scrollWidth > board.clientWidth && x <= rect.left + edgeSize) {

            const distance =
                Math.max(0, (rect.left + edgeSize) - x);

            const speed =
                Math.min(
                    maxSpeed,
                    5 + (distance / edgeSize) * 13
                );

            board.scrollLeft -= speed;

        }

    }


    /* =====================================================
       VERTICAL ACTIVE-COLUMN SCROLL
    ===================================================== */

    if (activeColumn) {

        const list =
            activeColumn.querySelector(
                ".task-list"
            );

        if (list && list.scrollHeight > list.clientHeight) {

            const rect =
                list.getBoundingClientRect();

            const edgeSize = 65;
            const maxSpeed = 16;

            if (y >= rect.bottom - edgeSize) {

                const distance =
                    Math.max(0, y - (rect.bottom - edgeSize));

                const speed =
                    Math.min(
                        maxSpeed,
                        4 + (distance / edgeSize) * 12
                    );

                list.scrollTop += speed;

            }
            else if (y <= rect.top + edgeSize) {

                const distance =
                    Math.max(0, (rect.top + edgeSize) - y);

                const speed =
                    Math.min(
                        maxSpeed,
                        4 + (distance / edgeSize) * 12
                    );

                list.scrollTop -= speed;

            }

        }

    }

}


function autoScrollPageWhileDragging(y)
{

    const edgeSize = 55;
    const maxSpeed = 12;

    if (y >= window.innerHeight - edgeSize) {

        const distance =
            Math.max(0, y - (window.innerHeight - edgeSize));

        window.scrollBy({
            top: Math.min(
                maxSpeed,
                3 + (distance / edgeSize) * 9
            ),
            left: 0
        });

    }
    else if (y <= edgeSize) {

        const distance =
            Math.max(0, edgeSize - y);

        window.scrollBy({
            top: -Math.min(
                maxSpeed,
                3 + (distance / edgeSize) * 9
            ),
            left: 0
        });

    }

}


/* =========================================================
   DESKTOP DRAG OVER
========================================================= */

document.addEventListener(
    "dragover",
    function(event) {

        if (!draggedCard) {

            return;

        }


        const column =
            event.target.closest(
                ".task-column"
            );


        if (!column) {

            return;

        }


        event.preventDefault();


        autoScrollWhileDragging(
            event.clientX,
            event.clientY,
            column
        );

        autoScrollPageWhileDragging(
            event.clientY
        );


        column.classList.add(
            "drag-over"
        );


        const list =
            column.querySelector(
                ".task-list"
            );


        if (list) {

            showDropPosition(
                list,
                event.clientY
            );

        }

    }
);


/* =========================================================
   DESKTOP DRAG LEAVE
========================================================= */

document.addEventListener(
    "dragleave",
    function(event) {

        const column =
            event.target.closest(
                ".task-column"
            );


        if (!column) {

            return;

        }


        if (
            !column.contains(
                event.relatedTarget
            )
        ) {

            column.classList.remove(
                "drag-over"
            );

        }

    }
);


/* =========================================================
   SAVE TASK PROGRESS
========================================================= */

function saveTaskProgress(
    taskId,
    progress
)
{
    if (!taskId || !progress) {
        return Promise.resolve(false);
    }

    const formData = new FormData();
    formData.append("task_id", taskId);
    formData.append("progress", progress);
    formData.append("board_id", <?= (int)$selected_board_id ?>);

    return fetch("index.php", {
        method: "POST",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        },
        body: formData
    })
    .then(async function(response) {
        const raw = await response.text();
        let data;

        try {
            data = JSON.parse(raw);
        } catch (error) {
            console.error("Progress update returned non-JSON:", raw);
            throw new Error("Unable to update task progress.");
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Unable to update task progress.");
        }

        return data;
    })
    .catch(function(error) {
        console.error("Progress update error:", error);
        alert(error.message || "Unable to update task progress.");
        return false;
    });
}


/* =========================================================
   DESKTOP DROP
========================================================= */

document.addEventListener(
    "drop",
    function(event) {

        if (!draggedCard) {

            return;

        }


        event.preventDefault();


        const column =
            event.target.closest(
                ".task-column"
            );


        if (!column) {

            return;

        }


        const newProgress =
            column.dataset.progress;


        const taskId =
            draggedCard.dataset.taskId;


        clearColumnHighlights();


        if (
            newProgress &&
            taskId
        ) {

            saveTaskProgress(
                taskId,
                newProgress
            ).then(function(data) {

                if (data && data.success) {
                    updateCardAfterFieldChange(
                        taskId,
                        "progress",
                        newProgress,
                        null
                    );
                }

            });

        }

    }
);


/* =========================================================
   OUTER BOARD DIRECT TOUCH SCROLL
   Swipe anywhere on the outer board background/header to
   scroll the complete six-column board in both directions.
   Task cards keep the existing long-press drag behavior and
   task lists keep their own vertical scrolling.
========================================================= */

document.addEventListener(
    "touchstart",
    function(event) {

        const board = event.target.closest(".board-wrapper");

        if (!board) {
            return;
        }

        /* Do not interfere with card drag (columns now scroll too). */
        if (
            event.target.closest(".task-card")
        ) {
            return;
        }

        const touch = event.touches[0];

        if (!touch) {
            return;
        }

        boardTouchScrolling = true;
        boardTouchMoved = false;
        boardTouchStartX = touch.clientX;
        boardTouchStartY = touch.clientY;
        boardTouchLastX = touch.clientX;
        boardTouchLastY = touch.clientY;

        boardTouchStartedInList =
            !!event.target.closest(".task-list");

    },
    { passive: true }
);


document.addEventListener(
    "touchmove",
    function(event) {

        if (!boardTouchScrolling) {
            return;
        }

        const board = document.querySelector(".board-wrapper");
        const touch = event.touches[0];

        if (!board || !touch) {
            return;
        }

        const dx = touch.clientX - boardTouchLastX;
        const dy = touch.clientY - boardTouchLastY;

        const totalX = touch.clientX - boardTouchStartX;
        const totalY = touch.clientY - boardTouchStartY;

        if (
            Math.abs(totalX) > TOUCH_MOVE_THRESHOLD ||
            Math.abs(totalY) > TOUCH_MOVE_THRESHOLD
        ) {
            boardTouchMoved = true;
        }

        if (!boardTouchMoved) {
            return;
        }

        /* Both column (task list) and background swipes now scroll
           the board horizontally and the relevant list/board
           vertically, via the shared helper. */
        performBoardTouchScroll(
            event.target,
            dx,
            dy
        );

        boardTouchLastX = touch.clientX;
        boardTouchLastY = touch.clientY;

        event.preventDefault();

    },
    { passive: false }
);


document.addEventListener(
    "touchend",
    function() {

        boardTouchScrolling = false;
        boardTouchMoved = false;
        boardTouchStartedInList = false;

    },
    { passive: true }
);


document.addEventListener(
    "touchcancel",
    function() {

        boardTouchScrolling = false;
        boardTouchMoved = false;
        boardTouchStartedInList = false;

    },
    { passive: true }
);


/* =========================================================
   TOUCH START
========================================================= */

document.addEventListener(
    "touchstart",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (!card) {

            return;

        }


        if (
            event.target.closest(
                ".task-menu"
            ) ||
            event.target.closest("a") ||
            event.target.closest("button")
        ) {

            return;

        }


        touchDraggedCard =
            card;


        touchStartX =
            event.touches[0].clientX;


        touchStartY =
            event.touches[0].clientY;


        touchCurrentX =
            touchStartX;


        touchCurrentY =
            touchStartY;


        isTouchDragging =
            false;


        originalParent =
            card.parentNode;


        originalNextSibling =
            card.nextSibling;


        card.classList.add(
            "touch-ready"
        );


        clearTimeout(
            touchLongPressTimer
        );


        touchLongPressTimer =
            setTimeout(
                function() {

                    if (!touchDraggedCard) {

                        return;

                    }


                    isTouchDragging =
                        true;


                    suppressCardClick =
                        true;


                    touchDraggedCard.classList.remove(
                        "touch-ready"
                    );


                    touchDraggedCard.classList.add(
                        "touch-dragging"
                    );


                    touchDraggedCard.classList.add(
                        "touch-source"
                    );


                    showMessage(
                        document.getElementById(
                            "dragHint"
                        ),
                        3000
                    );

                },
                TOUCH_LONG_PRESS
            );

    },
    {
        passive: true
    }
);


/* =========================================================
   TOUCH MOVE
========================================================= */

document.addEventListener(
    "touchmove",
    function(event) {

        if (!touchDraggedCard) {

            return;

        }


        touchCurrentX =
            event.touches[0].clientX;


        touchCurrentY =
            event.touches[0].clientY;


        const deltaX =
            touchCurrentX -
            touchStartX;


        const deltaY =
            touchCurrentY -
            touchStartY;


        const distance =
            Math.sqrt(
                deltaX * deltaX +
                deltaY * deltaY
            );


        if (!isTouchDragging) {

            /*
             * A normal swipe on a task card must scroll the OUTER
             * six-column board in both directions. Do this before
             * cancelling the card touch state so horizontal and
             * vertical swipes are not trapped by the card/column.
             * A short tap still opens the task details, while a
             * long press still enters the existing drag mode.
             */
            if (
                distance >
                TOUCH_MOVE_THRESHOLD
            ) {

                clearTimeout(
                    touchLongPressTimer
                );


                performBoardTouchScroll(
                    event.target,
                    touchCurrentX - touchStartX,
                    touchCurrentY - touchStartY
                );


                touchDraggedCard.classList.remove(
                    "touch-ready"
                );


                touchDraggedCard =
                    null;


                event.preventDefault();

            }


            return;

        }


        event.preventDefault();


        touchDraggedCard.style.transform =
            "translate3d(" +
            deltaX +
            "px," +
            deltaY +
            "px,0) scale(1.03)";


        const column =
            getColumnFromPoint(
                touchCurrentX,
                touchCurrentY
            );


        clearColumnHighlights();


        if (column) {

            column.classList.add(
                "drag-over"
            );


            const list =
                column.querySelector(
                    ".task-list"
                );


            if (list) {

                showDropPosition(
                    list,
                    touchCurrentY
                );

            }

        }


        /* =================================================
           AUTO HORIZONTAL + COLUMN VERTICAL SCROLL
        ================================================= */

        autoScrollWhileDragging(
            touchCurrentX,
            touchCurrentY,
            column
        );


        /* =================================================
           VERTICAL PAGE SCROLL
        ================================================= */

        autoScrollPageWhileDragging(
            touchCurrentY
        );

    },
    {
        passive: false
    }
);


/* =========================================================
   TOUCH END
========================================================= */

document.addEventListener(
    "touchend",
    function() {

        clearTimeout(
            touchLongPressTimer
        );


        if (!touchDraggedCard) {

            return;

        }


        if (!isTouchDragging) {

            touchDraggedCard.classList.remove(
                "touch-ready"
            );


            touchDraggedCard =
                null;


            return;

        }


        const card =
            touchDraggedCard;


        const taskId =
            card.dataset.taskId;


        const column =
            getColumnFromPoint(
                touchCurrentX,
                touchCurrentY
            );


        card.style.transform =
            "";


        card.classList.remove(
            "touch-dragging"
        );


        card.classList.remove(
            "touch-source"
        );


        clearColumnHighlights();


        if (
            column &&
            taskId
        ) {

            const newProgress =
                column.dataset.progress;


            saveTaskProgress(
                taskId,
                newProgress
            ).then(function(data) {

                if (data && data.success) {
                    updateCardAfterFieldChange(
                        taskId,
                        "progress",
                        newProgress,
                        null
                    );
                }

            });

        }
        else {

            if (
                originalParent &&
                card.parentNode !==
                originalParent
            ) {

                if (
                    originalNextSibling &&
                    originalNextSibling.parentNode ===
                    originalParent
                ) {

                    originalParent.insertBefore(
                        card,
                        originalNextSibling
                    );

                }
                else {

                    originalParent.appendChild(
                        card
                    );

                }

            }

        }


        touchDraggedCard =
            null;


        isTouchDragging =
            false;


        originalParent =
            null;


        originalNextSibling =
            null;


        setTimeout(
            function() {

                suppressCardClick =
                    false;

            },
            250
        );

    },
    {
        passive: true
    }
);


/* =========================================================
   TOUCH CANCEL
========================================================= */

document.addEventListener(
    "touchcancel",
    function() {

        clearTimeout(
            touchLongPressTimer
        );


        if (touchDraggedCard) {

            touchDraggedCard.classList.remove(
                "touch-ready"
            );


            touchDraggedCard.classList.remove(
                "touch-dragging"
            );


            touchDraggedCard.classList.remove(
                "touch-source"
            );


            touchDraggedCard.style.transform =
                "";

        }


        clearColumnHighlights();


        touchDraggedCard =
            null;


        isTouchDragging =
            false;


        originalParent =
            null;


        originalNextSibling =
            null;


        setTimeout(
            function() {

                suppressCardClick =
                    false;

            },
            250
        );

    },
    {
        passive: true
    }
);


/* =========================================================
   ESCAPE = CANCEL TOUCH DRAG
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key !==
            "Escape"
        ) {

            return;

        }


        clearTimeout(
            touchLongPressTimer
        );


        if (touchDraggedCard) {

            touchDraggedCard.classList.remove(
                "touch-ready"
            );


            touchDraggedCard.classList.remove(
                "touch-dragging"
            );


            touchDraggedCard.classList.remove(
                "touch-source"
            );


            touchDraggedCard.style.transform =
                "";

        }


        clearColumnHighlights();


        touchDraggedCard =
            null;


        isTouchDragging =
            false;


        originalParent =
            null;


        originalNextSibling =
            null;


        suppressCardClick =
            false;

    }
);


/* =========================================================
   VISIBILITY CHANGE CLEANUP
========================================================= */

document.addEventListener(
    "visibilitychange",
    function() {

        if (document.hidden) {

            clearTimeout(
                touchLongPressTimer
            );


            if (touchDraggedCard) {

                touchDraggedCard.classList.remove(
                    "touch-ready"
                );


                touchDraggedCard.classList.remove(
                    "touch-dragging"
                );


                touchDraggedCard.classList.remove(
                    "touch-source"
                );


                touchDraggedCard.style.transform =
                    "";

            }


            clearColumnHighlights();


            touchDraggedCard =
                null;


            isTouchDragging =
                false;


            originalParent =
                null;


            originalNextSibling =
                null;


            suppressCardClick =
                false;

        }

    }
);


/* =========================================================
   ADD / EDIT TASK MODALS
========================================================= */

const addBoardModalElement =
    document.getElementById(
        "addBoardModal"
    );


let addBoardModal = null;


if (addBoardModalElement) {
    addBoardModal =
        new bootstrap.Modal(
            addBoardModalElement
        );
}


const addTaskModalElement =
    document.getElementById(
        "addTaskModal"
    );


let addTaskModal = null;


if (addTaskModalElement) {

    addTaskModal =
        new bootstrap.Modal(
            addTaskModalElement
        );

}


/* =========================================================
   OPEN ADD TASK MODAL
========================================================= */

function openAddTaskModal(progress, boardId)
{

    if (!addTaskModal) {

        return;

    }


    const form =
        document.getElementById(
            "addTaskForm"
        );


    if (form) {

        form.reset();

        const boardInput =
            document.getElementById(
                "addTaskBoardId"
            );

        if (boardInput) {
            boardInput.value = Number(boardId) > 0
                ? Number(boardId)
                : <?= (int)$selected_board_id ?>;
        }

    }


    const errorBox =
        document.getElementById(
            "addTaskError"
        );


    if (errorBox) {

        errorBox.textContent = "";

        errorBox.classList.add(
            "d-none"
        );

    }


    const progressSelect =
        document.getElementById(
            "addTaskProgress"
        );


    if (
        progressSelect &&
        progress
    ) {

        progressSelect.value =
            progress;

    }


    addTaskModal.show();

}


/* =========================================================
   SUBMIT ADD / EDIT TASK FORM VIA AJAX
========================================================= */

function submitTaskForm(
    formId,
    actionUrl,
    errorBoxId,
    submitBtnId
)
{

    const form =
        document.getElementById(
            formId
        );


    if (!form) {

        return;

    }


    if (!form.reportValidity()) {

        return;

    }


    const errorBox =
        document.getElementById(
            errorBoxId
        );


    const submitBtn =
        document.getElementById(
            submitBtnId
        );


    const originalBtnHtml =
        submitBtn ?
        submitBtn.innerHTML :
        "";


    if (submitBtn) {

        submitBtn.disabled = true;

        submitBtn.innerHTML = `
            <span
                class="spinner-border spinner-border-sm"
            ></span>

            Saving...
        `;

    }


    if (errorBox) {

        errorBox.textContent = "";

        errorBox.classList.add(
            "d-none"
        );

    }


    const formData =
        new FormData(form);


    fetch(
        actionUrl,
        {
            method: "POST",
            headers: {
                "X-Requested-With":
                    "XMLHttpRequest"
            },
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            if (errorBox) {

                errorBox.textContent =
                    data.message ||
                    "Something went wrong.";

                errorBox.classList.remove(
                    "d-none"
                );

            }

            return;

        }


        window.location.reload();

    })
    .catch(function(error) {

        console.error(
            "Task form error:",
            error
        );


        if (errorBox) {

            errorBox.textContent =
                "Unable to save task. Please try again.";

            errorBox.classList.remove(
                "d-none"
            );

        }

    })
    .finally(function() {

        if (submitBtn) {

            submitBtn.disabled = false;

            submitBtn.innerHTML =
                originalBtnHtml;

        }

    });

}


/* =========================================================
   ADD BOARD VIA AJAX
   Add the board directly into the BOARD column.
========================================================= */

const addBoardForm = document.getElementById("addBoardForm");

if (addBoardForm) {

    addBoardForm.addEventListener("submit", function(event) {

        event.preventDefault();

        const submitBtn = addBoardForm.querySelector("button[type=submit]");
        const errorBox = addBoardForm.querySelector(".alert-danger");
        const originalHtml = submitBtn ? submitBtn.innerHTML : "";

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
        }

        if (errorBox) {
            errorBox.textContent = "";
            errorBox.classList.add("d-none");
        }

        const formData = new FormData(addBoardForm);

        fetch(addBoardForm.action, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: formData
        })
        .then(async function(response) {
            const raw = await response.text();
            let data;

            try {
                data = JSON.parse(raw);
            } catch (e) {
                console.error("Create board returned non-JSON:", raw);
                const preview = raw.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
                throw new Error(
                    "Invalid server response (HTTP " + response.status + "). " +
                    (preview ? preview.substring(0, 500) : "The server returned an empty response.")
                );
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to create board.");
            }

            return data;
        })
        .then(function(data) {

            const board = data.board;
            if (!board || !board.id) {
                throw new Error("Board was created but no board data was returned.");
            }

            const boardList = document.querySelector(".board-card-list");
            const boardCount = document.querySelector(".board-task-column .column-count");

            if (boardList) {
                const emptyState = boardList.querySelector(".board-empty");
                if (emptyState) emptyState.remove();

                boardList.querySelectorAll(".board-card").forEach(function(card) {
                    card.classList.remove("active");
                });

                const boardCard = document.createElement("div");
                boardCard.className = "board-card active";
                boardCard.dataset.boardId = board.id;
                boardCard.setAttribute("role", "button");
                boardCard.setAttribute("tabindex", "0");

                boardCard.innerHTML = `
                    <div class="board-card-name">${escapeHtml(board.name)}</div>
                    ${board.description ? `<div class="board-card-description">${escapeHtml(board.description)}</div>` : ""}
                    <div class="board-card-menu">
                        <button type="button" class="board-card-menu-button" aria-label="Board menu" title="Board menu">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <div class="board-card-menu-content">
                            <button type="button" class="board-card-menu-delete" data-board-id="${Number(board.id)}">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </div>
                    </div>`;

                boardList.appendChild(boardCard);
            }

            if (boardCount) {
                boardCount.textContent = document.querySelectorAll(".board-card").length;
            }

            const boardIdInput = document.getElementById("addTaskBoardId");
            if (boardIdInput) boardIdInput.value = board.id;

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("board_id", board.id);
            window.history.replaceState({}, "", currentUrl.toString());

            addBoardForm.reset();
            if (addBoardModal) addBoardModal.hide();

        })
        .catch(function(error) {
            console.error("Create board error:", error);

            if (errorBox) {
                errorBox.textContent = error.message || "Unable to create board.";
                errorBox.classList.remove("d-none");
            } else {
                alert(error.message || "Unable to create board.");
            }

        })
        .finally(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        });

    });
}


/* =========================================================
   ADD / EDIT SUBMIT BUTTON CLICKS
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        if (
            event.target.closest(
                "#addTaskSubmitBtn"
            )
        ) {

            submitTaskForm(
                "addTaskForm",
                "add.php",
                "addTaskError",
                "addTaskSubmitBtn"
            );

            return;

        }

    }
);


/* =========================================================
   INTERCEPT ADD BOARD / COLUMN ADD / EDIT LINKS
   OPEN MODALS INSTEAD OF NAVIGATING
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        /* =================================================
           BOARD COLUMN + ADD BOARD
        ================================================= */

        const boardColumnAddBtn =
            event.target.closest("#boardColumnAddBtn");

        if (boardColumnAddBtn) {
            event.preventDefault();
            if (addBoardModal) addBoardModal.show();
            return;
        }


        /* =================================================
           BOARD CARD THREE-DOT MENU
        ================================================= */

        const boardMenuButton =
            event.target.closest(".board-card-menu-button");

        if (boardMenuButton) {
            event.preventDefault();
            event.stopPropagation();

            const menu = boardMenuButton
                .closest(".board-card-menu")
                ?.querySelector(".board-card-menu-content");

            document.querySelectorAll(".board-card-menu-content.show")
                .forEach(function(item) {
                    if (item !== menu) item.classList.remove("show");
                });

            if (menu) menu.classList.toggle("show");
            return;
        }


        /* =================================================
           DELETE BOARD FROM THREE-DOT MENU
        ================================================= */

        const boardDeleteBtn =
            event.target.closest(".board-card-menu-delete");

        if (boardDeleteBtn) {
            event.preventDefault();
            event.stopPropagation();

            const boardId = Number(boardDeleteBtn.dataset.boardId || 0);
            const boardCard = boardDeleteBtn.closest(".board-card");
            const boardName = boardCard
                ? (boardCard.querySelector(".board-card-name")?.textContent.trim() || "this board")
                : "this board";

            if (boardId <= 0) return;

            if (!confirm('Are you sure you want to delete "' + boardName + '"?\n\nA board can only be deleted when it has no tasks.')) {
                return;
            }

            boardDeleteBtn.disabled = true;
            boardDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const formData = new FormData();
            formData.append("action", "delete_board");
            formData.append("board_id", boardId);

            fetch("index.php", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(async function(response) {
                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    console.error("Delete board returned non-JSON:", raw);
                    throw new Error("The server returned an invalid response. Please check the PHP error/log.");
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Unable to delete board.");
                }
                return data;
            })
            .then(function() {
                if (boardCard) boardCard.remove();

                const remainingCards = document.querySelectorAll(".board-card");
                const boardCount = document.querySelector(".board-task-column .column-count");
                if (boardCount) boardCount.textContent = remainingCards.length;

                const deletedWasSelected =
                    Number(new URL(window.location.href).searchParams.get("board_id") || 0) === boardId;

                if (deletedWasSelected) {
                    const firstBoard = document.querySelector(".board-card");
                    const currentUrl = new URL(window.location.href);

                    if (firstBoard) {
                        const firstBoardId = Number(firstBoard.dataset.boardId);
                        currentUrl.searchParams.set("board_id", firstBoardId);
                        window.history.replaceState({}, "", currentUrl.toString());

                        document.querySelectorAll(".board-card").forEach(function(card) {
                            card.classList.toggle("active", Number(card.dataset.boardId) === firstBoardId);
                        });

                        const boardIdInput = document.getElementById("addTaskBoardId");
                        if (boardIdInput) boardIdInput.value = firstBoardId;
                    } else {
                        currentUrl.searchParams.delete("board_id");
                        window.history.replaceState({}, "", currentUrl.toString());
                    }
                }
            })
            .catch(function(error) {
                console.error("Delete board error:", error);
                alert(error.message || "Unable to delete board.");
            })
            .finally(function() {
                boardDeleteBtn.disabled = false;
                boardDeleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete';
            });

            return;
        }


        /* =================================================
           CLOSE BOARD MENUS WHEN CLICKING ELSEWHERE
        ================================================= */

        if (!event.target.closest(".board-card-menu")) {
            document.querySelectorAll(".board-card-menu-content.show")
                .forEach(function(menu) { menu.classList.remove("show"); });
        }


        /* =================================================
           SELECT BOARD IN THE SAME PAGE
        ================================================= */

        const boardCard = event.target.closest(".board-card");

        if (boardCard) {
            event.preventDefault();

            const boardId = Number(boardCard.dataset.boardId || 0);

            if (boardId > 0) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set("board_id", boardId);
                window.location.href = currentUrl.toString();
            }

            return;
        }


        /* =================================================
           TOP RIGHT "+ ADD BOARD" BUTTON
        ================================================= */

        const addBoardBtn =
            event.target.closest(
                "#addBoardBtn"
            );

        if (addBoardBtn) {
            event.preventDefault();

            if (addBoardModal) {
                addBoardModal.show();
            }

            return;
        }


        /* =================================================
           PER COLUMN "+" ADD BUTTON
        ================================================= */

        const columnAddBtn =
            event.target.closest(
                "a.column-add-btn"
            );


        if (columnAddBtn) {

            event.preventDefault();


            const url =
                new URL(
                    columnAddBtn.href,
                    window.location.href
                );


            const progress =
                url.searchParams.get(
                    "progress"
                ) || "Todo";


            openAddTaskModal(
                progress
            );

            return;

        }

    }
);

</script>

</body>

</html>