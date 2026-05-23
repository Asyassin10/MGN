<?php

namespace App\Support;

class DownloadFilename
{
    public static function pdf(string ...$parts): string
    {
        $name = collect($parts)
            ->filter(fn ($part) => trim($part) !== '')
            ->map(fn ($part) => self::segment($part))
            ->filter()
            ->implode('-');

        return ($name !== '' ? $name : 'document').'.pdf';
    }

    private static function segment(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim((string) $value, '-');
    }
}
