<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaisseEntry extends Model
{
    use HasFactory;

    public const TYPES = ['entree', 'sortie'];

    protected $fillable = ['type', 'client_id', 'fournisseur_id', 'montant', 'note'];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }
}
