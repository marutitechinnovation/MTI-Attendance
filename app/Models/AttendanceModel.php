<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table         = 'attendance';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'qr_token_id', 'type', 'scan_label',
        'scan_latitude', 'scan_longitude', 'geofence_status',
        'scanned_at', 'date', 'note',
    ];
    protected $useTimestamps = true;

    // ─── Scan-type cycle ───────────────────────────────────────────────────────
    //
    //  Sequence within a single day:
    //    (empty / check_out)  → check_in
    //    check_in             → break_start
    //    break_start          → break_end
    //    break_end            → check_out
    //    check_out            → check_in  (should not normally happen same day)
    //
    // If the employee has already checked-out, subsequent scans restart at
    // check_in so an admin can fix edge-cases without a migration.

    /** Labels shown in the app / API response */
    public const LABELS = [
        'check_in'    => 'Shift Start',
        'break_start' => 'Break Start',
        'break_end'   => 'Break End',
        'check_out'   => 'Shift End',
    ];

    /** The fixed scan cycle */
    private const CYCLE = ['check_in', 'break_start', 'break_end', 'check_out'];

    public function getLastScanToday(int $employeeId): ?array
    {
        return $this->where('employee_id', $employeeId)
                    ->where('date', date('Y-m-d'))
                    ->orderBy('scanned_at', 'DESC')
                    ->first();
    }

    /**
     * Returns the NEXT scan type the employee should perform today.
     * Also returns a human-readable label.
     *
     * @return array{type: string, label: string}
     */
    public function getNextScan(int $employeeId): array
    {
        $last = $this->getLastScanToday($employeeId);

        if (!$last) {
            // First scan of the day
            return ['type' => 'check_in', 'label' => self::LABELS['check_in']];
        }

        $currentIndex = array_search($last['type'], self::CYCLE, true);

        if ($currentIndex === false || $currentIndex === count(self::CYCLE) - 1) {
            // Unknown type or already at check_out → restart
            return ['type' => 'check_in', 'label' => self::LABELS['check_in']];
        }

        $nextType = self::CYCLE[$currentIndex + 1];
        return ['type' => $nextType, 'label' => self::LABELS[$nextType]];
    }

    /**
     * Backwards-compatible helper used by old code.
     */
    public function getNextScanType(int $employeeId): string
    {
        return $this->getNextScan($employeeId)['type'];
    }

    // ─── Dashboard ──────────────────────────────────────────────────────────────

    public function getTodaySummary(): array
    {
        $today = date('Y-m-d');
        $db    = \Config\Database::connect();

        $totalEmp = $db->table('employees')->where('is_active', 1)->countAllResults();

        $present = (int) $db->query(
            "SELECT COUNT(DISTINCT employee_id) AS cnt FROM attendance WHERE date = ? AND type = 'check_in'",
            [$today]
        )->getRow()->cnt;

        $absent = $totalEmp - $present;

        $workStart = model('SettingsModel')->getSetting('work_start_time', '09:00');
        if (strlen($workStart) === 5) $workStart .= ':00'; // normalize to H:i:s
        $late = (int) $db->query(
            "SELECT COUNT(*) AS cnt FROM attendance WHERE date = ? AND type = 'check_in' AND TIME(scanned_at) > ?",
            [$today, $workStart]
        )->getRow()->cnt;

        return compact('totalEmp', 'present', 'absent', 'late');
    }

    public function getRecentLogs(int $limit = 5): array
    {
        $db = \Config\Database::connect();
        return $db->table('attendance a')
                  ->select('a.*, e.name as employee_name, e.employee_code, q.location_name')
                  ->join('employees e', 'e.id = a.employee_id')
                  ->join('qr_tokens q', 'q.id = a.qr_token_id', 'left')
                  ->orderBy('a.scanned_at', 'DESC')
                  ->limit($limit)
                  ->get()->getResultArray();
    }

    public function getCalendarEvents(): array
    {
        $db = \Config\Database::connect();
        $events = [];

        // Fetch Holidays
        $holidays = $db->table('holidays')->select('name, date')->get()->getResultArray();
        foreach ($holidays as $holiday) {
            $events[] = [
                'title' => $holiday['name'],
                'start' => $holiday['date'],
                'color' => '#dc3545', // Red
                'allDay' => true
            ];
        }

        return $events;
    }


    // ─── Reports ────────────────────────────────────────────────────────────────

    public function getDailyLog(string $date, ?int $employeeId = null, ?string $department = null): array
    {
        // Automatically check out missed logs before calculating the log
        $this->autoCheckoutMissedLogs();

        $db = \Config\Database::connect();
        $sql = $db->table('employees e')
                  ->select('e.id, e.employee_code, e.name, e.department')
                  ->where('e.is_active', 1);

        if ($employeeId) $sql->where('e.id', $employeeId);
        if ($department) $sql->where('e.department', $department);
        
        $employees = $sql->get()->getResultArray();
        
        $attQuery = $db->table('attendance')
                       ->where('date', $date)
                       ->orderBy('scanned_at', 'ASC');
        if ($employeeId) $attQuery->where('employee_id', $employeeId);
        $attendanceLogs = $attQuery->get()->getResultArray();

        $attByEmp = [];
        foreach ($attendanceLogs as $log) {
            $attByEmp[$log['employee_id']][] = $log;
        }

        foreach ($employees as &$emp) {
            $logs = $attByEmp[$emp['id']] ?? [];
            $emp['check_in'] = null;
            $emp['check_out'] = null;
            $emp['breaks'] = [];
            $emp['geofence_status'] = null;
            
            $breakStart = null;
            $totalBreakMins = 0;
            
            foreach ($logs as $log) {
                if ($log['type'] === 'check_in' && !$emp['check_in']) {
                    $emp['check_in'] = $log['scanned_at'];
                }
                if ($log['type'] === 'check_out') {
                    $emp['check_out'] = $log['scanned_at'];
                }
                if ($log['type'] === 'break_start') {
                    $breakStart = $log['scanned_at'];
                }
                if ($log['type'] === 'break_end' && $breakStart) {
                    $emp['breaks'][] = ['start' => $breakStart, 'end' => $log['scanned_at']];
                    $totalBreakMins += round((strtotime($log['scanned_at']) - strtotime($breakStart)) / 60);
                    $breakStart = null;
                }
                if ($log['geofence_status'] === 'flagged') {
                    $emp['geofence_status'] = 'flagged';
                } elseif (!$emp['geofence_status'] && $log['geofence_status']) {
                    $emp['geofence_status'] = $log['geofence_status'];
                }
            }
            
            if ($breakStart && !$emp['check_out']) {
                $emp['breaks'][] = ['start' => $breakStart, 'end' => null];
            }
            
            $grossMins = 0;
            if ($emp['check_in'] && $emp['check_out']) {
                $grossMins = round((strtotime($emp['check_out']) - strtotime($emp['check_in'])) / 60);
            }

            $emp['gross_minutes']       = $grossMins;
            $emp['total_break_minutes'] = $totalBreakMins;
            $emp['net_minutes']         = max(0, $grossMins - $totalBreakMins);
            
            $emp['break_start'] = $emp['breaks'][0]['start'] ?? null;
            $emp['break_end'] = $emp['breaks'][count($emp['breaks']) - 1]['end'] ?? null;
        }
        
        return $employees;
    }

    public function getMonthlyReport(string $month, ?int $employeeId = null, ?string $department = null): array
    {
        // check out missed logs first
        $this->autoCheckoutMissedLogs();

        $db = \Config\Database::connect();
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));

        $sql = $db->table('employees e')
                  ->select('e.id, e.employee_code, e.name, e.department')
                  ->where('e.is_active', 1);
                  
        if ($employeeId) {
            $sql->where('e.id', $employeeId);
        }
        if ($department) {
            $sql->where('e.department', $department);
        }
        
        $employees = $sql->get()->getResultArray();

        $attQuery = $db->table('attendance')
                       ->where('date >=', $from)
                       ->where('date <=', $to)
                       ->orderBy('scanned_at', 'ASC');
        $attendanceLogs = $attQuery->get()->getResultArray();
        
        $attByEmpDay = [];
        foreach ($attendanceLogs as $log) {
            $attByEmpDay[$log['employee_id']][$log['date']][] = $log;
        }

        $workStart = model('SettingsModel')->getSetting('work_start_time', '09:00:00');
        if (strlen($workStart) === 5) $workStart .= ':00'; // normalize to H:i:s
        
        $workingDaysInfo = $this->getWorkingDaysInfo($month);

        foreach ($employees as &$emp) {
            $emp['days_present'] = 0;
            $emp['late_days'] = 0;
            $emp['total_gross_minutes'] = 0;
            $emp['total_break_minutes'] = 0;
            $emp['total_net_minutes'] = 0;
            $emp['working_days_info'] = $workingDaysInfo;

            $empLogsByDate = $attByEmpDay[$emp['id']] ?? [];
            foreach ($empLogsByDate as $date => $logs) {
                $checkIn = null;
                $checkOut = null;
                $breakStart = null;
                $totalBreakMins = 0;

                foreach ($logs as $log) {
                    if ($log['type'] === 'check_in' && !$checkIn) {
                        $checkIn = $log['scanned_at'];
                        if (date('H:i:s', strtotime($checkIn)) > $workStart) {
                            $emp['late_days']++;
                        }
                    }
                    if ($log['type'] === 'check_out') {
                        $checkOut = $log['scanned_at'];
                    }
                    if ($log['type'] === 'break_start') {
                        $breakStart = $log['scanned_at'];
                    }
                    if ($log['type'] === 'break_end' && $breakStart) {
                        $totalBreakMins += round((strtotime($log['scanned_at']) - strtotime($breakStart)) / 60);
                        $breakStart = null;
                    }
                }

                if ($checkIn) {
                    $emp['days_present']++;
                }

                if ($checkIn && $checkOut) {
                    $grossMins = round((strtotime($checkOut) - strtotime($checkIn)) / 60);
                    $netMins = max(0, $grossMins - $totalBreakMins);
                    $emp['total_gross_minutes'] += $grossMins;
                    $emp['total_break_minutes'] += $totalBreakMins;
                    $emp['total_net_minutes'] += $netMins;
                }
            }
        }

        return $employees;
    }

    /**
     * Returns day-by-day attendance detail for one employee in a month.
     */
    public function getEmployeeMonthlyDetail(string $month, int $employeeId): array
    {
        $this->autoCheckoutMissedLogs();

        $db   = \Config\Database::connect();
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));
        $totalDays = (int) date('t', strtotime($from));

        $workStart = model('SettingsModel')->getSetting('work_start_time', '09:00:00');
        if (strlen($workStart) === 5) $workStart .= ':00'; // normalize to H:i:s

        // Get weekend config
        $settingsJson = model('SettingsModel')->getSetting('weekend_days', '["Saturday", "Sunday"]');
        $weekends = json_decode($settingsJson, true);
        if (!is_array($weekends)) $weekends = ['Saturday', 'Sunday'];

        // Get holidays
        $holidays = $db->table('holidays')
                       ->where('date >=', $from)
                       ->where('date <=', $to)
                       ->get()->getResultArray();
        $holidayMap = [];
        foreach ($holidays as $h) {
            $holidayMap[$h['date']] = $h['name'];
        }

        // Get all attendance for this employee in the month
        $logs = $db->table('attendance')
                   ->where('employee_id', $employeeId)
                   ->where('date >=', $from)
                   ->where('date <=', $to)
                   ->orderBy('scanned_at', 'ASC')
                   ->get()->getResultArray();

        $logsByDate = [];
        foreach ($logs as $log) {
            $logsByDate[$log['date']][] = $log;
        }

        $days = [];
        $totals = [
            'present' => 0, 'absent' => 0, 'late' => 0,
            'gross_minutes' => 0, 'break_minutes' => 0, 'net_minutes' => 0,
        ];

        for ($i = 1; $i <= $totalDays; $i++) {
            $dateStr = $month . '-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $dayName = date('l', strtotime($dateStr));
            $isFuture = $dateStr > date('Y-m-d');

            $day = [
                'date'          => $dateStr,
                'day_name'      => $dayName,
                'day_short'     => date('D', strtotime($dateStr)),
                'is_weekend'    => in_array($dayName, $weekends),
                'is_holiday'    => isset($holidayMap[$dateStr]),
                'holiday_name'  => $holidayMap[$dateStr] ?? null,
                'is_future'     => $isFuture,
                'check_in'      => null,
                'check_out'     => null,
                'breaks'        => [],
                'gross_minutes' => 0,
                'break_minutes' => 0,
                'net_minutes'   => 0,
                'is_late'       => false,
                'geofence_status' => null,
                'status'        => 'absent',
            ];

            if ($day['is_weekend'])  $day['status'] = 'weekend';
            if ($day['is_holiday'])  $day['status'] = 'holiday';
            if ($day['is_future'])   $day['status'] = 'future';

            $dayLogs = $logsByDate[$dateStr] ?? [];
            if (!empty($dayLogs)) {
                $breakStart = null;
                $totalBreakMins = 0;

                foreach ($dayLogs as $log) {
                    if ($log['type'] === 'check_in' && !$day['check_in']) {
                        $day['check_in'] = $log['scanned_at'];
                        if (date('H:i:s', strtotime($log['scanned_at'])) > $workStart) {
                            $day['is_late'] = true;
                            $totals['late']++;
                        }
                    }
                    if ($log['type'] === 'check_out') {
                        $day['check_out'] = $log['scanned_at'];
                    }
                    if ($log['type'] === 'break_start') {
                        $breakStart = $log['scanned_at'];
                    }
                    if ($log['type'] === 'break_end' && $breakStart) {
                        $day['breaks'][] = ['start' => $breakStart, 'end' => $log['scanned_at']];
                        $totalBreakMins += round((strtotime($log['scanned_at']) - strtotime($breakStart)) / 60);
                        $breakStart = null;
                    }
                    if ($log['geofence_status'] === 'flagged') {
                        $day['geofence_status'] = 'flagged';
                    } elseif (!$day['geofence_status'] && $log['geofence_status']) {
                        $day['geofence_status'] = $log['geofence_status'];
                    }
                }

                if ($breakStart && !$day['check_out']) {
                    $day['breaks'][] = ['start' => $breakStart, 'end' => null];
                }

                if ($day['check_in']) {
                    $day['status'] = $day['geofence_status'] === 'flagged' ? 'flagged' : 'present';
                    $totals['present']++;
                }

                if ($day['check_in'] && $day['check_out']) {
                    $grossMins = round((strtotime($day['check_out']) - strtotime($day['check_in'])) / 60);
                    $day['gross_minutes'] = $grossMins;
                    $day['break_minutes'] = $totalBreakMins;
                    $day['net_minutes']   = max(0, $grossMins - $totalBreakMins);

                    $totals['gross_minutes'] += $day['gross_minutes'];
                    $totals['break_minutes'] += $day['break_minutes'];
                    $totals['net_minutes']   += $day['net_minutes'];
                }
            } else {
                if (!$day['is_weekend'] && !$day['is_holiday'] && !$day['is_future']) {
                    $totals['absent']++;
                }
            }

            $days[] = $day;
        }

        return ['days' => $days, 'totals' => $totals];
    }

    /**
     * Calculates total working days in a given month.
     * Takes into account standard weekends and specific holidays.
     * 
     * @param string $month Format 'Y-m'
     * @return array
     */
    public function getWorkingDaysInfo(string $month): array
    {
        $db = \Config\Database::connect();
        
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $totalDays = (int) date('t', strtotime($from));
        
        $settingsJson = model('SettingsModel')->getSetting('weekend_days', '["Saturday", "Sunday"]');
        $weekends = json_decode($settingsJson, true);
        if (!is_array($weekends)) {
            $weekends = ['Saturday', 'Sunday'];
        }

        $weekendCount = 0;
        $workableDates = [];
        
        for ($i = 1; $i <= $totalDays; $i++) {
            $dateStr = $month . '-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $dayName = date('l', strtotime($dateStr));
            
            if (in_array($dayName, $weekends)) {
                $weekendCount++;
            } else {
                $workableDates[] = $dateStr;
            }
        }

        // Get holidays within this month
        $holidays = $db->table('holidays')
                       ->where('date >=', $from)
                       ->where('date <=', $to)
                       ->get()->getResultArray();

        $holidayCountOffWorkableDays = 0;
        foreach ($holidays as $holiday) {
            if (in_array($holiday['date'], $workableDates)) {
                $holidayCountOffWorkableDays++;
            }
        }

        $totalWorkingDays = $totalDays - $weekendCount - $holidayCountOffWorkableDays;
        
        return [
            'total_days' => $totalDays,
            'weekends' => $weekendCount,
            'holidays' => $holidayCountOffWorkableDays,
            'total_working_days' => $totalWorkingDays
        ];
    }

    // ─── Auto Checkout ──────────────────────────────────────────────────────────

    public function autoCheckoutMissedLogs(): void
    {
        $db = \Config\Database::connect();
        // 24 hours ago
        $twentyFourHoursAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $sql = "SELECT a.employee_id, a.date, a.scanned_at as check_in_time, a.qr_token_id
                FROM attendance a
                WHERE a.type = 'check_in'
                  AND a.scanned_at <= ?
                  AND NOT EXISTS (
                      SELECT 1 FROM attendance a2 
                      WHERE a2.employee_id = a.employee_id 
                        AND a2.date = a.date 
                        AND a2.type = 'check_out'
                  )";
                  
        $missed = $db->query($sql, [$twentyFourHoursAgo])->getResultArray();
        
        foreach ($missed as $m) {
            $checkoutTime = date('Y-m-d H:i:s', strtotime($m['check_in_time'] . ' + 24 hours'));
            
            $db->table('attendance')->insert([
                'employee_id'     => $m['employee_id'],
                'qr_token_id'     => $m['qr_token_id'] ?: 1,
                'type'            => 'check_out',
                'scan_label'      => 'Shift End (Auto)',
                'scan_latitude'   => null,
                'scan_longitude'  => null,
                'geofence_status' => 'inside',
                'scanned_at'      => $checkoutTime,
                'date'            => $m['date'],
                'note'            => 'Auto-logout after 24 hrs',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ─── Map Live ───────────────────────────────────────────────────────────────

    public function getLiveCheckedIn(): array
    {
        $db    = \Config\Database::connect();
        $today = date('Y-m-d');

        // Show employees whose LAST scan today is check_in or break_end
        // (i.e., they are currently "working", not on break or checked out)
        return $db->query("
            SELECT e.name, e.employee_code, q.location_name, q.latitude, q.longitude,
                   a.scanned_at, a.geofence_status, a.type AS last_scan_type
            FROM attendance a
            JOIN employees e ON e.id = a.employee_id
            JOIN qr_tokens q ON q.id = a.qr_token_id
            WHERE a.date = '$today'
              AND a.type IN ('check_in', 'break_end')
              AND NOT EXISTS (
                SELECT 1 FROM attendance a2
                WHERE a2.employee_id = a.employee_id
                  AND a2.date = '$today'
                  AND a2.scanned_at > a.scanned_at
              )
        ")->getResultArray();
    }

    // ─── Admin Edits ────────────────────────────────────────────────────────────

    public function deleteLogsByDate(string $date, int $employeeId): void
    {
        $db = \Config\Database::connect();
        $db->table('attendance')
           ->where('employee_id', $employeeId)
           ->where('date', $date)
           ->delete();
    }

    public function updateLogsByDate(string $date, int $employeeId, array $times): void
    {
        $db = \Config\Database::connect();
        
        // Handle single-value types
        $singleTypes = ['check_in', 'check_out'];
        foreach ($singleTypes as $type) {
            $timeVal = $times[$type] ?? null;
            
            // Delete old
            $db->table('attendance')
               ->where('employee_id', $employeeId)
               ->where('date', $date)
               ->where('type', $type)
               ->delete();
            
            if (!empty($timeVal)) {
                $db->table('attendance')->insert([
                    'employee_id'     => $employeeId,
                    'qr_token_id'     => 1,
                    'type'            => $type,
                    'scan_label'      => self::LABELS[$type] ?? ucwords(str_replace('_', ' ', $type)),
                    'date'            => $date,
                    'scanned_at'      => $date . ' ' . $timeVal . (strlen($timeVal) == 5 ? ':00' : ''),
                    'geofence_status' => 'inside',
                    'note'            => 'Edited by Admin',
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }
        
        // Handle breaks
        // Delete all old breaks
        $db->table('attendance')
           ->where('employee_id', $employeeId)
           ->where('date', $date)
           ->whereIn('type', ['break_start', 'break_end'])
           ->delete();
           
        $breakStarts = $times['break_starts'] ?? [];
        $breakEnds   = $times['break_ends'] ?? [];
        
        if (is_array($breakStarts)) {
            foreach ($breakStarts as $index => $timeVal) {
                if (empty($timeVal)) continue;
                
                // break_start
                $db->table('attendance')->insert([
                    'employee_id'     => $employeeId,
                    'qr_token_id'     => 1,
                    'type'            => 'break_start',
                    'scan_label'      => self::LABELS['break_start'],
                    'date'            => $date,
                    'scanned_at'      => $date . ' ' . $timeVal . (strlen($timeVal) == 5 ? ':00' : ''),
                    'geofence_status' => 'inside',
                    'note'            => 'Edited by Admin',
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
                
                // matching break_end
                if (!empty($breakEnds[$index])) {
                    $endTime = $breakEnds[$index];
                    $db->table('attendance')->insert([
                        'employee_id'     => $employeeId,
                        'qr_token_id'     => 1,
                        'type'            => 'break_end',
                        'scan_label'      => self::LABELS['break_end'],
                        'date'            => $date,
                        'scanned_at'      => $date . ' ' . $endTime . (strlen($endTime) == 5 ? ':00' : ''),
                        'geofence_status' => 'inside',
                        'note'            => 'Edited by Admin',
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
}
