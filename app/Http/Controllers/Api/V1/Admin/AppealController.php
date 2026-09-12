<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Aws\DynamoDb\ScanAppeals;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppealResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppealController extends Controller
{
    public function index(ScanAppeals $scan): AnonymousResourceCollection
    {
        return AppealResource::collection($scan->handle());
    }
}
