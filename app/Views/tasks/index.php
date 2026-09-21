<?php helper('task'); ?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Task Board - TODO APP</title>

<!-- Bootstrap -->
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<!-- Bootstrap Icons -->
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<!-- Existing Style -->
<link
    rel="stylesheet"
    href="<?= base_url('assets/style.css') ?>"
>

<!-- Task board style (moved out of the page so the browser can cache it) -->
<link
    rel="stylesheet"
    href="<?= base_url('assets/css/tasks.css') ?>"
>

</head>

<body>
<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg bg-white border-bottom">

<div class="container-fluid px-3 px-md-4">

    <a
        class="navbar-brand"
        href="<?= site_url('tasks') ?>"
    >
        ☑ TODO APP
    </a>


    <div class="d-flex align-items-center">

        <form method="GET" class="nav-search-form me-3">
            <div class="nav-search-input-wrapper">
                <i class="bi bi-search nav-search-icon"></i>
                <input
                    type="text"
                    name="search"
                    class="form-control nav-search-input"
                    placeholder="Search tasks..."
                    value="<?= esc($search) ?>"
                    aria-label="Search tasks"
                >
                <input
                    type="hidden"
                    name="progress"
                    value="<?= esc($progress_filter) ?>"
                >
            </div>
        </form>

        <span class="welcome-text me-3">

            Welcome,

            <strong>
                <?= esc(session('user_name') ?? 'User') ?>
            </strong>

        </span>


        <span class="role-badge me-3">

            <?= esc($display_role) ?>

        </span>


        <a
            href="<?= site_url('logout') ?>"
            class="btn logout-btn"
        >
            Logout
        </a>

    </div>

</div>

</nav>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="container-fluid px-3 px-md-4 main-container">


<div class="board-toolbar">


    <div class="board-title-area">

        <div class="board-heading">
            Task Board
        </div>


        <div class="board-subtitle">
            Drag and drop tasks between columns to update status
        </div>

    </div>


    <div class="board-controls">


        <div class="task-filter">

            <form
                method="GET"
                id="progressFilterForm"
            >


                <input
                    type="hidden"
                    name="search"
                    value="<?= esc($search) ?>"
                >


                <select
                    name="progress"
                    class="form-select"
                    onchange="document.getElementById('progressFilterForm').submit();"
                >

                    <option
                        value="all"
                        <?= $progress_filter === "all" ? "selected" : "" ?>
                    >
                        All Tasks
                    </option>


                    <option
                        value="Todo"
                        <?= $progress_filter === "Todo" ? "selected" : "" ?>
                    >
                        TODO
                    </option>


                    <option
                        value="In Progress"
                        <?= $progress_filter === "In Progress" ? "selected" : "" ?>
                    >
                        IN PROGRESS
                    </option>


                    <option
                        value="Pending"
                        <?= $progress_filter === "Pending" ? "selected" : "" ?>
                    >
                        PENDING
                    </option>


                    <option
                        value="Review"
                        <?= $progress_filter === "Review" ? "selected" : "" ?>
                    >
                        REVIEW
                    </option>


                    <option
                        value="Done"
                        <?= $progress_filter === "Done" ? "selected" : "" ?>
                    >
                        DONE
                    </option>

                </select>

            </form>

        </div>


        <?php if ($is_admin || $is_user): ?>

            <button
                type="button"
                class="btn btn-primary add-board-btn"
                id="addBoardBtn"
            >
                + Add Board
            </button>

        <?php endif; ?>


    </div>

</div>


<!-- =========================================================
     BOARD
========================================================= -->

<div class="board-wrapper">

<div class="task-board">


<?php foreach ($columns as $column): ?>
<!-- COLUMN: rendered once per progress value -->

<div
    class="task-column"
    data-progress="<?= esc($column['progress']) ?>"
