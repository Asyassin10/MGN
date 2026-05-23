<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fournisseur extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['nom', 'telephone', 'ville', 'note'];

    protected $appends = ['balance'];

    public function factures(): HasMany
    {
        return $this->hasMany(FournisseurFacture::class);
    }

    public function releveComptes(): HasMany
    {
        return $this->hasMany(FournisseurReleveCompte::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FournisseurPayment::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(FournisseurCheque::class);
    }

    public function getBalanceAttribute(): float
    {
        $factures = (float) ($this->factures_sum_montant ?? $this->factures()->sum('montant'));
        $payments = (float) ($this->payments_sum_montant ?? $this->payments()->sum('montant'));

        return round($factures - $payments, 2);
    }
}
