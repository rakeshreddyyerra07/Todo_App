
<?php


session_start();

require_once __DIR__ . "/../config/database.php";

/* Check Login */

if (!isset($_SESSION["user_id"])) {
    

    header("Location: ../auth/login.php");
    exit;
    
}




/* Get Active Tasks*/

$sql = "
    SELECT id, task, status, addedDate, editedDate
    FROM tasks
    WHERE status = 1
    ORDER BY id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die("Database Error: " . mysqli_error($conn));
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

    <title>My Tasks - Todo App</title>

    <link
        rel="stylesheet"
        href="../assets/style.css"
    >

</head>

<body>


<!--  NAVBAR -->

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


<!-- MAIN CONTENT -->

<main class="container">

    <div class="card">


        <!-- PAGE HEADER -->

        <div class="header-section">

            <div>

                <h1 class="title">
                    My Tasks
                </h1>

                <p>
                    Manage your active tasks
                </p>

            </div>


            <a
                href="add.php"
                class="btn btn-add"
            >
                + Add Task
            </a>

        </div>


        <!-- SUCCESS / ERROR MESSAGES -->

        <?php if (isset($_GET["added"])): ?>

            <div class="alert alert-success">
                Task added successfully.
            </div>

        <?php endif; ?>


        <?php if (isset($_GET["updated"])): ?>

            <div class="alert alert-success">
                Task updated successfully.
            </div>

        <?php endif; ?>


        <?php if (isset($_GET["deleted"])): ?>

            <div class="alert alert-success">
                Task deleted successfully.
            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-error">
                Something went wrong. Please try again.
            </div>

        <?php endif; ?>


        <!-- 
             TASK TABLE
         -->

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Task Description
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Added Date & Time
                        </th>

                        <th>
                            Edited Date & Time
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (mysqli_num_rows($result) > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($result)): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php
                                echo (int) $row["id"];
                                ?>

                            </td>


                            <!-- TASK -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row["task"]
                                );
                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span class="status-active">
                                    Active
                                </span>

                            </td>


                            <!-- ADDED DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y, h:i:s A",
                                    strtotime(
                                        $row["addedDate"]
                                    )
                                );

                                ?>

                            </td>


                            <!-- EDITED DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y, h:i:s A",
                                    strtotime(
                                        $row["editedDate"]
                                    )
                                );

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td>


                                <a
                                    href="edit.php?id=<?php echo (int) $row["id"]; ?>"
                                    class="btn btn-edit"
                                >
                                    Edit
                                </a>


                                <a
                                    href="delete.php?id=<?php echo (int) $row["id"]; ?>"
                                    class="btn btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this task?');"
                                >
                                    Delete
                                </a>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            style="text-align:center;"
                        >

                            <div class="empty-state">

                                <h3>
                                    No Active Tasks
                                </h3>

                                <p>
                                    You do not have any active tasks yet.
                                </p>

                                <br>

                                <a
                                    href="add.php"
                                    class="btn btn-add"
                                >
                                    + Add Your First Task
                                </a>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</main>

</body>

</html>

