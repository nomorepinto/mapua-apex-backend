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
            abort(401, 'Unauthenticated: Invalid Cognito token ('.$e->getMessage().').');
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

        $this->attachScopedId(
            $request,
            $claims,
            ['custom:organization_id', 'organization_id'],
            'X-Organization-Id',
            'ORGANIZATION#',
            'cognito.organization_id',
            'student',
            $role,
            $isAdmin,
        );

        $sub = $claims['sub'] ?? null;

        if (is_string($sub) && $sub !== '') {
            $request->attributes->set('cognito.sub', $sub);
            Context::addHidden('cognito.sub', $sub);
        }

        $this->attachScopedId(
            $request,
            $claims,
            ['custom:signatory_id', 'signatory_id'],
            'X-Signatory-Id',
            'SIGNATORY#',
            'cognito.signatory_id',
            'signatory',
            $role,
            $isAdmin,
        );

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  list<string>  $claimKeys
     */
    private function attachScopedId(
        Request $request,
        array $claims,
        array $claimKeys,
        string $header,
        string $prefix,
        string $attribute,
        string $requiredForRole,
        ?string $role,
        bool $isAdmin,
    ): void {
        $value = $this->firstNonEmptyClaim($claims, $claimKeys);

        if ($value !== null) {
            $value = Str::chopStart($value, $prefix);
            $request->attributes->set($attribute, $value);
            Context::addHidden($attribute, $value);

            return;
        }

        if ($isAdmin) {
            $fromHeader = $request->header($header);

            if (is_string($fromHeader) && $fromHeader !== '') {
                $fromHeader = Str::chopStart($fromHeader, $prefix);
                $request->attributes->set($attribute, $fromHeader);
                Context::addHidden($attribute, $fromHeader);
            }

            return;
        }

        if ($role === $requiredForRole) {
            abort(401, "Unauthenticated: Missing {$claimKeys[0]} in Cognito token.");
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  list<string>  $claimKeys
     */
    private function firstNonEmptyClaim(array $claims, array $claimKeys): ?string
    {
        foreach ($claimKeys as $key) {
            $value = $claims[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
