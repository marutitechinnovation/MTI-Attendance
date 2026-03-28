<?php

namespace App\Controllers\Api;

use App\Models\EmployeeModel;
use App\Libraries\JwtHelper;
use CodeIgniter\RESTful\ResourceController;

class AuthApi extends ResourceController
{
    protected $format = 'json';

    /**
     * POST /api/auth/login
     * Body: { "username": "rahul.sharma", "password": "secret123" }
     */
    public function login()
    {
        $body = $this->request->getJSON(true);

        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($username) || empty($password)) {
            return $this->failValidationErrors('username and password are required.');
        }

        $model = new EmployeeModel();
        $emp   = $model->where('username', $username)
                       ->where('is_active', 1)
                       ->first();

        if (!$emp) {
            return $this->fail('Invalid username or password.', 401);
        }

        $storedPassword = $emp['password'] ?? null;

        if (empty($storedPassword)) {
            return $this->fail('Account not activated. Contact admin to set your password.', 403);
        }

        // Check bcrypt first; fall back to plain-text for accounts not yet migrated
        $valid = password_verify($password, $storedPassword)
                 || (!str_starts_with($storedPassword, '$2y$') && $storedPassword === $password);

        if (!$valid) {
            return $this->fail('Invalid username or password.', 401);
        }

        $token = JwtHelper::encode([
            'sub'  => $emp['id'],
            'code' => $emp['employee_code'],
        ]);

        return $this->respond([
            'status'  => 'success',
            'message' => 'Login successful.',
            'token'   => $token,
            'data'    => [
                'id'            => $emp['id'],
                'employee_code' => $emp['employee_code'],
                'username'      => $emp['username'],
                'name'          => $emp['name'],
                'email'         => $emp['email'],
                'phone'         => $emp['phone'],
                'department'    => $emp['department'],
                'designation'   => $emp['designation'],
                'photo'         => $emp['photo'],
            ],
        ]);
    }

    /**
     * POST /api/auth/set-password  (Admin use — set/reset an employee password)
     * Body: { "employee_code": "EMP0001", "username": "rahul.sharma", "password": "secret123" }
     */
    public function setPassword()
    {
        $body = $this->request->getJSON(true);

        $code     = trim($body['employee_code'] ?? '');
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($code) || empty($username) || empty($password)) {
            return $this->failValidationErrors('employee_code, username, and password are required.');
        }

        if (strlen($password) < 6) {
            return $this->failValidationErrors('Password must be at least 6 characters.');
        }

        $model = new EmployeeModel();
        $emp   = $model->where('employee_code', $code)->first();

        if (!$emp) {
            return $this->failNotFound('Employee not found.');
        }

        $model->update($emp['id'], [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        return $this->respond([
            'status'  => 'success',
            'message' => 'Credentials updated successfully.',
        ]);
    }
}
