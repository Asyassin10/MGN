<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'prenom', 'poste', 'telephone'];

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }
}
