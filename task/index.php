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
   SEARCH / FILTER VALUES
========================================================= */

$search = $_GET["search"] ?? "";

$progress_filter = $_GET["progress"] ?? "";

$priority_filter = $_GET["priority"] ?? "";

$sort = $_GET["sort"] ?? "newest";


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
   PRIORITY FILTER
========================================================= */

if ($priority_filter !== "") {

    $where[] = "priority = ?";

    $params[] = $priority_filter;

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
   SORTING
========================================================= */

switch ($sort) {

    case "oldest":

        $order_sql = "id ASC";

        break;


    case "priority_high":

        $order_sql = "
            CASE priority
                WHEN 'High' THEN 1
                WHEN 'Medium' THEN 2
                WHEN 'Low' THEN 3
                ELSE 4
            END ASC,
            id DESC
        ";

        break;


    case "priority_low":

        $order_sql = "
            CASE priority
                WHEN 'Low' THEN 1
                WHEN 'Medium' THEN 2
                WHEN 'High' THEN 3
                ELSE 4
            END ASC,
            id DESC
        ";

        break;


    case "name_az":

        $order_sql = "task ASC";

        break;


    case "name_za":

        $order_sql = "task DESC";

        break;


    default:

        $order_sql = "id DESC";

        break;

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
    ORDER BY $order_sql
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
   DRAG AND DROP UPDATE
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

<title>Task Board - Todo App</title>


<!-- =====================================================
     BOOTSTRAP
====================================================== -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- =====================================================
     EXISTING CSS
====================================================== -->

<link
    rel="stylesheet"
    href="../assets/style.css"
>


<style>

    /* =====================================================
       PAGE
    ===================================================== */

    * {
        box-sizing: border-box;
    }


    body {

        background: #f5f7fb;

        margin: 0;

        min-height: 100vh;

    }


    /* =====================================================
       NAVBAR
    ===================================================== */

    .navbar {

        background: #ffffff;

        border-bottom: 1px solid #e6eaf0;

    }


    .nav-container {

        width: 100%;

        max-width: 1200px;

        margin: 0 auto;

        padding: 0 24px;

        min-height: 58px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

    }


    .logo {

        font-size: 20px;

        font-weight: 800;

        color: #13213c;

        white-space: nowrap;

    }


    .user-section {

        display: flex;

        align-items: center;

        gap: 12px;

        font-size: 12px;

        color: #526071;

    }


    /* =====================================================
       BOARD PAGE
    ===================================================== */

    .task-board-page {

        width: 100%;

        max-width: 1200px;

        margin: 0 auto;

        padding: 22px 24px;

    }


    /* =====================================================
       BOARD HEADER
    ===================================================== */

    .board-top {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

        margin-bottom: 14px;

    }


    .board-title-area {

        flex: 1;

        min-width: 0;

    }


    .board-title {

        margin: 0;

        font-size: 18px;

        font-weight: 700;

        color: #17233d;

    }


    .board-subtitle {

        margin: 5px 0 0;

        font-size: 11px;

        color: #7d8798;

    }


    .board-controls {

        display: flex;

        align-items: center;

        gap: 10px;

    }


    /* =====================================================
       SEARCH
    ===================================================== */

    .board-search {

        position: relative;

    }


    .board-search input {

        width: 270px;

        height: 38px;

        border: 1px solid #d8dfeb;

        border-radius: 6px;

        padding: 0 12px 0 35px;

        font-size: 12px;

        background: #ffffff;

        outline: none;

    }


    .board-search input:focus {

        border-color: #287be8;

    }


    .search-icon {

        position: absolute;

        left: 12px;

        top: 9px;

        font-size: 14px;

        color: #8b96a8;

        pointer-events: none;

    }


    /* =====================================================
       FILTER
    ===================================================== */

    .board-filter {

        width: 130px;

        height: 38px;

        border: 1px solid #d8dfeb;

        border-radius: 6px;

        padding: 0 10px;

        font-size: 12px;

        background: #ffffff;

        color: #26334d;

        outline: none;

    }


    /* =====================================================
       ADD TASK
    ===================================================== */

    .add-task-btn {

        height: 38px;

        padding: 0 16px;

        border: none;

        border-radius: 6px;

        background: #1478ee;

        color: #ffffff;

        font-size: 12px;

        font-weight: 600;

        text-decoration: none;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        white-space: nowrap;

    }


    .add-task-btn:hover {

        background: #086bdc;

        color: #ffffff;

    }


    /* =====================================================
       BOARD
    ===================================================== */

    .board {

        display: grid;

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 14px;

        width: 100%;

        align-items: start;

    }


    /* =====================================================
       COLUMN
    ===================================================== */

    .board-column {

        min-height: 450px;

        border: 1px solid #dfe5ee;

        border-radius: 7px;

        padding: 10px;

        transition:
            box-shadow 0.2s ease,
            border-color 0.2s ease,
            transform 0.2s ease;

    }


    .board-column.todo {

        background: #f2f5f9;

    }


    .board-column.in-progress {

        background: #eef5ff;

    }


    .board-column.review {

        background: #fff9e9;

    }


    .board-column.done {

        background: #effaf2;

    }


    .board-column.drag-over {

        border-color: #287be8;

        box-shadow:
            inset 0 0 0 2px #287be8,
            0 8px 20px rgba(
                40,
                123,
                232,
                0.10
            );

    }


    /* =====================================================
       COLUMN HEADER
    ===================================================== */

    .column-header {

        display: flex;

        align-items: center;

        gap: 8px;

        height: 25px;

        padding: 0 3px;

        margin-bottom: 8px;

    }


    .column-title {

        margin: 0;

        font-size: 12px;

        font-weight: 700;

    }


    .todo .column-title {

        color: #333b48;

    }


    .in-progress .column-title {

        color: #1762bd;

    }


    .review .column-title {

        color: #927020;

    }


    .done .column-title {

        color: #269451;

    }


    .task-count {

        font-size: 11px;

        color: #687386;

        font-weight: 600;

    }


    /* =====================================================
       TASK LIST
    ===================================================== */

    .task-list {

        min-height: 365px;

        position: relative;

        padding-bottom: 10px;

    }


    /* =====================================================
       DROP INDICATOR
    ===================================================== */

    .drop-indicator {

        height: 4px;

        margin: 5px 2px;

        border-radius: 4px;

        background: #287be8;

        box-shadow:
            0 0 0 2px rgba(
                40,
                123,
                232,
                0.12
            );

        pointer-events: none;

        transition: 0.1s ease;

    }


    /* =====================================================
       TASK CARD
    ===================================================== */

    .task-card {

        position: relative;

        background: #ffffff;

        border: 1px solid #e0e5eb;

        border-radius: 6px;

        padding: 11px;

        margin-bottom: 7px;

        cursor: grab;

        user-select: none;

        -webkit-user-select: none;

        touch-action: pan-y;

        box-shadow:
            0 1px 2px rgba(
                20,
                30,
                50,
                0.04
            );

        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            opacity 0.15s ease;

    }


    .task-card:hover {

        transform: translateY(-1px);

        box-shadow:
            0 3px 8px rgba(
                20,
                30,
                50,
                0.08
            );

    }


    .task-card:active {

        cursor: grabbing;

    }


    /* =====================================================
       DRAGGING CARD
    ===================================================== */

    .task-card.dragging {

        opacity: 0.35;

        transform:
            rotate(1deg)
            scale(0.98);

        box-shadow:
            0 12px 30px rgba(
                20,
                30,
                50,
                0.18
            );

    }


    /* =====================================================
       TOUCH DRAGGING
    ===================================================== */

    .task-card.touch-dragging {

        position: fixed;

        z-index: 9999;

        width: var(--drag-width);

        pointer-events: none;

        opacity: 0.94;

        transform:
            rotate(2deg)
            scale(1.03);

        box-shadow:
            0 15px 35px rgba(
                20,
                30,
                50,
                0.25
            );

    }


    .task-card.touch-source {

        opacity: 0.25;

    }


    /* =====================================================
       PRIORITY LEFT BORDER
    ===================================================== */

    .priority-border-high {

        border-left: 3px solid #ef5350;

    }


    .priority-border-medium {

        border-left: 3px solid #f2b400;

    }


    .priority-border-low {

        border-left: 3px solid #35a96d;

    }


    /* =====================================================
       TASK NAME
    ===================================================== */

    .task-name {

        padding-right: 20px;

        margin-bottom: 4px;

        font-size: 11px;

        font-weight: 700;

        color: #1d2738;

        line-height: 1.35;

    }


    /* =====================================================
       DESCRIPTION
    ===================================================== */

    .task-description {

        padding-right: 12px;

        margin-bottom: 9px;

        font-size: 10px;

        color: #4d586a;

        line-height: 1.35;

    }


    /* =====================================================
       BADGES
    ===================================================== */

    .card-badges {

        display: flex;

        align-items: center;

        gap: 5px;

        margin-bottom: 8px;

        flex-wrap: wrap;

    }


    .task-badge {

        display: inline-flex;

        align-items: center;

        padding: 2px 7px;

        border-radius: 8px;

        font-size: 9px;

        line-height: 13px;

        font-weight: 700;

        white-space: nowrap;

    }


    /* =====================================================
       PRIORITY
    ===================================================== */

    .priority-high {

        background: #ffdfe0;

        color: #e33e45;

    }


    .priority-medium {

        background: #ffe8a9;

        color: #9b7200;

    }


    .priority-low {

        background: #d8f5e5;

        color: #208957;

    }


    /* =====================================================
       PROGRESS
    ===================================================== */

    .progress-todo {

        background: #6c7480;

        color: #ffffff;

    }


    .progress-progress {

        background: #1478ee;

        color: #ffffff;

    }


    .progress-review {

        background: #f3c400;

        color: #3f3500;

    }


    .progress-done {

        background: #168b55;

        color: #ffffff;

    }


    /* =====================================================
       COMPLETION
    ===================================================== */

    .completion-complete {

        background: #d1e7dd;

        color: #0f5132;

    }


    .completion-incomplete {

        background: #f8d7da;

        color: #842029;

    }


    /* =====================================================
       DATE
    ===================================================== */

    .task-date {

        display: flex;

        align-items: center;

        gap: 4px;

        font-size: 9px;

        color: #5d6776;

    }


    .task-date-icon {

        font-size: 10px;

    }


    /* =====================================================
       THREE DOT MENU
    ===================================================== */

    .task-menu {

        position: absolute;

        top: 8px;

        right: 8px;

    }


    .task-menu-button {

        border: none;

        background: transparent;

        color: #637086;

        font-size: 17px;

        line-height: 16px;

        padding: 0;

        cursor: pointer;

        touch-action: manipulation;

    }


    .task-menu-button:hover {

        color: #1e2c43;

    }


    .task-menu-content {

        display: none;

        position: absolute;

        right: 0;

        top: 20px;

        z-index: 100;

        min-width: 80px;

        background: #ffffff;

        border: 1px solid #e1e6ed;

        border-radius: 5px;

        box-shadow:
            0 5px 15px rgba(
                0,
                0,
                0,
                0.12
            );

    }


    .task-menu.open
    .task-menu-content {

        display: block;

    }


    .task-menu-content a {

        display: block;

        padding: 7px 10px;

        color: #344054;

        text-decoration: none;

        font-size: 11px;

    }


    .task-menu-content a:hover {

        background: #f5f7fa;

    }


    .task-menu-content
    .delete-link {

        color: #e5484d;

    }


    /* =====================================================
       EMPTY COLUMN
    ===================================================== */

    .empty-column {

        min-height: 60px;

        display: flex;

        align-items: center;

        justify-content: center;

        color: #9aa3b1;

        font-size: 10px;

        border: 1px dashed #cfd6e0;

        border-radius: 6px;

        margin-top: 4px;

    }


    /* =====================================================
       DRAG HINT
    ===================================================== */

    .drag-hint {

        position: fixed;

        left: 50%;

        bottom: 18px;

        transform:
            translateX(-50%)
            translateY(20px);

        background: #17233d;

        color: #ffffff;

        padding: 8px 14px;

        border-radius: 20px;

        font-size: 11px;

        font-weight: 600;

        opacity: 0;

        pointer-events: none;

        z-index: 10000;

        transition:
            opacity 0.2s ease,
            transform 0.2s ease;

    }


    .drag-hint.show {

        opacity: 1;

        transform:
            translateX(-50%)
            translateY(0);

    }


    /* =====================================================
       RESPONSIVE - TABLET
    ===================================================== */

    @media (max-width: 950px) {

        .board {

            grid-template-columns:
                repeat(2, minmax(280px, 1fr));

        }


        .board-top {

            flex-direction: column;

            align-items: stretch;

        }


        .board-controls {

            flex-wrap: wrap;

        }


        .board-search {

            flex: 1;

            min-width: 200px;

        }


        .board-search input {

            width: 100%;

        }

    }


    /* =====================================================
       RESPONSIVE - MOBILE
    ===================================================== */

    @media (max-width: 600px) {

        .nav-container {

            padding: 0 12px;

            min-height: 54px;

        }


        .logo {

            font-size: 17px;

        }


        .user-section {

            gap: 6px;

            font-size: 10px;

        }


        .user-section span {

            max-width: 110px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .task-board-page {

            padding:
                15px 10px;

        }


        .board-title {

            font-size: 17px;

        }


        .board-subtitle {

            font-size: 10px;

        }


        .board {

            display: flex;

            overflow-x: auto;

            gap: 12px;

            padding-bottom: 14px;

            scroll-snap-type: x proximity;

            -webkit-overflow-scrolling: touch;

        }


        .board-column {

            min-width: 285px;

            width: 285px;

            flex: 0 0 285px;

            min-height: 430px;

            scroll-snap-align: start;

        }


        .board-controls {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);

            gap: 8px;

            width: 100%;

        }


        .board-search {

            grid-column:
                1 / -1;

            width: 100%;

        }


        .board-search input {

            width: 100%;

        }


        .board-filter {

            width: 100%;

        }


        .add-task-btn {

            width: 100%;

            padding: 0 10px;

        }


        .task-card {

            padding: 12px;

            margin-bottom: 8px;

        }


        .task-name {

            font-size: 11px;

        }


        .task-description {

            font-size: 10px;

        }


        .drag-hint {

            bottom: 12px;

            font-size: 10px;

            padding: 7px 12px;

        }

    }


    /* =====================================================
       VERY SMALL MOBILE
    ===================================================== */

    @media (max-width: 380px) {

        .board-column {

            min-width: 270px;

            width: 270px;

            flex-basis: 270px;

        }


        .user-section span {

            display: none;

        }

    }

</style>

</head>

<body>

<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">

<div class="nav-container">


    <div class="logo">

        ☑ TODO APP

    </div>


    <div class="user-section">

        <span>

            Welcome,

            <?php

            echo htmlspecialchars(
                $_SESSION["user_name"]
            );

            ?>

        </span>


        <a
            href="../auth/logout.php"
            class="btn btn-delete"
        >

            Logout

        </a>

    </div>

</div>

</nav>


<!-- =========================================================
     TASK BOARD ONLY
========================================================= -->

<main class="task-board-page">

<!-- =====================================================
     BOARD HEADER
====================================================== -->

<div class="board-top">


    <div class="board-title-area">

        <h1 class="board-title">

            Task Board

        </h1>


        <p class="board-subtitle">

            Drag and drop tasks between columns to update status

        </p>

    </div>


    <div class="board-controls">


        <!-- SEARCH -->

        <form
            method="GET"
            class="board-search"
        >

            <span class="search-icon">

                🔍

            </span>


            <input
                type="text"
                name="search"
                placeholder="Search tasks..."
                value="<?php echo htmlspecialchars($search); ?>"
            >


            <input
                type="hidden"
                name="progress"
                value="<?php echo htmlspecialchars($progress_filter); ?>"
            >


            <input
                type="hidden"
                name="priority"
                value="<?php echo htmlspecialchars($priority_filter); ?>"
            >


            <input
                type="hidden"
                name="sort"
                value="<?php echo htmlspecialchars($sort); ?>"
            >

        </form>


        <!-- FILTER -->

        <select
            class="board-filter"
            onchange="changeFilter(this.value)"
        >

            <option
                value="all"
                <?php
                echo (
                    $progress_filter === "" ||
                    $progress_filter === "all"
                )
                ? "selected"
                : "";
                ?>
            >

                All Tasks

            </option>


            <option
                value="Todo"
                <?php
                echo $progress_filter === "Todo"
                    ? "selected"
                    : "";
                ?>
            >

                TODO

            </option>


            <option
                value="In Progress"
                <?php
                echo $progress_filter === "In Progress"
                    ? "selected"
                    : "";
                ?>
            >

                IN PROGRESS

            </option>


            <option
                value="Review"
                <?php
                echo $progress_filter === "Review"
                    ? "selected"
                    : "";
                ?>
            >

                REVIEW

            </option>


            <option
                value="Done"
                <?php
                echo $progress_filter === "Done"
                    ? "selected"
                    : "";
                ?>
            >

                DONE

            </option>

        </select>


        <!-- ADD TASK -->

        <a
            href="add.php"
            class="add-task-btn"
        >

            + Add Task

        </a>

    </div>

</div>



<!-- =====================================================
     BOARD
====================================================== -->

<div class="board">


    <!-- =================================================
         TODO
    ================================================== -->

    <div
        class="board-column todo"
        data-progress="Todo"
    >

        <div class="column-header">

            <h2 class="column-title">

                TODO

            </h2>


            <span class="task-count">

                <?php echo count($todo_tasks); ?>

            </span>

        </div>


        <div class="task-list">


            <?php if (!empty($todo_tasks)): ?>


                <?php foreach ($todo_tasks as $row): ?>


                    <div
                        class="task-card priority-border-<?php echo strtolower($row["priority"]); ?>"
                        draggable="true"
                        data-task-id="<?php echo (int)$row["id"]; ?>"
                    >


                        <div class="task-name">

                            <?php

                            echo htmlspecialchars(
                                $row["task"]
                            );

                            ?>

                        </div>


                        <div class="task-description">

                            <?php

                            echo htmlspecialchars(
                                $row["description"] ?? ""
                            );

                            ?>

                        </div>


                        <div class="card-badges">


                            <span
                                class="task-badge <?php echo getPriorityClass($row["priority"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["priority"]
                                );

                                ?>

                            </span>


                            <span
                                class="task-badge <?php echo getProgressClass($row["progress"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["progress"]
                                );

                                ?>

                            </span>


                            <?php if ((int)$row["is_completed"] === 1): ?>

                                <span class="task-badge completion-complete">

                                    Completed

                                </span>

                            <?php else: ?>

                                <span class="task-badge completion-incomplete">

                                    Incomplete

                                </span>

                            <?php endif; ?>


                        </div>


                        <div class="task-date">

                            <span class="task-date-icon">

                                ◷

                            </span>


                            <?php

                            echo formatTaskDate(
                                $row["addedDate"]
                            );

                            ?>

                        </div>


                        <div class="task-menu">

                            <button
                                type="button"
                                class="task-menu-button"
                            >

                                ⋮

                            </button>


                            <div class="task-menu-content">


                                <a
                                    href="view.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    View

                                </a>


                                <a
                                    href="edit.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    Edit

                                </a>


                                <a
                                    href="delete.php?id=<?php echo (int)$row["id"]; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to delete this task?');"
                                >

                                    Delete

                                </a>


                            </div>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-column">

                    No tasks

                </div>


            <?php endif; ?>


        </div>

    </div>



    <!-- =================================================
         IN PROGRESS
    ================================================== -->

    <div
        class="board-column in-progress"
        data-progress="In Progress"
    >

        <div class="column-header">

            <h2 class="column-title">

                IN PROGRESS

            </h2>


            <span class="task-count">

                <?php echo count($in_progress_tasks); ?>

            </span>

        </div>


        <div class="task-list">


            <?php if (!empty($in_progress_tasks)): ?>


                <?php foreach ($in_progress_tasks as $row): ?>


                    <div
                        class="task-card priority-border-<?php echo strtolower($row["priority"]); ?>"
                        draggable="true"
                        data-task-id="<?php echo (int)$row["id"]; ?>"
                    >


                        <div class="task-name">

                            <?php

                            echo htmlspecialchars(
                                $row["task"]
                            );

                            ?>

                        </div>


                        <div class="task-description">

                            <?php

                            echo htmlspecialchars(
                                $row["description"] ?? ""
                            );

                            ?>

                        </div>


                        <div class="card-badges">


                            <span
                                class="task-badge <?php echo getPriorityClass($row["priority"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["priority"]
                                );

                                ?>

                            </span>


                            <span
                                class="task-badge <?php echo getProgressClass($row["progress"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["progress"]
                                );

                                ?>

                            </span>


                            <?php if ((int)$row["is_completed"] === 1): ?>

                                <span class="task-badge completion-complete">

                                    Completed

                                </span>

                            <?php else: ?>

                                <span class="task-badge completion-incomplete">

                                    Incomplete

                                </span>

                            <?php endif; ?>


                        </div>


                        <div class="task-date">

                            <span class="task-date-icon">

                                ◷

                            </span>


                            <?php

                            echo formatTaskDate(
                                $row["addedDate"]
                            );

                            ?>

                        </div>


                        <div class="task-menu">

                            <button
                                type="button"
                                class="task-menu-button"
                            >

                                ⋮

                            </button>


                            <div class="task-menu-content">


                                <a
                                    href="view.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    View

                                </a>


                                <a
                                    href="edit.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    Edit

                                </a>


                                <a
                                    href="delete.php?id=<?php echo (int)$row["id"]; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to delete this task?');"
                                >

                                    Delete

                                </a>


                            </div>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-column">

                    No tasks

                </div>


            <?php endif; ?>


        </div>

    </div>



    <!-- =================================================
         REVIEW
    ================================================== -->

    <div
        class="board-column review"
        data-progress="Review"
    >

        <div class="column-header">

            <h2 class="column-title">

                REVIEW

            </h2>


            <span class="task-count">

                <?php echo count($review_tasks); ?>

            </span>

        </div>


        <div class="task-list">


            <?php if (!empty($review_tasks)): ?>


                <?php foreach ($review_tasks as $row): ?>


                    <div
                        class="task-card priority-border-<?php echo strtolower($row["priority"]); ?>"
                        draggable="true"
                        data-task-id="<?php echo (int)$row["id"]; ?>"
                    >


                        <div class="task-name">

                            <?php

                            echo htmlspecialchars(
                                $row["task"]
                            );

                            ?>

                        </div>


                        <div class="task-description">

                            <?php

                            echo htmlspecialchars(
                                $row["description"] ?? ""
                            );

                            ?>

                        </div>


                        <div class="card-badges">


                            <span
                                class="task-badge <?php echo getPriorityClass($row["priority"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["priority"]
                                );

                                ?>

                            </span>


                            <span
                                class="task-badge <?php echo getProgressClass($row["progress"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["progress"]
                                );

                                ?>

                            </span>


                            <?php if ((int)$row["is_completed"] === 1): ?>

                                <span class="task-badge completion-complete">

                                    Completed

                                </span>

                            <?php else: ?>

                                <span class="task-badge completion-incomplete">

                                    Incomplete

                                </span>

                            <?php endif; ?>


                        </div>


                        <div class="task-date">

                            <span class="task-date-icon">

                                ◷

                            </span>


                            <?php

                            echo formatTaskDate(
                                $row["addedDate"]
                            );

                            ?>

                        </div>


                        <div class="task-menu">

                            <button
                                type="button"
                                class="task-menu-button"
                            >

                                ⋮

                            </button>


                            <div class="task-menu-content">


                                <a
                                    href="view.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    View

                                </a>


                                <a
                                    href="edit.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    Edit

                                </a>


                                <a
                                    href="delete.php?id=<?php echo (int)$row["id"]; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to delete this task?');"
                                >

                                    Delete

                                </a>


                            </div>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-column">

                    No tasks

                </div>


            <?php endif; ?>


        </div>

    </div>



    <!-- =================================================
         DONE
    ================================================== -->

    <div
        class="board-column done"
        data-progress="Done"
    >

        <div class="column-header">

            <h2 class="column-title">

                DONE

            </h2>


            <span class="task-count">

                <?php echo count($done_tasks); ?>

            </span>

        </div>


        <div class="task-list">


            <?php if (!empty($done_tasks)): ?>


                <?php foreach ($done_tasks as $row): ?>


                    <div
                        class="task-card priority-border-<?php echo strtolower($row["priority"]); ?>"
                        draggable="true"
                        data-task-id="<?php echo (int)$row["id"]; ?>"
                    >


                        <div class="task-name">

                            <?php

                            echo htmlspecialchars(
                                $row["task"]
                            );

                            ?>

                        </div>


                        <div class="task-description">

                            <?php

                            echo htmlspecialchars(
                                $row["description"] ?? ""
                            );

                            ?>

                        </div>


                        <div class="card-badges">


                            <span
                                class="task-badge <?php echo getPriorityClass($row["priority"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["priority"]
                                );

                                ?>

                            </span>


                            <span
                                class="task-badge <?php echo getProgressClass($row["progress"]); ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row["progress"]
                                );

                                ?>

                            </span>


                            <?php if ((int)$row["is_completed"] === 1): ?>

                                <span class="task-badge completion-complete">

                                    Completed

                                </span>

                            <?php else: ?>

                                <span class="task-badge completion-incomplete">

                                    Incomplete

                                </span>

                            <?php endif; ?>


                        </div>


                        <div class="task-date">

                            <span class="task-date-icon">

                                ◷

                            </span>


                            <?php

                            echo formatTaskDate(
                                $row["addedDate"]
                            );

                            ?>

                        </div>


                        <div class="task-menu">

                            <button
                                type="button"
                                class="task-menu-button"
                            >

                                ⋮

                            </button>


                            <div class="task-menu-content">


                                <a
                                    href="view.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    View

                                </a>


                                <a
                                    href="edit.php?id=<?php echo (int)$row["id"]; ?>"
                                >

                                    Edit

                                </a>


                                <a
                                    href="delete.php?id=<?php echo (int)$row["id"]; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Are you sure you want to delete this task?');"
                                >

                                    Delete

                                </a>


                            </div>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-column">

                    No tasks

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>

