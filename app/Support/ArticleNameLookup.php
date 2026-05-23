<?php

namespace App\Support;

class ArticleNameLookup
{
    private static ?array $cache = null;

    public static function resolve(string $reference, ?string $currentName = null): string
    {
        $lookup = self::map();
        $candidate = $lookup[$reference] ?? null;

        if ($candidate && self::isBetter($candidate, $currentName)) {
            return $candidate;
        }

        return trim((string) $currentName);
    }

    public static function map(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = '/Users/mac/Downloads/ETAT SL3A 2026.docx';
        if (! class_exists(\ZipArchive::class) || ! file_exists($path)) {
            return self::$cache = [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return self::$cache = [];
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $text = preg_replace('/<w:tab\\/>/', ' ', $xml);
        $text = preg_replace('/<\\/w:p>/', "\n", (string) $text);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $lines = collect(preg_split('/\\R+/', $text))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        $skip = ['سعر الجملة', 'سعر التقسيط', 'سعر الشراء', 'السلعة', 'كود', 'لائحة السلعة', 'POS'];
        $articles = [];

        foreach ($lines as $index => $line) {
            if (! preg_match('/^\\d{5}$/', $line)) {
                continue;
            }

            $previous = self::collectChunk($lines, $index, -1, $skip);
            $next = self::collectChunk($lines, $index, 1, $skip);

            $best = self::score($next) >= self::score($previous) ? $next : $previous;
            $best = trim((string) preg_replace('/\\s+/', ' ', $best));

            if ($best !== '') {
                $articles[$line] = $best;
            }
        }

        return self::$cache = $articles;
    }

    private static function collectChunk($lines, int $start, int $direction, array $skip): string
    {
        $parts = [];
        $index = $start + $direction;

        while ($index >= 0 && $index < $lines->count() && count($parts) < 3) {
            $value = trim((string) $lines[$index]);

            if ($value === '' || preg_match('/^\\d{5}$/', $value) || in_array($value, $skip, true)) {
                break;
            }

            $parts[] = $value;
            $index += $direction;
        }

        if ($direction < 0) {
            $parts = array_reverse($parts);
        }

        return self::clean(implode(' ', $parts));
    }

    private static function clean(string $value): string
    {
        $normalized = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        $value = $normalized !== false ? $normalized : $value;
        $value = str_replace('�', ' ', $value);
        $value = preg_replace('/[^\\p{Arabic}\\p{L}\\p{N}\\s.,:;()\\-\\/]/u', ' ', $value);

        return trim((string) preg_replace('/\\s+/', ' ', (string) $value));
    }

    private static function score(string $value): int
    {
        $value = self::clean($value);
        if ($value === '') {
            return 0;
        }

        $length = mb_strlen($value);
        $words = count(array_filter(explode(' ', $value)));

        return ($length * 2) + ($words * 5);
    }

    private static function isBetter(string $candidate, ?string $currentName): bool
    {
        return self::score($candidate) > self::score((string) $currentName);
    }
}
