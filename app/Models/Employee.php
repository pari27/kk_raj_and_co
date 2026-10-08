<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'mobile', 'gender', 'designation_id', 'photo_path'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(EmployeeDesignation::class, 'designation_id');
    }
}
