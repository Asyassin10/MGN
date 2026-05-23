<?php

namespace App\Models;

use App\Support\ArticleNameLookup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['reference', 'name'];
    protected $appends = ['display_name'];

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
