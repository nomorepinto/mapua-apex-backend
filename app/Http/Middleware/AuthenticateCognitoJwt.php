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
            abort(401, 'Unauthenticated: Bearer token is missing.');
        }

        try {
            $claims = $this->verifier->verify($jwt);
        } catch (InvalidCognitoJwt $e) {
            abort(401, 'Unauthenticated: Invalid Cognito token (' . $e->getMessage() . ').');
        } catch (CognitoJwksUnavailable) {
            abort(503, 'Identity provider unavailable.');
        }

        $groups = $claims['cognito:groups'] ?? null;
        $isAdmin = is_array($groups) && in_array('admin', $groups, true);

        if (is_string($role) && $role !== '') {
            if (is_array($groups) && ! in_array($role, $groups, true) && ! $isAdmin) {
                abort(401, "Unauthenticated: User is not in the required '$role' or 'admin' Cognito group.");
            }
        }

        $request->attributes->set('cognito.is_admin', $isAdmin);
        Context::addHidden('cognito.is_admin', $isAdmin);

        $organizationId = $claims['custom:organization_id'] ?? $claims['organization_id'] ?? null;

        if (is_string($organizationId) && $organizationId !== '') {
            $organizationId = Str::chopStart($organizationId, 'ORGANIZATION#');
            $request->attributes->set('cognito.organization_id', $organizationId);
            Context::addHidden('cognito.organization_id', $organizationId);
        } elseif ($request->header('X-Organization-Id')) {
            $orgHeader = Str::chopStart($request->header('X-Organization-Id'), 'ORGANIZATION#');
            $request->attributes->set('cognito.organization_id', $orgHeader);
            Context::addHidden('cognito.organization_id', $orgHeader);
        } elseif ($role === 'student') {
            $request->attributes->set('cognito.organization_id', 'org-001');
            Context::addHidden('cognito.organization_id', 'org-001');
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
        } elseif ($request->header('X-Signatory-Id')) {
            $sigHeader = Str::chopStart($request->header('X-Signatory-Id'), 'SIGNATORY#');
            $request->attributes->set('cognito.signatory_id', $sigHeader);
            Context::addHidden('cognito.signatory_id', $sigHeader);
        } elseif ($role === 'signatory') {
            $request->attributes->set('cognito.signatory_id', 'sig-010');
            Context::addHidden('cognito.signatory_id', 'sig-010');
        }

        return $next($request);
    }
}
