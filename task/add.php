
<?php

session_start();

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    if (
        isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
        strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
    ) {
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => false,
            "message" => "Your session has expired. Please login again."
        ]);

        exit();
    }

    header("Location: ../auth/login.php");
    exit();
}


/* =========================================================
   AJAX DETECTION
========================================================= */

$is_ajax = (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
);


/* =========================================================
   USER ACCESS
========================================================= */

$user_role = $_SESSION["user_role"] ?? "user";

$is_admin = ($user_role === "admin");
$is_user  = ($user_role === "user");


/* =========================================================
   DEFAULT VALUES
========================================================= */

$error = "";

$task = "";
$description = "";
$status = 1;
$priority = "Medium";
$progress = "Todo";

$board_id = 0;
$board_name = "";


/* =========================================================
   BOARD ID
   IMPORTANT:
   POST VALUE FIRST, THEN GET VALUE
========================================================= */

if (isset($_POST["board_id"])) {

    $board_id = (int)$_POST["board_id"];

} elseif (isset($_GET["board_id"])) {

    $board_id = (int)$_GET["board_id"];

}


/* =========================================================
   GET BOARD INFORMATION
========================================================= */

if ($board_id > 0) {

    $board_sql = "
        SELECT
            id,
            name
        FROM boards
        WHERE id = ?
        LIMIT 1
    ";

    $board_stmt = mysqli_prepare(
        $conn,
        $board_sql
    );

    if ($board_stmt) {

        mysqli_stmt_bind_param(
            $board_stmt,
            "i",
            $board_id
        );

        mysqli_stmt_execute(
            $board_stmt
        );

        $board_result =
            mysqli_stmt_get_result(
                $board_stmt
            );

        if (
            $board_result &&
            mysqli_num_rows($board_result) > 0
        ) {

            $board_row =
                mysqli_fetch_assoc(
                    $board_result
                );

            $board_name =
                $board_row["name"];

        }

        mysqli_stmt_close(
            $board_stmt
        );

    } else {

        $error =
            "Database error while checking board.";

    }

}


