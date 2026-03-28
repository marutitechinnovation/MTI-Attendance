<?php

namespace App\Filters;

use App\Libraries\JwtHelper;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;
        if (substr($authHeader, 0, 7) === 'Bearer ') {
            $token = substr($authHeader, 7);
        }

        if (!$token) {
            return service('response')
                ->setStatusCode(401)
                ->setContentType('application/json')
                ->setBody(json_encode([
                    'status'  => 'error',
                    'message' => 'Authentication required. Please log in.',
                ]));
        }

        $payload = JwtHelper::decode($token);
        if (!$payload) {
            return service('response')
                ->setStatusCode(401)
                ->setContentType('application/json')
                ->setBody(json_encode([
                    'status'  => 'error',
                    'message' => 'Token invalid or expired. Please log in again.',
                ]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
