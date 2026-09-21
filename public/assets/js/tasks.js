"use strict";

/* =========================================================
   TODO APP - TASK BOARD
   Server URLs and the selected board are provided by the
   view (window.TODO_APP) so this file has no hard-coded
   paths and can be cached by the browser.
========================================================= */

const APP = window.TODO_APP;

/* =========================================================
   GLOBAL VARIABLES
========================================================= */

let draggedCard = null;

let touchDraggedCard = null;

let touchStartX = 0;

let touchStartY = 0;

let touchCurrentX = 0;

let touchCurrentY = 0;

let touchLongPressTimer = null;

let isTouchDragging = false;

let originalParent = null;

let originalNextSibling = null;

let suppressCardClick = false;

let currentTaskId = 0;

const TOUCH_LONG_PRESS = 300;

const TOUCH_MOVE_THRESHOLD = 10;

/* =========================================================
   OUTER BOARD TOUCH SCROLL STATE
   Allows direct finger swiping on the six-column board
   to scroll horizontally and vertically.
========================================================= */

let boardTouchScrolling = false;

let boardTouchStartX = 0;

let boardTouchStartY = 0;

let boardTouchLastX = 0;

let boardTouchLastY = 0;

let boardTouchMoved = false;

let boardTouchStartedInList = false;


/* =========================================================
   SHARED TOUCH SCROLL HELPER
   Horizontal movement always scrolls the outer six-column
   board. Vertical movement scrolls whichever column's task
   list the touch is over (so cards keep appearing/disappearing
   correctly), falling back to the outer board if the touch
   is not over any task list.
========================================================= */

function performBoardTouchScroll(originElement, deltaX, deltaY)
{

    const board =
        document.querySelector(
            ".board-wrapper"
        );

    if (!board) {

        return;

    }


    if (
        board.scrollWidth >
        board.clientWidth
    ) {

        board.scrollLeft -=
            deltaX;

    }


    const list =
        originElement ?
            originElement.closest(
                ".task-list"
            ) :
            null;


    if (
        list &&
        list.scrollHeight >
        list.clientHeight
    ) {

        list.scrollTop -=
            deltaY;

    }
    else if (
        board.scrollHeight >
        board.clientHeight
    ) {

        board.scrollTop -=
            deltaY;

    }

}


/* =========================================================
   TASK DETAILS MODAL
========================================================= */

const taskDetailsModalElement =
    document.getElementById(
        "taskDetailsModal"
    );


let taskDetailsModal = null;


if (taskDetailsModalElement) {

    taskDetailsModal =
        new bootstrap.Modal(
            taskDetailsModalElement
        );

}


const viewTaskModalElement =
    document.getElementById(
        "viewTaskModal"
    );


let viewTaskModal = null;


if (viewTaskModalElement) {

    viewTaskModal =
        new bootstrap.Modal(
            viewTaskModalElement
        );

}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHtml(value)
{

    const div =
        document.createElement("div");

    div.textContent =
        value ?? "";

    return div.innerHTML;

}


/* =========================================================
   ATTRIBUTE ESCAPE
========================================================= */

function escapeAttribute(value)
{

    return escapeHtml(value)
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


/* =========================================================
   SET MODAL VALUE
========================================================= */

function setModalValue(
    elementId,
    value
)
{

    const element =
        document.getElementById(
            elementId
        );


    if (!element) {

        return;

    }


    element.textContent =
        value || "—";

}


/* =========================================================
   LOAD COMMENTS
========================================================= */

function loadTaskComments(taskId)
{

    const commentsList =
        document.getElementById(
            "taskCommentsList"
        );


    if (!commentsList) {

        return;

    }


    commentsList.innerHTML = `
        <div class="text-muted small">
            Loading comments...
        </div>
    `;


    fetch(
        APP.urls.getComments +
        encodeURIComponent(taskId),
        {
            method: "GET",
            cache: "no-store"
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            commentsList.innerHTML = `
                <div class="text-danger small">
                    ${escapeHtml(
                        data.message ||
                        "Unable to load comments."
                    )}
                </div>
            `;

            return;

        }


        if (
            !data.comments ||
            data.comments.length === 0
        ) {

            commentsList.innerHTML = `
                <div class="text-muted small">
                    No comments yet.
                </div>
            `;

            return;

        }


        commentsList.innerHTML =
            data.comments.map(
                function(comment) {

                    return `
                        <div class="task-comment-item">

                            <div class="task-comment-header">

                                <span class="task-comment-user">

                                    <i class="bi bi-person-circle"></i>

                                    ${escapeHtml(
                                        comment.user_name ||
                                        "User"
                                    )}

                                </span>


                                <span class="task-comment-date">

                                    ${escapeHtml(
                                        comment.created_at ||
                                        ""
                                    )}

                                </span>

                            </div>


                            <div class="task-comment-text">

                                ${escapeHtml(
                                    comment.comment ||
                                    ""
                                )}

                            </div>

                        </div>
                    `;

                }
            ).join("");

    })
    .catch(function(error) {

        console.error(
            "Comments error:",
            error
        );


        commentsList.innerHTML = `
            <div class="text-danger small">
                Unable to load comments.
            </div>
        `;

    });

}


/* =========================================================
   ADD COMMENT
========================================================= */

function addTaskComment()
{

    const input =
        document.getElementById(
            "taskCommentInput"
        );


    const button =
        document.getElementById(
            "addCommentButton"
        );


    if (!input) {

        return;

    }


    const comment =
        input.value.trim();


    if (currentTaskId <= 0) {

        alert("Invalid task.");

        return;

    }


    if (comment === "") {

        alert("Please enter a comment.");

        input.focus();

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "task_id",
        currentTaskId
    );


    formData.append(
        "comment",
        comment
    );


    if (button) {

        button.disabled = true;

        button.innerHTML = `
            <span
                class="spinner-border spinner-border-sm"
            ></span>

            Adding...
        `;

    }


    fetch(
        APP.urls.addComment,
        {
            method: "POST",
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            alert(
                data.message ||
                "Unable to add comment."
            );

            return;

        }


        input.value = "";

        loadTaskComments(
            currentTaskId
        );

    })
    .catch(function(error) {

        console.error(
            "Add comment error:",
            error
        );


        alert(
            "Unable to add comment."
        );

    })
    .finally(function() {

        if (button) {

            button.disabled = false;

            button.innerHTML = `
                <i class="bi bi-send"></i>
                Add Comment
            `;

        }

    });

}


/* =========================================================
   LOAD ATTACHMENTS
========================================================= */

function loadTaskAttachments(taskId)
{

    const attachmentsList =
        document.getElementById(
            "taskAttachmentsList"
        );


    if (!attachmentsList) {

        return;

    }


    attachmentsList.innerHTML = `
        <div class="text-muted small">
            Loading files...
        </div>
    `;


    fetch(
        APP.urls.getAttachments +
        encodeURIComponent(taskId),
        {
            method: "GET",
            cache: "no-store"
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            attachmentsList.innerHTML = `
                <div class="text-danger small">
                    ${escapeHtml(
                        data.message ||
                        "Unable to load files."
                    )}
                </div>
            `;

            return;

        }


        if (
            !data.attachments ||
            data.attachments.length === 0
        ) {

            attachmentsList.innerHTML = `
                <div class="text-muted small">
                    No files uploaded yet.
                </div>
            `;

            return;

        }


        attachmentsList.innerHTML =
            data.attachments.map(
                function(file) {

                    const fileUrl = file.file_url;


                    let preview = "";


                    if (file.is_image) {

                        preview = `
                            <a
                                href="${escapeAttribute(fileUrl)}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >

                                <img
                                    src="${escapeAttribute(fileUrl)}"
                                    class="task-attachment-preview"
                                    alt="${escapeAttribute(
                                        file.original_name
                                    )}"
                                >

                            </a>
                        `;

                    }
                    else {

                        preview = `
                            <div class="attachment-file-icon">

                                <i class="bi bi-file-earmark-text"></i>

                            </div>
                        `;

                    }


                    return `
                        <div
                            class="task-attachment-item"
                        >

                            ${preview}


                            <div
                                class="task-attachment-info"
                            >

                                <div>

                                    <div
                                        class="task-attachment-name"
                                    >
                                        ${escapeHtml(
                                            file.original_name
                                        )}
                                    </div>


                                    <div
                                        class="task-attachment-date"
                                    >
                                        ${escapeHtml(
                                            file.uploaded_at ||
                                            ""
                                        )}
                                    </div>

                                </div>


                                <div
                                    class="task-attachment-actions"
                                >

                                    <a
                                        href="${escapeAttribute(fileUrl)}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Open"
                                    >

                                        <i
                                            class="bi bi-box-arrow-up-right"
                                        ></i>

                                    </a>


                                    <button
                                        type="button"
                                        title="Delete"
                                        onclick="deleteTaskAttachment(${Number(file.id)})"
                                    >

                                        <i
                                            class="bi bi-trash text-danger"
                                        ></i>

                                    </button>

                                </div>

                            </div>

                        </div>
                    `;

                }
            ).join("");

    })
    .catch(function(error) {

        console.error(
            "Attachments error:",
            error
        );


        attachmentsList.innerHTML = `
            <div class="text-danger small">
                Unable to load files.
            </div>
        `;

    });

}


