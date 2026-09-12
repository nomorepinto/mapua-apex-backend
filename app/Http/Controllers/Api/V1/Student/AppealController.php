<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Aws\DynamoDb\CreateAppeal;
use App\Aws\DynamoDb\GetEvent;
use App\Aws\DynamoDb\GetSubmission;
use App\Aws\DynamoDb\ListSubmissionAppeals;
use App\Http\CognitoIdentity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Student\StoreAppealRequest;
use App\Http\Resources\Api\V1\AppealResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppealController extends Controller
{
    public function index(
        Request $request,
        string $event,
        string $submission,
        GetEvent $events,
        GetSubmission $submissions,
        ListSubmissionAppeals $appeals,
    ): AnonymousResourceCollection {
        $events->forOrganization($event, CognitoIdentity::organizationId($request));
        $submissions->require($event, $submission);

        return AppealResource::collection($appeals->handle($submission));
    }

    public function store(StoreAppealRequest $request, CreateAppeal $create): JsonResponse
    {
        $validated = $request->validated();
        $item = $create->handle(
            CognitoIdentity::organizationId($request),
            $validated['event_id'],
            $validated['submission_id'],
            $validated['comment'],
        );

        return (new AppealResource($item))->response()->setStatusCode(201);
    }
}
