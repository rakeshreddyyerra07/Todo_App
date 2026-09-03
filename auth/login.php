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
|
| This is only a fallback.
| On cloud hosting, REMOTE_ADDR may be the hosting server IP.
|
|--------------------------------------------------------------------------
*/

function getClientIP()
{
    /*
    | Cloudflare / proxy IP
    */
    if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {

        $ip = trim($_SERVER["HTTP_CF_CONNECTING_IP"]);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }


    /*
    | Client IP
    */
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {

        $ip = trim($_SERVER["HTTP_CLIENT_IP"]);

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }


    /*
    | Forwarded IP
    */
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {

        $forwardedIPs =
            explode(
                ",",
                $_SERVER["HTTP_X_FORWARDED_FOR"]
            );

        foreach ($forwardedIPs as $forwardedIP) {

            $forwardedIP =
                trim($forwardedIP);

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
    | Real server address
    */
    return trim(
        $_SERVER["REMOTE_ADDR"] ?? "Unknown"
    );
}


/*
|--------------------------------------------------------------------------
| Get Public IP
|--------------------------------------------------------------------------
|
| Browser sends the public IP using:
|
| https://api.ipify.org
|
| Server-side ipify is used as a fallback.
|
|--------------------------------------------------------------------------
*/

function getPublicIP()
{
    $ip = getClientIP();


    /*
    | Check whether server-detected IP is public
    */
    $isPublicIP =
        filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        );


    /*
    | If already public, return it
    */
    if ($isPublicIP !== false) {

        return $ip;
    }


    /*
    |--------------------------------------------------------------------------
    | Server-side ipify fallback
    |--------------------------------------------------------------------------
    */

    if (!function_exists("curl_init")) {

        return "Not Available";
    }


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
                2,

            CURLOPT_USERAGENT =>
                "Todo_App/1.0"
        ]
    );


    $response =
        curl_exec($curl);


    curl_close($curl);


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
            is_array($data) &&
            !empty($data["ip"])
        ) {

            $publicIP =
                trim(
                    $data["ip"]
                );


            $validPublicIP =
                filter_var(
                    $publicIP,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE |
                    FILTER_FLAG_NO_RES_RANGE
                );


            if (
                $validPublicIP !== false
            ) {

                return $publicIP;
            }
        }
    }


    return "Not Available";
}


/*
|--------------------------------------------------------------------------
| Get IP Location
|--------------------------------------------------------------------------
|
| Uses public IP.
|
|--------------------------------------------------------------------------
*/

function getIPLocation($ip)
{
    $result = [
        "city" =>
            "",

        "state" =>
            "",

        "country" =>
            ""
    ];


    /*
    | Invalid IP
    */
    if (
        empty($ip) ||
        $ip === "Unknown" ||
        $ip === "Not Available"
    ) {

        return $result;
    }


    /*
    | Validate IP
    */
    if (
        filter_var(
            $ip,
            FILTER_VALIDATE_IP
        ) === false
    ) {

        return $result;
    }


    /*
    | IP API
    */
    $url =
        "https://ipapi.co/" .
        urlencode($ip) .
        "/json/";


    if (!function_exists("curl_init")) {

        return $result;
    }


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

            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2,

            CURLOPT_USERAGENT =>
                "Todo_App/1.0"
        ]
    );


    $response =
        curl_exec($curl);


    curl_close($curl);


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

            $result["city"] =
                trim(
                    $data["city"] ?? ""
                );


            $result["state"] =
                trim(
                    $data["region"] ?? ""
                );


            $result["country"] =
                trim(
                    $data["country_name"] ?? ""
                );
        }
    }


    return $result;
}


/*
|--------------------------------------------------------------------------
| Reverse GPS Location
|--------------------------------------------------------------------------
|
| GPS is more accurate than IP location.
|
| Uses OpenStreetMap Nominatim.
|
|--------------------------------------------------------------------------
*/

