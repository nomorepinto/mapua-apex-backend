<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $expected = config('services.api.tokens.'.$role);
        $token = $request->header('X-Api-Key');

        if (! is_string($expected) || $expected === '' || ! is_string($token) || $token === '') {
            abort(401, 'Unauthenticated.');
        }

        if (! hash_equals(hash('sha256', $expected), hash('sha256', $token))) {
            abort(401, 'Unauthenticated.');
        }

        return $next($request);
    }
}
