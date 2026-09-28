<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'start_time', 'end_time', 'grace_period_minutes', 'break_duration_minutes'])]
class Shift extends Model
{
    use HasFactory;

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
