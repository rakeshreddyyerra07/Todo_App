<?php

session_start();

require_once __DIR__ . "/../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    exit("Please login first.");

}


$user_id = (int)$_SESSION["user_id"];


/* =========================================================
   GET ATTACHMENT ID
========================================================= */

$attachment_id =
    (int)($_GET["id"] ?? 0);


if ($attachment_id <= 0) {

    http_response_code(400);

    exit("Invalid attachment.");

}


/* =========================================================
   GET ATTACHMENT FROM DATABASE
========================================================= */

$sql = "
    SELECT
        id,
        task_id,
        user_id,
        original_name,
        stored_name,
        file_path,
        file_type,
        file_size

    FROM task_attachments

    WHERE id = ?

    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    http_response_code(500);

    exit("Database error.");

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $attachment_id
);


mysqli_stmt_execute(
    $stmt
);


$result = mysqli_stmt_get_result(
    $stmt
);


/* =========================================================
   CHECK ATTACHMENT
========================================================= */

if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close($stmt);

    http_response_code(404);

    exit("Attachment not found.");

}


$row = mysqli_fetch_assoc(
    $result
);


mysqli_stmt_close($stmt);


/* =========================================================
   GET STORED FILE NAME
========================================================= */

$stored_name =
    basename(
        $row["stored_name"] ?? ""
    );


if ($stored_name === "") {

    http_response_code(404);

    exit("File not found.");

}


/* =========================================================
   BUILD ACTUAL SERVER FILE PATH
========================================================= */

$file_path =
    __DIR__ .
    "/../uploads/task_files/" .
    $stored_name;


/* =========================================================
   CHECK FILE EXISTS
========================================================= */

if (
    !file_exists($file_path)
) {

    http_response_code(404);

    exit(
        "The attachment file no longer exists on the server."
    );

}


/* =========================================================
   CHECK IS FILE
========================================================= */

if (
    !is_file($file_path)
) {

    http_response_code(404);

    exit("Invalid attachment file.");

}


/* =========================================================
   GET MIME TYPE
========================================================= */

$mime_type = "";


if (
    function_exists("finfo_open")
) {

    $finfo = finfo_open(
        FILEINFO_MIME_TYPE
    );


    if ($finfo) {

        $detected_mime =
            finfo_file(
                $finfo,
                $file_path
            );


        if ($detected_mime) {

            $mime_type =
                $detected_mime;

        }


        finfo_close($finfo);

    }

}


/* =========================================================
   FALLBACK MIME TYPE
========================================================= */

if (
    $mime_type === ""
) {

    $mime_type =
        $row["file_type"] ??
        "application/octet-stream";

}


/* =========================================================
   ORIGINAL FILE NAME
========================================================= */

$original_name =
    basename(
        $row["original_name"] ?? ""
    );


if (
    $original_name === ""
) {

    $original_name =
        $stored_name;

}


/* =========================================================
   REMOVE QUOTES FROM FILE NAME
========================================================= */

$original_name =
    str_replace(
        [
            '"',
            "\r",
            "\n"
        ],
        "",
        $original_name
    );


/* =========================================================
   CLEAR OUTPUT BUFFER
========================================================= */

while (
    ob_get_level() > 0
) {

    ob_end_clean();

}


/* =========================================================
   SEND FILE HEADERS
========================================================= */

header(
    "Content-Type: " .
    $mime_type
);


header(
    "Content-Length: " .
    filesize($file_path)
);


header(
    "Content-Disposition: inline; filename=\"" .
    $original_name .
    "\""
);


header(
    "X-Content-Type-Options: nosniff"
);


header(
    "Cache-Control: private, max-age=3600"
);


/* =========================================================
   OUTPUT FILE
========================================================= */

readfile(
    $file_path
);


exit();

?>