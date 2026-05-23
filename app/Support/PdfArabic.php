<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

class PdfArabic
{
    private static ?Arabic $arabic = null;

    public static function prepare(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::prepare($item);
            }

            return $value;
        }

        if (! is_string($value) || ! preg_match('/\p{Arabic}/u', $value)) {
            return $value;
        }

        return self::arabic()->utf8Glyphs($value, 120, false, true);
    }

    private static function arabic(): Arabic
    {
        return self::$arabic ??= new Arabic();
    }
}
