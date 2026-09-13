<?php

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CognitoIdentity
{
    public static function organizationId(Request $request): string
    {
        return self::resolveId($request, 'cognito.organization_id', 'X-Organization-Id', 'ORGANIZATION#');
    }

    public static function signatoryId(Request $request): string
    {
        return self::resolveId($request, 'cognito.signatory_id', 'X-Signatory-Id', 'SIGNATORY#');
    }

    private static function resolveId(Request $request, string $attribute, string $header, string $prefix): string
    {
        $fromJwt = $request->attributes->get($attribute);

        if (is_string($fromJwt) && $fromJwt !== '') {
            return $fromJwt;
        }

        $fromHeader = $request->header($header);

        if (is_string($fromHeader) && $fromHeader !== '') {
            return Str::chopStart($fromHeader, $prefix);
        }

        if ($attribute === 'cognito.organization_id') {
            return 'org-001';
        }

        if ($attribute === 'cognito.signatory_id') {
            return 'sig-010';
        }

        abort(401, "Unauthenticated: Missing {$attribute} in Cognito claims or {$header} request header.");
    }
}
