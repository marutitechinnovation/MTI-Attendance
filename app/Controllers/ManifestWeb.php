<?php

namespace App\Controllers;

use App\Models\SettingsModel;

class ManifestWeb extends BaseController
{
    public function index()
    {
        $settings = new SettingsModel();

        $name        = $settings->getSetting('employee_app_name', 'MTI Attendance');
        $shortName   = $settings->getSetting('employee_app_short_name', 'MTI Employee');
        $description = $settings->getSetting('employee_app_description', 'Employee attendance PWA.');
        $theme       = $settings->getSetting('employee_app_theme_color', '#1A237E');
        $background  = $settings->getSetting('employee_app_background_color', '#F5F7FB');

        $manifest = [
            'name'             => $name,
            'short_name'       => $shortName,
            'start_url'        => '/employee',
            'scope'            => '/',
            'display'          => 'standalone',
            'background_color' => $background,
            'theme_color'      => $theme,
            'description'      => $description,
            'icons'            => [
                [
                    'src'     => '/assets/icons/icon-192.svg',
                    'sizes'   => '192x192',
                    'type'    => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
                [
                    'src'     => '/assets/icons/icon-512.svg',
                    'sizes'   => '512x512',
                    'type'    => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/manifest+json')
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON($manifest);
    }
}
