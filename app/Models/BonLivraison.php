<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonLivraison extends Model
{
    public const PAYMENT_MODES = ['espece' => 'Espèce', 'virement' => 'Virement', 'cheque' => 'Chèque', 'effet' => 'Effet'];

    protected $table = 'bon_livraisons';

    protected $fillable = ['reference', 'client_id', 'client_nom', 'client_telephone', 'employee_id', 'mode_paiement', 'note'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BonLivraisonLine::class);
    }
}
