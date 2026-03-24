<?php

namespace App\Controllers\Api;

use App\Models\QRTokenModel;
use CodeIgniter\RESTful\ResourceController;

class QRCodeApi extends ResourceController
{
    protected $format = 'json';

    public function __construct()
    {
        $this->model = new QRTokenModel();
    }

    public function index()
    {
        return $this->respond(['status' => 'success', 'data' => $this->model->getActive()]);
    }

    public function create()
    {
        $body = $this->request->getJSON(true) ?? [];
        $mode = isset($body['qr_mode']) && $body['qr_mode'] === 'rotating' ? 'rotating' : 'static';

        $token = $this->model->generateToken();
        $slug  = null;
        $rotAt = null;
        if ($mode === 'rotating') {
            $slug  = $this->model->generateUniquePublicSlug();
            $rotAt = date('Y-m-d H:i:s');
        }

        $body['token']                 = $token;
        $body['is_active']             = 1;
        $body['qr_mode']               = $mode;
        $body['public_slug']           = $slug;
        $body['last_token_rotated_at'] = $rotAt;

        $id = $this->model->insert($body);

        $out = ['status' => 'success', 'id' => $id, 'token' => $token, 'qr_mode' => $mode];
        if ($slug !== null) {
            $out['public_slug'] = $slug;
            $out['live_url']    = site_url('qr/v/' . $slug);
        }

        return $this->respondCreated($out);
    }

    public function update($id = null)
    {
        $body = $this->request->getJSON(true);
        if (!$this->model->find($id)) return $this->failNotFound('QR code not found.');
        $this->model->update($id, $body);
        return $this->respond(['status' => 'success', 'message' => 'Updated.']);
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) return $this->failNotFound('QR code not found.');
        $this->model->update($id, ['is_active' => 0]);
        return $this->respond(['status' => 'success', 'message' => 'Deactivated.']);
    }
}