</main>


<!-- =========================================================
     DRAG HINT
========================================================= -->

<div
    id="dragHint"
    class="drag-hint"
>

    Drop task to change status

</div>


<script>

/* =========================================================
   FILTER
========================================================= */

function changeFilter(value)
{

    const search =
        <?php echo json_encode($search); ?>;


    let url =
        "index.php";


    if (value !== "all") {

        url +=
            "?progress="
            + encodeURIComponent(value);

    }


    if (search !== "") {

        url +=
            (
                url.includes("?")
                ? "&"
                : "?"
            )
            + "search="
            + encodeURIComponent(search);

    }


    window.location.href = url;

}


/* =========================================================
   THREE DOT MENUS
========================================================= */

document.addEventListener(
    "click",
    function(event)
    {

        const button =
            event.target.closest(
                ".task-menu-button"
            );


        if (button) {

            event.stopPropagation();


            const menu =
                button.closest(
                    ".task-menu"
                );


            document
                .querySelectorAll(
                    ".task-menu.open"
                )
                .forEach(
                    function(item)
                    {

                        if (item !== menu) {

                            item.classList.remove(
                                "open"
                            );

                        }

                    }
                );


            menu.classList.toggle(
                "open"
            );


            return;

        }


        document
            .querySelectorAll(
                ".task-menu.open"
            )
            .forEach(
                function(menu)
                {

                    menu.classList.remove(
                        "open"
                    );

                }
            );

    }
);


