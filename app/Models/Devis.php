<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devis extends Model
{
    public const STATUS_PENDING = 'en_attente';
    public const STATUS_VALIDATED = 'valide';
    public const STATUS_CANCELLED = 'annule';

    protected $table = 'devis';

    protected $fillable = ['reference', 'fournisseur_id', 'depot_id', 'note'];

    protected function casts(): array
    {
        return ['validated_at' => 'datetime'];
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class)->withTrashed();
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DevisLine::class);
    }
}
