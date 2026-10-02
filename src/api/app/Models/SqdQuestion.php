<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One Service Quality Dimension statement (SQD0–SQD8) of an ARTA form
 * version. A new ARTA revision adds rows with a new form_version.
 *
 * @property int $id
 * @property string $code
 * @property string $statement
 * @property string $form_version
 * @property bool $included_in_overall
 * @property int $sort_order
 * @property bool $is_active
 */
class SqdQuestion extends Model
{
    /** The ARTA CSM form revision in use (MC 2022-05). */
    public const CURRENT_FORM_VERSION = 'ARTA-2022';

    /** @var list<string> */
    protected $fillable = ['code', 'statement', 'form_version', 'included_in_overall', 'sort_order', 'is_active'];

    /** Mirrors the column defaults so new instances are never null. */
    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['included_in_overall' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * The active questions of the current form, in form order.
     *
     * @param  Builder<SqdQuestion>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('form_version', self::CURRENT_FORM_VERSION)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}
