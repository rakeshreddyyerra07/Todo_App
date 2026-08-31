
<?php

session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";

$id = (int)($_GET["id"] ?? 0);

$task = "";
$status = 1;


/* =========================
   CHECK TASK ID
========================= */

if ($id <= 0) {

    header("Location: index.php");
    exit;
}


/* =========================
   GET TASK
========================= */

$sql = "SELECT id, task, status, priority, progress
        FROM tasks
        WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    header("Location: index.php");
    exit;
}

$row = $result->fetch_assoc();

$task = $row["task"];
$status = $row["status"];
$priority = $row["priority"];
$progress = $row["progress"];

$stmt->close();


/* =========================
   UPDATE TASK
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $task = trim($_POST["task"] ?? "");

    $status = (int)($_POST["status"] ?? 1);
    $priority = $_POST["priority"] ?? "Medium";
    $progress = $_POST["progress"] ?? "Todo";


    if (empty($task)) {

        $error = "Please enter a task.";

    } elseif (!in_array($status, [1, 2]) || !in_array($priority, ["Low", "Medium", "High"], true) || !in_array($progress, ["Todo", "In Progress", "Review", "Done"], true)) {

        $error = "Invalid status.";

    } else {

        $sql = "UPDATE tasks
                SET task = ?,
                    status = ?,
                    priority = ?,
                    progress = ?,
                    editedDate = CURRENT_TIMESTAMP
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("sissi", $task, $status, $priority, $progress, $id);

            if ($stmt->execute()) {

                $_SESSION["success"] = "Task updated successfully.";

                header("Location: index.php");
                exit;

            } else {

                $error = "Failed to update task.";
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Task</title>

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

                <div class="card-header bg-warning">

                    <h4 class="mb-0">
                        Edit Task
                    </h4>

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
                                required
                            ><?= htmlspecialchars($task) ?></textarea>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <?php foreach (["Low", "Medium", "High"] as $item): ?>
                                    <option value="<?= $item ?>" <?= $priority === $item ? "selected" : "" ?>><?= $item ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Task Progress</label>
                            <select name="progress" class="form-select">
                                <?php foreach (["Todo", "In Progress", "Review", "Done"] as $item): ?>
                                    <option value="<?= $item ?>" <?= $progress === $item ? "selected" : "" ?>><?= $item ?></option>
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


                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Update Task
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