/* =========================================================
   UPLOAD ATTACHMENT
========================================================= */

function uploadTaskAttachment(file)
{

    if (!file) {

        return;

    }


    if (currentTaskId <= 0) {

        alert("Invalid task.");

        return;

    }


    const maxSize =
        10 * 1024 * 1024;


    if (file.size > maxSize) {

        alert(
            "File size cannot exceed 10 MB."
        );

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "task_id",
        currentTaskId
    );


    formData.append(
        "attachment",
        file
    );


    const attachmentsList =
        document.getElementById(
            "taskAttachmentsList"
        );


    if (attachmentsList) {

        attachmentsList.innerHTML = `
            <div class="text-primary small">

                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>

                Uploading file...

            </div>
        `;

    }


    fetch(
        APP.urls.uploadAttachment,
        {
            method: "POST",
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            alert(
                data.message ||
                "Upload failed."
            );


            loadTaskAttachments(
                currentTaskId
            );

            return;

        }


        loadTaskAttachments(
            currentTaskId
        );

    })
    .catch(function(error) {

        console.error(
            "Upload error:",
            error
        );


        alert(
            "Unable to upload file."
        );


        loadTaskAttachments(
            currentTaskId
        );

    });

}


/* =========================================================
   DELETE ATTACHMENT
========================================================= */

function deleteTaskAttachment(
    attachmentId
)
{

    if (!attachmentId) {

        return;

    }


    if (
        !confirm(
            "Are you sure you want to delete this file?"
        )
    ) {

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "attachment_id",
        attachmentId
    );


    fetch(
        APP.urls.deleteAttachment,
        {
            method: "POST",
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            alert(
                data.message ||
                "Unable to delete file."
            );

            return;

        }


        loadTaskAttachments(
            currentTaskId
        );

    })
    .catch(function(error) {

        console.error(
            "Delete attachment error:",
            error
        );


        alert(
            "Unable to delete file."
        );

    });

}


/* =========================================================
   OPEN TASK DETAILS
========================================================= */

function openTaskDetails(card)
{

    if (!card || !taskDetailsModal) {

        return;

    }


    const taskId =
        card.dataset.taskId || "—";


    const taskTitle =
        card.dataset.taskTitle || "—";


    const taskDescription =
        card.dataset.taskDescription || "";


    const taskPriority =
        card.dataset.taskPriority || "—";


    const taskProgress =
        card.dataset.taskProgress || "—";


    const taskBoardName =
        card.dataset.taskBoardName || "—";


    const taskCompleted =
        card.dataset.taskCompleted || "—";

    const taskAdded =
        card.dataset.taskAdded || "—";


    const taskEdited =
        card.dataset.taskEdited || "—";


    /* =====================================================
       SAVE CURRENT TASK ID
    ===================================================== */

    currentTaskId =
        Number(taskId) || 0;


    /* =====================================================
       BASIC VALUES
    ===================================================== */

    setModalValue(
        "modalTaskTitle",
        taskTitle
    );


    setModalValue(
        "modalTaskPriority",
        taskPriority
    );


    setModalValue(
        "modalTaskProgress",
        taskProgress
    );


    setModalValue(
        "modalTaskBoardName",
        taskBoardName
    );


    const progressBoardNameElement =
        document.getElementById(
            "modalTaskProgressBoardName"
        );

    if (progressBoardNameElement) {
        progressBoardNameElement.innerHTML =
            '<i class="bi bi-kanban me-1"></i>Board: ' +
            escapeHtml(taskBoardName) +
            '';
    }


    setModalValue(
        "modalTaskCompleted",
        taskCompleted
    );

    setModalValue(
        "modalTaskAdded",
        taskAdded
    );


    setModalValue(
        "modalTaskEdited",
        taskEdited
    );


    /* =====================================================
       DESCRIPTION
    ===================================================== */

    const descriptionElement =
        document.getElementById(
            "modalTaskDescription"
        );


    if (descriptionElement) {

        if (
            taskDescription.trim() !== ""
        ) {

            descriptionElement.textContent =
                taskDescription;

            descriptionElement.classList.remove(
                "empty"
            );

        }
        else {

            descriptionElement.textContent =
                "No description provided.";

            descriptionElement.classList.add(
                "empty"
            );

        }

    }


    /* =====================================================
       COMPLETE / INCOMPLETE COLOR
    ===================================================== */

    const completedElement =
        document.getElementById(
            "modalTaskCompleted"
        );


    if (completedElement) {

        completedElement.classList.remove(
            "complete",
            "pending",
            "incomplete"
        );


        if (
            taskCompleted === "Completed"
        ) {

            completedElement.classList.add(
                "complete"
            );

        }
        else if (
            taskCompleted === "Pending"
        ) {

            completedElement.classList.add(
                "pending"
            );

        }
        else {

            completedElement.classList.add(
                "incomplete"
            );

        }

    }


    /* =====================================================
       PRE-FILL EDIT CONTROLS + RESET TO VIEW MODE
    ===================================================== */

    const prioritySelect =
        document.getElementById(
            "modalTaskPrioritySelect"
        );

    if (prioritySelect) {
        prioritySelect.value = taskPriority;
    }


    const progressSelect =
        document.getElementById(
            "modalTaskProgressSelect"
        );

    if (progressSelect) {
        progressSelect.value = taskProgress;
    }


    const completedSelect =
        document.getElementById(
            "modalTaskCompletedSelect"
        );

    if (completedSelect) {
        completedSelect.value = taskCompleted;
    }


    const descriptionInput =
        document.getElementById(
            "modalTaskDescriptionInput"
        );

    if (descriptionInput) {
        descriptionInput.value = taskDescription;
    }


    taskDetailsEditSnapshot = null;

    resetTaskFieldEditModes();


    /* =====================================================
       CLEAR COMMENT INPUT
    ===================================================== */

    const commentInput =
        document.getElementById(
            "taskCommentInput"
        );


    if (commentInput) {

        commentInput.value = "";

    }


    /* =====================================================
       LOAD COMMENTS
    ===================================================== */

    loadTaskComments(
        currentTaskId
    );


    /* =====================================================
       LOAD ATTACHMENTS
    ===================================================== */

    loadTaskAttachments(
        currentTaskId
    );


    /* =====================================================
       SHOW TRELLO-STYLE CARD DETAILS
       (separate from the Admin -> View screen)
    ===================================================== */

    taskDetailsModalElement.classList.remove("view-task-simple");
    taskDetailsModal.show();

}


/* =========================================================
   RESET INLINE EDIT MODES (VIEW CARD)
========================================================= */

function resetTaskFieldEditModes()
{

    const editBtn = document.getElementById("taskDetailsEditBtn");
    const saveBtn = document.getElementById("taskDetailsSaveBtn");
    const cancelBtn = document.getElementById("taskDetailsCancelBtn");

    document.querySelectorAll(".task-detail-select").forEach(function(select) {
        select.classList.add("d-none");
    });

    document.querySelectorAll(".task-detail-field .task-detail-value").forEach(function(chip) {
        chip.classList.remove("d-none");
    });

    const descriptionView = document.getElementById("modalTaskDescription");
    const descriptionInput = document.getElementById("modalTaskDescriptionInput");

    if (descriptionView) descriptionView.classList.remove("d-none");
    if (descriptionInput) descriptionInput.classList.add("d-none");

    if (editBtn) editBtn.classList.remove("d-none");
    if (saveBtn) saveBtn.classList.add("d-none");
    if (cancelBtn) cancelBtn.classList.add("d-none");

}


