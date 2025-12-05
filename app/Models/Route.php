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
 * @property string|null $description
 * @property string|null $zone_label
 * @property bool $is_active
 */
class Route extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use Activatable;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'zone_label',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function definitions(): HasMany
    {
        return $this->hasMany(RouteDefinition::class);
    }
}
