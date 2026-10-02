<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A "region of residence" option on the guest form (playbook Q9).
 *
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 */
class Region extends Model
{
    /** @var list<string> */
    protected $fillable = ['name', 'sort_order', 'is_active'];

    /** Mirrors the column defaults so new instances are never null. */
    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @param  Builder<Region>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Region>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }
}
