
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
   ADMIN ACCESS ONLY
========================================================= */

$user_role = $_SESSION["user_role"] ?? "user";

if ($user_role !== "admin") {

    header("Location: index.php");
    exit();

}


$error = "";

$task = "";
$description = "";
$status = 1;
$priority = "Medium";
$progress = "Todo";


/* =========================================================
   ADD TASK
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task = trim($_POST["task"] ?? "");

    $description = trim($_POST["description"] ?? "");

    $status = (int)($_POST["status"] ?? 1);

    $priority = $_POST["priority"] ?? "Medium";

    $progress = $_POST["progress"] ?? "Todo";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (empty($task)) {

        $error = "Please enter a task.";

    }

    elseif (
        !in_array($status, [1, 2]) ||
        !in_array(
            $priority,
            ["Low", "Medium", "High"],
            true
        ) ||
        !in_array(
            $progress,
            ["Todo", "In Progress", "Review", "Done"],
            true
        )
    ) {

        $error = "Invalid status.";

    }

    else {


        /* =================================================
           INSERT TASK
        ================================================= */

        $sql = "
            INSERT INTO tasks
            (
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
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
        ";


        $stmt = $conn->prepare($sql);


        if ($stmt) {

            $stmt->bind_param(
                "ssiss",
                $task,
                $description,
                $status,
                $priority,
                $progress
            );


            if ($stmt->execute()) {

                $_SESSION["success"] =
                    "Task added successfully.";


                header("Location: index.php");

                exit;

            }

            else {

                $error = "Failed to add task.";

            }


            $stmt->close();

        }

        else {

            $error = "Database error.";

        }

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


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="card-header bg-primary text-white">

                    <h4 class="mb-0">
                        Add Task
                    </h4>

                </div>


                <!-- =================================================
                     BODY
                ================================================== -->

                <div class="card-body">


                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         FORM
                    ================================================== -->

                    <form method="POST">


                        <!-- TASK -->

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


                        <!-- DESCRIPTION -->

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


                        <!-- PRIORITY -->

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
                                        <?= $priority === $item ? "selected" : "" ?>
                                    >

                                        <?= htmlspecialchars($item) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- PROGRESS -->

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
                                        "Review",
                                        "Done"
                                    ]
                                    as $item
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars($item) ?>"
                                        <?= $progress === $item ? "selected" : "" ?>
                                    >

                                        <?= htmlspecialchars($item) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- STATUS -->

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
                                    <?= $status == 1 ? "selected" : "" ?>
                                >
                                    Active
                                </option>


                                <option
                                    value="2"
                                    <?= $status == 2 ? "selected" : "" ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <!-- BUTTONS -->

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                Add Task

                            </button>


                            <a
                                href="index.php"
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

