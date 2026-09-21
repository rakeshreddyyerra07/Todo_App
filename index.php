<?php

session_start();

/*
|--------------------------------------------------------------------------
| Todo App Landing Page
|--------------------------------------------------------------------------
|
| If the user is already logged in,
| redirect to the Todo App dashboard.
|
| Otherwise, redirect to the Login page.
|
*/

if (isset($_SESSION["user_id"])) {

    header("Location: task/index.php");
    exit();

}

/* User is not logged in */

header("Location: auth/login.php");
exit();

?>