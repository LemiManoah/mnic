<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a Blade view to a PDF download.
 *
 * The counterpart to CsvExport, and deliberately the same shape so controllers
 * read the same either way. Views live under `resources/views/pdf` and all
 * extend `pdf.layout`, which is where the dompdf constraints (CSS 2.1 only, no
 * flexbox or grid, DejaVu Sans for Unicode) are handled once.
 *
 * The club name is injected here rather than in every view, so a page cannot
 * accidentally render a document with no letterhead.
 */
final readonly class PdfExport
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, string $filename, array $data): Response
    {
        $orientation = isset($data['headings']) && is_array($data['headings']) && count($data['headings']) > 7 ? 'landscape' : 'portrait';

        return Pdf::loadView($view, [
            ...$data,
            'clubName' => config('app.tenant.name', config('app.name')),
        ])
            ->setPaper('a4', $orientation)
            ->download($filename);
    }

    /**
     * Same document, rendered inline so it opens in the browser's PDF viewer
     * rather than landing in the downloads folder. Better for a receipt
     * somebody wants to glance at.
     *
     * @param  array<string, mixed>  $data
     */
    public function stream(string $view, string $filename, array $data): Response
    {
        return Pdf::loadView($view, [
            ...$data,
            'clubName' => config('app.tenant.name', config('app.name')),
        ])
            ->setPaper('a4')
            ->stream($filename);
    }
}
