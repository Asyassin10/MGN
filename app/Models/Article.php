<?php

namespace App\Models;

use App\Support\ArticleNameLookup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    public const PRICE_FIELDS = [
        'commission_vendeur', 'stock_minimum', 'poids',
        'prix_achat_ttc', 'prix_detail_ttc', 'prix_demi_gros_ttc', 'prix_gros_ttc', 'prix_special_ttc',
        'prix_min', 'prix_max',
    ];

    public const UNITS = ['U', 'L'];

    protected $fillable = [
        'reference', 'name', 'group_id', 'nom_fournisseur', 'unite',
        'commission_vendeur', 'stock_minimum', 'poids',
        'prix_achat_ttc', 'prix_detail_ttc', 'prix_demi_gros_ttc', 'prix_gros_ttc', 'prix_special_ttc',
        'prix_min', 'prix_max',
    ];
    protected $appends = ['display_name'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ArticleGroup::class, 'group_id');
    }

    public function depots(): BelongsToMany
    {
        return $this->belongsToMany(Depot::class, 'depot_article')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function operationLines(): HasMany
    {
        return $this->hasMany(OperationLine::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return ArticleNameLookup::resolve((string) $this->reference, (string) $this->name);
    }
}
