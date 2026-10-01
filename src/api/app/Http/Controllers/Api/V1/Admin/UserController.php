<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListUsersRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\SyncUserRolesRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Users admin screen. Permissions are checked by route middleware. */
class UserController extends Controller
{
    public function __construct(private readonly UserAccountService $accounts) {}

    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $request->sortColumn();

        $users = User::query()
            ->with('roles')
            ->when($request->search(), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)))
            ->when($request->validated('role_id'), fn ($q, $roleId) => $q->whereHas('roles', fn ($r) => $r->whereKey($roleId)))
            ->when($request->validated('status'), fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        [$user, $password] = $this->accounts->create(
            $request->user(),
            $data,
            collect($data['role_ids'])->map(fn ($id) => (int) $id),
        );

        return (new UserResource($user))
            ->additional(['temporary_password' => $password])
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->accounts->update($request->user(), $user, $request->validated()));
    }

    public function destroy(Request $request, User $user): Response
    {
        $this->accounts->delete($request->user(), $user);

        return response()->noContent();
    }

    public function syncRoles(SyncUserRolesRequest $request, User $user): UserResource
    {
        return new UserResource($this->accounts->syncRoles($request->user(), $user, $request->roleIds()));
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(['reset_two_factor' => ['sometimes', 'boolean']]);
        $password = $this->accounts->resetPassword($user, (bool) ($validated['reset_two_factor'] ?? false));

        return response()->json(['temporary_password' => $password]);
    }
}
