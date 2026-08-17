<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWorkDay extends Model
{
    protected $fillable = ['employee_id', 'work_date', 'status', 'note'];

    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }
}
