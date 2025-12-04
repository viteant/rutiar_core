<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin Builder
 * @property string name
 * @property string code
 * @property string country
 * @property string timezone
 * @property boolean is_active
 * @property integer $id
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'country',
        'timezone',
        'is_active',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function config(): HasOne
    {
        return $this->hasOne(CompanyConfig::class);
    }
}
