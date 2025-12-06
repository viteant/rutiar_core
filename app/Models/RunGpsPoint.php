<?php

namespace App\Models;

use App\Models\Traits\Activatable;
use App\Models\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 * @property int $id
 * @property int $company_id
 * @property int $run_id
 * @property Carbon $recorded_at
 * @property float $lat
 * @property float $lng
 * @property float|null $speed_kmh
 * @property bool $is_active
 */
class RunGpsPoint extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use Activatable;

    protected $table = 'run_gps_points';

    protected $fillable = [
        'company_id',
        'run_id',
        'recorded_at',
        'lat',
        'lng',
        'speed_kmh',
        'is_active',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'lat'         => 'decimal:7',
        'lng'         => 'decimal:7',
        'speed_kmh'   => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }
}
