<?php

require_once __DIR__ . "/config/database.php";

/* Get all active tasks */

$sql = "SELECT id, task, status, addedDate, editedDate
        FROM tasks
        WHERE status = 1
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>TODO APP</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>


<!-- NAVBAR -->

<div class="navbar">

    <div class="nav-container">

        <div class="logo">
            ☑ TODO APP
        </div>

    </div>

</div>


<!-- MAIN CONTENT -->

<div class="container">

    <div class="card">


        <!-- PAGE HEADER -->

        <div class="header-section">

            <div>

                <h1 class="title">
                    Tasks
                </h1>

               

            </div>


            <a href="add.php" class="btn btn-add">

                + Add Task

            </a>

        </div>


        <!-- TASK TABLE -->

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Task Description</th>

                        <th>Status</th>

                        <th>Added Date</th>

                        <th>Edited Date</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                    <?php

                    /* Check if active tasks exist */

                    if (mysqli_num_rows($result) > 0) {

                        /* Display all active tasks */

                        while ($row = mysqli_fetch_assoc($result)) {

                    ?>

                        <tr>

                            <td>
                                <?php echo $row['id']; ?>
                            </td>


                            <td>
                                <?php echo htmlspecialchars($row['task']); ?>
                            </td>


                            <td>

                                <span class="status-active">

                                    Active

                                </span>

                            </td>


                            <td>
                                <?php echo $row['addedDate']; ?>
                            </td>


                            <td>
                                <?php echo $row['editedDate']; ?>
                            </td>


                            <td>

                                <a
                                    href="edit.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-edit">

                                    Edit

                                </a>


                                <a
                                    href="delete.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this task?');">

                                    Delete

                                </a>

                            </td>

                        </tr>

                    <?php

                        }

                    } else {

                    ?>

                      
                    <?php

                    }

                    ?>

                </tbody>

            </table>

        </div>


        

    </div>

</div>

</body>

</html>