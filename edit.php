<?php

require_once __DIR__ . "/config/database.php";

if (!isset($_GET['id'])) {
    die("Task ID is missing");
}

$id = $_GET['id'];

/* Get task details */

$sql = "SELECT id, task, status, addedDate, editedDate
        FROM tasks
        WHERE id = $id";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Task not found");
}

$row = mysqli_fetch_assoc($result);


/* Update task */

if (isset($_POST['update'])) {

    $task = $_POST['task'];
    $status = $_POST['status'];

    $updateSql = "UPDATE tasks
                  SET task = '$task',
                      status = '$status'
                  WHERE id = $id";

    if (mysqli_query($conn, $updateSql)) {

        header("Location: index.php");
        exit();

    } else {

        echo "Update Error: " . mysqli_error($conn);

    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Edit Task</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

<!-- Navbar -->

<div class="navbar">

    <div class="nav-container">

        <h2>☑ TODO APP</h2>

    </div>

</div>


<!-- Edit Form -->

<div class="form-container">

    <div class="form-card">

        <h1>Edit Task</h1>

     


        <form method="POST">

            <!-- Task Description -->

            <div class="form-group">

                <label>Task Description</label>

                <input
                    type="text"
                    name="task"
                    value="<?php echo htmlspecialchars($row['task']); ?>"
                    required
                >

            </div>


            <!-- Status -->

            <div class="form-group">

                <label>Status</label>

                <select name="status">

                    <option
                        value="1"
                        <?php
                        if ($row['status'] == 1) {
                            echo "selected";
                        }
                        ?>
                    >
                        Active
                    </option>


                    <option
                        value="2"
                        <?php
                        if ($row['status'] == 2) {
                            echo "selected";
                        }
                        ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- Buttons -->

            <div class="form-buttons">

                <button
                    type="submit"
                    name="update"
                    class="save-btn"
                >
                    Update Task
                </button>


                <a
                    href="index.php"
                    class="back-btn"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>


<!-- Task Details -->

<div class="task-details">

    <h2>Current Task Details</h2>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Task Description</th>
                    <th>Status</th>
                    <th>Added Date</th>
                    <th>Edited Date</th>

                </tr>

            </thead>


            <tbody>

                <tr>

                    <td>
                        <?php echo $row['id']; ?>
                    </td>


                    <td>
                        <?php echo htmlspecialchars($row['task']); ?>
                    </td>


                    <td>

                        <?php

                        if ($row['status'] == 1) {

                        ?>

                            <span class="status-active">
                                Active
                            </span>

                        <?php

                        } else {

                        ?>

                            <span class="status-inactive">
                                Inactive
                            </span>

                        <?php

                        }

                        ?>

                    </td>


                    <td>
                        <?php echo $row['addedDate']; ?>
                    </td>


                    <td>
                        <?php echo $row['editedDate']; ?>
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <a
        href="index.php"
        class="back-home"
    >
        ← Back to Home
    </a>

</div>

</body>

</html>