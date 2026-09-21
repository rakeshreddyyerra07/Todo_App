<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks pages and AJAX endpoints for visitors who are not logged in.
 *
 * Normal page request -> redirect to /login
 * AJAX / fetch()      -> 401 + JSON (same message the original app used)
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user_id')) {
            return null;
        }

        $path = $request instanceof IncomingRequest ? $request->getPath() : '';

        // fetch() calls that always expect JSON (some do not send the AJAX header)
        $jsonEndpoint = (bool) preg_match(
            '#^(tasks/(comments|attachments)/(\d+|add|upload|delete)|tasks/update-(field|progress)|boards/(create|delete))$#',
            $path
        );

        $wantsJson = $jsonEndpoint
            || $request->isAJAX()
            || str_contains($request->getHeaderLine('Accept'), 'application/json');

        if ($wantsJson) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Your session has expired. Please login again.',
                ]);
        }

        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
