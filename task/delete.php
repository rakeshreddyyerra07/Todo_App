<?php

session_start();

require_once __DIR__ . "/../config/database.php";

$id = (int)($_GET["id"] ?? 0);


/* =========================
   CHECK ID
========================= */

if ($id <= 0) {

    header("Location: index.php");
    exit;
}


/* =========================
   DELETE TASK
========================= */

$sql = "DELETE FROM tasks WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $_SESSION["success"] = "Task deleted successfully.";

    } else {

        $_SESSION["error"] = "Failed to delete task.";
    }

    $stmt->close();

} else {

    $_SESSION["error"] = "Database error.";
}


/* =========================
   GO TO INDEX
========================= */

header("Location: index.php");
exit;
