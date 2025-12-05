<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Activatable
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }
}
