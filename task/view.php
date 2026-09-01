
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
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database Error: " . mysqli_error($conn));

}


mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (!$result) {

    die("Database Error: " . mysqli_error($conn));

}


/* =========================================================
   CHECK TASK EXISTS
========================================================= */

if (mysqli_num_rows($result) === 0) {

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
           VIEW PAGE
        ===================================================== */

        .view-card {

            max-width: 900px;

            margin: 40px auto;

        }


        .task-title {

            font-size: 30px;

            font-weight: 750;

            color: #17213d;

            margin-bottom: 10px;

        }


        .task-description {

            color: #74809c;

            font-size: 15px;

            line-height: 1.7;

            margin-bottom: 30px;

        }


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
           MARK COMPLETE BUTTON
        ===================================================== */

        .btn-complete {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 40px;

            padding: 0 16px;

            border-radius: 9px;

            background: #eaf8ef !important;

            color: #249150 !important;

            border: 1px solid #ccebd7 !important;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            transition: all 0.2s ease;

        }


        .btn-complete:hover {

            background: #d9f2e2 !important;

            color: #1d7d43 !important;

            border-color: #b9e3c8 !important;

            transform: translateY(-1px);

        }


        /* =====================================================
           MARK INCOMPLETE BUTTON
        ===================================================== */

        .btn-incomplete {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 40px;

            padding: 0 16px;

            border-radius: 9px;

            background: #fff4dc !important;

            color: #c77b00 !important;

            border: 1px solid #ffd98a !important;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            transition: all 0.2s ease;

        }


        .btn-incomplete:hover {

            background: #ffe8b5 !important;

            color: #a86400 !important;

            border-color: #ffc95c !important;

            transform: translateY(-1px);

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .detail-row {

                grid-template-columns: 1fr;

                gap: 6px;

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



<!-- =========================================================
     MAIN CONTENT
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
                class="btn btn-edit"
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
             TASK DETAILS
        ================================================= -->

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
             COMPLETE STATUS
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
             ADDED DATE
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Added Date & Time

            </div>

            <div class="detail-value">

                <?php

                echo date(
                    "d M Y, h:i:s A",
                    strtotime($task["addedDate"])
                );

                ?>

            </div>

        </div>



        <!-- =================================================
             EDITED DATE
        ================================================== -->

        <div class="detail-row">

            <div class="detail-label">

                Edited Date & Time

            </div>

            <div class="detail-value">

                <?php

                echo date(
                    "d M Y, h:i:s A",
                    strtotime($task["editedDate"])
                );

                ?>

            </div>

        </div>



        <!-- =================================================
             MARK COMPLETE / INCOMPLETE
             ONLY BUTTON ON VIEW PAGE
        ================================================== -->

        <div class="mt-4">

            <?php if ((int)$task["is_completed"] === 1): ?>

                <a
                    href="toggle_complete.php?id=<?php echo (int)$task["id"]; ?>"
                    class="btn btn-incomplete"
                >

                    Mark Incomplete

                </a>

            <?php else: ?>

                <a
                    href="toggle_complete.php?id=<?php echo (int)$task["id"]; ?>"
                    class="btn btn-complete"
                >

                    Mark Complete

                </a>

            <?php endif; ?>

        </div>


    </div>

</main>


</body>

</html>





