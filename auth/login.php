<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";
$email = "";


/*
|--------------------------------------------------------------------------
| Get Client IP Address
|--------------------------------------------------------------------------
*/

function getClientIP()
{
    /*
    |----------------------------------------------------------------------
    | Check common proxy headers
    |----------------------------------------------------------------------
    */

    $headers = [
        "HTTP_CF_CONNECTING_IP",
        "HTTP_X_REAL_IP",
        "HTTP_X_FORWARDED_FOR",
        "HTTP_CLIENT_IP"
    ];

    foreach ($headers as $header) {

        if (!empty($_SERVER[$header])) {

            $ips = explode(",", $_SERVER[$header]);

            foreach ($ips as $ip) {

                $ip = trim($ip);

                if (
                    filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE |
                        FILTER_FLAG_NO_RES_RANGE
                    )
                ) {

                    return $ip;
                }
            }
        }
    }


    /*
    |----------------------------------------------------------------------
    | Fallback
    |----------------------------------------------------------------------
    */

    $remoteIP = trim(
        $_SERVER["REMOTE_ADDR"] ?? ""
    );


    if (
        filter_var(
            $remoteIP,
            FILTER_VALIDATE_IP
        )
    ) {

        return $remoteIP;
    }


    return "Unknown";
}


/*
|--------------------------------------------------------------------------
| Get Public IP
|--------------------------------------------------------------------------
|
| If the server only sees localhost/private IP, ask ipify for the
| public IP.
|
*/

function getPublicIP()
{
    $ip = getClientIP();


    /*
    |----------------------------------------------------------------------
    | If we already have a public IP
    |----------------------------------------------------------------------
    */

    if (
        filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        )
    ) {

        return $ip;
    }


    /*
    |----------------------------------------------------------------------
    | Get public IP from ipify
    |----------------------------------------------------------------------
    */

    if (
        function_exists("curl_init")
    ) {

        $curl = curl_init();


        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL =>
                    "https://api.ipify.org?format=json",

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_TIMEOUT =>
                    10,

                CURLOPT_SSL_VERIFYPEER =>
                    true,

                CURLOPT_SSL_VERIFYHOST =>
                    2,

                CURLOPT_USERAGENT =>
                    "Todo_App/1.0"
            ]
        );


        $response = curl_exec(
            $curl
        );


        curl_close(
            $curl
        );


        if (
            $response !== false
            &&
            !empty($response)
        ) {

            $data = json_decode(
                $response,
                true
            );


            if (
                is_array($data)
                &&
                !empty($data["ip"])
                &&
                filter_var(
                    $data["ip"],
                    FILTER_VALIDATE_IP
                )
            ) {

                return trim(
                    $data["ip"]
                );
            }
        }
    }


    /*
    |----------------------------------------------------------------------
    | Return original IP if public lookup failed
    |----------------------------------------------------------------------
    */

    return $ip;
}


/*
|--------------------------------------------------------------------------
| Get Location From IP Address
|--------------------------------------------------------------------------
*/

function getIPLocation($ip)
{
    $city = "";
    $state = "";
    $country = "";


    /*
    |----------------------------------------------------------------------
    | Cannot geolocate local/private IP
    |----------------------------------------------------------------------
    */

    if (
        empty($ip)
        ||
        $ip === "Unknown"
        ||
        !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        )
    ) {

        return [
            "city" => "",
            "state" => "",
            "country" => ""
        ];
    }


    /*
    |----------------------------------------------------------------------
    | IP Location API
    |----------------------------------------------------------------------
    */

    $url =
        "https://ipapi.co/"
        .
        urlencode($ip)
        .
        "/json/";


    if (
        !function_exists("curl_init")
    ) {

        return [
            "city" => "",
            "state" => "",
            "country" => ""
        ];
    }


    $curl = curl_init();


    curl_setopt_array(
        $curl,
        [
            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                10,

            CURLOPT_CONNECTTIMEOUT =>
                5,

            CURLOPT_USERAGENT =>
                "Todo_App/1.0",

            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2
        ]
    );


    $response = curl_exec(
        $curl
    );


    curl_close(
        $curl
    );


    /*
    |----------------------------------------------------------------------
    | Process API response
    |----------------------------------------------------------------------
    */

    if (
        $response !== false
        &&
        !empty($response)
    ) {

        $data = json_decode(
            $response,
            true
        );


        if (
            is_array($data)
        ) {

            $city =
                trim(
                    $data["city"] ?? ""
                );


            $state =
                trim(
                    $data["region"] ?? ""
                );


            $country =
                trim(
                    $data["country_name"] ?? ""
                );
        }
    }


    return [
        "city" => $city,
        "state" => $state,
        "country" => $country
    ];
}


