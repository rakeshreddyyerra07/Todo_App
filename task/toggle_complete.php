
<?php

session_start();

require_once __DIR__ . "/../config/database.php";

/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}


/* =========================
   GET TASK ID
========================= */

$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {

    $stmt = $conn->prepare("
        UPDATE tasks
        SET 
            is_completed = NOT is_completed,
            editedDate = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $stmt->close();
}


/* =========================
   RETURN PAGE
========================= */

$return = $_GET["return"] ?? "index.php";

if (
    strpos($return, "//") !== false ||
    strpos($return, "\n") !== false ||
    strpos($return, "\r") !== false
) {
    $return = "index.php";
}


/* =========================
   REDIRECT
========================= */

$separator = strpos($return, "?") === false ? "?" : "&";

header(
    "Location: " .
    $return .
    $separator .
    "message=" .
    urlencode("Task completion updated.")
);

exit();

