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
        $token = $request->header('X-Api-Key');

        if (! is_string($token) || $token === '') {
            abort(401, 'Unauthenticated.');
        }

        if ($this->matches($token, $role) || ($role !== 'admin' && $this->matches($token, 'admin'))) {
            return $next($request);
        }

        abort(401, 'Unauthenticated.');
    }

    private function matches(string $token, string $role): bool
    {
        $expected = config('services.api.tokens.'.$role);

        if (! is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals(hash('sha256', $expected), hash('sha256', $token));
    }
}
