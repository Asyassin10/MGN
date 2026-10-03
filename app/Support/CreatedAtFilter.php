<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CreatedAtFilter
{
    public const KEYS = ['created_from', 'created_to'];

    /** Keeps only rows created between created_from and created_to (inclusive, either bound optional). */
    public static function apply(Builder|QueryBuilder $query, array $filters, string $column = 'created_at'): Builder|QueryBuilder
    {
        foreach (['created_from' => '>=', 'created_to' => '<='] as $key => $operator) {
            $value = $filters[$key] ?? null;

            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $query->whereDate($column, $operator, $value);
            }
        }

        return $query;
    }
}