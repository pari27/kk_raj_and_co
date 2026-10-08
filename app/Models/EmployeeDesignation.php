<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\EmployeeDesignationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_active'])]
class EmployeeDesignation extends Model
{
    /** @use HasFactory<EmployeeDesignationFactory> */
    use HasFactory, HasHashedRouteKey;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'designation_id');
    }
}