/* =========================================================
   DRAG AND DROP
   DESKTOP + MOBILE TOUCH
========================================================= */

let draggedTaskId = null;

let draggedCard = null;

let dropIndicator = null;

let touchClone = null;

let touchStartX = 0;

let touchStartY = 0;

let touchCurrentX = 0;

let touchCurrentY = 0;

let touchDragging = false;

let touchSourceCard = null;

let currentDropColumn = null;

let currentDropBefore = null;


/* =========================================================
   DRAG HINT
========================================================= */

const dragHint =
    document.getElementById(
        "dragHint"
    );


function showDragHint()
{

    if (!dragHint) {

        return;

    }


    dragHint.classList.add(
        "show"
    );

}


function hideDragHint()
{

    if (!dragHint) {

        return;

    }


    dragHint.classList.remove(
        "show"
    );

}


/* =========================================================
   CREATE DROP INDICATOR
========================================================= */

function createDropIndicator()
{

    if (!dropIndicator) {

        dropIndicator =
            document.createElement(
                "div"
            );


        dropIndicator.className =
            "drop-indicator";

    }


    return dropIndicator;

}


/* =========================================================
   REMOVE DROP INDICATOR
========================================================= */

function removeDropIndicator()
{

    if (
        dropIndicator &&
        dropIndicator.parentNode
    ) {

        dropIndicator.parentNode.removeChild(
            dropIndicator
        );

    }


    currentDropColumn =
        null;


    currentDropBefore =
        null;

}


