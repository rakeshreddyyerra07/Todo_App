<?php helper('task'); ?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Task - TODO APP</title>

    <!-- =================================================
         BOOTSTRAP
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- =================================================
         EXISTING APP CSS
    ================================================== -->

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f4f7fc;

            color: #17213d;

            min-height: 100vh;
        }

        /* =================================================
           NAVBAR
        ================================================= */

        .navbar {
            width: 100%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.96
                );

            border-bottom:
                1px solid #e5ebf5;

            box-shadow:
                0 4px 20px
                rgba(
                    35,
                    75,
                    140,
                    0.06
                );
        }

        .nav-container {
            width: 92%;

            max-width: 1250px;

            margin: auto;

            min-height: 78px;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }

        .logo {
            font-size: 25px;

            font-weight: 800;

            color: #18213f;

            letter-spacing: 0.3px;
        }

        .logo::first-letter {
            color: #2878ee;
        }

        .user-section {
            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 14px;

            color: #485475;
        }

        .role-badge {
            padding: 6px 10px;

            border-radius: 20px;

            background: #eef5ff;

            color: #176ce8;

            font-size: 12px;

            font-weight: 700;
        }

        .logout-link {
            color: #dc3545;

            font-weight: 600;
        }

        .logout-link:hover {
            text-decoration: underline;
        }

        /* =================================================
           CONTAINER
        ================================================= */

        .page-container {
            width: 92%;

            max-width: 800px;

            margin: 40px auto;
        }

        /* =================================================
           CARD
        ================================================= */

        .edit-card {
            background: #ffffff;

            border-radius: 20px;

            padding: 30px;

            border:
                1px solid #eef2f8;

            box-shadow:
                0 12px 35px
                rgba(
                    40,
                    82,
                    150,
                    0.09
                );
        }

        /* =================================================
           HEADER
        ================================================= */

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 28px;

            font-weight: 750;

            color: #17213d;

            margin-bottom: 6px;
        }

        .page-header p {
            font-size: 14px;

            color: #7a859b;
        }

        /* =================================================
           ALERT
        ================================================= */

        .alert-error {
            padding: 12px 15px;

            margin-bottom: 20px;

            border-radius: 9px;

            background: #fff0f1;

            color: #cf3139;

            border:
                1px solid #ffd6d9;

            font-size: 14px;
        }

        /* =================================================
           FORM GROUP
        ================================================= */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;

            color: #36415f;
        }

        .form-control,
        .form-select {
            width: 100%;

            min-height: 48px;

            padding:
                10px 15px;

            border:
                1px solid #dbe3f0;

            border-radius: 10px;

            background: #ffffff;

            color: #1c2742;

            font-size: 14px;

            outline: none;

            transition: 0.2s ease;
        }

        textarea.form-control {
            min-height: 130px;

            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #438cf2;

            box-shadow:
                0 0 0 3px
                rgba(
                    67,
                    140,
                    242,
                    0.12
                );
        }

        /* =================================================
           TASK ID
        ================================================= */

        .task-id-box {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            margin-bottom: 20px;

            border-radius: 8px;

            background: #f1f6ff;

            color: #176ce8;

            font-size: 12px;

            font-weight: 700;
        }

        /* =================================================
           BUTTONS
        ================================================= */

        .button-row {
            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 28px;
        }

        .btn-app {
            min-height: 42px;

            padding:
                0 18px;

            border: none;

            border-radius: 9px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            text-decoration: none;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .btn-app:hover {
            transform:
                translateY(-1px);
        }

        .btn-save {
            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #438ff4,
                    #176ce8
                );

            box-shadow:
                0 7px 18px
                rgba(
                    44,
                    124,
                    239,
                    0.22
                );
        }

        .btn-save:hover {
            color: #ffffff;

            box-shadow:
                0 10px 22px
                rgba(
                    44,
                    124,
                    239,
                    0.30
                );
        }

        .btn-cancel {
            background: #f1f4f8;

            color: #526078;

            border:
                1px solid #e0e6ee;
        }

        .btn-cancel:hover {
            background: #e8edf4;

            color: #36415f;
        }

        /* =================================================
           FOOTER
        ================================================= */

        .footer {
            text-align: center;

            color: #8993a8;

            font-size: 13px;

            padding: 25px 0;
        }

        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 768px) {

            .nav-container {
                min-height: 68px;
            }

            .logo {
                font-size: 21px;
            }

            .user-section span {
                display: none;
            }

            .page-container {
                width: 94%;

                margin: 25px auto;
            }

            .edit-card {
                padding: 20px;

                border-radius: 16px;
            }

            .page-header h1 {
                font-size: 24px;
            }

        }

        @media (max-width: 480px) {

            .button-row {
                flex-direction: column;
            }

            .btn-app {
                width: 100%;
            }

        }

    </style>

</head>

<body>


<!-- =====================================================
     NAVBAR
====================================================== -->

<nav class="navbar">

    <div class="nav-container">

        <div class="logo">
            ☑ TODO APP
        </div>

        <div class="user-section">

            <span>
                Welcome,
                <?= esc(session('user_name') ?? 'User') ?>
            </span>

            <span class="role-badge">
                <?= esc(ucfirst($user_role)) ?>
            </span>

            <a
                href="<?= site_url('logout') ?>"
                class="logout-link"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     MAIN
