<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPayment extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'date_paiement', 'montant', 'mode', 'reference', 'note'];

    protected $casts = [
        'date_paiement' => 'date',
        'montant' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
