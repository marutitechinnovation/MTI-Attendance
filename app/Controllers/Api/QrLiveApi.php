<?php

namespace App\Controllers\Api;

use App\Models\QRTokenModel;
use App\Models\SettingsModel;
use CodeIgniter\RESTful\ResourceController;

class QrLiveApi extends ResourceController
{
    protected $format = 'json';

    /**
     * GET api/qr/live-token/{slug}
     * Returns current scan token; applies time-based rotation. Public (no auth).
     */
    public function token(string $slug)
    {
        $model = new QRTokenModel();
        $row   = $model->findActiveRotatingBySlug($slug);

        if (!$row) {
            return $this->failNotFound('Invalid or inactive linked QR.');
        }

        $row   = $model->ensureFreshRotatingToken($row);
        $settings = new SettingsModel();
        $interval = $model->getRotatingIntervalSeconds($settings);

        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON([
                'status'             => 'success',
                'token'              => $row['token'],
                'interval_seconds'   => $interval,
                'location_name'      => $row['location_name'],
            ]);
    }
}
