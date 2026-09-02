
<?php

session_start();

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   LOGIN INFORMATION
========================================================= */

$loginIP =
    $_SESSION["login_ip"] ?? "Not Available";

$loginLatLang =
    $_SESSION["login_latlang"] ?? "Not Available";

$loginLocation =
    $_SESSION["login_location"] ?? "Not Available";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");
    exit();

}


/* =========================================================
   SEARCH / FILTER VALUES
========================================================= */

$search = $_GET["search"] ?? "";

$progress_filter = $_GET["progress"] ?? "";

$priority_filter = $_GET["priority"] ?? "";

$sort = $_GET["sort"] ?? "newest";


/* =========================================================
   BUILD QUERY
========================================================= */

$where = [];

$params = [];

$types = "";


/* =========================================================
   SEARCH TASK
========================================================= */

if ($search !== "") {

    $where[] = "task LIKE ?";

    $params[] = "%" . $search . "%";

    $types .= "s";

}


/* =========================================================
   PROGRESS FILTER
========================================================= */

if ($progress_filter !== "") {

    $where[] = "progress = ?";

    $params[] = $progress_filter;

    $types .= "s";

}


/* =========================================================
   PRIORITY FILTER
========================================================= */

if ($priority_filter !== "") {

    $where[] = "priority = ?";

    $params[] = $priority_filter;

    $types .= "s";

}


/* =========================================================
   WHERE SQL
========================================================= */

$where_sql = "";

if (!empty($where)) {

    $where_sql = "WHERE " . implode(" AND ", $where);

}


/* =========================================================
   SORTING
========================================================= */

switch ($sort) {

    case "oldest":

        $order_sql = "id ASC";

        break;


    case "priority_high":

        $order_sql = "
            CASE priority
                WHEN 'High' THEN 1
                WHEN 'Medium' THEN 2
                WHEN 'Low' THEN 3
                ELSE 4
            END ASC,
            id DESC
        ";

        break;


    case "priority_low":

        $order_sql = "
            CASE priority
                WHEN 'Low' THEN 1
                WHEN 'Medium' THEN 2
                WHEN 'High' THEN 3
                ELSE 4
            END ASC,
            id DESC
        ";

        break;


    case "name_az":

        $order_sql = "task ASC";

        break;


    case "name_za":

        $order_sql = "task DESC";

        break;


    default:

        $order_sql = "id DESC";

        break;

}


/* =========================================================
   GET TASKS
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
    $where_sql
    ORDER BY $order_sql
";


$stmt = mysqli_prepare($conn, $sql);


if (!$stmt) {

    die("Database Error: " . mysqli_error($conn));

}


/* =========================================================
   BIND PARAMETERS
========================================================= */

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}


/* =========================================================
   EXECUTE
========================================================= */

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (!$result) {

    die("Database Error: " . mysqli_error($conn));

}


/* =========================================================
   SEPARATE TASKS
========================================================= */

$todo_tasks = [];

$in_progress_tasks = [];

$review_tasks = [];

$done_tasks = [];


