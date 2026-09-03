
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
   ADMIN ACCESS ONLY
========================================================= */

$user_role = $_SESSION["user_role"] ?? "user";

if ($user_role !== "admin") {

    header("Location: index.php");
    exit();

}


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

