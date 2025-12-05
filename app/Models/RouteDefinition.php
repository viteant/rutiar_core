<?php

namespace App\Models;

use App\Enums\RunDirection;
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
 * @property int $route_id
 * @property int $corporate_id
 * @property int $partner_id
 * @property int|null $driver_id
 * @property int $version
 * @property bool $is_active
 * @property int|null $previous_definition_id
 * @property RunDirection $direction
 * @property string|null $reference_time
 * @property string|null $billing_code
 * @property float|null $base_fare_amount
 * @property int $created_by_user_id
 */
class RouteDefinition extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use Activatable;

    protected $fillable = [
        'company_id',
        'route_id',
        'corporate_id',
        'partner_id',
        'driver_id',
        'version',
        'is_active',
        'previous_definition_id',
        'direction',
        'reference_time',
        'billing_code',
        'base_fare_amount',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'route_id' => 'integer',
            'corporate_id' => 'integer',
            'partner_id' => 'integer',
            'driver_id' => 'integer',
            'previous_definition_id' => 'integer',
            'version' => 'integer',
            'is_active' => 'boolean',
            'base_fare_amount' => 'decimal:2',
            // time lo dejamos como string; no hace falta inventarnos un tipo
            'direction' => RunDirection::class,
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function previousDefinition(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_definition_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(RouteDefinitionPassenger::class);
    }

}
