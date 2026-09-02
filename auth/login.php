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
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {

        return trim(
            $_SERVER["HTTP_CLIENT_IP"]
        );
    }


    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {

        $forwardedIPs =
            explode(
                ",",
                $_SERVER["HTTP_X_FORWARDED_FOR"]
            );

        return trim(
            $forwardedIPs[0]
        );
    }


    return trim(
        $_SERVER["REMOTE_ADDR"] ?? "Unknown"
    );
}


/*
|--------------------------------------------------------------------------
| Get Public IP
|--------------------------------------------------------------------------
|
| When using XAMPP localhost, PHP normally receives:
|
| 127.0.0.1
| or
| ::1
|
| In that case we get the public IP from ipify.
|
*/

function getPublicIP()
{
    $ip =
        getClientIP();


    /*
    |--------------------------------------------------------------------------
    | Localhost
    |--------------------------------------------------------------------------
    */

    if (
        $ip === "127.0.0.1"
        ||
        $ip === "::1"
        ||
        $ip === "localhost"
    ) {

        $curl =
            curl_init();


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
                    2

            ]
        );


        $response =
            curl_exec(
                $curl
            );


        curl_close(
            $curl
        );


        if (
            $response !== false
        ) {

            $data =
                json_decode(
                    $response,
                    true
                );


            if (
                is_array($data)
                &&
                !empty($data["ip"])
            ) {

                return trim(
                    $data["ip"]
                );
            }
        }
    }


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
    |--------------------------------------------------------------------------
    | Invalid IP
    |--------------------------------------------------------------------------
    */

    if (
        empty($ip)
        ||
        $ip === "Unknown"
    ) {

        return [

            "city" =>
                "",

            "state" =>
                "",

            "country" =>
                ""

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | IP Location API
    |--------------------------------------------------------------------------
    */

    $url =
        "https://ipapi.co/"
        .
        urlencode($ip)
        .
        "/json/";


    $curl =
        curl_init();


    curl_setopt_array(
        $curl,
        [

            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                10,

            CURLOPT_USERAGENT =>
                "Todo_App/1.0",

            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2

        ]
    );


    $response =
        curl_exec(
            $curl
        );


    curl_close(
        $curl
    );


    /*
    |--------------------------------------------------------------------------
    | Process Response
    |--------------------------------------------------------------------------
    */

    if (
        $response !== false
        &&
        !empty($response)
    ) {

        $data =
            json_decode(
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

        "city" =>
            $city,

        "state" =>
            $state,

        "country" =>
            $country

    ];
}


/*
|--------------------------------------------------------------------------
| Save Login Attempt
|--------------------------------------------------------------------------
|
| Existing table:
|
| login_logs
|
| Columns:
|
| emailaddress
| ipaddress
| latlang
| location
| last_attempted_time
| status
|
| Password is NEVER stored.
|
*/

function saveLoginLog(
    $conn,
    $email,
    $ipaddress,
    $latlang,
    $location,
    $status
) {

    $last_attempted_time =
        date(
            "Y-m-d H:i:s"
        );


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
        VALUES (?, ?, ?, ?, ?, ?)
    ";


    $stmt =
        mysqli_prepare(
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
        "ssssss",
        $email,
        $ipaddress,
        $latlang,
        $location,
        $last_attempted_time,
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
    | Get Email
    |--------------------------------------------------------------------------
    */

    $email =
        trim(
            $_POST["email"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Get Password
    |--------------------------------------------------------------------------
    */

    $password =
        $_POST["password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Get IP Address
    |--------------------------------------------------------------------------
    */

    $ipaddress =
        getPublicIP();


    /*
    |--------------------------------------------------------------------------
    | Get Browser Latitude
    |--------------------------------------------------------------------------
    */

    $latitude =
        trim(
            $_POST["latitude"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Get Browser Longitude
    |--------------------------------------------------------------------------
    */

    $longitude =
        trim(
            $_POST["longitude"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Check Browser GPS
    |--------------------------------------------------------------------------
    */

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
    | Get Location From IP
    |--------------------------------------------------------------------------
    |
    | This is used to get:
    |
    | City
    | State
    | Country
    |
    */

    $ipLocation =
        getIPLocation(
            $ipaddress
        );


    $city =
        $ipLocation["city"];


    $state =
        $ipLocation["state"];


    $country =
        $ipLocation["country"];


    /*
    |--------------------------------------------------------------------------
    | Latitude + Longitude
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
    | Build Location
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Only City, State, Country will be stored.
    |
    | No:
    |
    | Browser GPS |
    |
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


        /*
        |--------------------------------------------------------------------------
        | Save Failed Attempt
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Save Failed Attempt
        |--------------------------------------------------------------------------
        */

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


            /*
            |--------------------------------------------------------------------------
            | Bind Email
            |--------------------------------------------------------------------------
            */

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            /*
            |--------------------------------------------------------------------------
            | Execute Query
            |--------------------------------------------------------------------------
            */

            mysqli_stmt_execute(
                $stmt
            );


            /*
            |--------------------------------------------------------------------------
            | Get Result
            |--------------------------------------------------------------------------
            */

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
                    | SUCCESSFUL LOGIN
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
                    | Store Session
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
                    | Redirect To Task Page
                    |--------------------------------------------------------------------------
                    */

                    header(
                        "Location: ../task/index.php"
                    );

                    exit();


                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | Wrong Password
                    |--------------------------------------------------------------------------
                    */

                    $error =
                        "Invalid email or password.";


                    /*
                    |--------------------------------------------------------------------------
                    | Save Failed Attempt
                    |--------------------------------------------------------------------------
                    */

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


                /*
                |--------------------------------------------------------------------------
                | Email Does Not Exist
                |--------------------------------------------------------------------------
                */

                $error =
                    "Invalid email or password.";


                /*
                |--------------------------------------------------------------------------
                | Save Failed Attempt
                |--------------------------------------------------------------------------
                */

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

        /*
        --------------------------------------------------------------
        Password Eye Icon
        --------------------------------------------------------------
        */

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

            transform:
                translateY(-50%);

            width: 18px;

            height: 12px;

            border:
                1.5px solid #777;

            border-radius:
                50% / 60%;

            cursor: pointer;

            box-sizing:
                border-box;

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

            transform:
                translate(
                    -50%,
                    -50%
                );

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


            <!--
            ==========================================================
            Browser Location
            ==========================================================
            -->


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


            <!--
            ==========================================================
            Email
            ==========================================================
            -->


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


            <!--
            ==========================================================
            Password
            ==========================================================
            -->


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


            <!--
            ==========================================================
            Remember + Forgot Password
            ==========================================================
            -->


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


                <a
                    href="forgot_password.php"
                >

                    Forgot Password?

                </a>


            </div>


            <!--
            ==========================================================
            Login Button
            ==========================================================
            -->


            <button
                type="submit"
                class="btn btn-primary"
                style="width:100%; height:48px;"
            >

                Login

            </button>


        </form>


        <!--
        ==========================================================
        Create Account
        ==========================================================
        -->


        <div class="auth-link">


            Don't have an account?


            <a
                href="register.php"
            >

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

        password.type =
            "text";

    } else {

        password.type =
            "password";
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


/*
|--------------------------------------------------------------------------
| Location Status
|--------------------------------------------------------------------------
*/

let locationCollected =
    false;


/*
|--------------------------------------------------------------------------
| Request Browser Location
|--------------------------------------------------------------------------
*/

function requestBrowserLocation()
{

    /*
    |--------------------------------------------------------------------------
    | Browser does not support Geolocation
    |--------------------------------------------------------------------------
    */

    if (
        !navigator.geolocation
    ) {

        console.log(
            "Geolocation is not supported."
        );


        /*
        | PHP will use IP fallback.
        */

        locationCollected =
            true;


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Ask Browser Permission
    |--------------------------------------------------------------------------
    */

    navigator.geolocation.getCurrentPosition(

        function(position) {


            /*
            --------------------------------------------------------------
            | Permission Granted
            --------------------------------------------------------------
            */

            const latitude =
                position.coords.latitude;


            const longitude =
                position.coords.longitude;


            /*
            --------------------------------------------------------------
            | Put Coordinates Into Hidden Fields
            --------------------------------------------------------------
            */

            document.getElementById(
                "latitude"
            ).value =
                latitude;


            document.getElementById(
                "longitude"
            ).value =
                longitude;


            console.log(
                "Browser GPS Latitude:",
                latitude
            );


            console.log(
                "Browser GPS Longitude:",
                longitude
            );


            console.log(
                "Browser location permission granted."
            );


            /*
            --------------------------------------------------------------
            | Location Completed
            --------------------------------------------------------------
            */

            locationCollected =
                true;

        },


        function(error) {


            /*
            --------------------------------------------------------------
            | Permission Denied / Timeout / Error
            --------------------------------------------------------------
            |
            | We don't stop login.
            |
            | PHP will capture IP address and use IP-based location.
            |
            --------------------------------------------------------------
            */

            console.log(
                "Browser location unavailable."
            );


            console.log(
                "Reason:",
                error.message
            );


            console.log(
                "Using PHP IP address fallback."
            );


            locationCollected =
                true;

        },


        {

            /*
            | Request accurate location
            */

            enableHighAccuracy:
                true,


            /*
            | Maximum waiting time
            */

            timeout:
                10000,


            /*
            | Do not use cached location
            */

            maximumAge:
                0

        }

    );

}


/*
|--------------------------------------------------------------------------
| Start Location Request
|--------------------------------------------------------------------------
*/

requestBrowserLocation();


/*
|--------------------------------------------------------------------------
| Submit Login
|--------------------------------------------------------------------------
|
| Wait for browser location request to finish.
|
| If the user denies permission, locationCollected becomes true and
| the form continues.
|
|--------------------------------------------------------------------------
*/

loginForm.addEventListener(
    "submit",
    function(event) {


        if (
            !locationCollected
        ) {

            event.preventDefault();


            console.log(
                "Waiting for location..."
            );


            const waitForLocation =
                setInterval(
                    function() {


                        if (
                            locationCollected
                        ) {

                            clearInterval(
                                waitForLocation
                            );


                            console.log(
                                "Submitting login..."
                            );


                            /*
                            | Native submit avoids triggering this
                            | submit event again.
                            */

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