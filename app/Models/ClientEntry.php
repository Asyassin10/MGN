<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientEntry extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'date_entree', 'montant', 'description'];

    protected $casts = [
        'date_entree' => 'date',
        'montant' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
