<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceTypeRequest;
use App\Http\Resources\ServiceTypeResource;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/** Service types admin screen (Internal / External). */
class ServiceTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServiceTypeResource::collection(ServiceType::withCount('services')->orderBy('type')->get());
    }

    public function store(ServiceTypeRequest $request): JsonResponse
    {
        $type = ServiceType::create($request->validated());

        return (new ServiceTypeResource($type->loadCount('services')))->response()->setStatusCode(201);
    }

    public function show(ServiceType $serviceType): ServiceTypeResource
    {
        return new ServiceTypeResource($serviceType->loadCount('services'));
    }

    public function update(ServiceTypeRequest $request, ServiceType $serviceType): ServiceTypeResource
    {
        $serviceType->fill($request->validated())->save();

        return new ServiceTypeResource($serviceType->loadCount('services'));
    }

    public function destroy(ServiceType $serviceType): Response
    {
        $used = $serviceType->services()->count();
        if ($used > 0) {
            throw ValidationException::withMessages([
                'service_type' => "{$used} service(s) use this type. Change their type first.",
            ]);
        }

        $serviceType->delete();

        return response()->noContent();
    }
}
