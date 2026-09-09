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

$user_role = trim(
    strtolower(
        $_SESSION["user_role"] ?? "user"
    )
);

$is_admin = ($user_role === "admin");
$is_user  = ($user_role === "user");


/* =========================================================
   AJAX DETECTION
   USED BY THE EDIT TASK MODAL
========================================================= */

$is_ajax = (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
);


/* =========================================================
   GET TASK ID
========================================================= */

$task_id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($task_id <= 0) {

    header("Location: index.php");
    exit();

}


/* =========================================================
   VARIABLES
========================================================= */

$task = "";
$description = "";
$status = 1;
$priority = "Medium";
$progress = "Todo";
$is_completed = 0;

$error = "";
$success = "";


/* =========================================================
   GET EXISTING TASK
========================================================= */

$select_sql = "
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
    WHERE id = ?
    LIMIT 1
";

$select_stmt = mysqli_prepare(
    $conn,
    $select_sql
);

if (!$select_stmt) {

    die(
        "Database error: " .
        htmlspecialchars(
            mysqli_error($conn),
            ENT_QUOTES,
            "UTF-8"
        )
    );

}

mysqli_stmt_bind_param(
    $select_stmt,
    "i",
    $task_id
);

mysqli_stmt_execute(
    $select_stmt
);

$result = mysqli_stmt_get_result(
    $select_stmt
);

if (!$result || mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($select_stmt);

    header("Location: index.php");
    exit();

}

$task_data = mysqli_fetch_assoc($result);

mysqli_stmt_close($select_stmt);


/* =========================================================
   LOAD VALUES
========================================================= */

$task = $task_data["task"] ?? "";

$description =
    $task_data["description"] ?? "";

$status =
    (int)($task_data["status"] ?? 1);

$priority =
    $task_data["priority"] ?? "Medium";

$progress =
    $task_data["progress"] ?? "Todo";

$is_completed =
    (int)($task_data["is_completed"] ?? 0);


