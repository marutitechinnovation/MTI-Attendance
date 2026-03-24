<?php

namespace App\Controllers\Api;

use App\Models\AttendanceModel;
use App\Models\QRTokenModel;
use App\Models\EmployeeModel;
use App\Models\SettingsModel;
use CodeIgniter\RESTful\ResourceController;

class AttendanceApi extends ResourceController
{
    protected $format = 'json';

    public function scan()
    {
        $body = $this->request->getJSON(true);

        $employeeId  = $body['employee_id']  ?? null;
        $token       = $body['qr_token']     ?? null;
        $scanLat     = $body['latitude']      ?? null;
        $scanLng     = $body['longitude']     ?? null;

        if (!$employeeId || !$token) {
            return $this->failValidationErrors('employee_id and qr_token are required.');
        }

        $qrModel   = new QRTokenModel();
        $attModel  = new AttendanceModel();
        $empModel  = new EmployeeModel();
        $settings  = new SettingsModel();

        // Process any pending auto-logouts
        $attModel->autoCheckoutMissedLogs();

        // Validate QR
        $qr = $qrModel->findByToken($token);
        if (!$qr) {
            return $this->failNotFound('Invalid or inactive QR code.');
        }

        // Validate Employee
        $employee = $empModel->find($employeeId);
        if (!$employee || !$employee['is_active']) {
            return $this->failNotFound('Employee not found.');
        }

        // Prevent multiple check-ins after shifting out
        $alreadyCheckedOut = $attModel->where('employee_id', $employeeId)
                                      ->where('date', date('Y-m-d'))
                                      ->where('type', 'check_out')
                                      ->first();

        if ($alreadyCheckedOut) {
            return $this->failForbidden('You have already completed your shift today. Please contact admin to delete or update data.');
        }

        // Geofence check
        $geofenceStatus = 'inside';
        $message        = null;
        $radius         = $qr['geofence_radius'] ?: (int)$settings->getSetting('default_geofence_radius', 50);

        // Skip geofence check if employee has the "allow anywhere" flag
        if (empty($employee['allow_anywhere_attendance'])) {
            if ($scanLat && $scanLng && $qr['latitude'] && $qr['longitude']) {
                $distance = $this->haversine(
                    (float)$qr['latitude'], (float)$qr['longitude'],
                    (float)$scanLat,        (float)$scanLng
                );
                if ($distance > $radius) {
                    $geofenceStatus = 'flagged';
                    $message = "You are " . round($distance) . "m away from {$qr['location_name']} (allowed: {$radius}m). Attendance marked but flagged for review.";
                }
            }
        }

        // Determine next scan type:
        //   • If the app sends an explicit 'scan_type' — honour it (user chose their action)
        //   • Otherwise fall back to server-side auto-detection
        $validTypes    = ['check_in', 'break_start', 'break_end', 'check_out'];
        $requestedType = $body['scan_type'] ?? null;

        if ($requestedType && in_array($requestedType, $validTypes, true)) {
            $type  = $requestedType;
            $label = AttendanceModel::LABELS[$type];
        } else {
            $nextScan = $attModel->getNextScan($employeeId);
            $type     = $nextScan['type'];
            $label    = $nextScan['label'];
        }

        // Save attendance
        $attModel->insert([
            'employee_id'     => $employeeId,
            'qr_token_id'     => $qr['id'],
            'type'            => $type,
            'scan_label'      => $label,
            'scan_latitude'   => $scanLat,
            'scan_longitude'  => $scanLng,
            'geofence_status' => $geofenceStatus,
            'scanned_at'      => date('Y-m-d H:i:s'),
            'date'            => date('Y-m-d'),
        ]);

        if (($qr['qr_mode'] ?? 'static') === 'rotating') {
            $qrModel->rotateToken((int) $qr['id']);
        }

        return $this->respond([
            'status'          => $geofenceStatus === 'flagged' ? 'flagged' : 'success',
            'type'            => $type,
            'label'           => $label,
            'employee'        => $employee['name'],
            'employee_code'   => $employee['employee_code'],
            'location'        => $qr['location_name'],
            'time'            => date('H:i:s'),
            'geofence_status' => $geofenceStatus,
            'message'         => $message,
        ]);
    }

    public function today()
    {
        $employeeId = $this->request->getGet('employee_id');
        if (!$employeeId) return $this->failValidationErrors('employee_id is required.');

        $model = new AttendanceModel();
        $model->autoCheckoutMissedLogs();

        $logs  = $model->where('employee_id', $employeeId)
                       ->where('date', date('Y-m-d'))
                       ->orderBy('scanned_at', 'ASC')
                       ->findAll();

        return $this->respond(['status' => 'success', 'data' => $logs]);
    }

    public function history()
    {
        $employeeId = $this->request->getGet('employee_id');
        $from       = $this->request->getGet('from') ?? date('Y-m-01');
        $to         = $this->request->getGet('to')   ?? date('Y-m-d');

        if (!$employeeId) return $this->failValidationErrors('employee_id is required.');

        $model = new AttendanceModel();
        $model->autoCheckoutMissedLogs();

        $logs  = $model->where('employee_id', $employeeId)
                       ->where('date >=', $from)
                       ->where('date <=', $to)
                       ->orderBy('date', 'DESC')
                       ->findAll();

        return $this->respond(['status' => 'success', 'data' => $logs]);
    }

    // ─── Haversine formula ──────────────────────────────────────────────────────
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R  = 6371000; // Earth radius meters
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);
        $a  = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
