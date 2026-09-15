<?php

namespace App\Http\Controllers\Api\V1\Signatory;

use App\Aws\DynamoDb\ApproveSubmission;
use App\Aws\DynamoDb\DenySubmission;
use App\Aws\DynamoDb\GetSubmission;
use App\Aws\DynamoDb\ListSignatoryQueue;
use App\Aws\DynamoDb\ReturnSubmission;
use App\Http\CognitoIdentity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Signatory\DenySubmissionRequest;
use App\Http\Resources\Api\V1\SubmissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubmissionController extends Controller
{
    public function index(Request $request, ListSignatoryQueue $queue): AnonymousResourceCollection
    {
        return SubmissionResource::collection($queue->handle(CognitoIdentity::signatoryId($request)));
    }

    public function show(Request $request, string $event, string $submission, GetSubmission $submissions): SubmissionResource
    {
        $item = $submissions->require($event, $submission);
        $signatory = CognitoIdentity::signatoryId($request);

        if (($item['current_signatory'] ?? null) !== 'SIGNATORY#'.$signatory) {
            abort(404);
        }

        return new SubmissionResource($item);
    }

    public function approve(
        Request $request,
        string $event,
        string $submission,
        ApproveSubmission $approve,
    ): SubmissionResource {
        return new SubmissionResource($approve->handle(CognitoIdentity::signatoryId($request), $event, $submission));
    }

    public function deny(
        DenySubmissionRequest $request,
        string $event,
        string $submission,
        DenySubmission $deny,
    ): SubmissionResource {
        return new SubmissionResource($deny->handle(
            CognitoIdentity::signatoryId($request),
            $event,
            $submission,
            $request->validated('comment'),
        ));
    }

    public function returnForRevision(
        DenySubmissionRequest $request,
        string $event,
        string $submission,
        ReturnSubmission $return,
    ): SubmissionResource {
        return new SubmissionResource($return->handle(
            CognitoIdentity::signatoryId($request),
            $event,
            $submission,
            $request->validated('comment'),
        ));
    }
}