/* =========================================================
   HANDLE UPDATE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task = trim(
        $_POST["task"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $status = isset($_POST["status"])
        ? (int)$_POST["status"]
        : 1;

    $priority =
        $_POST["priority"] ?? "Medium";

    $progress =
        $_POST["progress"] ?? "Todo";

    $is_completed =
        isset($_POST["is_completed"])
            ? (int)$_POST["is_completed"]
            : 0;


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($task === "") {

        $error =
            "Task title is required.";

    }


    /* =====================================================
       VALIDATE STATUS
    ===================================================== */

    if (
        $status !== 0 &&
        $status !== 1
    ) {

        $status = 1;

    }


    /* =====================================================
       VALIDATE PRIORITY
    ===================================================== */

    $allowed_priorities = [
        "High",
        "Medium",
        "Low"
    ];

    if (
        !in_array(
            $priority,
            $allowed_priorities,
            true
        )
    ) {

        $priority = "Medium";

    }


    /* =====================================================
       VALIDATE PROGRESS
    ===================================================== */

    $allowed_progress = [
        "Todo",
        "In Progress",
        "Review",
        "Done"
    ];

    if (
        !in_array(
            $progress,
            $allowed_progress,
            true
        )
    ) {

        $progress = "Todo";

    }


    /* =====================================================
       VALIDATE COMPLETION
    ===================================================== */

    if (
        $is_completed !== 0 &&
        $is_completed !== 1
    ) {

        $is_completed = 0;

    }


    /* =====================================================
       AUTO COMPLETE WHEN DONE
       
       If progress is Done, task is automatically
       considered completed.
    ===================================================== */

    if ($progress === "Done") {

        $is_completed = 1;

    }


    /* =====================================================
       UPDATE TASK
    ===================================================== */

    if ($error === "") {

        $update_sql = "
            UPDATE tasks
            SET
                task = ?,
                description = ?,
                status = ?,
                priority = ?,
                progress = ?,
                is_completed = ?,
                editedDate = CURRENT_TIMESTAMP
            WHERE id = ?
        ";

        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );

        if (!$update_stmt) {

            $error =
                "Unable to prepare update.";

        } else {

            mysqli_stmt_bind_param(
                $update_stmt,
                "ssissii",
                $task,
                $description,
                $status,
                $priority,
                $progress,
                $is_completed,
                $task_id
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {

                mysqli_stmt_close(
                    $update_stmt
                );

                if ($is_ajax) {

                    header("Content-Type: application/json");

                    echo json_encode([
                        "success"      => true,
                        "message"      => "Task updated successfully.",
                        "task"         => $task,
                        "description"  => $description,
                        "priority"     => $priority,
                        "progress"     => $progress,
                        "is_completed" => $is_completed,
                        "status"       => $status,
                        "edited"       => date("d M Y, h:i:s A")
                    ]);

                    exit();

                }

                header(
                    "Location: index.php?updated=1"
                );

                exit();

            } else {

                $error =
                    "Unable to update task. Please try again.";

                mysqli_stmt_close(
                    $update_stmt
                );

            }

        }

    }


    /* =====================================================
       AJAX ERROR RESPONSE
       (SUCCESS PATH ALREADY EXITED ABOVE)
    ===================================================== */

    if ($is_ajax) {

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => $error !== "" ? $error : "Unable to update task."
        ]);

        exit();

    }

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

    <title>Edit Task - TODO APP</title>

    <!-- =================================================
         BOOTSTRAP
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- =================================================
         EXISTING APP CSS
    ================================================== -->

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f4f7fc;

            color: #17213d;

            min-height: 100vh;
        }

        /* =================================================
           NAVBAR
        ================================================= */

        .navbar {
            width: 100%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.96
                );

            border-bottom:
                1px solid #e5ebf5;

            box-shadow:
                0 4px 20px
                rgba(
                    35,
                    75,
                    140,
                    0.06
                );
        }

        .nav-container {
            width: 92%;

            max-width: 1250px;

            margin: auto;

            min-height: 78px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }

        .logo {
            font-size: 25px;

            font-weight: 800;

            color: #18213f;

            letter-spacing: 0.3px;
        }

        .logo::first-letter {
            color: #2878ee;
        }

        .user-section {
            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 14px;

            color: #485475;
        }

        .role-badge {
            padding: 6px 10px;

            border-radius: 20px;

            background: #eef5ff;

            color: #176ce8;

            font-size: 12px;

            font-weight: 700;
        }

        .logout-link {
            color: #dc3545;

            font-weight: 600;
        }

        .logout-link:hover {
            text-decoration: underline;
        }

        /* =================================================
           CONTAINER
        ================================================= */

        .page-container {
            width: 92%;

            max-width: 800px;

            margin: 40px auto;
        }

        /* =================================================
           CARD
        ================================================= */

        .edit-card {
            background: #ffffff;

            border-radius: 20px;

            padding: 30px;

            border:
                1px solid #eef2f8;

            box-shadow:
                0 12px 35px
                rgba(
                    40,
                    82,
                    150,
                    0.09
                );
        }

        /* =================================================
           HEADER
        ================================================= */

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 28px;

            font-weight: 750;

            color: #17213d;

            margin-bottom: 6px;
        }

        .page-header p {
            font-size: 14px;

            color: #7a859b;
        }

        /* =================================================
           ALERT
        ================================================= */

        .alert-error {
            padding: 12px 15px;

            margin-bottom: 20px;

            border-radius: 9px;

            background: #fff0f1;

            color: #cf3139;

            border:
                1px solid #ffd6d9;

            font-size: 14px;
        }

        /* =================================================
           FORM GROUP
        ================================================= */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;

            color: #36415f;
        }

        .form-control,
        .form-select {
            width: 100%;

            min-height: 48px;

            padding:
                10px 15px;

            border:
                1px solid #dbe3f0;

            border-radius: 10px;

            background: #ffffff;

            color: #1c2742;

            font-size: 14px;

            outline: none;

            transition: 0.2s ease;
        }

        textarea.form-control {
            min-height: 130px;

            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #438cf2;

            box-shadow:
                0 0 0 3px
                rgba(
                    67,
                    140,
                    242,
                    0.12
                );
        }

        /* =================================================
           TASK ID
        ================================================= */

        .task-id-box {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            margin-bottom: 20px;

            border-radius: 8px;

            background: #f1f6ff;

            color: #176ce8;

            font-size: 12px;

            font-weight: 700;
        }

        /* =================================================
           BUTTONS
        ================================================= */

        .button-row {
            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 28px;
        }

        .btn-app {
            min-height: 42px;

            padding:
                0 18px;

            border: none;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            text-decoration: none;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .btn-app:hover {
            transform:
                translateY(-1px);
        }

        .btn-save {
            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #438ff4,
                    #176ce8
                );

            box-shadow:
                0 7px 18px
                rgba(
                    44,
                    124,
                    239,
                    0.22
                );
        }

        .btn-save:hover {
            color: #ffffff;

            box-shadow:
                0 10px 22px
                rgba(
                    44,
                    124,
                    239,
                    0.30
                );
        }

        .btn-cancel {
            background: #f1f4f8;

            color: #526078;

            border:
                1px solid #e0e6ee;
        }

        .btn-cancel:hover {
            background: #e8edf4;

            color: #36415f;
        }

        /* =================================================
           FOOTER
        ================================================= */

        .footer {
            text-align: center;

            color: #8993a8;

            font-size: 13px;

            padding: 25px 0;
        }

        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .nav-container {
                min-height: 68px;
            }

            .logo {
                font-size: 21px;
            }

            .user-section span {
                display: none;
            }

            .page-container {
                width: 94%;

                margin: 25px auto;
            }

            .edit-card {
                padding: 20px;

                border-radius: 16px;
            }

            .page-header h1 {
                font-size: 24px;
            }

        }

        @media (max-width: 480px) {

            .button-row {
                flex-direction: column;
            }

            .btn-app {
                width: 100%;
            }

        }

    </style>

</head>

<body>


