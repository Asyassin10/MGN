<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function chequeClients(): HasMany
    {
        return $this->hasMany(ChequeClient::class);
    }

    public function chequeFournisseurs(): HasMany
    {
        return $this->hasMany(ChequeFournisseur::class);
    }
}
