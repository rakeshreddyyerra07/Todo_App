<?php

namespace App\Controllers;

class Home extends BaseController
{
    /**
     * Same behaviour as the original index.php:
     * logged in -> task board, otherwise -> login page.
     */
    public function index()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/tasks');
        }

        return redirect()->to('/login');
    }

    public function test()
    {
        return 'Todo App CI4 is working!';
    }
}
