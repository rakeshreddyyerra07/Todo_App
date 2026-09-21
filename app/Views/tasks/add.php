<?php helper('task'); ?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Task</title>


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


                <div class="card-header bg-primary text-white">

                    <h4 class="mb-0">
                        Add Card / Task
                    </h4>

                </div>


                <div class="card-body">


                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">

                            <?= esc($error) ?>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $board_id > 0 &&
                        !empty($board_name)
                    ): ?>

                        <div class="alert alert-info">

                            <strong>Board:</strong>

                            <?= esc($board_name) ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST" action="<?= site_url('tasks/create') ?>">
            <?= csrf_field() ?>


                        <input
                            type="hidden"
                            name="board_id"
                            value="<?= (int)$board_id ?>"
                        >


                        <div class="mb-3">

                            <label class="form-label">
                                Task
                            </label>

                            <input
                                type="text"
                                name="task"
                                class="form-control"
                                placeholder="Enter task"
                                value="<?= esc($task) ?>"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Task Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                placeholder="Enter task description"
                            ><?= esc($description) ?></textarea>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Priority
                            </label>

                            <select
                                name="priority"
                                class="form-select"
                            >

                                <?php foreach (
                                    ["Low", "Medium", "High"]
                                    as $item
                                ): ?>

                                    <option
                                        value="<?= esc($item) ?>"
                                        <?= $priority === $item
                                            ? "selected"
                                            : "" ?>
                                    >
                                        <?= esc($item) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Task Progress
                            </label>

                            <select
                                name="progress"
                                class="form-select"
                            >

                                <?php foreach (
                                    [
                                        "Todo",
                                        "In Progress",
                                        "Pending",
                                        "Review",
                                        "Done"
                                    ]
                                    as $item
                                ): ?>

                                    <option
                                        value="<?= esc($item) ?>"
                                        <?= $progress === $item
                                            ? "selected"
                                            : "" ?>
                                    >
                                        <?= esc($item) ?>
                                    </option>

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
                                    <?= $status == 1
                                        ? "selected"
                                        : "" ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="2"
                                    <?= $status == 2
                                        ? "selected"
                                        : "" ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Add Card / Task
                            </button>


                            <a
                                href="<?= $board_id > 0 ? site_url('tasks?board_id=' . (int) $board_id) : site_url('tasks') ?>"
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

