<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeImpaye extends Model
{
    public const STATUSES = ['impaye', 'paye'];

    public const TYPES = ['cheque', 'effet'];

    protected $table = 'cheques_impayes';

    protected $fillable = [
        'type',
        'numero_cheque',
        'fournisseur_nom',
        'tireur_signataire',
        'date_remise',
        'statut',
        'date_paiement',
        'mode_paiement',
        'montant',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date_remise' => 'date',
            'date_paiement' => 'date',
            'montant' => 'decimal:2',
        ];
    }
}