/* =========================================================
   GET TASK BEFORE POSITION
========================================================= */

function getTaskBefore(
    taskList,
    mouseY
)
{

    const cards =
        [
            ...taskList.querySelectorAll(
                ".task-card:not(.dragging):not(.touch-source)"
            )
        ];


    let closest = {

        offset:
            Number.NEGATIVE_INFINITY,

        element:
            null

    };


    cards.forEach(
        function(card)
        {

            const box =
                card.getBoundingClientRect();


            const offset =
                mouseY
                -
                (
                    box.top
                    +
                    box.height / 2
                );


            if (
                offset < 0 &&
                offset > closest.offset
            ) {

                closest = {

                    offset:
                        offset,

                    element:
                        card

                };

            }

        }
    );


    return closest.element;

}


/* =========================================================
   SHOW DROP POSITION
========================================================= */

function showDropPosition(
    column,
    mouseY
)
{

    if (!column) {

        return;

    }


    const taskList =
        column.querySelector(
            ".task-list"
        );


    if (!taskList) {

        return;

    }


    const indicator =
        createDropIndicator();


    const before =
        getTaskBefore(
            taskList,
            mouseY
        );


    if (before) {

        taskList.insertBefore(
            indicator,
            before
        );

    }

    else {

        taskList.appendChild(
            indicator
        );

    }


    currentDropColumn =
        column;


    currentDropBefore =
        before;


    column.classList.add(
        "drag-over"
    );

}


