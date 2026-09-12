<?php

namespace App\Http;

use Illuminate\Http\Request;

final class CognitoIdentity
{
    public static function organizationId(Request $request): string
    {
        $organizationId = $request->attributes->get('cognito.organization_id');

        if (! is_string($organizationId) || $organizationId === '') {
            abort(401, 'Unauthenticated.');
        }

        return $organizationId;
    }

    public static function signatoryId(Request $request): string
    {
        $signatoryId = $request->attributes->get('cognito.signatory_id');

        if (! is_string($signatoryId) || $signatoryId === '') {
            abort(401, 'Unauthenticated.');
        }

        return $signatoryId;
    }
}
