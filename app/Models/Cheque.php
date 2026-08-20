<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    public const STATUSES = ['en_cours', 'en_caisse', 'impaye'];

    public const TYPES = ['cheque', 'effet'];

    protected $fillable = [
        'type',
        'numero_cheque',
        'client_nom',
        'tireur_signataire',
        'date_emission',
        'date_echeance',
        'statut',
        'facture_recue',
        'facture_donnee',
        'montant',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
            'date_echeance' => 'date',
            'facture_recue' => 'boolean',
            'facture_donnee' => 'boolean',
            'montant' => 'decimal:2',
        ];
    }
}