function getGPSLocation(
    $latitude,
    $longitude
) {

    $result = [
        "city" =>
            "",

        "state" =>
            "",

        "country" =>
            ""
    ];


    /*
    | Validate GPS
    */
    if (
        $latitude === "" ||
        $longitude === "" ||
        !is_numeric($latitude) ||
        !is_numeric($longitude)
    ) {

        return $result;
    }


    /*
    | Validate ranges
    */
    if (
        $latitude < -90 ||
        $latitude > 90 ||
        $longitude < -180 ||
        $longitude > 180
    ) {

        return $result;
    }


    /*
    | Nominatim reverse geocoding
    */
    $url =
        "https://nominatim.openstreetmap.org/reverse" .
        "?format=json" .
        "&lat=" .
        urlencode($latitude) .
        "&lon=" .
        urlencode($longitude) .
        "&zoom=10" .
        "&addressdetails=1";


    if (!function_exists("curl_init")) {

        return $result;
    }


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

            CURLOPT_SSL_VERIFYPEER =>
                true,

            CURLOPT_SSL_VERIFYHOST =>
                2,

            CURLOPT_USERAGENT =>
                "Todo_App/1.0"
        ]
    );


    $response =
        curl_exec($curl);


    curl_close($curl);


    if (
        $response === false ||
        empty($response)
    ) {

        return $result;
    }


    $data =
        json_decode(
            $response,
            true
        );


    if (
        !is_array($data)
    ) {

        return $result;
    }


    $address =
        $data["address"] ?? [];


    /*
    | City
    |
    | Depending on location, Nominatim may return:
    |
    | city
    | town
    | village
    | municipality
    |
    */
    $city =
        $address["city"]
        ?? $address["town"]
        ?? $address["village"]
        ?? $address["municipality"]
        ?? "";


    /*
    | State
    */
    $state =
        $address["state"]
        ?? $address["state_district"]
        ?? "";


    /*
    | Country
    */
    $country =
        $address["country"]
        ?? "";


    $result["city"] =
        trim($city);


    $result["state"] =
        trim($state);


    $result["country"] =
        trim($country);


    return $result;
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

    /*
    | Current date/time
    */
    $loginDateTime =
        date("Y-m-d H:i:s");


    /*
    | Insert query
    */
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


    /*
    | Prepare
    */
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


    /*
    | Bind
    */
    mysqli_stmt_bind_param(
        $stmt,
        "ssssss",
        $email,
        $ipaddress,
        $latlang,
        $location,
        $loginDateTime,
        $status
    );


    /*
    | Execute
    */
    if (
        !mysqli_stmt_execute($stmt)
    ) {

        error_log(
            "Login log insert failed: " .
            mysqli_stmt_error($stmt)
        );
    }


    /*
    | Close
    */
    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Already Logged In
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION["user_id"])
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
    | Public IP
    |--------------------------------------------------------------------------
    |
    | First use browser-provided public IP.
    | If unavailable, use server-side fallback.
    |
    |--------------------------------------------------------------------------
    */

    $browserIP =
        trim(
            $_POST["public_ip"] ?? ""
        );


    /*
    | Validate browser IP
    */
    $validBrowserIP =
        filter_var(
            $browserIP,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        );


    if (
        $validBrowserIP !== false
    ) {

        $ipaddress =
            $browserIP;

    } else {

        $ipaddress =
            getPublicIP();
    }


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
    | GPS Validation
    |--------------------------------------------------------------------------
    */

    $hasBrowserLocation =
        (
            $latitude !== "" &&
            $longitude !== "" &&
            is_numeric($latitude) &&
            is_numeric($longitude) &&
            $latitude >= -90 &&
            $latitude <= 90 &&
            $longitude >= -180 &&
            $longitude <= 180
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


    /*
    |--------------------------------------------------------------------------
    | Get GPS Location
    |--------------------------------------------------------------------------
    |
    | GPS location is preferred because it is usually
    | much more accurate than IP-based location.
    |
    |--------------------------------------------------------------------------
    */

    if (
        $hasBrowserLocation
    ) {

        $gpsLocation =
            getGPSLocation(
                $latitude,
                $longitude
            );


        /*
        | Replace IP location with GPS location
        | when available.
        */
        if (
            $gpsLocation["city"] !== ""
        ) {

            $city =
                $gpsLocation["city"];
        }


        if (
            $gpsLocation["state"] !== ""
        ) {

            $state =
                $gpsLocation["state"];
        }


        if (
            $gpsLocation["country"] !== ""
        ) {

            $country =
                $gpsLocation["country"];
        }
    }


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
    | Validate Email / Password
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
                    password,
                    role
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
            | Bind email
            */
            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            /*
            | Execute
            */
            mysqli_stmt_execute(
                $stmt
            );


            /*
            | Get result
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


                    $_SESSION["user_id"] =
                        $user["id"];


                    $_SESSION["user_name"] =
                        $user["name"];


                    $_SESSION["user_email"] =
                        $user["email"];


                    /*
                    |--------------------------------------------------------------------------
                    | USER ROLE
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION["user_role"] =
                        $user["role"];


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
                    | Wrong Password
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
                        "FAILED"
                    );
                }


            } else {

                /*
                |--------------------------------------------------------------------------
                | User Not Found
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
                    "FAILED"
                );
            }


            /*
            | Close query
            */
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

            <!-- Public IP -->

            <input
                type="hidden"
                name="public_ip"
                id="public_ip"
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
| Location Collection Status
|--------------------------------------------------------------------------
*/

let locationCollected =
    false;


/*
|--------------------------------------------------------------------------
| Public IP Collection Status
|--------------------------------------------------------------------------
*/

let publicIPCollected =
    false;


/*
|--------------------------------------------------------------------------
| Get User Public IP
|--------------------------------------------------------------------------
|
| This gets the user's actual public IP such as:
|
| 49.43.226.170
|
| instead of the hosting server IP.
|
|--------------------------------------------------------------------------
*/

async function getUserPublicIP()
{

    try {

        const response =
            await fetch(
                "https://api.ipify.org?format=json",
                {
                    cache: "no-store"
                }
            );


        if (
            !response.ok
        ) {

            throw new Error(
                "Unable to get public IP."
            );
        }


        const data =
            await response.json();


        if (
            data &&
            data.ip
        ) {

            document.getElementById(
                "public_ip"
            ).value =
                data.ip;


            console.log(
                "User Public IP:",
                data.ip
            );


            publicIPCollected =
                true;

        } else {

            publicIPCollected =
                true;
        }


    } catch (error) {

        console.log(
            "Public IP unavailable:",
            error.message
        );


        publicIPCollected =
            true;
    }
}


/*
|--------------------------------------------------------------------------
| Request Browser GPS
|--------------------------------------------------------------------------
*/

function requestBrowserLocation()
{

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
| Start IP + GPS Detection
|--------------------------------------------------------------------------
*/

getUserPublicIP();

requestBrowserLocation();


/*
|--------------------------------------------------------------------------
| Submit Login
|--------------------------------------------------------------------------
|
| Wait until IP and GPS detection has completed.
|
|--------------------------------------------------------------------------
*/

loginForm.addEventListener(
    "submit",
    function(event)
    {

        if (
            !locationCollected ||
            !publicIPCollected
        ) {

            event.preventDefault();


            console.log(
                "Waiting for IP/GPS information..."
            );


            const waitForLocation =
                setInterval(
                    function()
                    {

                        if (
                            locationCollected &&
                            publicIPCollected
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