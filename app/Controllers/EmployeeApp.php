<?php

namespace App\Controllers;

class EmployeeApp extends BaseController
{
    public function index()
    {
        return view('employee/app', [
            'pageTitle' => 'Employee App',
        ]);
    }
}
