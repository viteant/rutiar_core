<?php

namespace App\Models;

use App\Models\Traits\Activatable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property boolean $is_active
 * @property integer $tax_id
 * @property integer $driver_quota
 */
class Partner extends Model
{
    use HasFactory, BelongsToCompany, Activatable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'tax_id',
        'is_active',
        'driver_quota'
    ];

    protected function casts(): array
    {
        return [
            'driver_quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function effectiveDriverQuota(): ?int
    {
        if ($this->driver_quota !== null) {
            return $this->driver_quota;
        }

        $company = $this->company;

        if (!$company || !$company->config) {
            return null;
        }

        return $company->config->driver_quota_default;
    }
}
