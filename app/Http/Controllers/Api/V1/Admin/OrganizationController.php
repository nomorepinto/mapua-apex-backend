<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Aws\DynamoDb\OrganizationRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreOrganizationRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(OrganizationRecords $organizations): AnonymousResourceCollection
    {
        return OrganizationResource::collection($organizations->list());
    }

    public function store(StoreOrganizationRequest $request, OrganizationRecords $organizations): JsonResponse
    {
        $item = $organizations->create($request->validated('name'));

        return (new OrganizationResource($item))->response()->setStatusCode(201);
    }
}