let taskDetailsEditSnapshot = null;


function enterTaskDetailsEditMode()
{
    if (currentTaskId <= 0) return;

    const prioritySelect = document.getElementById("modalTaskPrioritySelect");
    const progressSelect = document.getElementById("modalTaskProgressSelect");
    const completedSelect = document.getElementById("modalTaskCompletedSelect");
    const descriptionInput = document.getElementById("modalTaskDescriptionInput");

    taskDetailsEditSnapshot = {
        priority: prioritySelect ? prioritySelect.value : "",
        progress: progressSelect ? progressSelect.value : "",
        is_completed: completedSelect ? completedSelect.value : "Incomplete",
        description: descriptionInput ? descriptionInput.value : ""
    };

    document.querySelectorAll(".task-detail-select").forEach(function(select) {
        select.classList.remove("d-none");
    });

    document.querySelectorAll(".task-detail-field .task-detail-value").forEach(function(chip) {
        chip.classList.add("d-none");
    });

    const descriptionView = document.getElementById("modalTaskDescription");
    if (descriptionView) descriptionView.classList.add("d-none");

    if (descriptionInput) {
        descriptionInput.classList.remove("d-none");
        descriptionInput.focus();
    }

    const editBtn = document.getElementById("taskDetailsEditBtn");
    const saveBtn = document.getElementById("taskDetailsSaveBtn");
    const cancelBtn = document.getElementById("taskDetailsCancelBtn");

    if (editBtn) editBtn.classList.add("d-none");
    if (saveBtn) saveBtn.classList.remove("d-none");
    if (cancelBtn) cancelBtn.classList.remove("d-none");
}


function cancelTaskDetailsEdit()
{
    if (taskDetailsEditSnapshot) {
        const prioritySelect = document.getElementById("modalTaskPrioritySelect");
        const progressSelect = document.getElementById("modalTaskProgressSelect");
        const completedSelect = document.getElementById("modalTaskCompletedSelect");
        const descriptionInput = document.getElementById("modalTaskDescriptionInput");

        if (prioritySelect) prioritySelect.value = taskDetailsEditSnapshot.priority;
        if (progressSelect) progressSelect.value = taskDetailsEditSnapshot.progress;
        if (completedSelect) completedSelect.value = taskDetailsEditSnapshot.is_completed;
        if (descriptionInput) descriptionInput.value = taskDetailsEditSnapshot.description;
    }

    resetTaskFieldEditModes();
    taskDetailsEditSnapshot = null;
}


function saveTaskDetailsEdit()
{
    if (currentTaskId <= 0) return;

    const prioritySelect = document.getElementById("modalTaskPrioritySelect");
    const progressSelect = document.getElementById("modalTaskProgressSelect");
    const completedSelect = document.getElementById("modalTaskCompletedSelect");
    const descriptionInput = document.getElementById("modalTaskDescriptionInput");
    const saveBtn = document.getElementById("taskDetailsSaveBtn");
    const cancelBtn = document.getElementById("taskDetailsCancelBtn");

    const values = {
        priority: prioritySelect ? prioritySelect.value : "",
        progress: progressSelect ? progressSelect.value : "",
        is_completed: completedSelect ? completedSelect.value : "Incomplete",
        description: descriptionInput ? descriptionInput.value : ""
    };

    const fields = ["priority", "progress", "is_completed", "description"];
    const originalHtml = saveBtn ? saveBtn.innerHTML : "";

    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    }
    if (cancelBtn) cancelBtn.disabled = true;

    let chain = Promise.resolve();
    const saved = {};

    fields.forEach(function(field) {
        chain = chain.then(function() {
            return saveTaskField(currentTaskId, field, values[field], function(data) {
                saved[field] = data;
            });
        });
    });

    chain.then(function() {
        setModalValue("modalTaskPriority", values.priority);
        setModalValue("modalTaskProgress", values.progress);
        setModalValue("modalTaskCompleted", values.is_completed);
        const editedDisplay =
            saved.description?.edited ||
            saved.is_completed?.edited ||
            saved.progress?.edited ||
            saved.priority?.edited ||
            document.getElementById("modalTaskEdited")?.textContent ||
            "";

        setModalValue("modalTaskEdited", editedDisplay);

        const descriptionView = document.getElementById("modalTaskDescription");
        if (descriptionView) {
            if (values.description.trim() !== "") {
                descriptionView.textContent = values.description;
                descriptionView.classList.remove("empty");
            } else {
                descriptionView.textContent = "No description provided.";
                descriptionView.classList.add("empty");
            }
        }

        updateCardAfterFieldChange(currentTaskId, "priority", values.priority, null);
        updateCardAfterFieldChange(currentTaskId, "progress", values.progress, null);
        updateCardAfterFieldChange(currentTaskId, "is_completed", values.is_completed, null);
        updateCardAfterFieldChange(currentTaskId, "description", values.description, editedDisplay);

        resetTaskFieldEditModes();
        taskDetailsEditSnapshot = null;
    }).catch(function(error) {
        console.error("Task details save error:", error);
        alert(error.message || "Could not save changes. Please try again.");
    }).finally(function() {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
        }
        if (cancelBtn) cancelBtn.disabled = false;
    });
}


document.addEventListener("click", function(event) {
    if (event.target.closest("#taskDetailsEditBtn")) {
        event.preventDefault();
        enterTaskDetailsEditMode();
        return;
    }

    if (event.target.closest("#taskDetailsCancelBtn")) {
        event.preventDefault();
        cancelTaskDetailsEdit();
        return;
    }

    if (event.target.closest("#taskDetailsSaveBtn")) {
        event.preventDefault();
        saveTaskDetailsEdit();
        return;
    }
});

/* =========================================================
   SAVE TASK FIELD (AJAX)
========================================================= */

function saveTaskField(taskId, field, value, onSuccess)
{

    if (!taskId) {
        return Promise.reject(new Error("Invalid task."));
    }

    const formData =
        new FormData();

    formData.append("action", "update_task_field");
    formData.append("task_id", taskId);
    formData.append("field", field);
    formData.append("value", value);

    return fetch(
        APP.urls.updateField,
        {
            method: "POST",
            body: formData
        }
    )
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data || !data.success) {
                    throw new Error(
                        (data && data.message) ?
                            data.message :
                            "Could not save changes. Please try again."
                    );
                }

                if (typeof onSuccess === "function") {
                    onSuccess(data);
                }

                return data;
            });
        })
        .catch(function(error) {
            console.error("Save task field error:", error);
            throw error;
        });

}


/* =========================================================
   PRIORITY / PROGRESS BADGE HELPERS (JS MIRROR OF PHP)
========================================================= */

function getPriorityBadgeClass(priority)
{

    switch (priority) {

        case "High":
            return "priority-high";

        case "Medium":
            return "priority-medium";

        case "Low":
            return "priority-low";

        default:
            return "priority-low";

    }

}


function getProgressBadgeClass(progress)
{

    switch (progress) {

        case "Todo":
            return "progress-todo";

        case "In Progress":
            return "progress-progress";

        case "Pending":
            return "progress-pending";

        case "Review":
            return "progress-review";

        case "Done":
            return "progress-done";

        default:
            return "progress-todo";

    }

}


/* =========================================================
   UPDATE BOARD CARD AFTER A FIELD IS SAVED
========================================================= */

