<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin user ─────────────────────────────────────────────────────────
        $this->db->table('users')->insert([
            'name'       => 'Admin',
            'email'      => 'admin@mti.com',
            'password'   => password_hash('admin123', PASSWORD_DEFAULT),
            'role'       => 'admin',
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // ─── Default settings ────────────────────────────────────────────────────
        $settings = [
            ['key' => 'company_name',         'value' => 'Your Company'],
            ['key' => 'work_start_time',       'value' => '09:00'],
            ['key' => 'work_end_time',         'value' => '18:00'],
            ['key' => 'default_geofence_radius', 'value' => '50'],
        ];
        foreach ($settings as $s) {
            $s['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('settings')->insert($s);
        }

        // ─── Demo employees ──────────────────────────────────────────────────────
        $employees = [
            ['employee_code' => 'EMP0001', 'name' => 'Rahul Sharma',  'department' => 'IT',      'designation' => 'Developer'],
            ['employee_code' => 'EMP0002', 'name' => 'Priya Patel',   'department' => 'HR',      'designation' => 'HR Manager'],
            ['employee_code' => 'EMP0003', 'name' => 'Amit Verma',    'department' => 'Sales',   'designation' => 'Sales Lead'],
            ['employee_code' => 'EMP0004', 'name' => 'Sneha Joshi',   'department' => 'Finance', 'designation' => 'Accountant'],
            ['employee_code' => 'EMP0005', 'name' => 'Karan Mehta',   'department' => 'IT',      'designation' => 'QA Engineer'],
        ];
        foreach ($employees as $e) {
            $e['is_active']  = 1;
            $e['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('employees')->insert($e);
        }

        // ─── Demo QR locations ───────────────────────────────────────────────────
        $locations = [
            ['location_name' => 'Main Gate',      'latitude' => 23.0225, 'longitude' => 72.5714, 'geofence_radius' => 50],
            ['location_name' => 'Office Floor 1', 'latitude' => 23.0230, 'longitude' => 72.5720, 'geofence_radius' => 30],
        ];
        foreach ($locations as $l) {
            $l['token']      = bin2hex(random_bytes(16));
            $l['is_active']  = 1;
            $l['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('qr_tokens')->insert($l);
        }
    }
}
