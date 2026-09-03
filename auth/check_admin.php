<?php

session_start();

echo "<h2>Admin Session Check</h2>";

echo "<pre>";

echo "User ID: ";
var_dump($_SESSION["user_id"] ?? null);

echo "User Name: ";
var_dump($_SESSION["user_name"] ?? null);

echo "User Email: ";
var_dump($_SESSION["user_email"] ?? null);

echo "User Role: ";
var_dump($_SESSION["user_role"] ?? null);

echo "</pre>";