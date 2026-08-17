<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequeClient extends Model
{
    use HasFactory;

    public const STATUSES = ['en_cours', 'en_caisse', 'impaye'];

    public const TYPES = ['cheque', 'effet'];

    protected $fillable = [
        'type',
        'numero_cheque',
        'client_id',
        'bank_id',
        'montant',
        'banque',
        'piece_jointe',
        'motif',
        'tireur_signataire',
        'date_emission',
        'date_echeance',
        'statut',
        'facture_recue',
        'facture_donnee',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'montant' => 'decimal:2',
        'facture_recue' => 'boolean',
        'facture_donnee' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
