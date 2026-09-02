<?php

$servername = getenv("db.fr-roub1.bengt.wasmernet.com");
$username   = getenv("user_6c0af212");
$password   = getenv("Dpw_LtxQbMJ30UpOdtcFibygjflYPE8wVzuC");
$dbname     = getenv("db_100af6ff);
$port       = getenv("20184);

$conn = mysqli_connect(
    $servername,
    $username,
    $password,
    $dbname,
    (int)$port
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");

?>
