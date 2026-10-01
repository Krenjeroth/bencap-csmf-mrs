<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;

/**
 * The signed-in user plus what the web app needs to decide which screens
 * to show and whether to send the user to a password or two-factor step.
 *
 * @mixin User
 */
class MeResource extends UserResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('roles.permissions');
        $isSystem = $this->isSystemAdministrator();

        return [
            ...parent::toArray($request),
            'is_system_administrator' => $isSystem,
            'two_factor_required' => $this->requiresTwoFactor(),
            // System Administrator holds every permission, including any
            // added outside the catalog.
            'permissions' => $isSystem
                ? collect(PermissionCatalog::titles())->merge($this->permissionTitles())->unique()->sort()->values()
                : $this->permissionTitles(),
        ];
    }
}
