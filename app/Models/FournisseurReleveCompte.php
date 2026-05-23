<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FournisseurReleveCompte extends Model
{
    use HasFactory;

    protected $fillable = ['fournisseur_id', 'code_client', 'date_releve', 'note'];

    protected $casts = [
        'date_releve' => 'date',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(FournisseurFacture::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FournisseurPayment::class);
    }
}
