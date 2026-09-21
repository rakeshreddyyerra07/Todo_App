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
   CHECK TASK EXISTS
========================================================= */

$stmt = $conn->prepare("
    SELECT id, is_completed
    FROM tasks
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {

    die(
        "Database error: " .
        htmlspecialchars($stmt->error)
    );

}

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    header("Location: index.php?error=1");
    exit();

}

$task = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   CURRENT COMPLETION STATUS
========================================================= */

$current_status = (int)$task["is_completed"];


/* =========================================================
   NEW COMPLETION STATUS
========================================================= */

$new_status = ($current_status === 1) ? 0 : 1;


/* =========================================================
   UPDATE TASK
========================================================= */

$stmt = $conn->prepare("
    UPDATE tasks
    SET
        is_completed = ?,
        editedDate = CURRENT_TIMESTAMP
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {

    die(
        "Database error: " .
        htmlspecialchars($conn->error)
    );

}

$stmt->bind_param(
    "ii",
    $new_status,
    $id
);


if (!$stmt->execute()) {

    die(
        "Database error: " .
        htmlspecialchars($stmt->error)
    );

}

$stmt->close();


/* =========================================================
   RETURN PAGE
========================================================= */

$return = $_GET["return"] ?? "view.php?id=" . $id;


/* =========================================================
   BASIC REDIRECT SECURITY
========================================================= */

if (
    strpos($return, "//") !== false ||
    strpos($return, "\n") !== false ||
    strpos($return, "\r") !== false
) {

    $return = "view.php?id=" . $id;

}


/* =========================================================
   REDIRECT
========================================================= */

if (strpos($return, "?") === false) {

    $return .= "?message=" . urlencode(
        "Task completion updated."
    );

} else {

    $return .= "&message=" . urlencode(
        "Task completion updated."
    );

}


header("Location: " . $return);

exit();

?>