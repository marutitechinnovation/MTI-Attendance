<?php

namespace App\Controllers;

use App\Models\AttendanceModel;
use App\Models\EmployeeModel;

class Reports extends BaseController
{
    public function index()
    {
        $month      = $this->request->getGet('month') ?? date('Y-m');
        $employeeId = $this->request->getGet('employee_id') ?: null;
        if ($employeeId !== null) {
            $employeeId = (int) $employeeId;
        }
        $department = $this->request->getGet('department') ?: null;
        
        $model  = new AttendanceModel();
        $report = $model->getMonthlyReport($month, $employeeId, $department);

        // Calculate working days in month
        $workingDaysInfo = $model->getWorkingDaysInfo($month);

        return view('reports/index', [
            'report'             => $report,
            'month'              => $month,
            'daysInMonth'        => $workingDaysInfo['total_days'],
            'workingDaysInfo'    => $workingDaysInfo,
            'employees'          => (new EmployeeModel())->getActive(),
            'departments'        => (new EmployeeModel())->getDepartments(),
            'selectedEmployee'   => $employeeId ?? '',
            'selectedDepartment' => $department ?? '',
            'pageTitle'          => 'Monthly Reports',
        ]);
    }

    public function employeeDetail()
    {
        $month      = $this->request->getGet('month') ?? date('Y-m');
        $employeeId = (int)($this->request->getGet('employee_id') ?? 0);

        if (!$employeeId) {
            return redirect()->to('/reports')->with('error', 'Employee not found.');
        }

        $empModel = new EmployeeModel();
        $employee = $empModel->find($employeeId);
        if (!$employee) {
            return redirect()->to('/reports')->with('error', 'Employee not found.');
        }

        $model  = new AttendanceModel();
        $detail = $model->getEmployeeMonthlyDetail($month, $employeeId);
        $workingDaysInfo = $model->getWorkingDaysInfo($month);

        return view('reports/employee_detail', [
            'employee'        => $employee,
            'month'           => $month,
            'days'            => $detail['days'],
            'totals'          => $detail['totals'],
            'workingDaysInfo' => $workingDaysInfo,
            'pageTitle'       => esc($employee['name']) . ' — Monthly Detail',
        ]);
    }

    public function exportCsv()
    {
        $month      = $this->request->getGet('month') ?? date('Y-m');
        $employeeId = $this->request->getGet('employee_id') ?: null;
        if ($employeeId !== null) {
            $employeeId = (int) $employeeId;
        }
        $department = $this->request->getGet('department') ?: null;
        
        $model = new AttendanceModel();
        $report = $model->getMonthlyReport($month, $employeeId, $department);
        $workingDaysInfo = $model->getWorkingDaysInfo($month);

        $filename = 'attendance_' . $month . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Department', 'Total Working Days', 'Days Present', 'Late Days', 'Days Absent', 'Total Hours', 'Break Hours', 'Net Hours', 'Avg Hours/Day']);


        foreach ($report as $row) {
            // Gross hours
            $grossMins = (int)($row['total_gross_minutes'] ?? 0);
            $grossH = floor($grossMins / 60);
            $grossM = $grossMins % 60;
            $grossStr = $grossMins > 0 ? $grossH . 'h' . ($grossM > 0 ? ' ' . $grossM . 'm' : '') : '0h';

            // Break hours
            $breakMins = (int)($row['total_break_minutes'] ?? 0);
            $breakH = floor($breakMins / 60);
            $breakM = $breakMins % 60;
            $breakStr = $breakMins > 0 ? $breakH . 'h' . ($breakM > 0 ? ' ' . $breakM . 'm' : '') : '0h';

            // Net hours
            $netMins = (int)($row['total_net_minutes'] ?? 0);
            $netH = floor($netMins / 60);
            $netM = $netMins % 60;
            $netStr = $netMins > 0 ? $netH . 'h' . ($netM > 0 ? ' ' . $netM . 'm' : '') : '0h';

            // Average per day (based on net)
            $avgMins = $row['days_present'] > 0 ? round($netMins / $row['days_present']) : 0;
            $avgH = floor($avgMins / 60);
            $avgM = $avgMins % 60;
            $avgStr = $avgMins > 0 ? $avgH . 'h' . ($avgM > 0 ? ' ' . $avgM . 'm' : '') : '0h';
            
            // Days absent is calculated against total working days 
            $daysAbsent = max(0, $workingDaysInfo['total_working_days'] - $row['days_present']);

            fputcsv($out, [
                $row['name'],
                $row['department'],
                $workingDaysInfo['total_working_days'],
                $row['days_present'],
                $row['late_days'],
                $daysAbsent,
                $grossStr,
                $breakStr,
                $netStr,
                $avgStr,
            ]);
        }
        fclose($out);
        exit;
    }
}