/* =========================================================
   CLEAR COLUMN HIGHLIGHTS
========================================================= */

function clearColumnHighlights()
{

    document
        .querySelectorAll(
            ".board-column.drag-over"
        )
        .forEach(
            function(column)
            {

                column.classList.remove(
                    "drag-over"
                );

            }
        );

}


/* =========================================================
   FIND COLUMN FROM POINT
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
        ".board-column"
    );

}


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
   DESKTOP DRAG START
========================================================= */

document
    .querySelectorAll(
        ".task-card"
    )
    .forEach(
        function(card)
        {

            card.addEventListener(
                "dragstart",
                function(event)
                {

                    draggedTaskId =
                        this.dataset.taskId;


                    draggedCard =
                        this;


                    this.classList.add(
                        "dragging"
                    );


                    showDragHint();


                    if (
                        event.dataTransfer
                    ) {

                        event.dataTransfer.effectAllowed =
                            "move";


                        event.dataTransfer.setData(
                            "text/plain",
                            draggedTaskId
                        );

                    }

                }
            );


            card.addEventListener(
                "dragend",
                function()
                {

                    this.classList.remove(
                        "dragging"
                    );


                    draggedTaskId =
                        null;


                    draggedCard =
                        null;


                    removeDropIndicator();

                    clearColumnHighlights();

                    hideDragHint();

                }
            );

        }
    );


