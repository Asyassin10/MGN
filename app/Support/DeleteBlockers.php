<?php

namespace App\Support;

class DeleteBlockers
{
    public static function message(string $subject, array $blockers): ?string
    {
        $blockers = array_filter($blockers);

        if ($blockers === []) {
            return null;
        }

        return 'Impossible de supprimer '.$subject.' : supprimez d’abord '.self::format($blockers).'.';
    }

    public static function format(array $blockers): string
    {
        $items = collect($blockers)
            ->map(fn (int $count, string $label) => $count.' '.$label)
            ->values();

        return $items->count() > 1
            ? $items->slice(0, -1)->implode(', ').' et '.$items->last()
            : $items->first();
    }
}