function updateCardAfterFieldChange(taskId, field, value, editedDisplay)
{

    const card =
        document.querySelector(
            '.task-card[data-task-id="' + taskId + '"]'
        );

    if (!card) {
        return;
    }


    if (editedDisplay) {

        card.dataset.taskEdited = editedDisplay;

        const dateElement =
            card.querySelector(".task-date");

        if (dateElement) {

            dateElement.innerHTML =
                '<i class="bi bi-clock"></i> ' +
                escapeHtml(editedDisplay);

        }

    }


    if (field === "priority") {

        card.dataset.taskPriority = value;

        card.classList.remove(
            "priority-high",
            "priority-medium",
            "priority-low"
        );

        card.classList.add(
            getPriorityBadgeClass(value)
        );

        const badge =
            card.querySelector(".priority-badge");

        if (badge) {
            badge.textContent = value;
        }

    }
    else if (field === "progress") {

        card.dataset.taskProgress = value;

        const badge =
            card.querySelector(".progress-badge");

        if (badge) {

            badge.classList.remove(
                "progress-todo",
                "progress-progress",
                "progress-review",
                "progress-done"
            );

            badge.classList.add(
                getProgressBadgeClass(value)
            );

            badge.textContent = value;

        }


        const targetList =
            document.querySelector(
                '.task-column[data-progress="' +
                value +
                '"] .task-list'
            );

        const sourceColumn =
            card.closest(".task-column");

        if (targetList && targetList !== card.parentElement) {

            const previousColumn =
                card.closest(".task-column");

            targetList.appendChild(card);

            updateColumnCount(previousColumn);
            updateColumnCount(
                card.closest(".task-column")
            );

        }

    }
    else if (field === "is_completed") {

        const completionValue =
            (String(value) === "Completed" || String(value) === "Pending")
                ? String(value)
                : "Incomplete";

        card.dataset.taskCompleted = completionValue;

        const badge =
            card.querySelector(".completion-badge");

        if (badge) {

            badge.classList.remove(
                "completed",
                "pending",
                "incomplete"
            );

            let badgeClass = "incomplete";
            let badgeHtml = '<i class="bi bi-circle"></i> Incomplete';

            if (completionValue === "Completed") {
                badgeClass = "completed";
                badgeHtml = '<i class="bi bi-check-circle-fill"></i> Completed';
            } else if (completionValue === "Pending") {
                badgeClass = "pending";
                badgeHtml = '<i class="bi bi-hourglass-split"></i> Pending';
            }

            badge.classList.add(badgeClass);
            badge.innerHTML = badgeHtml;

        }

    }
    else if (field === "description") {

        card.dataset.taskDescription = value;

        let descriptionElement =
            card.querySelector(".task-description");

        if (value.trim() !== "") {

            if (!descriptionElement) {

                descriptionElement =
                    document.createElement("div");

                descriptionElement.className =
                    "task-description";

                const titleElement =
                    card.querySelector(".task-title");

                if (titleElement) {

                    titleElement.insertAdjacentElement(
                        "afterend",
                        descriptionElement
                    );

                }

            }

            descriptionElement.innerHTML =
                escapeHtml(value).replace(/\n/g, "<br>");

        }
        else if (descriptionElement) {

            descriptionElement.remove();

        }

    }

}


/* =========================================================
   UPDATE COLUMN TASK COUNT
========================================================= */

function updateColumnCount(column)
{

    if (!column) {
        return;
    }

    const countElement =
        column.querySelector(".column-count");

    if (!countElement) {
        return;
    }

    const taskList =
        column.querySelector(".task-list");

    if (!taskList) {
        return;
    }

    countElement.textContent =
        taskList.querySelectorAll(".task-card").length;

}


/* =========================================================
   TASK CARD CLICK
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (!card) {

            return;

        }


        if (
            event.target.closest(
                ".task-menu"
            ) ||
            event.target.closest("a") ||
            event.target.closest("button")
        ) {

            return;

        }


        if (
            draggedCard ||
            isTouchDragging ||
            suppressCardClick
        ) {

            return;

        }


        openTaskDetails(card);

    }
);


/* =========================================================
   OPEN SEPARATE VIEW TASK DETAILS
   Used only by the Admin -> View menu item.
========================================================= */

function openViewTask(card)
{
    if (!card || !viewTaskModal) {
        return;
    }

    const title = card.dataset.taskTitle || "—";
    const description = card.dataset.taskDescription || "";
    const priority = card.dataset.taskPriority || "—";
    const progress = card.dataset.taskProgress || "—";
    const status = card.dataset.taskStatus || "—";
    const completed = card.dataset.taskCompleted || "Incomplete";
    const added = card.dataset.taskAdded || "—";
    const edited = card.dataset.taskEdited || "—";

    const titleElement = document.getElementById("viewTaskTitle");
    const descriptionElement = document.getElementById("viewTaskDescription");
    const priorityElement = document.getElementById("viewTaskPriority");
    const progressElement = document.getElementById("viewTaskProgress");
    const statusElement = document.getElementById("viewTaskStatus");
    const completedElement = document.getElementById("viewTaskCompleted");
    const addedElement = document.getElementById("viewTaskAdded");
    const editedElement = document.getElementById("viewTaskEdited");

    if (titleElement) titleElement.textContent = title;

    if (descriptionElement) {
        descriptionElement.textContent =
            description.trim() !== ""
                ? description
                : "No description provided.";
    }

    if (priorityElement) priorityElement.textContent = priority;
    if (progressElement) progressElement.textContent = progress;
    if (statusElement) statusElement.textContent = status;
    if (addedElement) addedElement.textContent = added;
    if (editedElement) editedElement.textContent = edited;

    if (completedElement) {
        completedElement.textContent = completed;

        completedElement.classList.toggle(
            "pending",
            completed === "Pending"
        );

        completedElement.classList.toggle(
            "incomplete",
            completed !== "Completed" && completed !== "Pending"
        );
    }

    if (taskDetailsModal && taskDetailsModalElement.classList.contains("show")) {
        taskDetailsModal.hide();
    }

    viewTaskModal.show();
}


/* =========================================================
   VIEW TASK FROM ADMIN THREE-DOT MENU
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const viewLink = event.target.closest(".task-view-link");

        if (!viewLink) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const card = viewLink.closest(".task-card");

        document.querySelectorAll(".task-menu-content.show").forEach(function(menu) {
            menu.classList.remove("show");
        });

        if (card) {
            openViewTask(card);
        }
    }
);


/* =========================================================
   DELETE TASK (NO PAGE NAVIGATION)
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const deleteLink =
            event.target.closest(
                ".delete-link"
            );

        if (!deleteLink) {

            return;

        }


        event.preventDefault();


        const confirmed =
            confirm(
                "Are you sure you want to delete this task?"
            );

        if (!confirmed) {

            return;

        }


        const card =
            deleteLink.closest(
                ".task-card"
            );

        const deleteUrl =
            deleteLink.getAttribute(
                "href"
            );


        fetch(
            deleteUrl,
            {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                credentials: "same-origin"
            }
        )
            .then(function() {

                document
                    .querySelectorAll(
                        ".task-menu-content.show"
                    )
                    .forEach(
                        function(item) {

                            item.classList.remove(
                                "show"
                            );

                        }
                    );


                if (card) {

                    const column =
                        card.closest(
                            ".task-column"
                        );

                    card.remove();

                    updateColumnCount(
                        column
                    );

                }

            })
            .catch(function() {

                alert(
                    "Could not delete the task. Please try again."
                );

            });

    }
);


/* =========================================================
   ADD COMMENT BUTTON
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        if (
            event.target.closest(
                "#addCommentButton"
            )
        ) {

            event.preventDefault();

            addTaskComment();

        }

    }
);


/* =========================================================
   BOARD CARD KEYBOARD SELECT
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        const boardCard = event.target.closest(".board-card");

        if (!boardCard || event.target.closest(".board-card-add")) {
            return;
        }

        event.preventDefault();

        const boardId = Number(boardCard.dataset.boardId || 0);

        if (boardId > 0) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("board_id", boardId);
            window.location.href = currentUrl.toString();
        }

    }
);


/* =========================================================
   CTRL + ENTER ADD COMMENT
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.target &&
            event.target.id ===
            "taskCommentInput" &&
            event.ctrlKey &&
            event.key === "Enter"
        ) {

            event.preventDefault();

            addTaskComment();

        }

    }
);


/* =========================================================
   FILE INPUT CHANGE
========================================================= */

document.addEventListener(
    "change",
    function(event) {

        if (
            event.target.id !==
            "taskAttachmentInput"
        ) {

            return;

        }


        const file =
            event.target.files[0];


        if (file) {

            uploadTaskAttachment(
                file
            );

        }


        event.target.value = "";

    }
);


