
<?php

/*
|--------------------------------------------------------------------------
| CI4 Login View
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Authentication/session/database processing is handled by:
|
| App\Controllers\AuthController
| App\Models\UserModel
| App\Models\LoginLogModel
|
| This file contains the Login UI and browser-side functionality.
|
|--------------------------------------------------------------------------
*/

$error = $error ?? "";
$email = $email ?? "";

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


    <!--
    |--------------------------------------------------------------------------
    | CI4 Asset Path
    |--------------------------------------------------------------------------
    -->

    <link
        rel="stylesheet"
        href="<?= base_url('assets/style.css') ?>"
    >


    <!--
    |--------------------------------------------------------------------------
    | Password Eye CSS
    |--------------------------------------------------------------------------
    -->

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


        <!--
        |--------------------------------------------------------------------------
        | Login Header
        |--------------------------------------------------------------------------
        -->

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


        <!--
        |--------------------------------------------------------------------------
        | Registration Success Message
        |--------------------------------------------------------------------------
        |
        | CI4 equivalent of:
        |
        | $_GET["registered"]
        |
        |--------------------------------------------------------------------------
        -->

        <?php if (session()->getFlashdata('registered')): ?>

            <div class="alert alert-success">

                <?= esc(session()->getFlashdata('registered')) ?>

            </div>

        <?php endif; ?>


        <!--
        |--------------------------------------------------------------------------
        | Login Error
        |--------------------------------------------------------------------------
        -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <?= esc($error) ?>

            </div>

        <?php endif; ?>


        <!--
        |--------------------------------------------------------------------------
        | Login Form
        |--------------------------------------------------------------------------
        |
        | CI4 sends POST request to:
        |
        | /login
        |
        | AuthController::authenticate()
        |
        |--------------------------------------------------------------------------
        -->

        <form
            method="POST"
            action="<?= site_url('login') ?>"
            id="loginForm"
        >

            <?= csrf_field() ?>


            <!--
            |--------------------------------------------------------------------------
            | Public IP
            |--------------------------------------------------------------------------
            -->

            <input
                type="hidden"
                name="public_ip"
                id="public_ip"
                value=""
            >


            <!--
            |--------------------------------------------------------------------------
            | Browser GPS Latitude
            |--------------------------------------------------------------------------
            -->

            <input
                type="hidden"
                name="latitude"
                id="latitude"
                value=""
            >


            <!--
            |--------------------------------------------------------------------------
            | Browser GPS Longitude
            |--------------------------------------------------------------------------
            -->

            <input
                type="hidden"
                name="longitude"
                id="longitude"
                value=""
            >


            <!--
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
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
                    value="<?= esc($email) ?>"
                    required
                >

            </div>


            <!--
            |--------------------------------------------------------------------------
            | Password
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Remember Me / Forgot Password
            |--------------------------------------------------------------------------
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


                <!--
                |--------------------------------------------------------------------------
                | Forgot Password
                |--------------------------------------------------------------------------
                |
                | This will be connected to the CI4 forgot-password
                | route when we migrate that module.
                |
                |--------------------------------------------------------------------------
                -->

                <a
                    href="<?= site_url('forgot-password') ?>"
                >
                    Forgot Password?
                </a>

            </div>


            <!--
            |--------------------------------------------------------------------------
            | Login Button
            |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Create Account
        |--------------------------------------------------------------------------
        -->

        <div class="auth-link">

            Don't have an account?

            <a
                href="<?= site_url('register') ?>"
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
|
| Same functionality as the original application.
|
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
| Same browser-side IP collection as the original application.
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

        }


        /*
        |--------------------------------------------------------------------------
        | Mark IP collection complete
        |--------------------------------------------------------------------------
        */

        publicIPCollected =
            true;


    } catch (error) {

        console.log(
            "Public IP unavailable:",
            error.message
        );


        /*
        |--------------------------------------------------------------------------
        | Server-side controller has fallback
        |--------------------------------------------------------------------------
        */

        publicIPCollected =
            true;
    }
}


/*
|--------------------------------------------------------------------------
| Request Browser GPS
|--------------------------------------------------------------------------
|
| Same GPS functionality as the original application.
|
|--------------------------------------------------------------------------
*/

function requestBrowserLocation()
{

    /*
    |--------------------------------------------------------------------------
    | Browser doesn't support GPS
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | Request GPS
    |--------------------------------------------------------------------------
    */

    navigator.geolocation.getCurrentPosition(

        function(position)
        {

            const latitude =
                position.coords.latitude;


            const longitude =
                position.coords.longitude;


            /*
            |--------------------------------------------------------------------------
            | Save latitude
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                "latitude"
            ).value =
                latitude;


            /*
            |--------------------------------------------------------------------------
            | Save longitude
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Login continues even when location isn't available.
            |--------------------------------------------------------------------------
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
| Wait for browser IP/GPS collection before submitting.
|
| The actual authentication is performed by:
|
| AuthController::authenticate()
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