/* =========================================================
   DESKTOP COLUMN DRAG OVER
========================================================= */

document
    .querySelectorAll(
        ".board-column"
    )
    .forEach(
        function(column)
        {

            column.addEventListener(
                "dragover",
                function(event)
                {

                    event.preventDefault();


                    if (!draggedTaskId) {

                        return;

                    }


                    if (
                        event.dataTransfer
                    ) {

                        event.dataTransfer.dropEffect =
                            "move";

                    }


                    clearColumnHighlights();


                    showDropPosition(
                        this,
                        event.clientY
                    );

                }
            );


            column.addEventListener(
                "dragleave",
                function(event)
                {

                    const rect =
                        this.getBoundingClientRect();


                    const outside =
                        event.clientX <
                            rect.left ||
                        event.clientX >
                            rect.right ||
                        event.clientY <
                            rect.top ||
                        event.clientY >
                            rect.bottom;


                    if (outside) {

                        this.classList.remove(
                            "drag-over"
                        );

                    }

                }
            );


            column.addEventListener(
                "drop",
                function(event)
                {

                    event.preventDefault();


                    if (!draggedTaskId) {

                        return;

                    }


                    const taskId =
                        draggedTaskId;


                    const newProgress =
                        this.dataset.progress;


                    removeDropIndicator();

                    clearColumnHighlights();

                    hideDragHint();


                    saveTaskProgress(
                        taskId,
                        newProgress
                    );

                }
            );

        }
    );


