<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depot extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'depot_article')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }
}
