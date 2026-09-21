<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Register, forgot password, reset password and logout.
 * (Login itself stays in AuthController.)
 */
class AccountController extends BaseController
{
    /* =====================================================================
       REGISTER   GET|POST /register
    ===================================================================== */

    public function register()
    {
        $error = '';
        $name  = '';
        $email = '';

        if ($this->request->is('post')) {
            $name            = trim((string) ($this->request->getPost('name') ?? ''));
            $email           = trim((string) ($this->request->getPost('email') ?? ''));
            $password        = (string) ($this->request->getPost('password') ?? '');
            $confirmPassword = (string) ($this->request->getPost('confirm_password') ?? '');
            $terms           = $this->request->getPost('terms') !== null;

            if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
                $error = 'Please fill in all fields.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } elseif (! $terms) {
                $error = 'Please accept the Terms and Conditions.';
            } else {
                $users = new UserModel();

                if ($users->where('email', $email)->first()) {
                    $error = 'An account with this email already exists.';
                } else {
                    $created = $users->insert([
                        'name'     => $name,
                        'email'    => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ]);

                    if ($created) {
                        return redirect()
                            ->to('/login')
                            ->with('registered', 'Account created successfully. Please login.');
                    }

                    $error = 'Registration failed. Please try again.';
                }
            }
        }

        return view('auth/register', [
            'error' => $error,
            'name'  => $name,
            'email' => $email,
        ]);
    }

    /* =====================================================================
       FORGOT PASSWORD   GET|POST /forgot-password
    ===================================================================== */

    public function forgotPassword()
    {
        $error     = '';
        $success   = '';
        $resetLink = '';
        $email     = '';

        if ($this->request->is('post')) {
            $email = trim((string) ($this->request->getPost('email') ?? ''));

            if ($email === '') {
                $error = 'Please enter your email address.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $users = new UserModel();
                $user  = $users->where('email', $email)->first();

                if (! $user) {
                    $error = 'No account was found with this email address.';
                } else {
                    $token = bin2hex(random_bytes(32));

                    $saved = $users->update((int) $user['id'], [
                        'reset_token'        => $token,
                        'reset_token_expiry' => date('Y-m-d H:i:s', time() + 3600), // 1 hour
                    ]);

                    if ($saved) {
                        $resetLink = site_url('reset-password') . '?token=' . urlencode($token);
                        $success   = 'Reset link created successfully.';
                    } else {
                        $error = 'Unable to save reset request.';
                    }
                }
            }
        }

        return view('auth/forgot_password', [
            'error'      => $error,
            'success'    => $success,
            'reset_link' => $resetLink,
            'email'      => $email,
        ]);
    }

    /* =====================================================================
       RESET PASSWORD   GET|POST /reset-password?token=...
    ===================================================================== */

    public function resetPassword()
    {
        $error   = '';
        $success = '';
        $token   = trim((string) ($this->request->getGet('token') ?? $this->request->getPost('token') ?? ''));
        $users   = new UserModel();
        $user    = null;

        // ---- check the reset token --------------------------------------
        if ($token === '') {
            $error = 'Invalid password reset link.';
        } else {
            $user = $users
                ->where('reset_token', $token)
                ->where('reset_token_expiry IS NOT NULL', null, false)
                ->where('reset_token_expiry >', date('Y-m-d H:i:s'))
                ->first();

            if (! $user) {
                $error = 'This password reset link is invalid or expired.';
            }
        }

        // ---- save the new password --------------------------------------
        if ($error === '' && $this->request->is('post')) {
            $password        = (string) ($this->request->getPost('password') ?? '');
            $confirmPassword = (string) ($this->request->getPost('confirm_password') ?? '');

            if ($password === '' || $confirmPassword === '') {
                $error = 'Please fill in all fields.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                $updated = $users->update((int) $user['id'], [
                    'password'           => password_hash($password, PASSWORD_DEFAULT),
                    'reset_token'        => null,
                    'reset_token_expiry' => null,
                ]);

                if ($updated) {
                    return redirect()->to('/login');
                }

                $error = 'Unable to update password.';
            }
        }

        return view('auth/reset_password', [
            'error'   => $error,
            'success' => $success,
            'token'   => $token,
        ]);
    }

    /* =====================================================================
       LOGOUT   GET /logout
    ===================================================================== */

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
