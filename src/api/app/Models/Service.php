<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A service an office lists in the Citizen's Charter for a given year.
 *
 * @property int $id
 * @property int $office_id
 * @property int $service_type_id
 * @property string $name
 * @property int $charter_year
 * @property bool $is_active
 * @property int $sort_order
 */
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['office_id', 'service_type_id', 'name', 'charter_year', 'is_active', 'sort_order'];

    /** Mirrors the column defaults so new instances are never null. */
    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'charter_year' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Office, $this> */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** @return BelongsTo<ServiceType, $this> */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /** @param  Builder<Service>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
