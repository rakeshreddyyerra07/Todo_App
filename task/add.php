<?php

session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";

$task = "";
$status = 1;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task = trim($_POST["task"] ?? "");
    $status = (int)($_POST["status"] ?? 1);

    if (empty($task)) {

        $error = "Please enter a task.";

    } elseif (!in_array($status, [1, 2])) {

        $error = "Invalid status.";

    } else {

        $sql = "INSERT INTO tasks (task, status, addedDate, editedDate)
                VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("si", $task, $status);

            if ($stmt->execute()) {

                $_SESSION["success"] = "Task added successfully.";

                header("Location: index.php");
                exit;

            } else {

                $error = "Failed to add task.";
            }

            $stmt->close();

        } else {

            $error = "Database error.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

                    <h4 class="mb-0">Add Task</h4>

                </div>

                <div class="card-body">

                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Task Description
                            </label>

                            <textarea
                                name="task"
                                class="form-control"
                                rows="4"
                                placeholder="Enter task description"
                                required
                            ><?= htmlspecialchars($task) ?></textarea>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status" class="form-select">

                                <option value="1"
                                    <?= $status == 1 ? "selected" : "" ?>>
                                    Active
                                </option>

                                <option value="2"
                                    <?= $status == 2 ? "selected" : "" ?>>
                                    Inactive
                                </option>

                            </select>

                        </div>


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