/* =========================================================
   THREE DOT MENU
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const menuButton =
            event.target.closest(
                ".task-menu-button"
            );


        if (menuButton) {

            event.preventDefault();

            event.stopPropagation();


            const menu =
                menuButton
                    .closest(".task-menu")
                    ?.querySelector(
                        ".task-menu-content"
                    );


            document
                .querySelectorAll(
                    ".task-menu-content.show"
                )
                .forEach(
                    function(item) {

                        if (item !== menu) {

                            item.classList.remove(
                                "show"
                            );

                        }

                    }
                );


            if (menu) {

                menu.classList.toggle(
                    "show"
                );

            }


            return;

        }


        if (
            !event.target.closest(
                ".task-menu-content"
            )
        ) {

            document
                .querySelectorAll(
                    ".task-menu-content.show"
                )
                .forEach(
                    function(item) {

                        item.classList.remove(
                            "show"
                        );

                    }
                );

        }

    }
);


/* =========================================================
   SHOW MESSAGE
========================================================= */

function showMessage(
    element,
    duration
)
{

    if (!element) {

        return;

    }


    element.style.display =
        "block";


    clearTimeout(
        element.hideTimer
    );


    element.hideTimer =
        setTimeout(
            function() {

                element.style.display =
                    "none";

            },
            duration || 1500
        );

}


/* =========================================================
   DESKTOP DRAG START
========================================================= */

document.addEventListener(
    "dragstart",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (!card) {

            return;

        }


        suppressCardClick =
            true;


        draggedCard =
            card;


        card.classList.add(
            "dragging"
        );


        if (event.dataTransfer) {

            event.dataTransfer.effectAllowed =
                "move";


            event.dataTransfer.setData(
                "text/plain",
                card.dataset.taskId
            );

        }


        showMessage(
            document.getElementById(
                "dragHint"
            ),
            3000
        );

    }
);


/* =========================================================
   DESKTOP DRAG END
========================================================= */

document.addEventListener(
    "dragend",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (card) {

            card.classList.remove(
                "dragging"
            );

        }


        clearColumnHighlights();


        draggedCard =
            null;


        setTimeout(
            function() {

                suppressCardClick =
                    false;

            },
            250
        );

    }
);


/* =========================================================
   GET TASK BEFORE POSITION
========================================================= */

function getTaskBefore(
    container,
    y
)
{

    const cards = [
        ...container.querySelectorAll(
            ".task-card:not(.dragging):not(.touch-dragging)"
        )
    ];


    let closest = null;

    let closestOffset =
        Number.NEGATIVE_INFINITY;


    cards.forEach(
        function(card) {

            const box =
                card.getBoundingClientRect();


            const offset =
                y -
                box.top -
                box.height / 2;


            if (
                offset < 0 &&
                offset > closestOffset
            ) {

                closestOffset =
                    offset;

                closest =
                    card;

            }

        }
    );


    return closest;

}


/* =========================================================
   SHOW DROP POSITION
========================================================= */

function showDropPosition(
    container,
    y
)
{

    document
        .querySelectorAll(
            ".drop-indicator"
        )
        .forEach(
            function(indicator) {

                indicator.remove();

            }
        );


    const before =
        getTaskBefore(
            container,
            y
        );


    const indicator =
        document.createElement(
            "div"
        );


    indicator.className =
        "drop-indicator";


    indicator.style.display =
        "block";


    if (before) {

        before.parentNode.insertBefore(
            indicator,
            before
        );

    }
    else {

        container.appendChild(
            indicator
        );

    }

}


/* =========================================================
   CLEAR COLUMN HIGHLIGHTS
========================================================= */

function clearColumnHighlights()
{

    document
        .querySelectorAll(
            ".task-column"
        )
        .forEach(
            function(column) {

                column.classList.remove(
                    "drag-over"
                );

            }
        );


    document
        .querySelectorAll(
            ".drop-indicator"
        )
        .forEach(
            function(indicator) {

                indicator.remove();

            }
        );

}


/* =========================================================
   GET COLUMN FROM POINT
========================================================= */

function getColumnFromPoint(
    x,
    y
)
{

    const element =
        document.elementFromPoint(
            x,
            y
        );


    if (!element) {

        return null;

    }


    return element.closest(
        ".task-column"
    );

}


/* =========================================================
   DRAG AUTO-SCROLL HELPERS
   Horizontal board scroll + vertical column scroll.
========================================================= */

function autoScrollWhileDragging(x, y, activeColumn)
{

    const board =
        document.querySelector(
            ".board-wrapper"
        );


    /* =====================================================
       OUTER BOARD VERTICAL SCROLL
       Keeps the whole six-column board independently scrollable
       in addition to each column's own task-list scroll.
    ===================================================== */

    if (board) {

        const boardRect =
            board.getBoundingClientRect();

        const outerEdgeSize = 55;
        const outerMaxSpeed = 12;

        if (board.scrollHeight > board.clientHeight) {

            if (y >= boardRect.bottom - outerEdgeSize) {

                const distance =
                    Math.max(0, y - (boardRect.bottom - outerEdgeSize));

                board.scrollTop += Math.min(
                    outerMaxSpeed,
                    3 + (distance / outerEdgeSize) * 9
                );

            }
            else if (y <= boardRect.top + outerEdgeSize) {

                const distance =
                    Math.max(0, (boardRect.top + outerEdgeSize) - y);

                board.scrollTop -= Math.min(
                    outerMaxSpeed,
                    3 + (distance / outerEdgeSize) * 9
                );

            }

        }

    }


    /* =====================================================
       HORIZONTAL BOARD SCROLL
    ===================================================== */

    if (board) {

        const rect =
            board.getBoundingClientRect();

        const edgeSize = 70;
        const maxSpeed = 18;

        /* Only scroll horizontally when the board actually has overflow. */
        if (board.scrollWidth > board.clientWidth && x >= rect.right - edgeSize) {

            const distance =
                Math.max(0, x - (rect.right - edgeSize));

            const speed =
                Math.min(
                    maxSpeed,
                    5 + (distance / edgeSize) * 13
                );

            board.scrollLeft += speed;

        }
        else if (board.scrollWidth > board.clientWidth && x <= rect.left + edgeSize) {

            const distance =
                Math.max(0, (rect.left + edgeSize) - x);

            const speed =
                Math.min(
                    maxSpeed,
                    5 + (distance / edgeSize) * 13
                );

            board.scrollLeft -= speed;

        }

    }


    /* =====================================================
       VERTICAL ACTIVE-COLUMN SCROLL
    ===================================================== */

    if (activeColumn) {

        const list =
            activeColumn.querySelector(
                ".task-list"
            );

        if (list && list.scrollHeight > list.clientHeight) {

            const rect =
                list.getBoundingClientRect();

            const edgeSize = 65;
            const maxSpeed = 16;

            if (y >= rect.bottom - edgeSize) {

                const distance =
                    Math.max(0, y - (rect.bottom - edgeSize));

                const speed =
                    Math.min(
                        maxSpeed,
                        4 + (distance / edgeSize) * 12
                    );

                list.scrollTop += speed;

            }
            else if (y <= rect.top + edgeSize) {

                const distance =
                    Math.max(0, (rect.top + edgeSize) - y);

                const speed =
                    Math.min(
                        maxSpeed,
                        4 + (distance / edgeSize) * 12
                    );

                list.scrollTop -= speed;

            }

        }

    }

}


function autoScrollPageWhileDragging(y)
{

    const edgeSize = 55;
    const maxSpeed = 12;

    if (y >= window.innerHeight - edgeSize) {

        const distance =
            Math.max(0, y - (window.innerHeight - edgeSize));

        window.scrollBy({
            top: Math.min(
                maxSpeed,
                3 + (distance / edgeSize) * 9
            ),
            left: 0
        });

    }
    else if (y <= edgeSize) {

        const distance =
            Math.max(0, edgeSize - y);

        window.scrollBy({
            top: -Math.min(
                maxSpeed,
                3 + (distance / edgeSize) * 9
            ),
            left: 0
        });

    }

}


/* =========================================================
   DESKTOP DRAG OVER
========================================================= */

