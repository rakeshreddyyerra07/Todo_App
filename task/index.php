<?php

session_start();

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
   BOARD / CREATE BOARD
========================================================= */

$selected_board_id = isset($_GET["board_id"]) ? (int)$_GET["board_id"] : 0;

$board_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create_board") {

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
                "INSERT INTO todo_app.boards (name, description, created_by) VALUES (?, ?, ?)"
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
                    header("Location: index.php?board_id=" . (int)$new_board_id);
                    exit();
                }

                $board_error = "Database Error: " . mysqli_stmt_error($board_stmt);
                mysqli_stmt_close($board_stmt);
            }
        }
    }
}


/* =========================================================
   LOAD BOARDS
========================================================= */

$boards = [];

$board_result = mysqli_query(
    $conn,
    "SELECT id, name, description FROM todo_app.boards ORDER BY id ASC"
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
    FROM todo_app.tasks
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

$review_tasks = [];

$done_tasks = [];


while ($row = mysqli_fetch_assoc($result)) {

    if ($row["progress"] === "Todo") {

        $todo_tasks[] = $row;

    }

    elseif ($row["progress"] === "In Progress") {

        $in_progress_tasks[] = $row;

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
        "Review",
        "Done"
    ];


    if (
        $task_id > 0 &&
        in_array(
            $new_progress,
            $allowed_progress,
            true
        )
    ) {

        $update_sql = "
            UPDATE todo_app.tasks
            SET
                progress = ?,
                editedDate = NOW()
            WHERE id = ?
        ";


        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );


        if ($update_stmt) {

            mysqli_stmt_bind_param(
                $update_stmt,
                "si",
                $new_progress,
                $task_id
            );


            mysqli_stmt_execute(
                $update_stmt
            );


            mysqli_stmt_close(
                $update_stmt
            );

        }

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


    .board-list-wrapper {
        margin-bottom: 18px;
        padding: 18px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }


    .board-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }


    .board-list-title {
        font-size: 18px;
        font-weight: 700;
    }


    .board-list-subtitle {
        margin-top: 3px;
        color: #6b7280;
        font-size: 13px;
    }


    .board-list {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 2px;
    }


    .board-list-item {
        display: inline-flex;
        align-items: center;
        min-width: 150px;
        min-height: 48px;
        padding: 10px 16px;
        border: 1px solid #d9dee7;
        border-radius: 9px;
        background: #f8fafc;
        color: #1f2937;
        text-decoration: none;
        font-weight: 600;
        transition: .15s ease;
    }


    .board-list-item:hover {
        border-color: #1473e6;
        color: #1473e6;
    }


    .board-list-item.active {
        background: #1473e6;
        border-color: #1473e6;
        color: #ffffff;
    }


    .board-list-item-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    .board-list-empty {
        color: #6b7280;
        padding: 10px 0;
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
        overflow-x: auto;
        padding-bottom: 15px;
    }


    .task-board {
        display: grid;
        grid-template-columns: repeat(4, minmax(260px, 1fr));
        gap: 18px;
        min-width: 1050px;
    }


    /* =====================================================
       COLUMNS
    ===================================================== */

    .task-column {
        border-radius: 9px;
        padding: 12px;
        height: calc(100vh - 265px);
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
        touch-action: pan-y;
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
            grid-template-columns: repeat(2, minmax(280px, 1fr));
            min-width: 0;
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
            gap: 12px;
            min-width: max-content;
        }


        .task-column {
            width: 285px;
            min-width: 285px;
        }


        .board-wrapper {
            overflow-x: auto;
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
            width: 270px;
            min-width: 270px;
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

            <button
                type="button"
                class="btn btn-primary add-task-btn"
                id="topAddTaskBtn"
                data-progress="Todo"
            >
                + Add Card/Task
            </button>

        <?php endif; ?>


    </div>

</div>


<!-- =========================================================
     BOARD LIST
========================================================= -->

<div class="board-list-wrapper">

    <div class="board-list-header">
        <div>
            <div class="board-list-title">Boards</div>
            <div class="board-list-subtitle">Select a board to view its cards/tasks</div>
        </div>
    </div>

    <div class="board-list">

        <?php foreach ($boards as $board): ?>

            <a
                href="?board_id=<?= (int)$board["id"] ?>&search=<?= urlencode($search) ?>&progress=<?= urlencode($progress_filter) ?>"
                class="board-list-item <?= ((int)$board["id"] === $selected_board_id) ? "active" : "" ?>"
            >
                <span class="board-list-item-name">
                    <?= htmlspecialchars($board["name"]) ?>
                </span>
            </a>

        <?php endforeach; ?>

        <?php if (empty($boards)): ?>
            <div class="board-list-empty">No boards yet. Click <strong>+ Add Board</strong> to create one.</div>
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

                        <a href="view.php?id=<?= (int)$task["id"] ?>">
                            View
                        </a>


                        <a href="edit.php?id=<?= (int)$task["id"] ?>">
                            Edit
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                onclick="return confirm('Are you sure you want to delete this task?');"
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

                        <a href="view.php?id=<?= (int)$task["id"] ?>">
                            View
                        </a>


                        <a href="edit.php?id=<?= (int)$task["id"] ?>">
                            Edit
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                onclick="return confirm('Are you sure you want to delete this task?');"
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

                        <a href="view.php?id=<?= (int)$task["id"] ?>">
                            View
                        </a>


                        <a href="edit.php?id=<?= (int)$task["id"] ?>">
                            Edit
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                onclick="return confirm('Are you sure you want to delete this task?');"
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

                        <a href="view.php?id=<?= (int)$task["id"] ?>">
                            View
                        </a>


                        <a href="edit.php?id=<?= (int)$task["id"] ?>">
                            Edit
                        </a>


                        <?php if ($is_admin): ?>

                            <a
                                href="delete.php?id=<?= (int)$task["id"] ?>"
                                class="delete-link"
                                onclick="return confirm('Are you sure you want to delete this task?');"
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


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
            ></button>

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

                        <div class="trello-card-section-heading">
                            <i class="bi bi-text-paragraph"></i>
                            Description
                        </div>

                        <div
                            class="task-detail-description empty"
                            id="modalTaskDescription"
                        >
                            No description provided.
                        </div>

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
                            Task ID
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskId"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Priority
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskPriority"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Progress
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskProgress"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Completion
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskCompleted"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Status
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskStatus"
                        >
                            —
                        </span>

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

        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-secondary task-details-close-btn"
                data-bs-dismiss="modal"
            >
                Close
            </button>

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

            <form method="POST" action="index.php">
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


<!-- =========================================================
     EDIT TASK MODAL
========================================================= -->

<div
    class="modal fade"
    id="editTaskModal"
    tabindex="-1"
    aria-labelledby="editTaskModalLabel"
    aria-hidden="true"
>

<div class="modal-dialog modal-dialog-centered">

    <div class="modal-content">

        <div class="modal-header">

            <h5
                class="modal-title"
                id="editTaskModalLabel"
            >
                Edit Task
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
                id="editTaskError"
                class="alert alert-danger d-none"
            ></div>

            <form id="editTaskForm">

                <input
                    type="hidden"
                    name="id"
                    id="editTaskId"
                >

                <div class="mb-3">

                    <label class="form-label">
                        Task
                    </label>

                    <input
                        type="text"
                        name="task"
                        id="editTaskTitle"
                        class="form-control"
                        placeholder="Enter task title"
                        maxlength="255"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="editTaskDescription"
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
                        id="editTaskPriority"
                        class="form-select"
                    >
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Progress
                    </label>

                    <select
                        name="progress"
                        id="editTaskProgress"
                        class="form-select"
                    >
                        <option value="Todo">Todo</option>
                        <option value="In Progress">In Progress</option>
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
                        id="editTaskStatus"
                        class="form-select"
                    >
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Completion
                    </label>

                    <select
                        name="is_completed"
                        id="editTaskCompleted"
                        class="form-select"
                    >
                        <option value="0">Incomplete</option>
                        <option value="1">Complete</option>
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
                id="editTaskSubmitBtn"
            >
                Update Task
            </button>

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


    const taskCompleted =
        card.dataset.taskCompleted || "—";


    const taskStatus =
        card.dataset.taskStatus || "—";


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
        "modalTaskId",
        taskId
    );


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
        "modalTaskCompleted",
        taskCompleted
    );


    setModalValue(
        "modalTaskStatus",
        taskStatus
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
       ACTIVE / INACTIVE COLOR
    ===================================================== */

    const statusElement =
        document.getElementById(
            "modalTaskStatus"
        );


    if (statusElement) {

        statusElement.classList.remove(
            "status-active",
            "status-inactive"
        );


        if (
            taskStatus === "Active"
        ) {

            statusElement.classList.add(
                "status-active"
            );

        }
        else {

            statusElement.classList.add(
                "status-inactive"
            );

        }

    }


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

    if (
        !taskId ||
        !progress
    ) {

        return;

    }


    const form =
        document.createElement(
            "form"
        );


    form.method =
        "POST";


    form.action =
        "index.php";


    form.style.display =
        "none";


    const taskInput =
        document.createElement(
            "input"
        );


    taskInput.type =
        "hidden";


    taskInput.name =
        "task_id";


    taskInput.value =
        taskId;


    const progressInput =
        document.createElement(
            "input"
        );


    progressInput.type =
        "hidden";


    progressInput.name =
        "progress";


    progressInput.value =
        progress;


    form.appendChild(
        taskInput
    );


    form.appendChild(
        progressInput
    );


    document.body.appendChild(
        form
    );


    form.submit();

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
            );

        }

    }
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

            if (
                distance >
                TOUCH_MOVE_THRESHOLD
            ) {

                clearTimeout(
                    touchLongPressTimer
                );


                touchDraggedCard.classList.remove(
                    "touch-ready"
                );


                touchDraggedCard =
                    null;

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
           AUTO HORIZONTAL SCROLL
        ================================================= */

        const board =
            document.querySelector(
                ".board-wrapper"
            );


        if (board) {

            const rect =
                board.getBoundingClientRect();


            const edgeSize =
                55;


            const scrollSpeed =
                12;


            if (
                touchCurrentX >
                rect.right -
                edgeSize
            ) {

                board.scrollLeft +=
                    scrollSpeed;

            }
            else if (
                touchCurrentX <
                rect.left +
                edgeSize
            ) {

                board.scrollLeft -=
                    scrollSpeed;

            }

        }


        /* =================================================
           VERTICAL PAGE SCROLL
        ================================================= */

        const verticalEdge =
            60;


        if (
            touchCurrentY >
            window.innerHeight -
            verticalEdge
        ) {

            window.scrollBy(
                0,
                10
            );

        }
        else if (
            touchCurrentY <
            verticalEdge
        ) {

            window.scrollBy(
                0,
                -10
            );

        }

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
            );

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


