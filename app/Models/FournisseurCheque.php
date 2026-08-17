<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FournisseurCheque extends Model
{
    use HasFactory;

    public const STATUSES = ['en_cours', 'en_caisse', 'impaye'];

    public const TYPES = ['cheque', 'effet'];

    protected $fillable = [
        'fournisseur_id',
        'fournisseur_releve_compte_id',
        'type',
        'numero_cheque',
        'banque',
        'montant',
        'piece_jointe',
        'motif',
        'tireur_signataire',
        'date_emission',
        'date_echeance',
        'statut',
        'facture_recue',
        'facture_donnee',
        'note',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'montant' => 'decimal:2',
        'facture_recue' => 'boolean',
        'facture_donnee' => 'boolean',
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
