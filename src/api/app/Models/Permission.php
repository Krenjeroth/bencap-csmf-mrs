<?php

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A permission title in resource.action form (dashboard.view, users.create).
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property bool $is_protected
 */
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['title', 'description'];

    /** Mirrors the column default so new instances are never null. */
    protected $attributes = ['is_protected' => false];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_protected' => 'boolean'];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /** The resource part of the title: "users" for "users.create". */
    public function resource(): string
    {
        return explode('.', $this->title, 2)[0];
    }
}