const editTaskModalElement =
    document.getElementById(
        "editTaskModal"
    );


let editTaskModal = null;


if (editTaskModalElement) {

    editTaskModal =
        new bootstrap.Modal(
            editTaskModalElement
        );

}


/* =========================================================
   OPEN ADD TASK MODAL
========================================================= */

function openAddTaskModal(progress)
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
            boardInput.value = "<?= (int)$selected_board_id ?>";
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
   OPEN EDIT TASK MODAL
========================================================= */

function openEditTaskModal(card)
{

    if (!card || !editTaskModal) {

        return;

    }


    const errorBox =
        document.getElementById(
            "editTaskError"
        );


    if (errorBox) {

        errorBox.textContent = "";

        errorBox.classList.add(
            "d-none"
        );

    }


    document.getElementById(
        "editTaskId"
    ).value =
        card.dataset.taskId || "0";


    document.getElementById(
        "editTaskTitle"
    ).value =
        card.dataset.taskTitle || "";


    document.getElementById(
        "editTaskDescription"
    ).value =
        card.dataset.taskDescription || "";


    document.getElementById(
        "editTaskPriority"
    ).value =
        card.dataset.taskPriority || "Medium";


    document.getElementById(
        "editTaskProgress"
    ).value =
        card.dataset.taskProgress || "Todo";


    document.getElementById(
        "editTaskStatus"
    ).value =
        (
            card.dataset.taskStatus ===
            "Active"
        ) ? "1" : "0";


    document.getElementById(
        "editTaskCompleted"
    ).value =
        (
            card.dataset.taskCompleted ===
            "Complete"
        ) ? "1" : "0";


    editTaskModal.show();

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


        if (
            event.target.closest(
                "#editTaskSubmitBtn"
            )
        ) {

            const taskId =
                document.getElementById(
                    "editTaskId"
                ).value;


            submitTaskForm(
                "editTaskForm",
                "edit.php?id=" +
                encodeURIComponent(
                    taskId
                ),
                "editTaskError",
                "editTaskSubmitBtn"
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
           TOP RIGHT "+ ADD CARD/TASK" BUTTON
        ================================================= */

        const topAddTaskBtn =
            event.target.closest(
                "#topAddTaskBtn"
            );

        if (topAddTaskBtn) {
            event.preventDefault();

            openAddTaskModal(
                topAddTaskBtn.dataset.progress || "Todo"
            );

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


        /* =================================================
           EDIT LINK INSIDE TASK MENU
        ================================================= */

        const editLink =
            event.target.closest(
                'a[href^="edit.php?id="]'
            );


        if (editLink) {

            event.preventDefault();


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


            const card =
                editLink.closest(
                    ".task-card"
                );


            if (card) {

                openEditTaskModal(
                    card
                );

            }

            return;

        }

    }
);

</script>

</body>

</html>