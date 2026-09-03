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
   GET TASK ID
========================================================= */

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {

    header("Location: index.php?error=1");
    exit();

}


/* =========================================================
   GET TASK
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
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars(mysqli_error($conn))
    );

}

mysqli_stmt_bind_param($stmt, "i", $id);

if (!mysqli_stmt_execute($stmt)) {

    die(
        "Database Error: " .
        htmlspecialchars(mysqli_stmt_error($stmt))
    );

}

$result = mysqli_stmt_get_result($stmt);

if (!$result) {

    die(
        "Database Error: " .
        htmlspecialchars(mysqli_error($conn))
    );

}


/* =========================================================
   CHECK TASK EXISTS
========================================================= */

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    header("Location: index.php?error=1");
    exit();

}

$task = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   PRIORITY CLASS
========================================================= */

function getPriorityClass($priority)
{

    switch ($priority) {

        case "High":
            return "bg-danger";

        case "Medium":
            return "bg-warning text-dark";

        case "Low":
            return "bg-success";

        default:
            return "bg-secondary";

    }

}


/* =========================================================
   PROGRESS CLASS
========================================================= */

function getProgressClass($progress)
{

    switch ($progress) {

        case "Todo":
            return "bg-secondary";

        case "In Progress":
            return "bg-primary";

        case "Review":
            return "bg-warning text-dark";

        case "Done":
            return "bg-success";

        default:
            return "bg-secondary";

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

    <title>View Task - Todo App</title>


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
           VIEW CARD
        ===================================================== */

        .view-card {

            max-width: 900px;

            margin: 40px auto;

        }


        /* =====================================================
           TASK TITLE
        ===================================================== */

        .task-title {

            font-size: 30px;

            font-weight: 750;

            color: #17213d;

            margin-bottom: 10px;

        }


        /* =====================================================
           TASK DESCRIPTION
        ===================================================== */

        .task-description {

            color: #74809c;

            font-size: 15px;

            line-height: 1.7;

            margin-bottom: 30px;

        }


        /* =====================================================
           DETAIL ROW
        ===================================================== */

        .detail-row {

            display: grid;

            grid-template-columns: 200px 1fr;

            gap: 20px;

            padding: 16px 0;

            border-bottom: 1px solid #edf1f6;

        }


        .detail-label {

            font-weight: 700;

            color: #36415f;

        }


        .detail-value {

            color: #48536d;

        }


        /* =====================================================
           COMPLETE STATUS
        ===================================================== */

        .complete-status {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 7px 13px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 700;

            white-space: nowrap;

        }


        .complete-status.completed {

            background: #eaf8ef;

            color: #249150;

            border: 1px solid #d4f4df;

        }


        .complete-status.incomplete {

            background: #fff4dc;

            color: #c77b00;

            border: 1px solid #ffd98a;

        }


        /* =====================================================
           ACTION BUTTON
        ===================================================== */

        .task-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf1f6;

        }


        .task-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 40px;

            padding: 0 16px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            transition: all 0.2s ease;

        }


        /* =====================================================
           MARK COMPLETE
        ===================================================== */

        .btn-complete {

            background: #eaf8ef;

            color: #249150;

            border: 1px solid #ccebd7;

        }


        .btn-complete:hover {

            background: #d9f2e2;

            color: #1d7d43;

        }


        /* =====================================================
           MARK INCOMPLETE
        ===================================================== */

        .btn-incomplete {

            background: #fff4dc;

            color: #c77b00;

            border: 1px solid #ffd98a;

        }


        .btn-incomplete:hover {

            background: #ffe8b5;

            color: #a86400;

        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .btn-back {

            background: #f1f3f5;

            color: #495057;

            border: 1px solid #dee2e6;

        }


        .btn-back:hover {

            background: #e2e6ea;

            color: #343a40;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .detail-row {

                grid-template-columns: 1fr;

                gap: 6px;

            }


            .task-actions {

                flex-direction: column;

            }


            .task-btn {

                width: 100%;

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


        <!-- LOGO -->

        <div class="logo">

            ☑ TODO APP

        </div>


        <!-- USER SECTION -->

        <div class="user-section">

            <span>

                Welcome,

                <?php

                echo htmlspecialchars(
                    $_SESSION["user_name"] ?? ""
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
     MAIN
========================================================= -->

<main class="container">


    <div class="card view-card">


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="header-section">

            <div>

                <h1 class="title">

                    View Task

                </h1>

                <p>

                    Task details

                </p>

            </div>


            <a
                href="index.php"
                class="task-btn btn-back"
            >

                ← Back

            </a>

        </div>



        <!-- =================================================
             TASK NAME
        ================================================== -->

        <h2 class="task-title">

            <?php

            echo htmlspecialchars(
                $task["task"] ?? ""
            );

            ?>

        </h2>



        <!-- =================================================
             DESCRIPTION
        ================================================== -->

        <div class="task-description">

            <?php

            if (!empty($task["description"])) {

                echo nl2br(
                    htmlspecialchars(
                        $task["description"]
                    )
                );

            } else {

                echo "No description available.";

            }

            ?>

        </div>



        <!-- =================================================
             TASK ID
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Task ID

            </div>

            <div class="detail-value">

                <?php

                echo (int)$task["id"];

                ?>

            </div>

        </div>



        <!-- =================================================
             PRIORITY
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Priority

            </div>

            <div class="detail-value">

                <span
                    class="badge <?php echo getPriorityClass($task["priority"]); ?>"
                >

                    <?php

                    echo htmlspecialchars(
                        $task["priority"]
                    );

                    ?>

                </span>

            </div>

        </div>



        <!-- =================================================
             PROGRESS
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Progress

            </div>

            <div class="detail-value">

                <span
                    class="badge <?php echo getProgressClass($task["progress"]); ?>"
                >

                    <?php

                    echo htmlspecialchars(
                        $task["progress"]
                    );

                    ?>

                </span>

            </div>

        </div>



        <!-- =================================================
             COMPLETE
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Complete

            </div>

            <div class="detail-value">

                <?php if ((int)$task["is_completed"] === 1): ?>

                    <span class="complete-status completed">

                        Complete

                    </span>

                <?php else: ?>

                    <span class="complete-status incomplete">

                        Incomplete

                    </span>

                <?php endif; ?>

            </div>

        </div>



        <!-- =================================================
             STATUS
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Status

            </div>

            <div class="detail-value">

                <?php if ((int)$task["status"] === 1): ?>

                    <span class="status-active">

                        Active

                    </span>

                <?php else: ?>

                    <span class="status-inactive">

                        Inactive

                    </span>

                <?php endif; ?>

            </div>

        </div>



        <!-- =================================================
             ADDED DATE & TIME
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Added Date & Time

            </div>

            <div class="detail-value">

                <?php

                if (!empty($task["addedDate"])) {

                    echo date(
                        "d M Y, h:i:s A",
                        strtotime($task["addedDate"])
                    );

                } else {

                    echo "Not Available";

                }

                ?>

            </div>

        </div>



        <!-- =================================================
             EDITED DATE & TIME
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Edited Date & Time

            </div>

            <div class="detail-value">

                <?php

                if (!empty($task["editedDate"])) {

                    echo date(
                        "d M Y, h:i:s A",
                        strtotime($task["editedDate"])
                    );

                } else {

                    echo "Not Available";

                }

                ?>

            </div>

        </div>



        <!-- =================================================
             ONLY COMPLETE / INCOMPLETE BUTTON
        ================================================== -->

        <div class="task-actions">

            <?php if ((int)$task["is_completed"] === 1): ?>

                <!-- TASK IS COMPLETE -->

                <a
                    href="toggle_complete.php?id=<?php echo (int)$task["id"]; ?>"
                    class="task-btn btn-incomplete"
                    onclick="return confirm('Mark this task as incomplete?');"
                >

                    ↩ Mark Incomplete

                </a>

            <?php else: ?>

                <!-- TASK IS INCOMPLETE -->

                <a
                    href="toggle_complete.php?id=<?php echo (int)$task["id"]; ?>"
                    class="task-btn btn-complete"
                    onclick="return confirm('Mark this task as completed?');"
                >

                    ✓ Mark Complete

                </a>

            <?php endif; ?>

        </div>


    </div>

</main>


</body>

</html>