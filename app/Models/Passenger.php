<?php

namespace App\Models;

use App\Models\Traits\Activatable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model
{
    use HasFactory, BelongsToCompany, Activatable;

    protected $table = 'passengers';

    protected $fillable = [
        'company_id',
        'corporate_id',
        'full_name',
        'employee_code',
        'document_id',
        'home_address',
        'home_lat',
        'home_lng',
        'shift_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'home_lat'  => 'decimal:7',
            'home_lng'  => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }
}
