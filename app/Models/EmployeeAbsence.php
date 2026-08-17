<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAbsence extends Model
{
    protected $fillable = ['employee_id', 'absence_date', 'status', 'note'];

    protected function casts(): array
    {
        return ['absence_date' => 'date'];
    }
}
