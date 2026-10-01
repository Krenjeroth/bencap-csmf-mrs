<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

/**
 * Short lookup lists for pickers. A user who may manage users needs the
 * role names to assign them, even without access to the Roles screen; the
 * same applies to permission names on the Roles screen.
 */
class OptionsController extends Controller
{
    /** GET /api/v1/admin/role-options (users.view) */
    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => Role::query()
                ->orderByDesc('is_system')
                ->orderBy('title')
                ->get(['id', 'title', 'is_system']),
        ]);
    }

    /** GET /api/v1/admin/permission-options (roles.view) */
    public function permissions(): JsonResponse
    {
        return response()->json([
            'data' => Permission::query()
                ->orderBy('title')
                ->get(['id', 'title', 'description'])
                ->map(fn (Permission $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'resource' => $p->resource(),
                    'description' => $p->description,
                ]),
        ]);
    }
}
