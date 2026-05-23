<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequeFournisseur extends Model
{
    use HasFactory;

    public const STATUSES = ['en_cours', 'en_caisse', 'impaye'];
    public const TYPES = ['cheque', 'effet'];

    protected $fillable = [
        'type',
        'numero_cheque',
        'fournisseur_id',
        'bank_id',
        'montant',
        'banque',
        'piece_jointe',
        'motif',
        'tireur_signataire',
        'date_emission',
        'date_echeance',
        'statut',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'montant' => 'decimal:2',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(ChequePartyFournisseur::class, 'fournisseur_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
