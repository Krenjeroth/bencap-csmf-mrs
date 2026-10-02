<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'charter_year' => $this->charter_year,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'office' => $this->whenLoaded('office', fn () => [
                'id' => $this->office->id,
                'code' => $this->office->code,
                'name' => $this->office->name,
            ]),
            'service_type' => $this->whenLoaded('serviceType', fn () => [
                'id' => $this->serviceType->id,
                'type' => $this->serviceType->type,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
