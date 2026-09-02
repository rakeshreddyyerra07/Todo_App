<?php

$servername = "db.fr-roub1.bengt.wasmernet.com";
$username   = "user_6c0af212";
$password   = "pw_LtxQbMJ30UpOdtcFibygjflYPE8wVzuC";
$dbname     = "db_100af6ff";
$port       = 20184;

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

?>
