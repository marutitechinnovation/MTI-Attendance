<?php

namespace App\Controllers;

use App\Models\SettingsModel;

class Settings extends BaseController
{
    protected SettingsModel $model;

    public function __construct()
    {
        $this->model = new SettingsModel();
    }

    public function index()
    {
        return view('settings/index', [
            'settings'  => $this->model->getAll(),
            'pageTitle' => 'Settings',
        ]);
    }

    public function update()
    {
        $keys = [
            'company_name',
            'work_start_time',
            'work_end_time',
            'default_geofence_radius',
            'employee_app_name',
            'employee_app_short_name',
            'employee_app_page_title',
            'employee_app_description',
            'map_tile_url',
            'map_tile_subdomains',
            'map_attribution',
            'stat_label_check_in',
            'stat_label_break_start',
            'stat_label_break_end',
            'stat_label_check_out',
            'stat_status_working',
            'stat_status_on_break',
            'stat_status_complete',
            'stat_status_not_in',
        ];

        foreach ($keys as $key) {
            $val = $this->request->getPost($key);
            if ($val === null) {
                continue;
            }
            $val = is_string($val) ? trim($val) : $val;
            if ($key === 'employee_app_name' && $val === '') {
                continue;
            }
            $this->model->saveSetting($key, $val);
        }

        foreach (
            [
                'employee_app_theme_color',
                'employee_app_background_color',
                'employee_app_accent_color',
            ] as $ckey
        ) {
            $raw = $this->request->getPost($ckey);
            if ($raw === null || $raw === '') {
                continue;
            }
            $norm = $this->normalizeHexColor((string) $raw);
            if ($norm !== null) {
                $this->model->saveSetting($ckey, $norm);
            }
        }

        $provider = $this->request->getPost('map_tile_provider');
        if ($provider !== null && in_array($provider, ['osm', 'custom'], true)) {
            $this->model->saveSetting('map_tile_provider', $provider);
        }

        if ($this->request->getPost('clear_map_api_key')) {
            $this->model->saveSetting('map_api_key', '');
        } else {
            $mapKey = $this->request->getPost('map_api_key');
            if ($mapKey !== null && $mapKey !== '') {
                $this->model->saveSetting('map_api_key', (string) $mapKey);
            }
        }

        $qrRot = $this->request->getPost('qr_rotating_interval_seconds');
        if ($qrRot !== null && $qrRot !== '') {
            $v = max(5, min(600, (int) $qrRot));
            $this->model->saveSetting('qr_rotating_interval_seconds', (string) $v);
        }

        $weekendDays = $this->request->getPost('weekend_days');
        if (is_array($weekendDays)) {
            $this->model->saveSetting('weekend_days', json_encode($weekendDays));
        } else {
            $this->model->saveSetting('weekend_days', json_encode([]));
        }

        return redirect()->to('/settings')->with('success', 'Settings saved successfully.');
    }

    private function normalizeHexColor(string $val): ?string
    {
        $val = trim($val);
        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $val, $m)) {
            return '#' . strtolower($m[1]);
        }
        if (preg_match('/^([0-9A-Fa-f]{6})$/', $val, $m)) {
            return '#' . strtolower($m[1]);
        }

        return null;
    }
}
