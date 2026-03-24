<?php

namespace App\Controllers;

use App\Models\QRTokenModel;

class QrDisplay extends BaseController
{
    /**
     * Public kiosk / browser page: shows a QR that encodes the current scan token
     * for a rotating (linked) location. Slug is unguessable.
     */
    public function live(string $slug)
    {
        $model = new QRTokenModel();
        $row   = $model->findActiveRotatingBySlug($slug);

        if (!$row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('qr/live', [
            'slug'          => $slug,
            'locationName'  => $row['location_name'],
            'pageTitle'     => 'Attendance — ' . $row['location_name'],
        ]);
    }
}
