
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
    | Cloud/Proxy IP
    */
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        return trim($_SERVER["HTTP_CLIENT_IP"]);
    }

    /*
    | Forwarded IP
    */
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {

        $forwardedIPs = explode(
            ",",
            $_SERVER["HTTP_X_FORWARDED_FOR"]
        );

        /*
        | Use first forwarded IP
        */
        foreach ($forwardedIPs as $forwardedIP) {

            $forwardedIP = trim($forwardedIP);

            if (
                filter_var(
                    $forwardedIP,
                    FILTER_VALIDATE_IP
                )
            ) {
                return $forwardedIP;
            }
        }
    }

    /*
    | Normal server IP
    */
    return trim(
        $_SERVER["REMOTE_ADDR"] ?? "Unknown"
    );
}


/*
|--------------------------------------------------------------------------
| Get Current Public IP
|--------------------------------------------------------------------------
|
| Localhost/private IPs are NOT saved.
|
| Examples:
|
| 127.0.0.1
| 127.0.0.100
| ::1
| 192.168.x.x
| 10.x.x.x
| 172.16.x.x - 172.31.x.x
|
| For these, we ask ipify for the current public IP.
|
|--------------------------------------------------------------------------
*/

function getPublicIP()
{
    $ip = getClientIP();

    /*
    | Check if detected IP is a real public IP
    */
    $isPublicIP = filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    );

    /*
    | Already public
    */
    if ($isPublicIP !== false) {
        return $ip;
    }

    /*
    | Get public IP from ipify
    */
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

    $response = curl_exec($curl);

    curl_close($curl);

    if (
        $response !== false &&
        !empty($response)
    ) {

        $data = json_decode(
            $response,
            true
        );

        if (
            is_array($data) &&
            !empty($data["ip"])
        ) {

            $publicIP = trim(
                $data["ip"]
            );

            /*
            | Verify returned IP is public
            */
            $validPublicIP = filter_var(
                $publicIP,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($validPublicIP !== false) {
                return $publicIP;
            }
        }
    }

    return "Not Available";
}


/*
|--------------------------------------------------------------------------
| Get Location From Public IP
|--------------------------------------------------------------------------
|
| Returns:
|
| city
| state
| country
| timezone
|
|--------------------------------------------------------------------------
*/

function getIPLocation($ip)
{
    $city = "";
    $state = "";
    $country = "";
    $timezone = "";


    /*
    | Invalid IP
    */
    if (
        empty($ip) ||
        $ip === "Unknown" ||
        $ip === "Not Available"
    ) {

        return [
            "city" =>
                "",

            "state" =>
                "",

            "country" =>
                "",

            "timezone" =>
                ""
        ];
    }


    /*
    | IP API
    */
    $url =
        "https://ipapi.co/" .
        urlencode($ip) .
        "/json/";


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

            CURLOPT_USERAGENT =>
                "Todo_App/1.0",

            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2
        ]
    );


    $response =
        curl_exec($curl);


    curl_close($curl);


    /*
    | Process API response
    */
    if (
        $response !== false &&
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


            /*
            | IP based timezone
            */
            $timezone =
                trim(
                    $data["timezone"] ?? ""
                );
        }
    }


    return [
        "city" =>
            $city,

        "state" =>
            $state,

        "country" =>
            $country,

        "timezone" =>
            $timezone
    ];
}


/*
|--------------------------------------------------------------------------
| Validate Timezone
|--------------------------------------------------------------------------
*/

function isValidTimezone($timezone)
{
    if (
        empty($timezone)
    ) {
        return false;
    }

    return in_array(
        $timezone,
        DateTimeZone::listIdentifiers(),
        true
    );
}


/*
|--------------------------------------------------------------------------
| Get Login Date/Time In User Timezone
|--------------------------------------------------------------------------
*/

