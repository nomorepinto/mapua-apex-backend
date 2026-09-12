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
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            abort(401, 'Unauthenticated.');
        }

        $hashedToken = hash('sha256', $token);

        foreach (config('services.api.tokens', []) as $validToken) {
            if (hash_equals(hash('sha256', (string) $validToken), $hashedToken)) {
                return $next($request);
            }
        }

        abort(401, 'Unauthenticated.');
    }
}