/* =========================================================
   ADD TASK
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task =
        trim($_POST["task"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $status =
        (int)($_POST["status"] ?? 1);

    $priority =
        $_POST["priority"] ?? "Medium";

    $progress =
        $_POST["progress"] ?? "Todo";

    /*
     * Get board ID again from POST.
     * This guarantees the AJAX-created task
     * belongs to the selected board.
     */
    $board_id =
        (int)($_POST["board_id"] ?? 0);


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (!($is_admin || $is_user)) {

        $error =
            "You do not have permission to add tasks.";

    }

    elseif ($board_id <= 0) {

        $error =
            "Please select a board.";

    }

    else {

        /*
         * Re-check the board AFTER getting the POST board_id.
         */
        $board_name = "";

        $board_sql = "
            SELECT
                id,
                name
            FROM boards
            WHERE id = ?
            LIMIT 1
        ";

        $board_stmt =
            mysqli_prepare(
                $conn,
                $board_sql
            );

        if (!$board_stmt) {

            $error =
                "Database error while checking board.";

        } else {

            mysqli_stmt_bind_param(
                $board_stmt,
                "i",
                $board_id
            );

            mysqli_stmt_execute(
                $board_stmt
            );

            $board_result =
                mysqli_stmt_get_result(
                    $board_stmt
                );

            if (
                $board_result &&
                mysqli_num_rows($board_result) > 0
            ) {

                $board_row =
                    mysqli_fetch_assoc(
                        $board_result
                    );

                $board_name =
                    $board_row["name"];

            }

            mysqli_stmt_close(
                $board_stmt
            );


            if ($board_name === "") {

                $error =
                    "Selected board does not exist.";

            }

        }

    }


    /* =====================================================
       OTHER VALIDATION
    ===================================================== */

    if ($error === "" && empty($task)) {

        $error =
            "Please enter a task.";

    }


    if (
        $error === "" &&
        !in_array(
            $status,
            [1, 2],
            true
        )
    ) {

        $error =
            "Invalid status.";

    }


    if (
        $error === "" &&
        !in_array(
            $priority,
            ["Low", "Medium", "High"],
            true
        )
    ) {

        $error =
            "Invalid priority.";

    }


    if (
        $error === "" &&
        !in_array(
            $progress,
            [
                "Todo",
                "In Progress",
                "Pending",
                "Review",
                "Done"
            ],
            true
        )
    ) {

        $error =
            "Invalid progress.";

    }


    /* =====================================================
       INSERT TASK
    ===================================================== */

    if ($error === "") {

        $sql = "
            INSERT INTO tasks
            (
                board_id,
                task,
                description,
                status,
                priority,
                progress,
                addedDate,
                editedDate
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            $error =
                "Database error: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ississ",
                $board_id,
                $task,
                $description,
                $status,
                $priority,
                $progress
            );


            if (
                mysqli_stmt_execute(
                    $stmt
                )
            ) {

                $new_task_id =
                    mysqli_insert_id($conn);

                mysqli_stmt_close(
                    $stmt
                );


                /* =========================================
                   AJAX SUCCESS
                ========================================= */

                if ($is_ajax) {

                    header(
                        "Content-Type: application/json; charset=UTF-8"
                    );

                    echo json_encode([
                        "success" => true,
                        "message" =>
                            "Task added successfully.",
                        "task_id" =>
                            (int)$new_task_id,
                        "board_id" =>
                            (int)$board_id,
                        "board_name" =>
                            $board_name,
                        "progress" =>
                            $progress
                    ]);

                    exit();

                }


                /* =========================================
                   NORMAL FORM SUCCESS
                ========================================= */

                $_SESSION["success"] =
                    "Task added successfully.";


                header(
                    "Location: index.php?board_id=" .
                    (int)$board_id
                );

                exit();

            }


            $error =
                "Failed to add task: " .
                mysqli_stmt_error($stmt);

            mysqli_stmt_close(
                $stmt
            );

        }

    }


    /* =====================================================
       AJAX ERROR
    ===================================================== */

    if ($is_ajax) {

        header(
            "Content-Type: application/json; charset=UTF-8"
        );

        echo json_encode([
            "success" => false,
            "message" =>
                $error !== ""
                    ? $error
                    : "Failed to add task."
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

    <title>Add Task</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow">


                <div class="card-header bg-primary text-white">

                    <h4 class="mb-0">
                        Add Card / Task
                    </h4>

                </div>


                <div class="card-body">


                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $board_id > 0 &&
                        !empty($board_name)
                    ): ?>

                        <div class="alert alert-info">

                            <strong>Board:</strong>

                            <?= htmlspecialchars($board_name) ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST">


                        <input
                            type="hidden"
                            name="board_id"
                            value="<?= (int)$board_id ?>"
                        >


                        <div class="mb-3">

                            <label class="form-label">
                                Task
                            </label>

                            <input
                                type="text"
                                name="task"
                                class="form-control"
                                placeholder="Enter task"
                                value="<?= htmlspecialchars($task) ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Task Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                placeholder="Enter task description"
                            ><?= htmlspecialchars($description) ?></textarea>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Priority
                            </label>

                            <select
                                name="priority"
                                class="form-select"
                            >

                                <?php foreach (
                                    ["Low", "Medium", "High"]
                                    as $item
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars($item) ?>"
                                        <?= $priority === $item
                                            ? "selected"
                                            : "" ?>
                                    >
                                        <?= htmlspecialchars($item) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Task Progress
                            </label>

                            <select
                                name="progress"
                                class="form-select"
                            >

                                <?php foreach (
                                    [
                                        "Todo",
                                        "In Progress",
                                        "Pending",
                                        "Review",
                                        "Done"
                                    ]
                                    as $item
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars($item) ?>"
                                        <?= $progress === $item
                                            ? "selected"
                                            : "" ?>
                                    >
                                        <?= htmlspecialchars($item) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >

                                <option
                                    value="1"
                                    <?= $status == 1
                                        ? "selected"
                                        : "" ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="2"
                                    <?= $status == 2
                                        ? "selected"
                                        : "" ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Add Card / Task
                            </button>


                            <a
                                href="<?= $board_id > 0
                                    ? 'index.php?board_id=' .
                                      (int)$board_id
                                    : 'index.php'
                                ?>"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>

