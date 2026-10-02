<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonLivraisonLine extends Model
{
    public const PRICE_TYPES = [
        'detail' => 'prix_detail_ht',
        'demi_gros' => 'prix_demi_gros_ht',
        'gros' => 'prix_gros_ht',
        'special' => 'prix_special_ht',
        'min' => 'prix_min',
        'max' => 'prix_max',
    ];

    protected $table = 'bon_livraison_lines';

    protected $fillable = ['bon_livraison_id', 'article_id', 'depot_id', 'operation_id', 'quantity', 'unite', 'price_type', 'prix'];

    public function bonLivraison(): BelongsTo
    {
        return $this->belongsTo(BonLivraison::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }
}