<!-- =====================================================
     NAVBAR
====================================================== -->

<nav class="navbar">

    <div class="nav-container">

        <div class="logo">
            ☑ TODO APP
        </div>

        <div class="user-section">

            <span>
                Welcome,
                <?= htmlspecialchars(
                    $_SESSION["user_name"] ?? "User",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </span>

            <span class="role-badge">
                <?= htmlspecialchars(
                    ucfirst($user_role),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </span>

            <a
                href="../auth/logout.php"
                class="logout-link"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
====================================================== -->

<div class="page-container">

    <div class="edit-card">


        <!-- =============================================
             HEADER
        ============================================== -->

        <div class="page-header">

            <h1>
                Edit Task
            </h1>

            <p>
                Update the task details, progress and status.
            </p>

        </div>


        <!-- =============================================
             ERROR
        ============================================== -->

        <?php if ($error !== ""): ?>

            <div class="alert-error">

                <i class="bi bi-exclamation-circle"></i>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =============================================
             TASK ID
        ============================================== -->

        <div class="task-id-box">

            <i class="bi bi-hash"></i>

            Task ID:
            <?= (int)$task_id ?>

        </div>


        <!-- =============================================
             FORM
        ============================================== -->

        <form
            method="POST"
            action="edit.php?id=<?= (int)$task_id ?>"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$task_id ?>"
            >


            <!-- =========================================
                 TASK
            ========================================== -->

            <div class="form-group">

                <label
                    for="task"
                    class="form-label"
                >
                    Task
                </label>

                <input
                    type="text"
                    id="task"
                    name="task"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $task,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    placeholder="Enter task title"
                    maxlength="255"
                    required
                >

            </div>


            <!-- =========================================
                 DESCRIPTION
            ========================================== -->

            <div class="form-group">

                <label
                    for="description"
                    class="form-label"
                >
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    placeholder="Enter task description"
                ><?= htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></textarea>

            </div>


            <!-- =========================================
                 PRIORITY
            ========================================== -->

            <div class="form-group">

                <label
                    for="priority"
                    class="form-label"
                >
                    Priority
                </label>

                <select
                    id="priority"
                    name="priority"
                    class="form-select"
                >

                    <option
                        value="High"
                        <?= $priority === "High"
                            ? "selected"
                            : "" ?>
                    >
                        High
                    </option>

                    <option
                        value="Medium"
                        <?= $priority === "Medium"
                            ? "selected"
                            : "" ?>
                    >
                        Medium
                    </option>

                    <option
                        value="Low"
                        <?= $priority === "Low"
                            ? "selected"
                            : "" ?>
                    >
                        Low
                    </option>

                </select>

            </div>


            <!-- =========================================
                 PROGRESS
            ========================================== -->

            <div class="form-group">

                <label
                    for="progress"
                    class="form-label"
                >
                    Progress
                </label>

                <select
                    id="progress"
                    name="progress"
                    class="form-select"
                >

                    <option
                        value="Todo"
                        <?= $progress === "Todo"
                            ? "selected"
                            : "" ?>
                    >
                        Todo
                    </option>

                    <option
                        value="In Progress"
                        <?= $progress === "In Progress"
                            ? "selected"
                            : "" ?>
                    >
                        In Progress
                    </option>

                    <option
                        value="Review"
                        <?= $progress === "Review"
                            ? "selected"
                            : "" ?>
                    >
                        Review
                    </option>

                    <option
                        value="Done"
                        <?= $progress === "Done"
                            ? "selected"
                            : "" ?>
                    >
                        Done
                    </option>

                </select>

            </div>


            <!-- =========================================
                 STATUS
            ========================================== -->

            <div class="form-group">

                <label
                    for="status"
                    class="form-label"
                >
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-select"
                >

                    <option
                        value="1"
                        <?= $status === 1
                            ? "selected"
                            : "" ?>
                    >
                        Active
                    </option>

                    <option
                        value="0"
                        <?= $status === 0
                            ? "selected"
                            : "" ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- =========================================
                 COMPLETION
            ========================================== -->

            <div class="form-group">

                <label
                    for="is_completed"
                    class="form-label"
                >
                    Completion
                </label>

                <select
                    id="is_completed"
                    name="is_completed"
                    class="form-select"
                >

                    <option
                        value="0"
                        <?= $is_completed === 0
                            ? "selected"
                            : "" ?>
                    >
                        Incomplete
                    </option>

                    <option
                        value="1"
                        <?= $is_completed === 1
                            ? "selected"
                            : "" ?>
                    >
                        Complete
                    </option>

                </select>

            </div>


            <!-- =========================================
                 BUTTONS
            ========================================== -->

            <div class="button-row">

                <a
                    href="index.php"
                    class="btn-app btn-cancel"
                >
                    <i class="bi bi-x-lg"></i>
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn-app btn-save"
                >
                    <i class="bi bi-check-lg"></i>
                    Update Task
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     FOOTER
====================================================== -->

<div class="footer">

    TODO APP

</div>


</body>

</html>