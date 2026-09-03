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
        "d M Y, h:i A",
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
            UPDATE tasks
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


        .search-box {
            position: relative;
            width: 338px;
        }


        .search-box input {
            height: 48px;
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
            min-height: 450px;
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


        .task-list {
            min-height: 390px;
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

        @media (max-width: 1100px) {

            .task-board {
                grid-template-columns: repeat(2, minmax(280px, 1fr));
                min-width: 0;
            }


            .search-box {
                width: 280px;
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
            }


            .search-box {
                width: 100%;
            }


            .task-filter {
                width: 100%;
            }


            .add-task-btn {
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


            <!-- USER ROLE -->

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


    <!-- =====================================================
         HEADER + CONTROLS
    ====================================================== -->

    <div class="board-toolbar">


        <!-- TITLE -->

        <div class="board-title-area">

            <div class="board-heading">
                Task Board
            </div>


            <div class="board-subtitle">
                Drag and drop tasks between columns to update status
            </div>

        </div>


        <!-- CONTROLS -->

        <div class="board-controls">


            <!-- SEARCH -->

            <form
                method="GET"
                class="search-box"
            >

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

            </form>


            <!-- ALL TASKS -->

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


            <!-- ADD TASK -->

            <?php if ($is_admin): ?>

                <a
                    href="add.php"
                    class="btn btn-primary add-task-btn"
                >

                    + Add Task

                </a>

            <?php endif; ?>


        </div>

    </div>


    <!-- =====================================================
         BOARD
    ====================================================== -->

    <div class="board-wrapper">

        <div class="task-board">


            <!-- =================================================
                 TODO
            ================================================== -->

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

                </div>


                <div class="task-list">

                    <?php foreach ($todo_tasks as $task): ?>

                        <div
                            class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                            draggable="true"
                            data-task-id="<?= (int)$task["id"] ?>"
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


                                <!-- PRIORITY -->

                                <span class="priority-badge">

                                    <?= htmlspecialchars($task["priority"]) ?>

                                </span>


                                <!-- PROGRESS -->

                                <span
                                    class="progress-badge <?= htmlspecialchars(getProgressClass($task["progress"])) ?>"
                                >

                                    <?= htmlspecialchars($task["progress"]) ?>

                                </span>


                                <!-- COMPLETION -->

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


                            <!-- DATE -->

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


                            <!-- ADMIN MENU -->

                            <?php if ($is_admin): ?>

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
                                        >
                                            View
                                        </a>


                                        <a
                                            href="edit.php?id=<?= (int)$task["id"] ?>"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete.php?id=<?= (int)$task["id"] ?>"
                                            class="delete-link"
                                            onclick="return confirm('Are you sure you want to delete this task?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>


                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- =================================================
                 IN PROGRESS
            ================================================== -->

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

                </div>


                <div class="task-list">

                    <?php foreach ($in_progress_tasks as $task): ?>

                        <div
                            class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                            draggable="true"
                            data-task-id="<?= (int)$task["id"] ?>"
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


                            <?php if ($is_admin): ?>

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
                                        >
                                            View
                                        </a>


                                        <a
                                            href="edit.php?id=<?= (int)$task["id"] ?>"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete.php?id=<?= (int)$task["id"] ?>"
                                            class="delete-link"
                                            onclick="return confirm('Are you sure you want to delete this task?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- =================================================
                 REVIEW
            ================================================== -->

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

                </div>


                <div class="task-list">

                    <?php foreach ($review_tasks as $task): ?>

                        <div
                            class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                            draggable="true"
                            data-task-id="<?= (int)$task["id"] ?>"
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


                            <?php if ($is_admin): ?>

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
                                        >
                                            View
                                        </a>


                                        <a
                                            href="edit.php?id=<?= (int)$task["id"] ?>"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete.php?id=<?= (int)$task["id"] ?>"
                                            class="delete-link"
                                            onclick="return confirm('Are you sure you want to delete this task?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- =================================================
                 DONE
            ================================================== -->

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

                </div>


                <div class="task-list">

                    <?php foreach ($done_tasks as $task): ?>

                        <div
                            class="task-card <?= htmlspecialchars(getPriorityClass($task["priority"])) ?>"
                            draggable="true"
                            data-task-id="<?= (int)$task["id"] ?>"
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


                            <?php if ($is_admin): ?>

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
                                        >
                                            View
                                        </a>


                                        <a
                                            href="edit.php?id=<?= (int)$task["id"] ?>"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete.php?id=<?= (int)$task["id"] ?>"
                                            class="delete-link"
                                            onclick="return confirm('Are you sure you want to delete this task?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

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

const TOUCH_LONG_PRESS = 300;

const TOUCH_MOVE_THRESHOLD = 10;


/* =========================================================
   THREE DOT MENU
========================================================= */

document.addEventListener("click", function(event) {

    const menuButton = event.target.closest(".task-menu-button");


    if (menuButton) {

        event.preventDefault();

        event.stopPropagation();


        const menu = menuButton
            .closest(".task-menu")
            ?.querySelector(".task-menu-content");


        document
            .querySelectorAll(".task-menu-content.show")
            .forEach(function(item) {

                if (item !== menu) {

                    item.classList.remove("show");

                }

            });


        if (menu) {

            menu.classList.toggle("show");

        }


        return;

    }


    if (
        !event.target.closest(".task-menu-content")
    ) {

        document
            .querySelectorAll(".task-menu-content.show")
            .forEach(function(item) {

                item.classList.remove("show");

            });

    }

});


/* =========================================================
   SHOW MESSAGE
========================================================= */

function showMessage(element, duration)
{

    if (!element) {

        return;

    }


    element.style.display = "block";


    clearTimeout(
        element.hideTimer
    );


    element.hideTimer = setTimeout(
        function() {

            element.style.display = "none";

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

        const card = event.target.closest(".task-card");


        if (!card) {

            return;

        }


        draggedCard = card;


        card.classList.add("dragging");


        if (event.dataTransfer) {

            event.dataTransfer.effectAllowed = "move";


            event.dataTransfer.setData(
                "text/plain",
                card.dataset.taskId
            );

        }


        showMessage(
            document.getElementById("dragHint"),
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

        const card = event.target.closest(".task-card");


        if (card) {

            card.classList.remove("dragging");

        }


        clearColumnHighlights();


        draggedCard = null;

    }
);


/* =========================================================
   GET TASK BEFORE POSITION
========================================================= */

function getTaskBefore(container, y)
{

    const cards = [
        ...container.querySelectorAll(
            ".task-card:not(.dragging):not(.touch-dragging)"
        )
    ];


    let closest = null;

    let closestOffset = Number.NEGATIVE_INFINITY;


    cards.forEach(function(card) {

        const box = card.getBoundingClientRect();


        const offset =
            y -
            box.top -
            box.height / 2;


        if (
            offset < 0 &&
            offset > closestOffset
        ) {

            closestOffset = offset;

            closest = card;

        }

    });


    return closest;

}


/* =========================================================
   SHOW DROP POSITION
========================================================= */

function showDropPosition(container, y)
{

    document
        .querySelectorAll(".drop-indicator")
        .forEach(function(indicator) {

            indicator.remove();

        });


    const before =
        getTaskBefore(
            container,
            y
        );


    const indicator =
        document.createElement("div");


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
        .querySelectorAll(".task-column")
        .forEach(function(column) {

            column.classList.remove(
                "drag-over"
            );

        });


    document
        .querySelectorAll(".drop-indicator")
        .forEach(function(indicator) {

            indicator.remove();

        });

}


/* =========================================================
   GET COLUMN FROM POINT
========================================================= */

function getColumnFromPoint(x, y)
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

    if (!taskId || !progress) {

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
            event.target.closest(".task-menu") ||
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
                rect.right - edgeSize
            ) {

                board.scrollLeft +=
                    scrollSpeed;

            }
            else if (
                touchCurrentX <
                rect.left + edgeSize
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
                card.parentNode !== originalParent
            ) {

                if (
                    originalNextSibling &&
                    originalNextSibling.parentNode === originalParent
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

        if (event.key !== "Escape") {

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

        }

    }
);

</script>


</body>

</html>