while ($row = mysqli_fetch_assoc($result)) {

    if ($row["progress"] === "Todo") {

        $todo_tasks[] = $row;

    }

    elseif ($row["progress"] === "In Progress") {

        $in_progress_tasks[] = $row;

    }

    elseif ($row["progress"] === "Review") {

        $review_tasks[] = $row;

    }

    elseif ($row["progress"] === "Done") {

        $done_tasks[] = $row;

    }

}


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

    <title>My Tasks - Todo App</title>


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
           ACTION BUTTONS
        ===================================================== */

        .action-buttons {

            display: flex;

            flex-wrap: nowrap;

            align-items: center;

            gap: 6px;

            white-space: nowrap;

        }


        .action-buttons .btn {

            flex-shrink: 0;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-container {

            width: 100%;

            overflow-x: auto;

        }


        .table {

            min-width: 1200px;

        }


        .table th,
        .table td {

            vertical-align: middle;

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


        /* =====================================================
           COMPLETE - GREEN
        ===================================================== */

        .complete-status.completed {

            background: #eaf8ef;

            color: #249150;

            border: 1px solid #d4f4df;

        }


        /* =====================================================
           INCOMPLETE - ORANGE
        ===================================================== */

        .complete-status.incomplete {

            background: #fff4dc;

            color: #c77b00;

            border: 1px solid #ffd98a;

        }


        /* =====================================================
           MARK COMPLETE BUTTON
        ===================================================== */

        .btn-complete {

            display: inline-flex !important;

            align-items: center;

            justify-content: center;

            min-height: 32px;

            padding: 0 11px;

            border-radius: 8px;

            background: #eaf8ef !important;

            color: #249150 !important;

            border: 1px solid #ccebd7 !important;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

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
           MARK INCOMPLETE BUTTON - ORANGE
        ===================================================== */

        .btn-incomplete {

            display: inline-flex !important;

            align-items: center;

            justify-content: center;

            min-height: 32px;

            padding: 0 11px;

            border-radius: 8px;

            background: #fff4dc !important;

            color: #c77b00 !important;

            border: 1px solid #ffd98a !important;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

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
           COMPLETED TASK TEXT
        ===================================================== */

        .completed-task {

            text-decoration: none !important;

            color: inherit !important;

            opacity: 1 !important;

        }


        /* =====================================================
           STATUS ACTIVE
        ===================================================== */

        .status-active {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 7px 13px;

            border-radius: 7px;

            background: #eaf8ef;

            color: #249150;

            border: 1px solid #d4f4df;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           STATUS INACTIVE
        ===================================================== */

        .status-inactive {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 7px 13px;

            border-radius: 7px;

            background: #fff0f1;

            color: #ed4249;

            border: 1px solid #ffd6d9;

            font-size: 12px;

            font-weight: 700;

        }


        /* =====================================================
           LOGIN INFORMATION
           NEW - ONLY FOR IP / LOCATION DISPLAY
        ===================================================== */

        .login-location-card {

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 18px 20px;

            margin-bottom: 25px;

            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);

        }


        .login-location-title {

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 15px;

        }


        .login-info-row {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 9px 0;

            border-bottom: 1px solid #eeeeee;

        }


        .login-info-row:last-child {

            border-bottom: none;

        }


        .login-info-label {

            font-weight: 600;

            min-width: 180px;

        }


        .login-info-value {

            word-break: break-word;

        }


        @media (max-width: 600px) {

            .login-info-row {

                display: block;

            }


            .login-info-label {

                display: block;

                margin-bottom: 4px;

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

    <div class="card">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="header-section">

            <div>

                <h1 class="title">

                    My Tasks

                </h1>


                <p>

                    Manage your tasks

                </p>

            </div>


            <a
                href="add.php"
                class="btn btn-add"
            >

                + Add Task

            </a>

        </div>


        <!-- =================================================
             LOGIN INFORMATION
             NEW
        ================================================== -->

        <div class="login-location-card">

            <div class="login-location-title">

                Login Information

            </div>


            <div class="login-info-row">

                <span class="login-info-label">

                    IP Address:

                </span>

                <span class="login-info-value">

                    <?php

                    echo htmlspecialchars(
                        $loginIP
                    );

                    ?>

                </span>

            </div>


            <div class="login-info-row">

                <span class="login-info-label">

                    Latitude / Longitude:

                </span>

                <span class="login-info-value">

                    <?php

                    echo htmlspecialchars(
                        $loginLatLang
                    );

                    ?>

                </span>

            </div>


            <div class="login-info-row">

                <span class="login-info-label">

                    Location:

                </span>

                <span class="login-info-value">

                    <?php

                    echo htmlspecialchars(
                        $loginLocation
                    );

                    ?>

                </span>

            </div>

        </div>



        <!-- =================================================
             MESSAGES
        ================================================== -->

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


        <?php if (isset($_GET["completed"])): ?>

            <div class="alert alert-success">

                Task completion status updated successfully.

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-danger">

                Something went wrong. Please try again.

            </div>

        <?php endif; ?>



        <!-- =================================================
             SEARCH + FILTER
        ================================================== -->

        <div class="mb-4">

            <form method="GET">

                <div class="row g-3">


                    <!-- SEARCH -->

                    <div class="col-md-4">

                        <label class="form-label">

                            Search Task

                        </label>


                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by task..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >

                    </div>



                    <!-- PROGRESS -->

                    <div class="col-md-2">

                        <label class="form-label">

                            Progress

                        </label>


                        <select
                            name="progress"
                            class="form-select"
                        >

                            <option value="">

                                All

                            </option>


                            <option
                                value="Todo"
                                <?php

                                echo $progress_filter === "Todo"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Todo

                            </option>


                            <option
                                value="In Progress"
                                <?php

                                echo $progress_filter === "In Progress"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                In Progress

                            </option>


                            <option
                                value="Review"
                                <?php

                                echo $progress_filter === "Review"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Review

                            </option>


                            <option
                                value="Done"
                                <?php

                                echo $progress_filter === "Done"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Done

                            </option>

                        </select>

                    </div>



                    <!-- PRIORITY -->

                    <div class="col-md-2">

                        <label class="form-label">

                            Priority

                        </label>


                        <select
                            name="priority"
                            class="form-select"
                        >

                            <option value="">

                                All

                            </option>


                            <option
                                value="High"
                                <?php

                                echo $priority_filter === "High"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                High

                            </option>


                            <option
                                value="Medium"
                                <?php

                                echo $priority_filter === "Medium"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Medium

                            </option>


                            <option
                                value="Low"
                                <?php

                                echo $priority_filter === "Low"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Low

                            </option>

                        </select>

                    </div>



                    <!-- SORT -->

                    <div class="col-md-2">

                        <label class="form-label">

                            Sort

                        </label>


                        <select
                            name="sort"
                            class="form-select"
                        >

                            <option
                                value="newest"
                                <?php

                                echo $sort === "newest"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Newest

                            </option>


                            <option
                                value="oldest"
                                <?php

                                echo $sort === "oldest"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Oldest

                            </option>


                            <option
                                value="priority_high"
                                <?php

                                echo $sort === "priority_high"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                High Priority

                            </option>


                            <option
                                value="priority_low"
                                <?php

                                echo $sort === "priority_low"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Low Priority

                            </option>


                            <option
                                value="name_az"
                                <?php

                                echo $sort === "name_az"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Name A-Z

                            </option>


                            <option
                                value="name_za"
                                <?php

                                echo $sort === "name_za"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Name Z-A

                            </option>

                        </select>

                    </div>



                    <!-- SEARCH BUTTON -->

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >

                            Search

                        </button>

                    </div>

                </div>

            </form>

        </div>



        <!-- =================================================
             TABLE DISPLAY FUNCTION
        ================================================== -->

        <?php

        function displayTaskTable($tasks, $emptyMessage)
        {

        ?>

        <div class="table-container">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Task</th>

                        <th>Description</th>

                        <th>Priority</th>

                        <th>Progress</th>

                        <th>Complete</th>

                        <th>Status</th>

                        <th>Added Date & Time</th>

                        <th>Edited Date & Time</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (!empty($tasks)): ?>


                    <?php foreach ($tasks as $row): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php

                                echo (int)$row["id"];

                                ?>

                            </td>



                            <!-- TASK -->

                            <td>

                                <?php if ((int)$row["is_completed"] === 1): ?>

                                    <span class="completed-task">

                                        <?php

                                        echo htmlspecialchars(
                                            $row["task"]
                                        );

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <?php

                                    echo htmlspecialchars(
                                        $row["task"]
                                    );

                                    ?>

                                <?php endif; ?>

                            </td>



                            <!-- DESCRIPTION -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row["description"] ?? ""
                                );

                                ?>

                            </td>



                            <!-- PRIORITY -->

                            <td>

                                <span
                                    class="badge <?php echo getPriorityClass($row["priority"]); ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $row["priority"]
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- PROGRESS -->

                            <td>

                                <span
                                    class="badge <?php echo getProgressClass($row["progress"]); ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $row["progress"]
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- COMPLETE COLUMN -->

                            <td>

                                <?php if ((int)$row["is_completed"] === 1): ?>

                                    <span class="complete-status completed">

                                        Complete

                                    </span>

                                <?php else: ?>

                                    <span class="complete-status incomplete">

                                        Incomplete

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php if ((int)$row["status"] === 1): ?>

                                    <span class="status-active">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="status-inactive">

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- ADDED DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y, h:i:s A",
                                    strtotime($row["addedDate"])
                                );

                                ?>

                            </td>



                            <!-- EDITED DATE -->

                            <td>

                                <?php

                                echo date(
                                    "d M Y, h:i:s A",
                                    strtotime($row["editedDate"])
                                );

                                ?>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <div class="action-buttons">


                                    <!-- VIEW BUTTON -->

                                    <a
                                        href="view.php?id=<?php echo (int)$row["id"]; ?>"
                                        class="btn btn-primary btn-sm"
                                    >

                                        View

                                    </a>


                                    <!-- EDIT BUTTON -->

                                    <a
                                        href="edit.php?id=<?php echo (int)$row["id"]; ?>"
                                        class="btn btn-edit btn-sm"
                                    >

                                        Edit

                                    </a>


                                    <!-- DELETE BUTTON -->

                                    <a
                                        href="delete.php?id=<?php echo (int)$row["id"]; ?>"
                                        class="btn btn-delete btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this task?');"
                                    >

                                        Delete

                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="10"
                            class="text-center"
                        >

                            <?php echo $emptyMessage; ?>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


        <?php

        }

        ?>



        <!-- =================================================
             TODO
        ================================================== -->

        <div class="mb-5">

            <div class="alert alert-secondary">

                <strong>

                    TODO

                </strong>


                <span class="float-end">

                    <?php echo count($todo_tasks); ?>

                    Tasks

                </span>

            </div>


            <?php

            displayTaskTable(
                $todo_tasks,
                "No Todo Tasks"
            );

            ?>

        </div>



        <!-- =================================================
             IN PROGRESS
        ================================================== -->

        <div class="mb-5">

            <div class="alert alert-primary">

                <strong>

                    IN PROGRESS

                </strong>


                <span class="float-end">

                    <?php echo count($in_progress_tasks); ?>

                    Tasks

                </span>

            </div>


            <?php

            displayTaskTable(
                $in_progress_tasks,
                "No In Progress Tasks"
            );

            ?>

        </div>



        <!-- =================================================
             REVIEW
        ================================================== -->

        <div class="mb-5">

            <div class="alert alert-warning">

                <strong>

                    REVIEW

                </strong>


                <span class="float-end">

                    <?php echo count($review_tasks); ?>

                    Tasks

                </span>

            </div>


            <?php

            displayTaskTable(
                $review_tasks,
                "No Review Tasks"
            );

            ?>

        </div>



        <!-- =================================================
             DONE
        ================================================== -->

        <div class="mb-4">

            <div class="alert alert-success">

                <strong>

                    DONE

                </strong>


                <span class="float-end">

                    <?php echo count($done_tasks); ?>

                    Tasks

                </span>

            </div>


            <?php

            displayTaskTable(
                $done_tasks,
                "No Done Tasks"
            );

            ?>

        </div>


    </div>

</main>


</body>

</html>

