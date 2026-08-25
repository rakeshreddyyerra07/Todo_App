<?php

require_once __DIR__ . "/config/database.php";

if (isset($_POST['submit'])) {

    $task = $_POST['task'];
    $status = $_POST['status'];

    $sql = "INSERT INTO tasks (task, status)
            VALUES ('$task', '$status')";

    if (mysqli_query($conn, $sql)) {
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Task</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

    <!-- Navbar -->

    <div class="navbar">

        <div class="nav-container">
            <h2>☑ TODO APP</h2>
        </div>

    </div>


    <!-- Form Container -->

    <div class="form-container">

        <div class="form-card">

            <h1>Add Task</h1>

         


            <form method="POST">

                <div class="form-group">

                    <label>Task Description</label>

                    <input
                        type="text"
                        name="task"
                        placeholder="Enter task description"
                        required>

                </div>


                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="1">
                            Active
                        </option>

                        <option value="2">
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="form-buttons">

                    <button
                        type="submit"
                        name="submit"
                        class="save-btn">

                        Add Task

                    </button>


                    <a
                        href="index.php"
                        class="back-btn">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</body>

</html>