<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListRolesRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Roles admin screen. Permissions are checked by route middleware. */
class RoleController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(ListRolesRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $request->sortColumn();

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($request->search(), fn ($q, $term) => $q->where('title', 'like', $term))
            ->orderByDesc('is_system')
            ->orderBy($column, $direction)
            ->paginate($request->perPage())
            ->withQueryString();

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = DB::transaction(function () use ($data) {
            $role = Role::create(['title' => $data['title'], 'description' => $data['description'] ?? null]);
            if (! empty($data['permission_ids'])) {
                $this->syncAndAudit($role, collect($data['permission_ids'])->map(fn ($id) => (int) $id));
            }

            return $role;
        });

        return (new RoleResource($role->load('permissions')->loadCount(['permissions', 'users'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions')->loadCount(['permissions', 'users']));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $data = $request->validated();

        if ($role->is_system && isset($data['title']) && $data['title'] !== $role->title) {
            throw ValidationException::withMessages(['title' => 'The System Administrator role cannot be renamed.']);
        }

        $role->fill($data)->save();

        return new RoleResource($role->load('permissions')->loadCount(['permissions', 'users']));
    }

    public function destroy(Role $role): Response
    {
        if ($role->is_system) {
            throw ValidationException::withMessages(['role' => 'The System Administrator role cannot be deleted.']);
        }

        $assigned = $role->users()->count();
        if ($assigned > 0) {
            throw ValidationException::withMessages([
                'role' => "This role is assigned to {$assigned} user(s). Remove it from them first.",
            ]);
        }

        $role->delete();

        return response()->noContent();
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RoleResource
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'permission_ids' => 'The System Administrator role always has every permission.',
            ]);
        }

        DB::transaction(fn () => $this->syncAndAudit($role, $request->permissionIds()));

        return new RoleResource($role->load('permissions')->loadCount(['permissions', 'users']));
    }

    /** @param  Collection<int, int>  $permissionIds */
    private function syncAndAudit(Role $role, $permissionIds): void
    {
        $before = $role->permissions()->pluck('title')->sort()->values()->all();
        $role->permissions()->sync($permissionIds);
        $after = $role->permissions()->pluck('title')->sort()->values()->all();

        if ($before !== $after) {
            $this->audit->record('permissions_synced', $role, ['permissions' => $before], ['permissions' => $after]);
        }
    }
}
