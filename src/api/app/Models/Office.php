<?php

namespace App\Models;

use Database\Factories\OfficeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A provincial office or hospital that runs its own client satisfaction survey.
 *
 * @property int $id
 * @property string $code
 * @property string $slug
 * @property string $name
 * @property bool $is_active
 * @property int $sort_order
 */
class Office extends Model
{
    /** @use HasFactory<OfficeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['code', 'slug', 'name', 'is_active', 'sort_order'];

    /** Mirrors the column defaults so new instances are never null. */
    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @param  Builder<Office>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Office>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }
}