/* =========================================================
   TOUCH DRAG HELPERS
========================================================= */

function startTouchDrag(
    card,
    event
)
{

    if (
        event.touches.length !== 1
    ) {

        return;

    }


    if (
        event.target.closest(
            ".task-menu"
        )
    ) {

        return;

    }


    const touch =
        event.touches[0];


    touchStartX =
        touch.clientX;


    touchStartY =
        touch.clientY;


    touchCurrentX =
        touch.clientX;


    touchCurrentY =
        touch.clientY;


    touchSourceCard =
        card;


    touchDragging =
        false;

}


/* =========================================================
   BEGIN TOUCH DRAG
========================================================= */

function beginTouchDrag()
{

    if (
        !touchSourceCard ||
        touchDragging
    ) {

        return;

    }


    touchDragging =
        true;


    draggedTaskId =
        touchSourceCard.dataset.taskId;


    const rect =
        touchSourceCard.getBoundingClientRect();


    touchClone =
        touchSourceCard.cloneNode(
            true
        );


    touchClone.classList.add(
        "touch-dragging"
    );


    touchClone.classList.remove(
        "touch-source"
    );


    touchClone.style.setProperty(
        "--drag-width",
        rect.width + "px"
    );


    touchClone.style.left =
        rect.left + "px";


    touchClone.style.top =
        rect.top + "px";


    touchClone.style.height =
        rect.height + "px";


    document.body.appendChild(
        touchClone
    );


    touchSourceCard.classList.add(
        "touch-source"
    );


    showDragHint();

}


