<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Activatable
{
    protected static function bootActivatable(): void
    {
        static::addGlobalScope('active', function (Builder $builder): void {
            $builder->where('is_active', true);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    public function delete(): void
    {
        // Never physically delete, always use logical deactivation
        $this->deactivate();
    }
}
