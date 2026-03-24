<?php

namespace App\Controllers;

use App\Models\SettingsModel;

class EmployeeApp extends BaseController
{
    public function index()
    {
        $model    = new SettingsModel();
        $settings = $model->getAll();

        $defaults = [
            'employee_app_name'               => 'MTI Attendance',
            'employee_app_short_name'         => 'MTI Employee',
            'employee_app_page_title'       => 'Employee App',
            'employee_app_theme_color'      => '#1A237E',
            'employee_app_background_color' => '#F5F7FB',
            'employee_app_accent_color'       => '#00BCD4',
            'stat_label_check_in'           => 'Check In',
            'stat_label_break_start'        => 'Break Start',
            'stat_label_break_end'          => 'Break End',
            'stat_label_check_out'          => 'Check Out',
            'stat_status_working'           => 'Working',
            'stat_status_on_break'          => 'On Break',
            'stat_status_complete'          => 'Shift Complete',
            'stat_status_not_in'            => 'Not Checked In',
        ];
        $s = array_merge($defaults, $settings);

        $pwaConfig = [
            'scanLabels' => [
                'check_in'    => $s['stat_label_check_in'],
                'break_start' => $s['stat_label_break_start'],
                'break_end'   => $s['stat_label_break_end'],
                'check_out'   => $s['stat_label_check_out'],
            ],
            'statusLabels' => [
                'working'   => $s['stat_status_working'],
                'on_break'  => $s['stat_status_on_break'],
                'complete'  => $s['stat_status_complete'],
                'not_in'    => $s['stat_status_not_in'],
            ],
        ];

        $flashSuccess       = session()->getFlashdata('success');
        $flashError         = session()->getFlashdata('error');
        $loginBannerMessage = is_string($flashSuccess) ? $flashSuccess : (is_string($flashError) ? $flashError : null);
        $loginBannerIsError = is_string($flashError) && ! is_string($flashSuccess);

        return view('employee/app', [
            'pageTitle'           => $s['employee_app_page_title'],
            'brandName'           => $s['employee_app_name'],
            'shortAppName'        => $s['employee_app_short_name'],
            'themeColor'          => $s['employee_app_theme_color'],
            'backgroundColor'     => $s['employee_app_background_color'],
            'accentColor'         => $s['employee_app_accent_color'],
            'statLabels'          => $pwaConfig['scanLabels'],
            'statStatusNotIn'     => $s['stat_status_not_in'],
            'pwaConfigJson'       => json_encode($pwaConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE),
            'loginBannerMessage'  => $loginBannerMessage,
            'loginBannerIsError'  => $loginBannerIsError,
            'adminLoginUrl'       => base_url('admin/login'),
        ]);
    }
}