document.addEventListener(
    "dragover",
    function(event) {

        if (!draggedCard) {

            return;

        }


        const column =
            event.target.closest(
                ".task-column"
            );


        if (!column) {

            return;

        }


        event.preventDefault();


        autoScrollWhileDragging(
            event.clientX,
            event.clientY,
            column
        );

        autoScrollPageWhileDragging(
            event.clientY
        );


        column.classList.add(
            "drag-over"
        );


        const list =
            column.querySelector(
                ".task-list"
            );


        if (list) {

            showDropPosition(
                list,
                event.clientY
            );

        }

    }
);


/* =========================================================
   DESKTOP DRAG LEAVE
========================================================= */

document.addEventListener(
    "dragleave",
    function(event) {

        const column =
            event.target.closest(
                ".task-column"
            );


        if (!column) {

            return;

        }


        if (
            !column.contains(
                event.relatedTarget
            )
        ) {

            column.classList.remove(
                "drag-over"
            );

        }

    }
);


/* =========================================================
   SAVE TASK PROGRESS
========================================================= */

function saveTaskProgress(
    taskId,
    progress
)
{
    if (!taskId || !progress) {
        return Promise.resolve(false);
    }

    const formData = new FormData();
    formData.append("action", "update_progress");
    formData.append("task_id", taskId);
    formData.append("progress", progress);
    formData.append("board_id", APP.selectedBoardId);

    return fetch(APP.urls.updateProgress, {
        method: "POST",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
        },
        body: formData
    })
    .then(async function(response) {
        const raw = await response.text();
        let data;

        try {
            data = JSON.parse(raw);
        } catch (error) {
            console.error("Progress update returned non-JSON:", raw);
            throw new Error("Unable to update task progress.");
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Unable to update task progress.");
        }

        return data;
    })
    .catch(function(error) {
        console.error("Progress update error:", error);
        alert(error.message || "Unable to update task progress.");
        return false;
    });
}


/* =========================================================
   DESKTOP DROP
========================================================= */

document.addEventListener(
    "drop",
    function(event) {

        if (!draggedCard) {

            return;

        }


        event.preventDefault();


        const column =
            event.target.closest(
                ".task-column"
            );


        if (!column) {

            return;

        }


        const newProgress =
            column.dataset.progress;


        const taskId =
            draggedCard.dataset.taskId;


        clearColumnHighlights();


        if (
            newProgress &&
            taskId
        ) {

            saveTaskProgress(
                taskId,
                newProgress
            ).then(function(data) {

                if (data && data.success) {
                    updateCardAfterFieldChange(
                        taskId,
                        "progress",
                        newProgress,
                        null
                    );
                }

            });

        }

    }
);


/* =========================================================
   OUTER BOARD DIRECT TOUCH SCROLL
   Swipe anywhere on the outer board background/header to
   scroll the complete six-column board in both directions.
   Task cards keep the existing long-press drag behavior and
   task lists keep their own vertical scrolling.
========================================================= */

document.addEventListener(
    "touchstart",
    function(event) {

        const board = event.target.closest(".board-wrapper");

        if (!board) {
            return;
        }

        /* Do not interfere with card drag (columns now scroll too). */
        if (
            event.target.closest(".task-card")
        ) {
            return;
        }

        const touch = event.touches[0];

        if (!touch) {
            return;
        }

        boardTouchScrolling = true;
        boardTouchMoved = false;
        boardTouchStartX = touch.clientX;
        boardTouchStartY = touch.clientY;
        boardTouchLastX = touch.clientX;
        boardTouchLastY = touch.clientY;

        boardTouchStartedInList =
            !!event.target.closest(".task-list");

    },
    { passive: true }
);


document.addEventListener(
    "touchmove",
    function(event) {

        if (!boardTouchScrolling) {
            return;
        }

        const board = document.querySelector(".board-wrapper");
        const touch = event.touches[0];

        if (!board || !touch) {
            return;
        }

        const dx = touch.clientX - boardTouchLastX;
        const dy = touch.clientY - boardTouchLastY;

        const totalX = touch.clientX - boardTouchStartX;
        const totalY = touch.clientY - boardTouchStartY;

        if (
            Math.abs(totalX) > TOUCH_MOVE_THRESHOLD ||
            Math.abs(totalY) > TOUCH_MOVE_THRESHOLD
        ) {
            boardTouchMoved = true;
        }

        if (!boardTouchMoved) {
            return;
        }

        /* Both column (task list) and background swipes now scroll
           the board horizontally and the relevant list/board
           vertically, via the shared helper. */
        performBoardTouchScroll(
            event.target,
            dx,
            dy
        );

        boardTouchLastX = touch.clientX;
        boardTouchLastY = touch.clientY;

        event.preventDefault();

    },
    { passive: false }
);


document.addEventListener(
    "touchend",
    function() {

        boardTouchScrolling = false;
        boardTouchMoved = false;
        boardTouchStartedInList = false;

    },
    { passive: true }
);


document.addEventListener(
    "touchcancel",
    function() {

        boardTouchScrolling = false;
        boardTouchMoved = false;
        boardTouchStartedInList = false;

    },
    { passive: true }
);


/* =========================================================
   TOUCH START
========================================================= */

document.addEventListener(
    "touchstart",
    function(event) {

        const card =
            event.target.closest(
                ".task-card"
            );


        if (!card) {

            return;

        }


        if (
            event.target.closest(
                ".task-menu"
            ) ||
            event.target.closest("a") ||
            event.target.closest("button")
        ) {

            return;

        }


        touchDraggedCard =
            card;


        touchStartX =
            event.touches[0].clientX;


        touchStartY =
            event.touches[0].clientY;


        touchCurrentX =
            touchStartX;


        touchCurrentY =
            touchStartY;


        isTouchDragging =
            false;


        originalParent =
            card.parentNode;


        originalNextSibling =
            card.nextSibling;


        card.classList.add(
            "touch-ready"
        );


        clearTimeout(
            touchLongPressTimer
        );


        touchLongPressTimer =
            setTimeout(
                function() {

                    if (!touchDraggedCard) {

                        return;

                    }


                    isTouchDragging =
                        true;


                    suppressCardClick =
                        true;


                    touchDraggedCard.classList.remove(
                        "touch-ready"
                    );


                    touchDraggedCard.classList.add(
                        "touch-dragging"
                    );


                    touchDraggedCard.classList.add(
                        "touch-source"
                    );


                    showMessage(
                        document.getElementById(
                            "dragHint"
                        ),
                        3000
                    );

                },
                TOUCH_LONG_PRESS
            );

    },
    {
        passive: true
    }
);


/* =========================================================
   TOUCH MOVE
========================================================= */

document.addEventListener(
    "touchmove",
    function(event) {

        if (!touchDraggedCard) {

            return;

        }


        touchCurrentX =
            event.touches[0].clientX;


        touchCurrentY =
            event.touches[0].clientY;


        const deltaX =
            touchCurrentX -
            touchStartX;


        const deltaY =
            touchCurrentY -
            touchStartY;


        const distance =
            Math.sqrt(
                deltaX * deltaX +
                deltaY * deltaY
            );


        if (!isTouchDragging) {

            /*
             * A normal swipe on a task card must scroll the OUTER
             * six-column board in both directions. Do this before
             * cancelling the card touch state so horizontal and
             * vertical swipes are not trapped by the card/column.
             * A short tap still opens the task details, while a
             * long press still enters the existing drag mode.
             */
            if (
                distance >
                TOUCH_MOVE_THRESHOLD
            ) {

                clearTimeout(
                    touchLongPressTimer
                );


                performBoardTouchScroll(
                    event.target,
                    touchCurrentX - touchStartX,
                    touchCurrentY - touchStartY
                );


                touchDraggedCard.classList.remove(
                    "touch-ready"
                );


                touchDraggedCard =
                    null;


                event.preventDefault();

            }


            return;

        }


        event.preventDefault();


        touchDraggedCard.style.transform =
            "translate3d(" +
            deltaX +
            "px," +
            deltaY +
            "px,0) scale(1.03)";


        const column =
            getColumnFromPoint(
                touchCurrentX,
                touchCurrentY
            );


        clearColumnHighlights();


        if (column) {

            column.classList.add(
                "drag-over"
            );


            const list =
                column.querySelector(
                    ".task-list"
                );


            if (list) {

                showDropPosition(
                    list,
                    touchCurrentY
                );

            }

        }


        /* =================================================
           AUTO HORIZONTAL + COLUMN VERTICAL SCROLL
        ================================================= */

        autoScrollWhileDragging(
            touchCurrentX,
            touchCurrentY,
            column
        );


        /* =================================================
           VERTICAL PAGE SCROLL
        ================================================= */

        autoScrollPageWhileDragging(
            touchCurrentY
        );

    },
    {
        passive: false
    }
);


