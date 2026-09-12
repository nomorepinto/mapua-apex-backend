<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Aws\DynamoDb\GetEvent;
use App\Aws\DynamoDb\GetSubmission;
use App\Aws\DynamoDb\ListSubmissionNotifications;
use App\Http\CognitoIdentity;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(
        Request $request,
        string $event,
        string $submission,
        GetEvent $events,
        GetSubmission $submissions,
        ListSubmissionNotifications $notifications,
    ): AnonymousResourceCollection {
        $events->forOrganization($event, CognitoIdentity::organizationId($request));
        $submissions->require($event, $submission);

        return NotificationResource::collection($notifications->handle($submission));
    }
}
