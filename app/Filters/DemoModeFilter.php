<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * Public demo instance: visitors can look at everything and try a QR scan,
 * but nothing else can be changed. Enabled with app.demoMode = true in .env.
 */
class DemoModeFilter implements FilterInterface
{
    private const ALLOWED_WRITES = ['admin/login', 'api/auth/login', 'api/attendance/scan'];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!filter_var(env('app.demoMode', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $path = trim($request->getPath(), '/');
        if (in_array($path, self::ALLOWED_WRITES, true)) {
            return;
        }

        $message = 'This is a read-only demo, so changes are not saved.';

        if (str_starts_with($path, 'api/')) {
            return service('response')
                ->setStatusCode(403)
                ->setContentType('application/json')
                ->setBody(json_encode(['status' => 'error', 'message' => $message]));
        }

        return redirect()->back()->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
