<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Short lookup lists for pickers. A user who may manage users needs the
 * role and office names to assign them, even without access to the Roles
 * or Offices screens; the same applies to the other pickers.
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

    /** GET /api/v1/admin/office-options (users.view, offices.view or services.view) */
    public function offices(Request $request): JsonResponse
    {
        // Through the Gate, so deactivated accounts are refused like everywhere else.
        abort_unless($request->user()->canAny(['users.view', 'offices.view', 'services.view']), 403);

        return response()->json([
            'data' => Office::query()
                ->ordered()
                ->get(['id', 'code', 'name', 'is_active']),
        ]);
    }

    /** GET /api/v1/admin/service-type-options (services.view) */
    public function serviceTypes(): JsonResponse
    {
        return response()->json([
            'data' => ServiceType::query()->orderBy('type')->get(['id', 'type']),
        ]);
    }
}
