<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class FinancePdf
{
    public static function download(array $document, string $filename): Response
    {
        $logoPath = public_path('logo-palmeraie.svg');

        $pdf = Pdf::loadView('pdf.finance-document', [
            'document' => PdfArabic::prepare($document),
            'logoDataUri' => file_exists($logoPath)
                ? 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($logoPath))
                : null,
        ]);

        return $pdf->download($filename);
    }
}
