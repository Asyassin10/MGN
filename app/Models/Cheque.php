<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cheque extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = ['en_cours', 'encaisse', 'impaye'];
    public const TYPES = ['client', 'fournisseur'];

    protected $fillable = [
        'type',
        'tier_id',
        'tier_type',
        'numero_cheque',
        'banque',
        'tireur_signataire',
        'montant',
        'date_emission',
        'date_echeance',
        'statut',
        'note',
        'attachment',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'montant' => 'decimal:2',
    ];

    public function tier(): MorphTo
    {
        return $this->morphTo();
    }
}
