<?php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "todo_app";
$port       = 3306;

$conn = mysqli_connect(
    $servername,
    $username,
    $password,
    $dbname,
    $port
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");