/* =========================================================
   TOUCH END
========================================================= */

document.addEventListener(
    "touchend",
    function() {

        clearTimeout(
            touchLongPressTimer
        );


        if (!touchDraggedCard) {

            return;

        }


        if (!isTouchDragging) {

            touchDraggedCard.classList.remove(
                "touch-ready"
            );


            touchDraggedCard =
                null;


            return;

        }


        const card =
            touchDraggedCard;


        const taskId =
            card.dataset.taskId;


        const column =
            getColumnFromPoint(
                touchCurrentX,
                touchCurrentY
            );


        card.style.transform =
            "";


        card.classList.remove(
            "touch-dragging"
        );


        card.classList.remove(
            "touch-source"
        );


        clearColumnHighlights();


        if (
            column &&
            taskId
        ) {

            const newProgress =
                column.dataset.progress;


            saveTaskProgress(
                taskId,
                newProgress
            ).then(function(data) {

                if (data && data.success) {
                    updateCardAfterFieldChange(
                        taskId,
                        "progress",
                        newProgress,
                        null
                    );
                }

            });

        }
        else {

            if (
                originalParent &&
                card.parentNode !==
                originalParent
            ) {

                if (
                    originalNextSibling &&
                    originalNextSibling.parentNode ===
                    originalParent
                ) {

                    originalParent.insertBefore(
                        card,
                        originalNextSibling
                    );

                }
                else {

                    originalParent.appendChild(
                        card
                    );

                }

            }

        }


        touchDraggedCard =
            null;


        isTouchDragging =
            false;


        originalParent =
            null;


        originalNextSibling =
            null;


        setTimeout(
            function() {

                suppressCardClick =
                    false;

            },
            250
        );

    },
    {
        passive: true
    }
);


/* =========================================================
   TOUCH CANCEL
========================================================= */

document.addEventListener(
    "touchcancel",
    function() {

        clearTimeout(
            touchLongPressTimer
        );


        if (touchDraggedCard) {

            touchDraggedCard.classList.remove(
                "touch-ready"
            );


            touchDraggedCard.classList.remove(
                "touch-dragging"
            );


            touchDraggedCard.classList.remove(
                "touch-source"
            );


            touchDraggedCard.style.transform =
                "";

        }


        clearColumnHighlights();


        touchDraggedCard =
            null;


        isTouchDragging =
            false;


        originalParent =
            null;


        originalNextSibling =
            null;


        setTimeout(
            function() {

                suppressCardClick =
                    false;

            },
            250
        );

    },
    {
        passive: true
    }
);


/* =========================================================
   ESCAPE = CANCEL TOUCH DRAG
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key !==
            "Escape"
        ) {

            return;

        }


        clearTimeout(
            touchLongPressTimer
        );


        if (touchDraggedCard) {

            touchDraggedCard.classList.remove(
                "touch-ready"
            );


            touchDraggedCard.classList.remove(
                "touch-dragging"
            );


            touchDraggedCard.classList.remove(
                "touch-source"
            );


            touchDraggedCard.style.transform =
                "";

        }


        clearColumnHighlights();


        touchDraggedCard =
            null;


        isTouchDragging =
            false;


        originalParent =
            null;


        originalNextSibling =
            null;


        suppressCardClick =
            false;

    }
);


/* =========================================================
   VISIBILITY CHANGE CLEANUP
========================================================= */

document.addEventListener(
    "visibilitychange",
    function() {

        if (document.hidden) {

            clearTimeout(
                touchLongPressTimer
            );


            if (touchDraggedCard) {

                touchDraggedCard.classList.remove(
                    "touch-ready"
                );


                touchDraggedCard.classList.remove(
                    "touch-dragging"
                );


                touchDraggedCard.classList.remove(
                    "touch-source"
                );


                touchDraggedCard.style.transform =
                    "";

            }


            clearColumnHighlights();


            touchDraggedCard =
                null;


            isTouchDragging =
                false;


            originalParent =
                null;


            originalNextSibling =
                null;


            suppressCardClick =
                false;

        }

    }
);


/* =========================================================
   ADD / EDIT TASK MODALS
========================================================= */

const addBoardModalElement =
    document.getElementById(
        "addBoardModal"
    );


let addBoardModal = null;


if (addBoardModalElement) {
    addBoardModal =
        new bootstrap.Modal(
            addBoardModalElement
        );
}


const addTaskModalElement =
    document.getElementById(
        "addTaskModal"
    );


let addTaskModal = null;


if (addTaskModalElement) {

    addTaskModal =
        new bootstrap.Modal(
            addTaskModalElement
        );

}


/* =========================================================
   OPEN ADD TASK MODAL
========================================================= */

function openAddTaskModal(progress, boardId)
{

    if (!addTaskModal) {

        return;

    }


    const form =
        document.getElementById(
            "addTaskForm"
        );


    if (form) {

        form.reset();

        const boardInput =
            document.getElementById(
                "addTaskBoardId"
            );

        if (boardInput) {
            boardInput.value = Number(boardId) > 0
                ? Number(boardId)
                : APP.selectedBoardId;
        }

    }


    const errorBox =
        document.getElementById(
            "addTaskError"
        );


    if (errorBox) {

        errorBox.textContent = "";

        errorBox.classList.add(
            "d-none"
        );

    }


    const progressSelect =
        document.getElementById(
            "addTaskProgress"
        );


    if (
        progressSelect &&
        progress
    ) {

        progressSelect.value =
            progress;

    }


    addTaskModal.show();

}


/* =========================================================
   SUBMIT ADD / EDIT TASK FORM VIA AJAX
========================================================= */

function submitTaskForm(
    formId,
    actionUrl,
    errorBoxId,
    submitBtnId
)
{

    const form =
        document.getElementById(
            formId
        );


    if (!form) {

        return;

    }


    if (!form.reportValidity()) {

        return;

    }


    const errorBox =
        document.getElementById(
            errorBoxId
        );


    const submitBtn =
        document.getElementById(
            submitBtnId
        );


    const originalBtnHtml =
        submitBtn ?
        submitBtn.innerHTML :
        "";


    if (submitBtn) {

        submitBtn.disabled = true;

        submitBtn.innerHTML = `
            <span
                class="spinner-border spinner-border-sm"
            ></span>

            Saving...
        `;

    }


    if (errorBox) {

        errorBox.textContent = "";

        errorBox.classList.add(
            "d-none"
        );

    }


    const formData =
        new FormData(form);


    fetch(
        actionUrl,
        {
            method: "POST",
            headers: {
                "X-Requested-With":
                    "XMLHttpRequest"
            },
            body: formData
        }
    )
    .then(function(response) {

        return response.json();

    })
    .then(function(data) {

        if (!data.success) {

            if (errorBox) {

                errorBox.textContent =
                    data.message ||
                    "Something went wrong.";

                errorBox.classList.remove(
                    "d-none"
                );

            }

            return;

        }


        window.location.reload();

    })
    .catch(function(error) {

        console.error(
            "Task form error:",
            error
        );


        if (errorBox) {

            errorBox.textContent =
                "Unable to save task. Please try again.";

            errorBox.classList.remove(
                "d-none"
            );

        }

    })
    .finally(function() {

        if (submitBtn) {

            submitBtn.disabled = false;

            submitBtn.innerHTML =
                originalBtnHtml;

        }

    });

}


/* =========================================================
   ADD BOARD VIA AJAX
   Add the board directly into the BOARD column.
========================================================= */

const addBoardForm = document.getElementById("addBoardForm");

