<?php

namespace App\Models;

use Database\Factories\ServiceTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Internal (to the province's own offices and employees) or External
 * (to citizens, businesses and other governments), as ARTA defines them.
 *
 * @property int $id
 * @property string $type
 * @property string|null $description
 */
class ServiceType extends Model
{
    /** @use HasFactory<ServiceTypeFactory> */
    use HasFactory;

    public const INTERNAL = 'Internal';

    public const EXTERNAL = 'External';

    /** @var list<string> */
    protected $fillable = ['type', 'description'];

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
