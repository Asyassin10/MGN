<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLine extends Model
{
    use HasFactory;

    protected $fillable = ['operation_id', 'article_id', 'reference', 'quantity'];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