function getUserDateTime($timezone)
{
    /*
    | If browser timezone is valid
    */
    if (
        isValidTimezone($timezone)
    ) {

        try {

            $date = new DateTime(
                "now",
                new DateTimeZone($timezone)
            );

            return $date->format(
                "Y-m-d H:i:s"
            );

        } catch (
            Exception $e
        ) {
            // Continue to fallback
        }
    }


    /*
    | Fallback to India timezone
    |
    | This is only used if the browser timezone
    | and IP timezone cannot be detected.
    */
    $date = new DateTime(
        "now",
        new DateTimeZone("Asia/Kolkata")
    );


    return $date->format(
        "Y-m-d H:i:s"
    );
}


/*
|--------------------------------------------------------------------------
| Save Login Attempt
|--------------------------------------------------------------------------
|
| Saves:
|
| emailaddress
| ipaddress
| latlang
| location
| timezone
| last_attempted_time
| added_date
| status
|
|--------------------------------------------------------------------------
*/

function saveLoginLog(
    $conn,
    $email,
    $ipaddress,
    $latlang,
    $location,
    $timezone,
    $status
) {

    /*
    | Generate local time based on user's timezone
    */
    $loginDateTime =
        getUserDateTime(
            $timezone
        );


    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    |
    | The database table must contain:
    |
    | timezone
    | added_date
    |
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO login_logs
        (
            emailaddress,
            ipaddress,
            latlang,
            location,
            timezone,
            last_attempted_time,
            added_date,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    if (!$stmt) {

        error_log(
            "Login log prepare failed: " .
            mysqli_error($conn)
        );

        return;
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ssssssss",
        $email,
        $ipaddress,
        $latlang,
        $location,
        $timezone,
        $loginDateTime,
        $loginDateTime,
        $status
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        error_log(
            "Login log insert failed: " .
            mysqli_stmt_error($stmt)
        );
    }


    mysqli_stmt_close(
        $stmt
    );
}


/*
|--------------------------------------------------------------------------
| Already Logged In
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
    | CURRENT PUBLIC IP
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


    /*
    |--------------------------------------------------------------------------
    | Browser Timezone
    |--------------------------------------------------------------------------
    |
    | JavaScript sends something like:
    |
    | Asia/Kolkata
    | America/New_York
    | Europe/London
    |
    |--------------------------------------------------------------------------
    */

    $browserTimezone =
        trim(
            $_POST["timezone"] ?? ""
        );


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
        $ipLocation["city"];


    $state =
        $ipLocation["state"];


    $country =
        $ipLocation["country"];


    $ipTimezone =
        $ipLocation["timezone"];


    /*
    |--------------------------------------------------------------------------
    | Choose User Timezone
    |--------------------------------------------------------------------------
    |
    | Priority:
    |
    | 1. Browser timezone
    | 2. IP timezone
    | 3. Asia/Kolkata fallback
    |
    |--------------------------------------------------------------------------
    */

    if (
        isValidTimezone(
            $browserTimezone
        )
    ) {

        $userTimezone =
            $browserTimezone;

    } elseif (
        isValidTimezone(
            $ipTimezone
        )
    ) {

        $userTimezone =
            $ipTimezone;

    } else {

        $userTimezone =
            "Asia/Kolkata";
    }


    /*
    |--------------------------------------------------------------------------
    | GPS Validation
    |--------------------------------------------------------------------------
    */

    $hasBrowserLocation =
        (
            $latitude !== "" &&
            $longitude !== "" &&
            is_numeric($latitude) &&
            is_numeric($longitude)
        );


    /*
    |--------------------------------------------------------------------------
    | Save GPS
    |--------------------------------------------------------------------------
    */

    if (
        $hasBrowserLocation
    ) {

        $latlang =
            $latitude .
            ", " .
            $longitude;

    } else {

        $latlang =
            "Not Available";
    }


    /*
    |--------------------------------------------------------------------------
    | Build City, State, Country
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
    | Validate Email/Password
    |--------------------------------------------------------------------------
    */

    if (
        $email === "" ||
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
            $userTimezone,
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
            $userTimezone,
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
                $userTimezone,
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
                $result &&
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
                    | SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    saveLoginLog(
                        $conn,
                        $email,
                        $ipaddress,
                        $latlang,
                        $location,
                        $userTimezone,
                        "SUCCESS"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | New Session
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(
                        true
                    );


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

                    /*
                    |--------------------------------------------------------------------------
                    | WRONG PASSWORD
                    |--------------------------------------------------------------------------
                    */

                    $error =
                        "Invalid email or password.";


                    saveLoginLog(
                        $conn,
                        $email,
                        $ipaddress,
                        $latlang,
                        $location,
                        $userTimezone,
                        "FAILED"
                    );
                }


            } else {

                /*
                |--------------------------------------------------------------------------
                | USER NOT FOUND
                |--------------------------------------------------------------------------
                */

                $error =
                    "Invalid email or password.";


                saveLoginLog(
                    $conn,
                    $email,
                    $ipaddress,
                    $latlang,
                    $location,
                    $userTimezone,
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


            <!-- Browser GPS -->

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


            <!-- Browser Timezone -->

            <input
                type="hidden"
                name="timezone"
                id="timezone"
            >


            <!-- Email -->

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


            <!-- Password -->

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


            <!-- Remember / Forgot -->

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


            <!-- Login -->

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
| Get Browser Timezone
|--------------------------------------------------------------------------
|
| Example:
|
| Asia/Kolkata
| America/New_York
| Europe/London
|
|--------------------------------------------------------------------------
*/

function getBrowserTimezone()
{

    try {

        const timezone =
            Intl.DateTimeFormat()
                .resolvedOptions()
                .timeZone;


        if (
            timezone
        ) {

            document.getElementById(
                "timezone"
            ).value =
                timezone;


            console.log(
                "Browser Timezone:",
                timezone
            );

            return;
        }

    } catch (
        error
    ) {

        console.log(
            "Timezone detection failed."
        );
    }


    /*
    | Fallback
    */
    document.getElementById(
        "timezone"
    ).value =
        "";
}


/*
|--------------------------------------------------------------------------
| Request Browser GPS
|--------------------------------------------------------------------------
*/

function requestBrowserLocation()
{

    /*
    | Get timezone immediately
    */
    getBrowserTimezone();


    /*
    | Browser doesn't support GPS
    */
    if (
        !navigator.geolocation
    ) {

        console.log(
            "Geolocation is not supported."
        );


        locationCollected =
            true;


        return;
    }


    /*
    | Request GPS
    */
    navigator.geolocation.getCurrentPosition(

        function(position)
        {

            const latitude =
                position.coords.latitude;


            const longitude =
                position.coords.longitude;


            /*
            | Save latitude
            */
            document.getElementById(
                "latitude"
            ).value =
                latitude;


            /*
            | Save longitude
            */
            document.getElementById(
                "longitude"
            ).value =
                longitude;


            console.log(
                "GPS Latitude:",
                latitude
            );


            console.log(
                "GPS Longitude:",
                longitude
            );


            console.log(
                "Browser location permission granted."
            );


            locationCollected =
                true;
        },


        function(error)
        {

            console.log(
                "Browser location unavailable."
            );


            console.log(
                "Reason:",
                error.message
            );


            /*
            | Login continues.
            |
            | PHP will still save:
            |
            | Public IP
            | IP location
            | Browser timezone
            |
            */
            locationCollected =
                true;
        },


        {
            enableHighAccuracy:
                true,

            timeout:
                10000,

            maximumAge:
                0
        }

    );
}


/*
|--------------------------------------------------------------------------
| Start GPS + Timezone Detection
|--------------------------------------------------------------------------
*/

requestBrowserLocation();


/*
|--------------------------------------------------------------------------
| Submit Login
|--------------------------------------------------------------------------
|
| Wait for GPS request.
|
|--------------------------------------------------------------------------
*/

loginForm.addEventListener(
    "submit",
    function(event)
    {

        if (
            !locationCollected
        ) {

            event.preventDefault();


            console.log(
                "Waiting for location..."
            );


            const waitForLocation =
                setInterval(
                    function()
                    {

                        if (
                            locationCollected
                        ) {

                            clearInterval(
                                waitForLocation
                            );


                            console.log(
                                "Submitting login..."
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