>

    <div class="column-header">

        <span class="column-title">
            <?= esc($column['label']) ?>
        </span>


        <span class="column-count">
            <?= count($column['tasks']) ?>
        </span>


        <?php if ($is_admin): ?>

            <a
                href="<?= site_url('tasks/add') ?>?progress=<?= rawurlencode($column['progress']) ?>"
                class="column-add-btn"
                title="Add task to <?= esc($column['label']) ?>"
                aria-label="Add task to <?= esc($column['label']) ?>"
            >
                <i class="bi bi-plus"></i>
            </a>

        <?php endif; ?>

    </div>


    <div class="task-list">

        <?php foreach ($column['tasks'] as $task): ?>

            <div
                class="task-card <?= esc(getPriorityClass($task["priority"])) ?>"
                draggable="true"
                data-task-id="<?= (int)$task["id"] ?>"
                data-task-title="<?= esc($task["task"]) ?>"
                data-task-description="<?= esc($task["description"] ?? "") ?>"
                data-task-board-name="<?= esc($selected_board["name"] ?? "") ?>"
                data-task-priority="<?= esc($task["priority"]) ?>"
                data-task-progress="<?= esc($task["progress"]) ?>"
                data-task-completed="<?= esc(normalizeCompletionValue($task["is_completed"] ?? "")) ?>"
                data-task-status="<?= ((int)$task["status"] === 1) ? "Active" : "Inactive" ?>"
                data-task-added="<?= esc(formatTaskDate($task["addedDate"])) ?>"
                data-task-edited="<?= esc(formatTaskDate($task["editedDate"])) ?>"
            >


                <div class="task-title">

                    <?= esc($task["task"]) ?>

                </div>

                <?php if ($selected_board): ?>
                    <div class="task-board-name-badge">
                        <i class="bi bi-kanban"></i>
                        <?= esc($selected_board["name"]) ?>
                    </div>
                <?php endif; ?>


                <?php if (!empty($task["description"])): ?>

                    <div class="task-description">

                        <?= nl2br(esc($task["description"])) ?>

                    </div>

                <?php endif; ?>


                <div class="task-meta">

                    <span class="priority-badge">
                        <?= esc($task["priority"]) ?>
                    </span>


                    <span
                        class="progress-badge <?= esc(getProgressClass($task["progress"])) ?>"
                    >
                        <?= esc($task["progress"]) ?>
                    </span>


                    <?php
                        $completion_value = normalizeCompletionValue($task["is_completed"] ?? "");
                    ?>

                    <span class="completion-badge <?= esc(getCompletionClass($completion_value)) ?>">

                        <?php if ($completion_value === "Completed"): ?>
                            <i class="bi bi-check-circle-fill"></i> Completed
                        <?php elseif ($completion_value === "Pending"): ?>
                            <i class="bi bi-hourglass-split"></i> Pending
                        <?php else: ?>
                            <i class="bi bi-circle"></i> Incomplete
                        <?php endif; ?>

                    </span>

                </div>


                <?php if (!empty($task["editedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= esc(formatTaskDate($task["editedDate"])) ?>

                    </div>

                <?php elseif (!empty($task["addedDate"])): ?>

                    <div class="task-date">

                        <i class="bi bi-clock"></i>

                        <?= esc(formatTaskDate($task["addedDate"])) ?>

                    </div>

                <?php endif; ?>


                <?php if ($is_admin): ?>

                    <div class="task-menu">

                        <button
                            type="button"
                            class="task-menu-button"
                            aria-label="Task menu"
                        >
                            ⋮
                        </button>

                        <div class="task-menu-content">

                            <a
                                href="#"
                                class="task-view-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                View
                            </a>

                            <a
                                href="<?= site_url('tasks/delete/' . (int)$task['id']) ?>"
                                class="delete-link"
                                data-task-id="<?= (int)$task["id"] ?>"
                            >
                                Delete
                            </a>

                        </div>

                    </div>

                <?php endif; ?>


            </div>

        <?php endforeach; ?>

    </div>

</div>
<?php endforeach; ?>

<!-- =========================================================
     BOARD COLUMN
========================================================= -->

<div
    class="task-column board-task-column"
    data-progress="Board"
>

    <div class="column-header">

        <span class="column-title">BOARD</span>

        <span class="column-count"><?= count($boards) ?></span>

        <?php if ($is_admin || $is_user): ?>
            <button
                type="button"
                class="column-add-btn"
                id="boardColumnAddBtn"
                title="Add board"
                aria-label="Add board"
            >
                <i class="bi bi-plus"></i>
            </button>
        <?php endif; ?>

    </div>

    <div class="board-card-list">

        <?php foreach ($boards as $board): ?>

            <div
                class="board-card <?= ((int)$board["id"] === $selected_board_id) ? "active" : "" ?>"
                data-board-id="<?= (int)$board["id"] ?>"
                role="button"
                tabindex="0"
            >

                <div class="board-card-name">
                    <?= esc($board["name"]) ?>
                </div>

                <?php if (!empty($board["description"])): ?>
                    <div class="board-card-description">
                        <?= esc($board["description"]) ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_admin || $is_user): ?>
                    <div class="board-card-menu">
                        <button
                            type="button"
                            class="board-card-menu-button"
                            aria-label="Board menu"
                            title="Board menu"
                        >
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                        <div class="board-card-menu-content">
                            <button
                                type="button"
                                class="board-card-menu-delete"
                                data-board-id="<?= (int)$board["id"] ?>"
                            >
                                <i class="bi bi-trash me-1"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

        <?php if (empty($boards)): ?>
            <div class="board-empty">
                No boards yet. Use <strong>+ Add Board</strong> to create your first board.
            </div>
        <?php endif; ?>

    </div>

</div>

</div>

</div>


<!-- =========================================================
     TASK DETAILS MODAL (TRELLO-STYLE CARD VIEW)
========================================================= -->

<div
    class="modal fade task-details-modal"
    id="taskDetailsModal"
    tabindex="-1"
    aria-labelledby="taskDetailsModalLabel"
    aria-hidden="true"
>

<div class="modal-dialog modal-dialog-centered modal-lg trello-card-dialog">

    <div class="modal-content trello-card-content">


        <!-- MODAL HEADER -->

        <div class="modal-header trello-card-header">

            <div class="trello-card-header-text">

                <span class="trello-card-eyebrow">
                    Card Details
                </span>

                <h5
                    class="modal-title trello-card-modal-title"
                    id="taskDetailsModalLabel"
                >
                    View Task
                </h5>

            </div>


            <div class="task-details-header-actions">

                <button
                    type="button"
                    class="btn btn-primary task-details-edit-btn"
                    id="taskDetailsEditBtn"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </button>

                <button
                    type="button"
                    class="btn btn-outline-secondary task-details-back-btn"
                    data-bs-dismiss="modal"
                    aria-label="Back"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>

            </div>

        </div>


        <!-- MODAL BODY -->

        <div class="modal-body trello-card-body">

            <div class="trello-card-layout">


                <!-- =========================================
                     MAIN COLUMN
                ========================================= -->

                <div class="trello-card-main">


                    <!-- TASK NAME -->

                    <div class="trello-card-title-row">

                        <i class="bi bi-card-heading trello-card-title-icon"></i>

                        <div
                            class="task-detail-title"
                            id="modalTaskTitle"
                        >
                            —
                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="trello-card-section">

                        <div class="trello-card-section-heading-row">

                            <div class="trello-card-section-heading">
                                <i class="bi bi-text-paragraph"></i>
                                Description
                            </div>

                        </div>

                        <div
                            class="task-detail-description empty"
                            id="modalTaskDescription"
                        >
                            No description provided.
                        </div>

                        <textarea
                            class="form-control d-none"
                            id="modalTaskDescriptionInput"
                            rows="4"
                            placeholder="Enter task description"
                        ></textarea>

                    </div>


                    <!-- =================================================
                         COMMENTS / ACTIVITY
                    ================================================== -->

                    <div class="trello-card-section">

                        <div class="trello-card-section-heading">
                            <i class="bi bi-chat-left-text"></i>
                            Activity
                        </div>


                        <div class="trello-comment-composer">

                            <div class="trello-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="trello-comment-composer-input">

                                <textarea
                                    id="taskCommentInput"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Write a comment..."
                                ></textarea>


                                <div class="d-flex justify-content-end mt-2">

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        id="addCommentButton"
                                    >

                                        <i class="bi bi-send"></i>

                                        Save

                                    </button>

                                </div>

                            </div>

                        </div>


                        <div
                            id="taskCommentsList"
                            class="task-comments-list mt-3"
                        >

                            <div class="text-muted small">

                                Loading comments...

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         FILES / IMAGES
                    ================================================== -->

                    <div class="trello-card-section">

                        <div class="trello-card-section-heading-row">

                            <div class="trello-card-section-heading">
                                <i class="bi bi-paperclip"></i>
                                Attachments
                            </div>


                            <label
                                for="taskAttachmentInput"
                                class="btn btn-outline-secondary btn-sm"
                                style="cursor:pointer;"
                            >

                                <i class="bi bi-upload"></i>

                                Add

                            </label>


                            <input
                                type="file"
                                id="taskAttachmentInput"
                                hidden
                            >

                        </div>


                        <div
                            id="taskAttachmentsList"
                            class="task-attachments-list mt-2"
                        >

                            <div class="text-muted small">

                                Loading files...

                            </div>

                        </div>

                    </div>


                </div>


                <!-- =========================================
                     SIDEBAR COLUMN
                ========================================= -->

                <div class="trello-card-sidebar">

                    <div class="trello-sidebar-label">
                        About this card
                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Board
                        </span>

                        <span
                            class="task-detail-value trello-chip"
                            id="modalTaskBoardName"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Priority
                        </span>

                        <div class="task-detail-field">

                            <span
                                class="task-detail-value trello-chip"
                                id="modalTaskPriority"
                            >
                                —
                            </span>

                            <select
                                class="task-detail-select d-none"
                                id="modalTaskPrioritySelect"
                                data-field="priority"
                            >
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>

                        </div>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Progress
                        </span>

                        <div class="task-detail-field">

                            <div class="task-detail-field">

                                <span
                                    class="task-detail-value trello-chip"
                                    id="modalTaskProgress"
                                >
                                    —
                                </span>

                                <span
                                    class="task-progress-board-name d-block mt-2 small text-muted"
                                    id="modalTaskProgressBoardName"
                                >
                                    <i class="bi bi-kanban me-1"></i>
                                    Board: —
                                </span>

                            </div>

                            <select
                                class="task-detail-select d-none"
                                id="modalTaskProgressSelect"
                                data-field="progress"
                            >
                                <option value="Todo">Todo</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending">Pending</option>
                                <option value="Review">Review</option>
                                <option value="Done">Done</option>
                            </select>

                        </div>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            Completion
                        </span>

                        <div class="task-detail-field">

                            <span
                                class="task-detail-value trello-chip"
                                id="modalTaskCompleted"
                            >
                                —
                            </span>

                            <select
                                class="task-detail-select d-none"
                                id="modalTaskCompletedSelect"
                                data-field="is_completed"
                            >
                                <option value="Incomplete">Incomplete</option>
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>

                        </div>

                    </div>


                    <div class="trello-sidebar-divider"></div>


                    <div class="trello-sidebar-label">
                        Dates
                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            <i class="bi bi-plus-circle"></i>
                            Added
                        </span>

                        <span
                            class="task-detail-value trello-chip trello-chip-date"
                            id="modalTaskAdded"
                        >
                            —
                        </span>

                    </div>


                    <div class="trello-sidebar-item">

                        <span class="trello-sidebar-item-label">
                            <i class="bi bi-pencil"></i>
                            Last edited
                        </span>

                        <span
                            class="task-detail-value trello-chip trello-chip-date"
                            id="modalTaskEdited"
                        >
                            —
                        </span>

                    </div>


                </div>


            </div>

        </div>


        <!-- MODAL FOOTER -->

        <div class="modal-footer task-details-edit-footer">

            <div class="task-details-footer-actions">

                <button
                    type="button"
                    class="btn btn-secondary task-details-cancel-btn d-none"
                    id="taskDetailsCancelBtn"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-primary task-details-save-btn d-none"
                    id="taskDetailsSaveBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Save
                </button>

            </div>

        </div>


    </div>

</div>

</div>


<!-- =========================================================
     VIEW TASK MODAL (ADMIN -> VIEW)
     Separate from the Trello-style Card Details modal.
========================================================= -->

<div
    class="modal fade view-task-modal"
    id="viewTaskModal"
    tabindex="-1"
    aria-labelledby="viewTaskModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-xl view-task-dialog">
        <div class="modal-content view-task-content">

            <div class="view-task-header">
                <div>
                    <h2 id="viewTaskModalLabel" class="view-task-heading">View Task</h2>
                    <div class="view-task-subtitle">Task details</div>
                </div>

                <button
                    type="button"
                    class="btn btn-light view-task-back-btn"
                    data-bs-dismiss="modal"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>
            </div>

            <div class="view-task-body">

                <h1 id="viewTaskTitle" class="view-task-title">—</h1>

                <div class="view-task-description-section">
                    <h3 class="view-task-section-heading">Description</h3>
                    <div id="viewTaskDescription" class="view-task-description">
                        No description provided.
                    </div>
                </div>

                <div class="view-task-fields">

                    <div class="view-task-field-row">
                        <div class="view-task-field-label">Priority</div>
                        <div id="viewTaskPriority" class="view-task-field-value view-task-chip priority">—</div>
                    </div>

                    <div class="view-task-field-row">
                        <div class="view-task-field-label">Progress</div>
                        <div id="viewTaskProgress" class="view-task-field-value view-task-chip progress">—</div>
                    </div>

                    <div class="view-task-field-row">
                        <div class="view-task-field-label">Status</div>
                        <div id="viewTaskStatus" class="view-task-field-value view-task-chip status">—</div>
                    </div>

                    <div class="view-task-field-row">
                        <div class="view-task-field-label">Complete</div>
                        <div id="viewTaskCompleted" class="view-task-field-value view-task-chip completed">—</div>
                    </div>

                    <div class="view-task-field-row">
                        <div class="view-task-field-label">Added Date &amp; Time</div>
                        <div id="viewTaskAdded" class="view-task-field-value view-task-date">—</div>
                    </div>

                    <div class="view-task-field-row">
                        <div class="view-task-field-label">Edited Date &amp; Time</div>
                        <div id="viewTaskEdited" class="view-task-field-value view-task-date">—</div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>


<!-- =========================================================
     ADD BOARD MODAL
========================================================= -->

<div
    class="modal fade"
    id="addBoardModal"
    tabindex="-1"
    aria-labelledby="addBoardModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBoardModalLabel">Add Board</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="<?= site_url('boards/create') ?>" id="addBoardForm">
                <div class="modal-body">
                    <?php if ($board_error !== ""): ?>
                        <div class="alert alert-danger">
                            <?= esc($board_error) ?>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="action" value="create_board">

                    <div class="mb-3">
                        <label class="form-label">Board Name</label>
                        <input
                            type="text"
                            name="board_name"
                            class="form-control"
                            maxlength="255"
                            placeholder="Enter board name"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea
                            name="board_description"
                            class="form-control"
                            rows="4"
                            placeholder="Enter board description"
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Add Board
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =========================================================
     ADD TASK MODAL
========================================================= -->

<div
    class="modal fade"
    id="addTaskModal"
    tabindex="-1"
    aria-labelledby="addTaskModalLabel"
    aria-hidden="true"
>

<div class="modal-dialog modal-dialog-centered">

    <div class="modal-content">

        <div class="modal-header">

            <h5
                class="modal-title"
                id="addTaskModalLabel"
            >
                Add Task
            </h5>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
            ></button>

        </div>

        <div class="modal-body">

            <div
                id="addTaskError"
                class="alert alert-danger d-none"
            ></div>

            <form id="addTaskForm">

                <input
                    type="hidden"
                    name="board_id"
                    id="addTaskBoardId"
                    value="<?= (int)$selected_board_id ?>"
                >

                <div class="mb-3">

                    <label class="form-label">
                        Task
                    </label>

                    <input
                        type="text"
                        name="task"
                        id="addTaskTitle"
                        class="form-control"
                        placeholder="Enter task"
                        maxlength="255"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Task Description
                    </label>

                    <textarea
                        name="description"
                        id="addTaskDescription"
                        class="form-control"
                        rows="4"
                        placeholder="Enter task description"
                    ></textarea>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Priority
                    </label>

                    <select
                        name="priority"
                        id="addTaskPriority"
                        class="form-select"
                    >
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Task Progress
                    </label>

                    <select
                        name="progress"
                        id="addTaskProgress"
                        class="form-select"
                    >
                        <option value="Todo">Todo</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                        <option value="Review">Review</option>
                        <option value="Done">Done</option>
                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        id="addTaskStatus"
                        class="form-select"
                    >
                        <option value="1" selected>Active</option>
                        <option value="2">Inactive</option>
                    </select>

                </div>

            </form>

        </div>

        <div class="modal-footer">

            <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn btn-primary"
                id="addTaskSubmitBtn"
            >
                Add Task
            </button>

        </div>

    </div>

</div>

</div>




</div>

</div>


<!-- =========================================================
     DRAG HINT
========================================================= -->

<div
    id="dragHint"
    class="drag-hint"
>
    Move task to another column
</div>


<!-- =========================================================
     DRAG SUCCESS
========================================================= -->

<div
    id="dragSuccess"
    class="drag-success"
>
    Task status updated
</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =========================================================
     SERVER VALUES USED BY assets/js/tasks.js
========================================================= -->

<script>
window.TODO_APP = {
    selectedBoardId: <?= (int) $selected_board_id ?>,
    urls: {
        getComments:      "<?= site_url('tasks/comments') ?>/",
        addComment:       "<?= site_url('tasks/comments/add') ?>",
        getAttachments:   "<?= site_url('tasks/attachments') ?>/",
        uploadAttachment: "<?= site_url('tasks/attachments/upload') ?>",
        deleteAttachment: "<?= site_url('tasks/attachments/delete') ?>",
        updateField:      "<?= site_url('tasks/update-field') ?>",
        updateProgress:   "<?= site_url('tasks/update-progress') ?>",
        addTask:          "<?= site_url('tasks/create') ?>",
        createBoard:      "<?= site_url('boards/create') ?>",
        deleteBoard:      "<?= site_url('boards/delete') ?>"
    }
};
</script>

<script src="<?= base_url('assets/js/tasks.js') ?>"></script>

</body>
</html>
