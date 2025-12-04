<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @mixin Builder
 * @property int $id
 * @property int $company_id
 * @property Carbon|null $planning_cutoff_time
 * @property int $default_waiting_minutes
 * @property int $max_drivers_per_partner
 * @property bool $allow_driver_reorder
 * @property array|null $settings
 */
class CompanyConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'planning_cutoff_time',
        'default_waiting_minutes',
        'max_drivers_per_partner',
        'driver_quota_default',
        'allow_driver_reorder',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'planning_cutoff_time' => 'datetime:H:i:s',
            'default_waiting_minutes' => 'integer',
            'max_drivers_per_partner' => 'integer',
            'driver_quota_default' => 'integer',
            'allow_driver_reorder' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
