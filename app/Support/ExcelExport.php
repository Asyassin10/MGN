<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExport
{
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            echo "\xEF\xBB\xBF";
            echo '<table border="1"><thead><tr>';

            foreach ($headers as $header) {
                echo '<th>'.e($header).'</th>';
            }

            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';

                foreach ($row as $cell) {
                    echo '<td>'.e((string) $cell).'</td>';
                }

                echo '</tr>';
            }

            echo '</tbody></table>';
        }, Str::finish($filename, '.xls'), [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}
