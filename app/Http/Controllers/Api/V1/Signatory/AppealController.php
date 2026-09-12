<?php

namespace App\Http\Controllers\Api\V1\Signatory;

use App\Aws\DynamoDb\ListSignatoryAppeals;
use App\Aws\DynamoDb\ResolveAppeal;
use App\Http\CognitoIdentity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Signatory\ResolveAppealRequest;
use App\Http\Resources\Api\V1\AppealResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppealController extends Controller
{
    public function index(Request $request, ListSignatoryAppeals $appeals): AnonymousResourceCollection
    {
        return AppealResource::collection($appeals->handle(CognitoIdentity::signatoryId($request)));
    }

    public function resolve(
        ResolveAppealRequest $request,
        string $event,
        string $submission,
        string $appeal,
        ResolveAppeal $resolve,
    ): AppealResource {
        $validated = $request->validated();

        return new AppealResource($resolve->handle(
            CognitoIdentity::signatoryId($request),
            $event,
            $submission,
            $appeal,
            $validated['resolution'],
            $validated['comment'],
        ));
    }
}