====================================================== -->

<div class="page-container">

    <div class="edit-card">


        <!-- =============================================
             HEADER
        ============================================== -->

        <div class="page-header">

            <h1>
                Edit Task
            </h1>

            <p>
                Update the task details, progress and status.
            </p>

        </div>


        <!-- =============================================
             ERROR
        ============================================== -->

        <?php if ($error !== ""): ?>

            <div class="alert-error">

                <i class="bi bi-exclamation-circle"></i>

                <?= esc($error) ?>

            </div>

        <?php endif; ?>


        <!-- =============================================
             TASK ID
        ============================================== -->

        <div class="task-id-box">

            <i class="bi bi-hash"></i>

            Task ID:
            <?= (int)$task_id ?>

        </div>


        <!-- =============================================
             FORM
        ============================================== -->

        <form
            method="POST"
            action="<?= site_url('tasks/edit/' . (int) $task_id) ?>"
        >
    <?= csrf_field() ?>

            <input
                type="hidden"
                name="id"
                value="<?= (int)$task_id ?>"
            >


            <!-- =========================================
                 TASK
            ========================================== -->

            <div class="form-group">

                <label
                    for="task"
                    class="form-label"
                >
                    Task
                </label>

                <input
                    type="text"
                    id="task"
                    name="task"
                    class="form-control"
                    value="<?= esc($task) ?>"
                    placeholder="Enter task title"
                    maxlength="255"
                    required
                >

            </div>


            <!-- =========================================
                 DESCRIPTION
            ========================================== -->

            <div class="form-group">

                <label
                    for="description"
                    class="form-label"
                >
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    placeholder="Enter task description"
                ><?= esc($description) ?></textarea>

            </div>


            <!-- =========================================
                 PRIORITY
            ========================================== -->

            <div class="form-group">

                <label
                    for="priority"
                    class="form-label"
                >
                    Priority
                </label>

                <select
                    id="priority"
                    name="priority"
                    class="form-select"
                >

                    <option
                        value="High"
                        <?= $priority === "High"
                            ? "selected"
                            : "" ?>
                    >
                        High
                    </option>

                    <option
                        value="Medium"
                        <?= $priority === "Medium"
                            ? "selected"
                            : "" ?>
                    >
                        Medium
                    </option>

                    <option
                        value="Low"
                        <?= $priority === "Low"
                            ? "selected"
                            : "" ?>
                    >
                        Low
                    </option>

                </select>

            </div>


            <!-- =========================================
                 PROGRESS
            ========================================== -->

            <div class="form-group">

                <label
                    for="progress"
                    class="form-label"
                >
                    Progress
                </label>

                <select
                    id="progress"
                    name="progress"
                    class="form-select"
                >

                    <option
                        value="Todo"
                        <?= $progress === "Todo"
                            ? "selected"
                            : "" ?>
                    >
                        Todo
                    </option>

                    <option
                        value="In Progress"
                        <?= $progress === "In Progress"
                            ? "selected"
                            : "" ?>
                    >
                        In Progress
                    </option>

                    <option
                        value="Pending"
                        <?= $progress === "Pending"
                            ? "selected"
                            : "" ?>
                    >
                        Pending
                    </option>

                    <option
                        value="Review"
                        <?= $progress === "Review"
                            ? "selected"
                            : "" ?>
                    >
                        Review
                    </option>

                    <option
                        value="Done"
                        <?= $progress === "Done"
                            ? "selected"
                            : "" ?>
                    >
                        Done
                    </option>

                </select>

            </div>


            <!-- =========================================
                 STATUS
            ========================================== -->

            <div class="form-group">

                <label
                    for="status"
                    class="form-label"
                >
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-select"
                >

                    <option
                        value="1"
                        <?= $status === 1
                            ? "selected"
                            : "" ?>
                    >
                        Active
                    </option>

                    <option
                        value="0"
                        <?= $status === 0
                            ? "selected"
                            : "" ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- =========================================
                 COMPLETION
            ========================================== -->

            <div class="form-group">

                <label
                    for="is_completed"
                    class="form-label"
                >
                    Completion
                </label>

                <select
                    id="is_completed"
                    name="is_completed"
                    class="form-select"
                >

                    <option
                        value="Incomplete"
                        <?= $is_completed === "Incomplete"
                            ? "selected"
                            : "" ?>
                    >
                        Incomplete
                    </option>

                    <option
                        value="Pending"
                        <?= $is_completed === "Pending"
                            ? "selected"
                            : "" ?>
                    >
                        Pending
                    </option>

                    <option
                        value="Completed"
                        <?= $is_completed === "Completed"
                            ? "selected"
                            : "" ?>
                    >
                        Completed
                    </option>

                </select>

            </div>


            <!-- =========================================
                 BUTTONS
            ========================================== -->

            <div class="button-row">

                <a
                    href="<?= site_url('tasks?board_id=' . (int) $board_id) ?>"
                    class="btn-app btn-cancel"
                >
                    <i class="bi bi-x-lg"></i>
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn-app btn-save"
                >
                    <i class="bi bi-check-lg"></i>
                    Update Task
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     FOOTER
====================================================== -->

<div class="footer">

    TODO APP

</div>


</body>

</html>