/*
|--------------------------------------------------------------------------
| Save Login Log
|--------------------------------------------------------------------------
*/

function saveLoginLog(
    $conn,
    $email,
    $ipaddress,
    $latlang,
    $location,
    $status
) {

    $sql = "
        INSERT INTO login_logs
        (
            emailaddress,
            ipaddress,
            latlang,
            location,
            last_attempted_time,
            status
        )
        VALUES (?, ?, ?, ?, NOW(), ?)
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    if (!$stmt) {

        error_log(
            "Login log prepare failed: "
            .
            mysqli_error($conn)
        );

        return;
    }


    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $email,
        $ipaddress,
        $latlang,
        $location,
        $status
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        error_log(
            "Login log insert failed: "
            .
            mysqli_stmt_error($stmt)
        );
    }


    mysqli_stmt_close(
        $stmt
    );
}


/*
|--------------------------------------------------------------------------
| Check Already Logged In
|--------------------------------------------------------------------------
*/

if (
    isset(
        $_SESSION["user_id"]
    )
) {

    header(
        "Location: ../task/index.php"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| Login Processing
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */

    $email =
        trim(
            $_POST["email"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Password
    |--------------------------------------------------------------------------
    */

    $password =
        $_POST["password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Get IP
    |--------------------------------------------------------------------------
    */

    $ipaddress =
        getPublicIP();


    /*
    |--------------------------------------------------------------------------
    | Browser GPS
    |--------------------------------------------------------------------------
    */

    $latitude =
        trim(
            $_POST["latitude"] ?? ""
        );


    $longitude =
        trim(
            $_POST["longitude"] ?? ""
        );


    $hasBrowserLocation =
        (
            $latitude !== ""
            &&
            $longitude !== ""
            &&
            is_numeric($latitude)
            &&
            is_numeric($longitude)
        );


    /*
    |--------------------------------------------------------------------------
    | Save GPS coordinates
    |--------------------------------------------------------------------------
    */

    if (
        $hasBrowserLocation
    ) {

        $latlang =
            $latitude
            .
            ", "
            .
            $longitude;

    } else {

        $latlang =
            "Not Available";
    }


    /*
    |--------------------------------------------------------------------------
    | Get IP Location
    |--------------------------------------------------------------------------
    */

    $ipLocation =
        getIPLocation(
            $ipaddress
        );


    $city =
        $ipLocation["city"] ?? "";


    $state =
        $ipLocation["state"] ?? "";


    $country =
        $ipLocation["country"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Build Location
    |--------------------------------------------------------------------------
    */

    $locationParts = [];


    if (
        $city !== ""
    ) {

        $locationParts[] =
            $city;
    }


    if (
        $state !== ""
    ) {

        $locationParts[] =
            $state;
    }


    if (
        $country !== ""
    ) {

        $locationParts[] =
            $country;
    }


    if (
        count($locationParts) > 0
    ) {

        $location =
            implode(
                ", ",
                $locationParts
            );

    } else {

        $location =
            "Not Available";
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $email === ""
        ||
        $password === ""
    ) {

        $error =
            "Please enter your email and password.";


        saveLoginLog(
            $conn,
            $email,
            $ipaddress,
            $latlang,
            $location,
            "FAILED"
        );

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";


        saveLoginLog(
            $conn,
            $email,
            $ipaddress,
            $latlang,
            $location,
            "FAILED"
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $stmt =
            mysqli_prepare(
                $conn,

                "SELECT
                    id,
                    name,
                    email,
                    password
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );


        if (!$stmt) {

            $error =
                "Something went wrong. Please try again.";


            saveLoginLog(
                $conn,
                $email,
                $ipaddress,
                $latlang,
                $location,
                "FAILED"
            );

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            mysqli_stmt_execute(
                $stmt
            );


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            /*
            |--------------------------------------------------------------------------
            | User Found
            |--------------------------------------------------------------------------
            */

            if (
                $result
                &&
                mysqli_num_rows($result) === 1
            ) {

                $user =
                    mysqli_fetch_assoc(
                        $result
                    );


                /*
                |--------------------------------------------------------------------------
                | Verify Password
                |--------------------------------------------------------------------------
                */

                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Successful Login
                    |--------------------------------------------------------------------------
                    */

                    saveLoginLog(
                        $conn,
                        $email,
                        $ipaddress,
                        $latlang,
                        $location,
                        "SUCCESS"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Regenerate Session
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(
                        true
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Session Data
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION["user_id"] =
                        $user["id"];


                    $_SESSION["user_name"] =
                        $user["name"];


                    $_SESSION["user_email"] =
                        $user["email"];


                    /*
                    |--------------------------------------------------------------------------
                    | Redirect
                    |--------------------------------------------------------------------------
                    */

                    header(
                        "Location: ../task/index.php"
                    );

                    exit();


                } else {

                    $error =
                        "Invalid email or password.";


                    saveLoginLog(
                        $conn,
                        $email,
                        $ipaddress,
                        $latlang,
                        $location,
                        "FAILED"
                    );
                }


            } else {

                $error =
                    "Invalid email or password.";


                saveLoginLog(
                    $conn,
                    $email,
                    $ipaddress,
                    $latlang,
                    $location,
                    "FAILED"
                );
            }


            mysqli_stmt_close(
                $stmt
            );
        }
    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login - Todo App
    </title>

    <link
        rel="stylesheet"
        href="../assets/style.css"
    >

    <style>

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 40px;
        }

        .password-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 12px;
            border: 1.5px solid #777;
            border-radius: 50% / 60%;
            cursor: pointer;
            box-sizing: border-box;
        }

        .password-eye::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 5px;
            background: #777;
            border-radius: 50%;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

    </style>

</head>


<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-header">

            <div class="auth-icon">
                🔒
            </div>

            <h1>
                Welcome Back! 👋
            </h1>

            <p>
                Login to continue to your account.
            </p>

        </div>


        <?php if (
            isset($_GET["registered"])
        ): ?>

            <div class="alert alert-success">

                Account created successfully.
                Please login.

            </div>

        <?php endif; ?>


        <?php if (
            $error !== ""
        ): ?>

            <div class="alert alert-error">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            id="loginForm"
        >

            <input
                type="hidden"
                name="latitude"
                id="latitude"
            >

            <input
                type="hidden"
                name="longitude"
                id="longitude"
            >


            <div class="form-group">

                <label class="form-label">
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    value="<?php
                        echo htmlspecialchars(
                            $email
                        );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label class="form-label">
                    Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        name="password"
                        id="loginPassword"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >

                    <span
                        class="password-eye"
                        id="toggleLoginPassword"
                        onclick="toggleLoginPassword()"
                        title="Show password"
                    ></span>

                </div>

            </div>


            <div
                class="checkbox-row"
                style="justify-content:space-between;"
            >

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                    "
                >

                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                    >

                    <label for="remember">
                        Remember me
                    </label>

                </div>


                <a href="forgot_password.php">
                    Forgot Password?
                </a>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
                style="width:100%; height:48px;"
            >
                Login
            </button>

        </form>


        <div class="auth-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Password Show / Hide
|--------------------------------------------------------------------------
*/

function toggleLoginPassword()
{
    const password =
        document.getElementById(
            "loginPassword"
        );

    if (
        password.type === "password"
    ) {

        password.type = "text";

    } else {

        password.type = "password";
    }
}


/*
|--------------------------------------------------------------------------
| Login Form
|--------------------------------------------------------------------------
*/

const loginForm =
    document.getElementById(
        "loginForm"
    );


let locationCollected = false;


/*
|--------------------------------------------------------------------------
| Request Browser GPS
|--------------------------------------------------------------------------
*/

function requestBrowserLocation()
{

    if (
        !navigator.geolocation
    ) {

        locationCollected = true;

        return;
    }


    navigator.geolocation.getCurrentPosition(

        function(position) {

            document.getElementById(
                "latitude"
            ).value =
                position.coords.latitude;


            document.getElementById(
                "longitude"
            ).value =
                position.coords.longitude;


            console.log(
                "GPS:",
                position.coords.latitude,
                position.coords.longitude
            );


            locationCollected = true;

        },

        function(error) {

            console.log(
                "Browser GPS unavailable:",
                error.message
            );


            /*
            | PHP will use IP location.
            */

            locationCollected = true;

        },

        {

            enableHighAccuracy: true,

            timeout: 10000,

            maximumAge: 0

        }
    );
}


/*
|--------------------------------------------------------------------------
| Start GPS Request
|--------------------------------------------------------------------------
*/

requestBrowserLocation();


/*
|--------------------------------------------------------------------------
| Submit Form
|--------------------------------------------------------------------------
*/

loginForm.addEventListener(
    "submit",
    function(event) {

        if (
            !locationCollected
        ) {

            event.preventDefault();


            const waitForLocation =
                setInterval(
                    function() {

                        if (
                            locationCollected
                        ) {

                            clearInterval(
                                waitForLocation
                            );


                            loginForm.submit();
                        }

                    },
                    100
                );
        }

    }
);

</script>


</body>

</html>