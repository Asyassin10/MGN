<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class FinancePdf
{
    public static function preview(array $document, string $filename): Response
    {
        $pdf = Pdf::loadView('pdf.finance-document', [
            'document' => PdfArabic::prepare($document),
        ]);

        // Inline rendering lets the browser preview and download without persisting a PDF on disk.
        return $pdf->stream($filename);
    }

    public static function a5(string $view, array $document, string $filename): Response
    {
        $pdf = Pdf::loadView($view, [
            'document' => PdfArabic::prepare($document),
        ])->setPaper('a5', 'portrait');

        return $pdf->stream($filename);
    }
}
