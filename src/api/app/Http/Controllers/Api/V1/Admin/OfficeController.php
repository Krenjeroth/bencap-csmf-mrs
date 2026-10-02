<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListOfficesRequest;
use App\Http\Requests\Admin\OfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/** Offices admin screen. Permissions are checked by route middleware. */
class OfficeController extends Controller
{
    public function index(ListOfficesRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $request->sortColumn();

        $offices = Office::query()
            ->with('parent:id,code,name')
            ->withCount(['services', 'users', 'children', 'services as active_services_count' => fn ($q) => $q->where('is_active', true)])
            ->when($request->search(), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('code', 'like', $term)
                ->orWhere('name', 'like', $term)))
            ->when($request->validated('status'), fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderBy($column, $direction)
            ->orderBy('name')
            ->paginate($request->perPage())
            ->withQueryString();

        return OfficeResource::collection($offices);
    }

    public function store(OfficeRequest $request): JsonResponse
    {
        $office = Office::create($request->validated());

        return (new OfficeResource($office->load('parent:id,code,name')->loadCount(['services', 'users', 'children'])))->response()->setStatusCode(201);
    }

    public function show(Office $office): OfficeResource
    {
        return new OfficeResource($office->load('parent:id,code,name')->loadCount(['services', 'users', 'children']));
    }

    public function update(OfficeRequest $request, Office $office): OfficeResource
    {
        $office->fill($request->validated())->save();

        return new OfficeResource($office->load('parent:id,code,name')->loadCount(['services', 'users', 'children']));
    }

    /** Only an office nothing refers to can be deleted; otherwise deactivate it. */
    public function destroy(Office $office): Response
    {
        $children = $office->children()->count();
        if ($children > 0) {
            throw ValidationException::withMessages([
                'office' => "{$children} office(s) sit under this office. Move them to another office first.",
            ]);
        }

        $services = $office->services()->count();
        $users = $office->users()->withTrashed()->count();
        if ($services > 0 || $users > 0) {
            throw ValidationException::withMessages([
                'office' => "This office has {$services} service(s) and {$users} user account(s). Deactivate it instead, so its history stays intact.",
            ]);
        }

        $office->delete();

        return response()->noContent();
    }
}
