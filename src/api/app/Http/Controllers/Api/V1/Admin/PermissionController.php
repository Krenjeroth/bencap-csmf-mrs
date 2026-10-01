<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListPermissionsRequest;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Permissions admin screen. Catalog permissions (is_protected) are checked
 * in code, so their titles are fixed and they cannot be deleted.
 */
class PermissionController extends Controller
{
    public function index(ListPermissionsRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $request->sortColumn();

        $permissions = Permission::query()
            ->withCount('roles')
            ->when($request->search(), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'like', $term)
                ->orWhere('description', 'like', $term)))
            ->orderBy($column, $direction)
            ->paginate($request->perPage())
            ->withQueryString();

        return PermissionResource::collection($permissions);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = DB::transaction(function () use ($request) {
            $permission = Permission::create($request->validated());

            // System Administrator holds every permission, including new ones.
            Role::where('is_system', true)->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$permission->id]));

            return $permission;
        });

        return (new PermissionResource($permission->loadCount('roles')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Permission $permission): PermissionResource
    {
        return new PermissionResource($permission->loadCount('roles'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $data = $request->validated();

        if ($permission->is_protected && isset($data['title']) && $data['title'] !== $permission->title) {
            throw ValidationException::withMessages([
                'title' => 'This permission is used by the system, so its title cannot change. You can edit its description.',
            ]);
        }

        $permission->fill($data)->save();

        return new PermissionResource($permission->loadCount('roles'));
    }

    public function destroy(Permission $permission): Response
    {
        if ($permission->is_protected) {
            throw ValidationException::withMessages([
                'permission' => 'This permission is used by the system and cannot be deleted.',
            ]);
        }

        $permission->delete();

        return response()->noContent();
    }
}
