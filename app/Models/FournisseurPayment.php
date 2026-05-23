<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FournisseurPayment extends Model
{
    use HasFactory;

    protected $fillable = ['fournisseur_id', 'fournisseur_releve_compte_id', 'fournisseur_cheque_id', 'numero_cheque', 'banque', 'date_echeance', 'date_paiement', 'montant', 'mode', 'reference', 'note'];

    protected $casts = [
        'date_paiement' => 'date',
        'date_echeance' => 'date',
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

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(FournisseurCheque::class, 'fournisseur_cheque_id');
    }
}
