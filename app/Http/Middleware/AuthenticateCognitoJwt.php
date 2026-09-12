<?php

namespace App\Http\Middleware;

use App\Auth\CognitoJwksUnavailable;
use App\Auth\CognitoJwtVerifier;
use App\Auth\InvalidCognitoJwt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCognitoJwt
{
    public function __construct(private CognitoJwtVerifier $verifier) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $jwt = $request->bearerToken();

        if (! is_string($jwt) || $jwt === '') {
            abort(401, 'Unauthenticated.');
        }

        try {
            $claims = $this->verifier->verify($jwt);
        } catch (InvalidCognitoJwt) {
            abort(401, 'Unauthenticated.');
        } catch (CognitoJwksUnavailable) {
            abort(503, 'Identity provider unavailable.');
        }

        if (is_string($role) && $role !== '') {
            $groups = $claims['cognito:groups'] ?? null;

            if (is_array($groups) && ! in_array($role, $groups, true)) {
                abort(401, 'Unauthenticated.');
            }
        }

        $organizationId = $claims['custom:organization_id'] ?? $claims['organization_id'] ?? null;

        if (is_string($organizationId) && $organizationId !== '') {
            $organizationId = Str::chopStart($organizationId, 'ORGANIZATION#');
            $request->attributes->set('cognito.organization_id', $organizationId);
            Context::addHidden('cognito.organization_id', $organizationId);
        } elseif ($role === 'student') {
            abort(401, 'Unauthenticated.');
        }

        $sub = $claims['sub'] ?? null;

        if (is_string($sub) && $sub !== '') {
            $request->attributes->set('cognito.sub', $sub);
            Context::addHidden('cognito.sub', $sub);
        }

        $signatoryId = $claims['custom:signatory_id'] ?? null;

        if (is_string($signatoryId) && $signatoryId !== '') {
            $signatoryId = Str::chopStart($signatoryId, 'SIGNATORY#');
            $request->attributes->set('cognito.signatory_id', $signatoryId);
            Context::addHidden('cognito.signatory_id', $signatoryId);
        } elseif ($role === 'signatory') {
            abort(401, 'Unauthenticated.');
        }

        return $next($request);
    }
}
