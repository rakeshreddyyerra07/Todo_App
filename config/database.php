<?php

$getEnvironmentValue = static function (array $names, string $default = ""): string {
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== "") {
            return $value;
        }
    }

    return $default;
};

$databaseUrl = $getEnvironmentValue(["DATABASE_URL", "MYSQL_URL"]);
$urlParts = $databaseUrl !== "" ? parse_url($databaseUrl) : false;

$host = $getEnvironmentValue(["MYSQLHOST", "DB_HOST"], is_array($urlParts) ? ($urlParts["host"] ?? "") : "localhost");
$port = $getEnvironmentValue(["MYSQLPORT", "DB_PORT"], is_array($urlParts) ? (string)($urlParts["port"] ?? 3306) : "3306");
$user = $getEnvironmentValue(["MYSQLUSER", "DB_USERNAME"], is_array($urlParts) ? urldecode($urlParts["user"] ?? "") : "root");
$password = $getEnvironmentValue(["MYSQLPASSWORD", "DB_PASSWORD"], is_array($urlParts) ? urldecode($urlParts["pass"] ?? "") : "");
$database = $getEnvironmentValue(["MYSQLDATABASE", "DB_DATABASE"], is_array($urlParts) ? ltrim($urlParts["path"] ?? "", "/") : "todo_app");

if (
    empty($host) ||
    empty($port) ||
    empty($user) ||
    empty($password) ||
    empty($database)
) {
    die("Database configuration is missing. Set MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, and MYSQLDATABASE in the app service.");
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
