<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\LoginLogModel;

class AuthController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = session();
    }

    /*
    |--------------------------------------------------------------------------
    | Login Page
    |--------------------------------------------------------------------------
    */

    public function login()
    {
        /*
        | Already logged in
        */
        if ($this->session->has('user_id')) {
            return redirect()->to('/tasks');
        }

        return view('auth/login', [
            'error' => '',
            'email' => ''
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Login Processing
    |--------------------------------------------------------------------------
    */

    public function authenticate()
    {
        /*
        | Already logged in
        */
        if ($this->session->has('user_id')) {
            return redirect()->to('/tasks');
        }


        /*
        |--------------------------------------------------------------------------
        | Get Email
        |--------------------------------------------------------------------------
        */

        $email = trim(
            $this->request->getPost('email') ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | Get Password
        |--------------------------------------------------------------------------
        */

        $password =
            $this->request->getPost('password') ?? '';


        /*
        |--------------------------------------------------------------------------
        | Get Browser Public IP
        |--------------------------------------------------------------------------
        */

        $browserIP = trim(
            $this->request->getPost('public_ip') ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Browser IP
        |--------------------------------------------------------------------------
        */

        $validBrowserIP = filter_var(
            $browserIP,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        );


        if ($validBrowserIP !== false) {
            $ipaddress = $browserIP;
        } else {
            $ipaddress = $this->getPublicIP();
        }


        /*
        |--------------------------------------------------------------------------
        | Browser GPS
        |--------------------------------------------------------------------------
        */

        $latitude = trim(
            $this->request->getPost('latitude') ?? ''
        );

        $longitude = trim(
            $this->request->getPost('longitude') ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | GPS Validation
        |--------------------------------------------------------------------------
        */

        $hasBrowserLocation =
            $latitude !== '' &&
            $longitude !== '' &&
            is_numeric($latitude) &&
            is_numeric($longitude) &&
            $latitude >= -90 &&
            $latitude <= 90 &&
            $longitude >= -180 &&
            $longitude <= 180;


        /*
        |--------------------------------------------------------------------------
        | Save GPS
        |--------------------------------------------------------------------------
        */

        if ($hasBrowserLocation) {

            $latlang =
                $latitude . ', ' . $longitude;

        } else {

            $latlang =
                'Not Available';
        }


        /*
        |--------------------------------------------------------------------------
        | Get IP Location
        |--------------------------------------------------------------------------
        */

        $ipLocation =
            $this->getIPLocation($ipaddress);


        $city =
            $ipLocation['city'];

        $state =
            $ipLocation['state'];

        $country =
            $ipLocation['country'];


        /*
        |--------------------------------------------------------------------------
        | GPS Location
        |--------------------------------------------------------------------------
        */

        if ($hasBrowserLocation) {

            $gpsLocation =
                $this->getGPSLocation(
                    $latitude,
                    $longitude
                );


            /*
            | GPS location replaces IP location
            | when available.
            */

            if ($gpsLocation['city'] !== '') {
                $city =
                    $gpsLocation['city'];
            }

            if ($gpsLocation['state'] !== '') {
                $state =
                    $gpsLocation['state'];
            }

            if ($gpsLocation['country'] !== '') {
                $country =
                    $gpsLocation['country'];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Build Location
        |--------------------------------------------------------------------------
        */

        $locationParts = [];


        if ($city !== '') {
            $locationParts[] =
                $city;
        }


        if ($state !== '') {
            $locationParts[] =
                $state;
        }


        if ($country !== '') {
            $locationParts[] =
                $country;
        }


        if (count($locationParts) > 0) {

            $location =
                implode(
                    ', ',
                    $locationParts
                );

        } else {

            $location =
                'Not Available';
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Email / Password
        |--------------------------------------------------------------------------
        */

        if (
            $email === '' ||
            $password === ''
        ) {

            $error =
                'Please enter your email and password.';


            $this->saveLoginLog(
                $email,
                $ipaddress,
                $latlang,
                $location,
                'FAILED'
            );


            return view('auth/login', [
                'error' => $error,
                'email' => $email
            ]);
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                'Please enter a valid email address.';


            $this->saveLoginLog(
                $email,
                $ipaddress,
                $latlang,
                $location,
                'FAILED'
            );


            return view('auth/login', [
                'error' => $error,
                'email' => $email
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $userModel =
            new UserModel();


        $user =
            $userModel
                ->where('email', $email)
                ->first();


        /*
        |--------------------------------------------------------------------------
        | User Found
        |--------------------------------------------------------------------------
        */

        if ($user) {

            /*
            |--------------------------------------------------------------------------
            | Verify Password
            |--------------------------------------------------------------------------
            */

            if (
                password_verify(
                    $password,
                    $user['password']
                )
            ) {

                /*
                |--------------------------------------------------------------------------
                | SUCCESS LOGIN
                |--------------------------------------------------------------------------
                */

                $this->saveLoginLog(
                    $email,
                    $ipaddress,
                    $latlang,
                    $location,
                    'SUCCESS'
                );


                /*
                |--------------------------------------------------------------------------
                | Regenerate Session
                |--------------------------------------------------------------------------
                */

                $this->session->regenerate(true);


                /*
                |--------------------------------------------------------------------------
                | Save Session Data
                |--------------------------------------------------------------------------
                */

                $this->session->set([
                    'user_id' =>
                        $user['id'],

                    'user_name' =>
                        $user['name'],

                    'user_email' =>
                        $user['email'],

                    'user_role' =>
                        $user['role']
                ]);


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                return redirect()->to('/tasks');
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Invalid Login
        |--------------------------------------------------------------------------
        */

        $error =
            'Invalid email or password.';


        $this->saveLoginLog(
            $email,
            $ipaddress,
            $latlang,
            $location,
            'FAILED'
        );


        return view('auth/login', [
            'error' => $error,
            'email' => $email
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Client IP
    |--------------------------------------------------------------------------
    */

    private function getClientIP()
    {
        /*
        | Cloudflare
        */

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {

            $ip =
                trim(
                    $_SERVER['HTTP_CF_CONNECTING_IP']
                );


            if (
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP
                )
            ) {

                return $ip;
            }
        }


        /*
        | Client IP
        */

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

            $ip =
                trim(
                    $_SERVER['HTTP_CLIENT_IP']
                );


            if (
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP
                )
            ) {

                return $ip;
            }
        }


        /*
        | Forwarded IP
        */

        if (
            !empty(
                $_SERVER['HTTP_X_FORWARDED_FOR']
            )
        ) {

            $forwardedIPs =
                explode(
                    ',',
                    $_SERVER['HTTP_X_FORWARDED_FOR']
                );


            foreach (
                $forwardedIPs as $forwardedIP
            ) {

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
        | Server address
        */

        return trim(
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Public IP
    |--------------------------------------------------------------------------
    */

    private function getPublicIP()
    {
        $ip =
            $this->getClientIP();


        $isPublicIP =
            filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE |
                FILTER_FLAG_NO_RES_RANGE
            );


        if ($isPublicIP !== false) {
            return $ip;
        }


        /*
        | cURL unavailable
        */

        if (!function_exists('curl_init')) {
            return 'Not Available';
        }


        $curl =
            curl_init();


        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL =>
                    'https://api.ipify.org?format=json',

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_TIMEOUT =>
                    10,

                CURLOPT_SSL_VERIFYPEER =>
                    true,

                CURLOPT_SSL_VERIFYHOST =>
                    2,

                CURLOPT_USERAGENT =>
                    'Todo_App/1.0'
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
                !empty($data['ip'])
            ) {

                $publicIP =
                    trim(
                        $data['ip']
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


        return 'Not Available';
    }


    /*
    |--------------------------------------------------------------------------
    | Get IP Location
    |--------------------------------------------------------------------------
    */

    private function getIPLocation($ip)
    {
        $result = [
            'city' =>
                '',

            'state' =>
                '',

            'country' =>
                ''
        ];


        if (
            empty($ip) ||
            $ip === 'Unknown' ||
            $ip === 'Not Available'
        ) {

            return $result;
        }


        if (
            filter_var(
                $ip,
                FILTER_VALIDATE_IP
            ) === false
        ) {

            return $result;
        }


        if (!function_exists('curl_init')) {
            return $result;
        }


        $url =
            'https://ipapi.co/' .
            urlencode($ip) .
            '/json/';


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
                    'Todo_App/1.0'
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


            if (is_array($data)) {

                $result['city'] =
                    trim(
                        $data['city'] ?? ''
                    );


                $result['state'] =
                    trim(
                        $data['region'] ?? ''
                    );


                $result['country'] =
                    trim(
                        $data['country_name'] ?? ''
                    );
            }
        }


        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | GPS Reverse Location
    |--------------------------------------------------------------------------
    */

    private function getGPSLocation(
        $latitude,
        $longitude
    ) {

        $result = [
            'city' =>
                '',

            'state' =>
                '',

            'country' =>
                ''
        ];


        if (
            $latitude === '' ||
            $longitude === '' ||
            !is_numeric($latitude) ||
            !is_numeric($longitude)
        ) {

            return $result;
        }


        if (
            $latitude < -90 ||
            $latitude > 90 ||
            $longitude < -180 ||
            $longitude > 180
        ) {

            return $result;
        }


        if (!function_exists('curl_init')) {
            return $result;
        }


        $url =
            'https://nominatim.openstreetmap.org/reverse' .
            '?format=json' .
            '&lat=' .
            urlencode($latitude) .
            '&lon=' .
            urlencode($longitude) .
            '&zoom=10' .
            '&addressdetails=1';


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
                    'Todo_App/1.0'
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


        if (!is_array($data)) {
            return $result;
        }


        $address =
            $data['address'] ?? [];


        $city =
            $address['city']
            ?? $address['town']
            ?? $address['village']
            ?? $address['municipality']
            ?? '';


        $state =
            $address['state']
            ?? $address['state_district']
            ?? '';


        $country =
            $address['country']
            ?? '';


        $result['city'] =
            trim($city);


        $result['state'] =
            trim($state);


        $result['country'] =
            trim($country);


        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Save Login Log
    |--------------------------------------------------------------------------
    */

    private function saveLoginLog(
        $email,
        $ipaddress,
        $latlang,
        $location,
        $status
    ) {

        $loginLogModel =
            new LoginLogModel();


        $loginLogModel->insert([
            'emailaddress' =>
                $email,

            'ipaddress' =>
                $ipaddress,

            'latlang' =>
                $latlang,

            'location' =>
                $location,

            'last_attempted_time' =>
                date('Y-m-d H:i:s'),

            'status' =>
                $status
        ]);
    }
}