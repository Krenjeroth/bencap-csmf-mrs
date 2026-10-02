<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'two_factor_enabled' => $this->hasEnabledTwoFactor(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'office' => $this->whenLoaded('office', fn () => $this->office ? [
                'id' => $this->office->id,
                'code' => $this->office->code,
                'name' => $this->office->name,
            ] : null),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'title' => $role->title,
                'is_system' => $role->is_system,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
