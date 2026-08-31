<?php

$host = getenv("MYSQLHOST");
$port = getenv("MYSQLPORT");
$user = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");
$database = getenv("MYSQLDATABASE");

if (
    empty($host) ||
    empty($port) ||
    empty($user) ||
    empty($password) ||
    empty($database)
) {
    die("Database environment variables are missing.");
}

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database,
    (int)$port
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
