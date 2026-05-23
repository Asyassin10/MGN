<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FournisseurFacture extends Model
{
    use HasFactory;

    protected $fillable = ['fournisseur_id', 'fournisseur_releve_compte_id', 'numero_facture', 'date_facture', 'montant', 'note'];

    protected $casts = [
        'date_facture' => 'date',
        'montant' => 'decimal:2',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function releveCompte(): BelongsTo
    {
        return $this->belongsTo(FournisseurReleveCompte::class, 'fournisseur_releve_compte_id');
    }
}
