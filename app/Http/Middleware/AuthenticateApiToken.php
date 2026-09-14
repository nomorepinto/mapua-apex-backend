<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $this->reject($request, $role, 'api_key_missing', 'Unauthenticated: X-Api-Key is missing.');
        }

        if ($this->matches($token, $role) || ($role !== 'admin' && $this->matches($token, 'admin'))) {
            return $next($request);
        }

        if (! $this->isConfigured($role) && ($role === 'admin' || ! $this->isConfigured('admin'))) {
            $this->reject(
                $request,
                $role,
                'api_key_unconfigured',
                'Unauthenticated: API_TOKEN_'.strtoupper($role).' is not configured on the server.',
            );
        }

        $this->reject($request, $role, 'api_key_invalid', 'Unauthenticated: Invalid X-Api-Key for '.$role.' routes.');
    }

    private function matches(string $token, string $role): bool
    {
        $expected = config('services.api.tokens.'.$role);

        if (! is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals(hash('sha256', $expected), hash('sha256', $token));
    }

    private function isConfigured(string $role): bool
    {
        $expected = config('services.api.tokens.'.$role);

        return is_string($expected) && $expected !== '';
    }

    private function reject(Request $request, string $role, string $reason, string $message): never
    {
        Log::warning('API token authentication failed.', [
            'reason' => $reason,
            'role' => $role,
            'method' => $request->method(),
            'path' => $request->path(),
            'has_api_key' => is_string($request->header('X-Api-Key')) && $request->header('X-Api-Key') !== '',
            'has_bearer' => is_string($request->bearerToken()) && $request->bearerToken() !== '',
            'role_token_configured' => $this->isConfigured($role),
            'admin_token_configured' => $this->isConfigured('admin'),
        ]);

        abort(401, $message);
    }
}
