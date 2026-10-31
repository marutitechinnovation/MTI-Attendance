<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DemoSeeder extends Seeder
{
    private const PASSWORD = 'demo1234';

    public function run(): void
    {
        if (!filter_var(env('app.demoMode', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new \RuntimeException('DemoSeeder only runs when app.demoMode is enabled.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->disableForeignKeyChecks();
        foreach (['attendance', 'holidays', 'qr_tokens', 'employees', 'users'] as $table) {
            $this->db->table($table)->truncate();
        }
        $this->db->enableForeignKeyChecks();

        $this->db->table('users')->insert([
            'name'       => 'Demo Admin',
            'email'      => 'admin@demo.bitlence.com',
            'password'   => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'role'       => 'admin',
            'is_active'  => 1,
            'created_at' => $now,
        ]);

        $settings = [
            'company_name'            => 'Demo Company',
            'employee_app_name'       => 'Bitlence Attendance',
            'employee_app_short_name' => 'Bitlence Attendance',
            'work_start_time'         => '09:30',
            'work_end_time'           => '18:30',
        ];
        foreach ($settings as $key => $value) {
            $row = ['key' => $key, 'value' => $value, 'updated_at' => $now];
            $this->db->table('settings')->where('key', $key)->countAllResults() > 0
                ? $this->db->table('settings')->where('key', $key)->update($row)
                : $this->db->table('settings')->insert($row);
        }

        $staff = [
            ['Demo Employee', 'Operations', 'Team Member'],
            ['Aarav Mehta', 'IT', 'Software Developer'],
            ['Priya Patel', 'HR', 'HR Manager'],
            ['Rohan Desai', 'Sales', 'Sales Lead'],
            ['Sneha Joshi', 'Finance', 'Accountant'],
            ['Karan Shah', 'IT', 'QA Engineer'],
            ['Neha Trivedi', 'Marketing', 'Content Lead'],
            ['Vikram Rao', 'Operations', 'Site Supervisor'],
            ['Isha Nair', 'Sales', 'Account Executive'],
            ['Dev Kapoor', 'Finance', 'Payroll Executive'],
            ['Meera Iyer', 'HR', 'Recruiter'],
            ['Arjun Solanki', 'Operations', 'Store Keeper'],
        ];
        $employees = [];
        foreach ($staff as $i => [$name, $department, $designation]) {
            $employees[] = [
                'employee_code'             => sprintf('EMP%04d', $i + 1),
                'username'                  => $i === 0 ? 'demo.employee' : null,
                'password'                  => $i === 0 ? password_hash(self::PASSWORD, PASSWORD_BCRYPT) : null,
                'name'                      => $name,
                'email'                     => strtolower(explode(' ', $name)[0]) . '@demo.bitlence.com',
                'department'                => $department,
                'designation'               => $designation,
                'join_date'                 => date('Y-m-d', strtotime('-' . (90 + $i * 25) . ' days')),
                'is_active'                 => 1,
                'allow_anywhere_attendance' => $i === 0 ? 1 : 0,
                'created_at'                => $now,
            ];
        }
        $this->db->table('employees')->insertBatch($employees);

        $this->db->table('qr_tokens')->insertBatch([
            [
                'token' => bin2hex(random_bytes(16)), 'location_name' => 'Head Office',
                'latitude' => 23.0225, 'longitude' => 72.5714, 'geofence_radius' => 100, 'is_active' => 1,
                'qr_mode' => 'rotating', 'public_slug' => 'demo', 'last_token_rotated_at' => $now, 'created_at' => $now,
            ],
            [
                'token' => bin2hex(random_bytes(16)), 'location_name' => 'Warehouse',
                'latitude' => 23.0301, 'longitude' => 72.5802, 'geofence_radius' => 75, 'is_active' => 1,
                'qr_mode' => 'static', 'public_slug' => null, 'last_token_rotated_at' => null, 'created_at' => $now,
            ],
        ]);

        $this->db->table('holidays')->insertBatch([
            ['name' => 'Company Offsite', 'date' => date('Y-m-d', strtotime('+12 days')), 'created_at' => $now],
            ['name' => 'Foundation Day', 'date' => date('Y-m-d', strtotime('+40 days')), 'created_at' => $now],
        ]);

        $this->db->table('attendance')->insertBatch($this->attendanceLogs(count($staff), $now));
    }

    /** Thirty days of history, plus a believable "today". The demo employee starts today unscanned. */
    private function attendanceLogs(int $employeeCount, string $now): array
    {
        $logs = [];

        for ($back = 30; $back >= 1; $back--) {
            $date = date('Y-m-d', strtotime("-{$back} days"));
            if (date('N', strtotime($date)) === '7') {
                continue;
            }
            for ($id = 1; $id <= $employeeCount; $id++) {
                if (mt_rand(1, 100) <= 6) {
                    continue; // absent
                }
                $flagged = mt_rand(1, 100) <= 3;
                $breakAt = $this->timeOn($date, 13, mt_rand(0, 20));
                $logs[] = $this->log($id, 1, 'check_in', $this->timeOn($date, 9, mt_rand(5, 50)), $flagged, $now);
                $logs[] = $this->log($id, 1, 'break_start', $breakAt, false, $now);
                $logs[] = $this->log($id, 1, 'break_end', date('Y-m-d H:i:s', strtotime($breakAt) + mt_rand(25, 45) * 60), false, $now);
                $logs[] = $this->log($id, 1, 'check_out', $this->timeOn($date, 18, mt_rand(25, 55)), false, $now);
            }
        }

        $today = date('Y-m-d');
        for ($id = 2; $id <= $employeeCount; $id++) {
            if ($id === 7 || $id === 11) {
                continue; // absent today
            }
            $logs[] = $this->log($id, $id % 4 === 0 ? 2 : 1, 'check_in', $this->timeOn($today, 9, mt_rand(5, 50)), $id === 9, $now);
            if ($id === 4) {
                $logs[] = $this->log($id, 1, 'break_start', $this->timeOn($today, 13, 10), false, $now);
            }
        }

        return $logs;
    }

    private function timeOn(string $date, int $hour, int $minute): string
    {
        return sprintf('%s %02d:%02d:%02d', $date, $hour, $minute, mt_rand(0, 59));
    }

    private function log(int $employeeId, int $qrId, string $type, string $at, bool $flagged, string $now): array
    {
        return [
            'employee_id'     => $employeeId,
            'qr_token_id'     => $qrId,
            'type'            => $type,
            'scan_latitude'   => $qrId === 1 ? 23.0225 : 23.0301,
            'scan_longitude'  => $qrId === 1 ? 72.5714 : 72.5802,
            'geofence_status' => $flagged ? 'flagged' : 'inside',
            'scanned_at'      => $at,
            'date'            => substr($at, 0, 10),
            'created_at'      => $now,
        ];
    }
}
