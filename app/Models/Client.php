<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['nom', 'telephone', 'ville', 'note'];

    protected $appends = ['balance'];

    public function entries(): HasMany
    {
        return $this->hasMany(ClientEntry::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }

    public function getBalanceAttribute(): float
    {
        $entries = (float) ($this->entries_sum_montant ?? $this->entries()->sum('montant'));
        $payments = (float) ($this->payments_sum_montant ?? $this->payments()->sum('montant'));

        return round($entries - $payments, 2);
    }
}
