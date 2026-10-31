<?php

namespace App\Controllers;

class Landing extends BaseController
{
    public function index()
    {
        if (filter_var(env('app.demoMode', false), FILTER_VALIDATE_BOOLEAN)) {
            return redirect()->to('/admin/login');
        }

        return redirect()->to('/employee');
    }

    public function privacy()
    {
        return view('landing/privacy');
    }
}
