<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/tg/*',
        '/<token>/webhook',
        '/send/all/info',
    ];

    /**
     * The domains that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $exceptDomains = [
        'localhost',
        'localhost:3000',
        'localhost:3001',
        'smart-flat-v2.local',
        'smart-f.vanzzo.net',
        'sf.vanzzo.net',
    ];

    /**
     * Determine if the request has a URI/Domain that should pass through CSRF verification.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        if (in_array($request->getHost(), $this->exceptDomains, true)) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