/* =========================================================
   MOVE TOUCH CLONE
========================================================= */

function moveTouchClone(
    x,
    y
)
{

    if (!touchClone) {

        return;

    }


    const rect =
        touchClone.getBoundingClientRect();


    const left =
        x
        -
        (
            rect.width / 2
        );


    const top =
        y
        -
        25;


    touchClone.style.left =
        left + "px";


    touchClone.style.top =
        top + "px";

}


/* =========================================================
   TOUCH MOVE
========================================================= */

document
    .querySelectorAll(
        ".task-card"
    )
    .forEach(
        function(card)
        {

            card.addEventListener(
                "touchstart",
                function(event)
                {

                    startTouchDrag(
                        this,
                        event
                    );

                },
                {
                    passive: true
                }
            );


            card.addEventListener(
                "touchmove",
                function(event)
                {

                    if (
                        !touchSourceCard ||
                        touchSourceCard !== this
                    ) {

                        return;

                    }


                    if (
                        event.touches.length !== 1
                    ) {

                        return;

                    }


                    const touch =
                        event.touches[0];


                    touchCurrentX =
                        touch.clientX;


                    touchCurrentY =
                        touch.clientY;


                    const distanceX =
                        Math.abs(
                            touchCurrentX
                            -
                            touchStartX
                        );


                    const distanceY =
                        Math.abs(
                            touchCurrentY
                            -
                            touchStartY
                        );


                    if (
                        !touchDragging &&
                        (
                            distanceX > 8 ||
                            distanceY > 8
                        )
                    ) {

                        beginTouchDrag();

                    }


                    if (!touchDragging) {

                        return;

                    }


                    event.preventDefault();


                    moveTouchClone(
                        touchCurrentX,
                        touchCurrentY
                    );


                    const column =
                        getColumnFromPoint(
                            touchCurrentX,
                            touchCurrentY
                        );


                    clearColumnHighlights();


                    if (column) {

                        showDropPosition(
                            column,
                            touchCurrentY
                        );

                    }

                },
                {
                    passive: false
                }
            );


            card.addEventListener(
                "touchend",
                function(event)
                {

                    if (
                        !touchSourceCard ||
                        touchSourceCard !== this
                    ) {

                        return;

                    }


                    if (!touchDragging) {

                        touchSourceCard =
                            null;

                        return;

                    }


                    const column =
                        getColumnFromPoint(
                            touchCurrentX,
                            touchCurrentY
                        );


                    let taskId =
                        draggedTaskId;


                    let newProgress =
                        column
                        ? column.dataset.progress
                        : "";


                    if (
                        taskId &&
                        newProgress
                    ) {

                        saveTaskProgress(
                            taskId,
                            newProgress
                        );

                    }


                    finishTouchDrag();

                }
            );


            card.addEventListener(
                "touchcancel",
                function()
                {

                    finishTouchDrag();

                }
            );

        }
    );


/* =========================================================
   FINISH TOUCH DRAG
========================================================= */

function finishTouchDrag()
{

    if (touchClone) {

        touchClone.remove();

    }


    touchClone =
        null;


    if (touchSourceCard) {

        touchSourceCard.classList.remove(
            "touch-source"
        );

    }


    touchSourceCard =
        null;


    touchDragging =
        false;


    draggedTaskId =
        null;


    removeDropIndicator();

    clearColumnHighlights();

    hideDragHint();

}


/* =========================================================
   PREVENT MENU FROM STARTING DRAG
========================================================= */

document
    .querySelectorAll(
        ".task-menu"
    )
    .forEach(
        function(menu)
        {

            menu.addEventListener(
                "mousedown",
                function(event)
                {

                    event.stopPropagation();

                }
            );


            menu.addEventListener(
                "touchstart",
                function(event)
                {

                    event.stopPropagation();

                },
                {
                    passive: true
                }
            );

        }
    );


/* =========================================================
   MOBILE HORIZONTAL SCROLL
========================================================= */

const board =
    document.querySelector(
        ".board"
    );


let boardTouchStartX = 0;

let boardTouchStartY = 0;


if (board) {

    board.addEventListener(
        "touchstart",
        function(event)
        {

            if (
                touchDragging ||
                event.target.closest(
                    ".task-card"
                )
            ) {

                return;

            }


            if (
                event.touches.length !== 1
            ) {

                return;

            }


            boardTouchStartX =
                event.touches[0].clientX;


            boardTouchStartY =
                event.touches[0].clientY;

        },
        {
            passive: true
        }
    );

}


/* =========================================================
   ESCAPE TO CANCEL DRAG
========================================================= */

document.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Escape" &&
            touchDragging
        ) {

            finishTouchDrag();

        }

    }
);

</script>

</body>

</html>