<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApi
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $authKey = $request->header('Auth');
        if (! $authKey || $authKey !== config('services.flat_key')) {
            return new JsonResponse('Auth error!', 403);
        }

        return $next($request);
    }
}
