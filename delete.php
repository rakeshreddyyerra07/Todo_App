<?php

require_once __DIR__ . "/config/database.php";

if (!isset($_GET['id'])) {
    die("Task ID is missing.");
}

$id = $_GET['id'];

$sql = "UPDATE tasks
        SET status = 0
        WHERE id = $id";

$result = mysqli_query($conn, $sql);

if ($result) {

    header("Location: index.php");
    exit();

} else {

    die("Task could not be deleted: " . mysqli_error($conn));

}

?>