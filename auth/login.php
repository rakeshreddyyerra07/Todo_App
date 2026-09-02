
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
        | Use first valid forwarded IP
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
| For private/local IPs, ipify is used to obtain the
| current public IP address.
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
|
|--------------------------------------------------------------------------
*/

function getIPLocation($ip)
{
    $city = "";
    $state = "";
    $country = "";


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
| Database columns used:
|
| emailaddress
| ipaddress
| latlang
| location
| last_attempted_time
| status
|
| The database column "addedDate" is NOT included because
| MySQL automatically fills it using CURRENT_TIMESTAMP.
|
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
    | Current server date/time
    */
    $loginDateTime =
        date("Y-m-d H:i:s");


    /*
    |--------------------------------------------------------------------------
    | Insert Login Log
    |--------------------------------------------------------------------------
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


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    /*
    | Prepare failed
    */
    if (!$stmt) {

        error_log(
            "Login log prepare failed: " .
            mysqli_error($conn)
        );

        return;
    }


    /*
    | Bind values
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
    | Execute insert
    */
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


    /*
    | Close statement
    */
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
    | Current Public IP
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


        /*
        | Log failed attempt
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
        | Log failed attempt
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


        /*
        | Database query preparation failed
        */
        if (!$stmt) {

            $error =
                "Something went wrong. Please try again.";


            /*
            | Log failed attempt
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
            | Bind email
            */
            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            /*
            | Execute query
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


                    /*
                    | Log failed attempt
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
                | USER NOT FOUND
                |--------------------------------------------------------------------------
                */

                $error =
                    "Invalid email or password.";


                /*
                | Log failed attempt
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


            /*
            | Close user query
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
| Location Status
|--------------------------------------------------------------------------
*/

let locationCollected =
    false;


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
            |
            | PHP will still save:
            |
            | Public IP
            | IP location
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
| Start GPS Detection
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
```
