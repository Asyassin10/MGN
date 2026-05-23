<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChequePartyClient extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'telephone', 'email'];

    public function cheques(): HasMany
    {
        return $this->hasMany(ChequeClient::class, 'client_id');
    }
}