if (addBoardForm) {

    addBoardForm.addEventListener("submit", function(event) {

        event.preventDefault();

        const submitBtn = addBoardForm.querySelector("button[type=submit]");
        const errorBox = addBoardForm.querySelector(".alert-danger");
        const originalHtml = submitBtn ? submitBtn.innerHTML : "";

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
        }

        if (errorBox) {
            errorBox.textContent = "";
            errorBox.classList.add("d-none");
        }

        const formData = new FormData(addBoardForm);

        fetch(APP.urls.createBoard, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
            },
            body: formData
        })
        .then(async function(response) {
            const raw = await response.text();
            let data;

            try {
                data = JSON.parse(raw);
            } catch (e) {
                console.error("Create board returned non-JSON:", raw);
                const preview = raw.replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
                throw new Error(
                    "Invalid server response (HTTP " + response.status + "). " +
                    (preview ? preview.substring(0, 500) : "The server returned an empty response.")
                );
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to create board.");
            }

            return data;
        })
        .then(function(data) {

            const board = data.board;
            if (!board || !board.id) {
                throw new Error("Board was created but no board data was returned.");
            }

            const boardList = document.querySelector(".board-card-list");
            const boardCount = document.querySelector(".board-task-column .column-count");

            if (boardList) {
                const emptyState = boardList.querySelector(".board-empty");
                if (emptyState) emptyState.remove();

                boardList.querySelectorAll(".board-card").forEach(function(card) {
                    card.classList.remove("active");
                });

                const boardCard = document.createElement("div");
                boardCard.className = "board-card active";
                boardCard.dataset.boardId = board.id;
                boardCard.setAttribute("role", "button");
                boardCard.setAttribute("tabindex", "0");

                boardCard.innerHTML = `
                    <div class="board-card-name">${escapeHtml(board.name)}</div>
                    ${board.description ? `<div class="board-card-description">${escapeHtml(board.description)}</div>` : ""}
                    <div class="board-card-menu">
                        <button type="button" class="board-card-menu-button" aria-label="Board menu" title="Board menu">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <div class="board-card-menu-content">
                            <button type="button" class="board-card-menu-delete" data-board-id="${Number(board.id)}">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </div>
                    </div>`;

                boardList.appendChild(boardCard);
            }

            if (boardCount) {
                boardCount.textContent = document.querySelectorAll(".board-card").length;
            }

            const boardIdInput = document.getElementById("addTaskBoardId");
            if (boardIdInput) boardIdInput.value = board.id;

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("board_id", board.id);
            window.history.replaceState({}, "", currentUrl.toString());

            addBoardForm.reset();
            if (addBoardModal) addBoardModal.hide();

        })
        .catch(function(error) {
            console.error("Create board error:", error);

            if (errorBox) {
                errorBox.textContent = error.message || "Unable to create board.";
                errorBox.classList.remove("d-none");
            } else {
                alert(error.message || "Unable to create board.");
            }

        })
        .finally(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        });

    });
}


/* =========================================================
   ADD / EDIT SUBMIT BUTTON CLICKS
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        if (
            event.target.closest(
                "#addTaskSubmitBtn"
            )
        ) {

            submitTaskForm(
                "addTaskForm",
                APP.urls.addTask,
                "addTaskError",
                "addTaskSubmitBtn"
            );

            return;

        }

    }
);


/* =========================================================
   INTERCEPT ADD BOARD / COLUMN ADD / EDIT LINKS
   OPEN MODALS INSTEAD OF NAVIGATING
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        /* =================================================
           BOARD COLUMN + ADD BOARD
        ================================================= */

        const boardColumnAddBtn =
            event.target.closest("#boardColumnAddBtn");

        if (boardColumnAddBtn) {
            event.preventDefault();
            if (addBoardModal) addBoardModal.show();
            return;
        }


        /* =================================================
           BOARD CARD THREE-DOT MENU
        ================================================= */

        const boardMenuButton =
            event.target.closest(".board-card-menu-button");

        if (boardMenuButton) {
            event.preventDefault();
            event.stopPropagation();

            const menu = boardMenuButton
                .closest(".board-card-menu")
                ?.querySelector(".board-card-menu-content");

            document.querySelectorAll(".board-card-menu-content.show")
                .forEach(function(item) {
                    if (item !== menu) item.classList.remove("show");
                });

            if (menu) menu.classList.toggle("show");
            return;
        }


        /* =================================================
           DELETE BOARD FROM THREE-DOT MENU
        ================================================= */

        const boardDeleteBtn =
            event.target.closest(".board-card-menu-delete");

        if (boardDeleteBtn) {
            event.preventDefault();
            event.stopPropagation();

            const boardId = Number(boardDeleteBtn.dataset.boardId || 0);
            const boardCard = boardDeleteBtn.closest(".board-card");
            const boardName = boardCard
                ? (boardCard.querySelector(".board-card-name")?.textContent.trim() || "this board")
                : "this board";

            if (boardId <= 0) return;

            if (!confirm('Are you sure you want to delete "' + boardName + '"?\n\nA board can only be deleted when it has no tasks.')) {
                return;
            }

            boardDeleteBtn.disabled = true;
            boardDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const formData = new FormData();
            formData.append("action", "delete_board");
            formData.append("board_id", boardId);

            fetch(APP.urls.deleteBoard, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(async function(response) {
                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    console.error("Delete board returned non-JSON:", raw);
                    throw new Error("The server returned an invalid response. Please check the PHP error/log.");
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Unable to delete board.");
                }
                return data;
            })
            .then(function() {
                if (boardCard) boardCard.remove();

                const remainingCards = document.querySelectorAll(".board-card");
                const boardCount = document.querySelector(".board-task-column .column-count");
                if (boardCount) boardCount.textContent = remainingCards.length;

                const deletedWasSelected =
                    Number(new URL(window.location.href).searchParams.get("board_id") || 0) === boardId;

                if (deletedWasSelected) {
                    const firstBoard = document.querySelector(".board-card");
                    const currentUrl = new URL(window.location.href);

                    if (firstBoard) {
                        const firstBoardId = Number(firstBoard.dataset.boardId);
                        currentUrl.searchParams.set("board_id", firstBoardId);
                        window.history.replaceState({}, "", currentUrl.toString());

                        document.querySelectorAll(".board-card").forEach(function(card) {
                            card.classList.toggle("active", Number(card.dataset.boardId) === firstBoardId);
                        });

                        const boardIdInput = document.getElementById("addTaskBoardId");
                        if (boardIdInput) boardIdInput.value = firstBoardId;
                    } else {
                        currentUrl.searchParams.delete("board_id");
                        window.history.replaceState({}, "", currentUrl.toString());
                    }
                }
            })
            .catch(function(error) {
                console.error("Delete board error:", error);
                alert(error.message || "Unable to delete board.");
            })
            .finally(function() {
                boardDeleteBtn.disabled = false;
                boardDeleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete';
            });

            return;
        }


        /* =================================================
           CLOSE BOARD MENUS WHEN CLICKING ELSEWHERE
        ================================================= */

        if (!event.target.closest(".board-card-menu")) {
            document.querySelectorAll(".board-card-menu-content.show")
                .forEach(function(menu) { menu.classList.remove("show"); });
        }


        /* =================================================
           SELECT BOARD IN THE SAME PAGE
        ================================================= */

        const boardCard = event.target.closest(".board-card");

        if (boardCard) {
            event.preventDefault();

            const boardId = Number(boardCard.dataset.boardId || 0);

            if (boardId > 0) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set("board_id", boardId);
                window.location.href = currentUrl.toString();
            }

            return;
        }


        /* =================================================
           TOP RIGHT "+ ADD BOARD" BUTTON
        ================================================= */

        const addBoardBtn =
            event.target.closest(
                "#addBoardBtn"
            );

        if (addBoardBtn) {
            event.preventDefault();

            if (addBoardModal) {
                addBoardModal.show();
            }

            return;
        }


        /* =================================================
           PER COLUMN "+" ADD BUTTON
        ================================================= */

        const columnAddBtn =
            event.target.closest(
                "a.column-add-btn"
            );


        if (columnAddBtn) {

            event.preventDefault();


            const url =
                new URL(
                    columnAddBtn.href,
                    window.location.href
                );


            const progress =
                url.searchParams.get(
                    "progress"
                ) || "Todo";


            openAddTaskModal(
                progress
            );

            return;

        }

    }
);
