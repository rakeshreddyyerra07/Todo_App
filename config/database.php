<?php

$severname = "localhost";
$username= "root";
$password= "";
$dbname= "todo_app";

$conn = mysqli_connect(
    $severname,
    $username,
    $password,
    $dbname
);

if (!$conn) {

die(
     "Database connection failed: "
        . mysqli_connect_error()
);
}

?>

