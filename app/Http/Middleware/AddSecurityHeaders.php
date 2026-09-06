<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * The headers added to every web response.
     *
     * The panel can delete media and power the host off, so it must never be
     * framed. Strict-Transport-Security is left to Cloudflare, which already
     * sends it on every response.
     *
     * X-Robots-Tag keeps the panel out of search engines even when a crawler
     * reaches a URL without reading robots.txt first, and it is the only signal
     * that works for non-HTML responses, which carry no meta tag.
     *
     * @var array<string, string>
     */
    private const HEADERS = [
        'Content-Security-Policy' => "frame-ancestors 'none'",
        'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate',
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $header => $value) {
            if (! $response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }

        return $response;
    }
}
