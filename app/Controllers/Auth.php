<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('admin_logged_in')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    /**
     * Old/bookmarked URL — send staff to the public employee gateway.
     */
    public function employeeLoginRedirect()
    {
        return redirect()->to('/login');
    }

    public function adminLogin()
    {
        if (session()->get('admin_logged_in')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/admin_login');
    }

    public function adminLoginPost()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->to('/admin/login')->withInput()->with('errors', $this->validator->getErrors());
        }

        $model = new UserModel();
        $user  = $model->findByEmail($this->request->getPost('email'));

        if (!$user || !password_verify($this->request->getPost('password'), $user['password'])) {
            return redirect()->to('/admin/login')->withInput()->with(
                'error',
                'Invalid email or password.'
            );
        }

        if ($user['role'] !== 'admin') {
            return redirect()->to('/admin/login')->withInput()->with(
                'error',
                'Only administrator accounts can access the web dashboard.'
            );
        }

        session()->set([
            'admin_logged_in' => true,
            'admin_id'        => $user['id'],
            'admin_name'      => $user['name'],
            'admin_email'     => $user['email'],
            'admin_role'      => $user['role'],
        ]);

        $model->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/admin/login')->with('success', 'You have been logged out.');
    }
}
