<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleGroup extends Model
{
    public const DEFAULT_NAME = 'Général';

    protected $fillable = ['name'];

    public static function general(): self
    {
        return static::firstOrCreate(['name' => self::DEFAULT_NAME]);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'group_id');
    }
}
