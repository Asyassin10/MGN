<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'prenom', 'poste', 'telephone', 'email', 'address', 'salary', 'salary_payment_day', 'hire_date', 'work_days', 'status', 'notes'];

    protected function casts(): array
    {
        return ['salary' => 'decimal:2', 'hire_date' => 'date', 'work_days' => 'array'];
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function workDays(): HasMany
    {
        return $this->hasMany(EmployeeWorkDay::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(EmployeeAbsence::class);
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(EmployeeSalaryPayment::class);
    }
}
