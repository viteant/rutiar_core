<?php

namespace App\Models;

use App\Models\Traits\Activatable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Builder
 * @property int $id
 * @property int $company_id
 * @property int $route_definition_id
 * @property int $passenger_id
 * @property int $pickup_order
 * @property string|null $planned_pickup_time
 * @property string|null $pickup_address
 * @property float|null $pickup_lat
 * @property float|null $pickup_lng
 * @property bool $is_active
 */
class RouteDefinitionPassenger extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use Activatable;

    protected $fillable = [
        'company_id',
        'route_definition_id',
        'passenger_id',
        'pickup_order',
        'planned_pickup_time',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'route_definition_id' => 'integer',
            'passenger_id' => 'integer',
            'pickup_order' => 'integer',
            'pickup_lat' => 'float',
            'pickup_lng' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function routeDefinition(): BelongsTo
    {
        return $this->belongsTo(RouteDefinition::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }
}
