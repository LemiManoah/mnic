<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

/**
 * One report, either format.
 *
 * Headings and rows are built once by the caller and handed here; whether the
 * reader wanted a spreadsheet or a document is a rendering decision, not a
 * reason to build the data twice. Two builders would drift, and somebody
 * comparing a PDF against a CSV would have no way to tell which was right.
 */
final readonly class TabularReport
{
    public function __construct(
        private CsvExport $csv,
        private PdfExport $pdf,
    ) {
        //
    }

    /**
     * Rows are `array<int, ...>` rather than `list<...>` on purpose: they come
     * from Eloquent collections, and neither consumer needs sequential keys —
     * fputcsv takes any array and the Blade view calls array_values itself.
     *
     * @param  list<string>  $headings
     * @param  array<int, array<int, string|int|null>>  $rows
     * @param  list<int>  $numericColumns  Zero-based indexes to right-align in the PDF.
     */
    public function render(
        string $format,
        string $basename,
        string $title,
        string $subtitle,
        array $headings,
        array $rows,
        array $numericColumns = [],
    ): Response {
        if ($format !== 'pdf') {
            return $this->csv->stream(sprintf('%s.csv', $basename), $headings, $rows);
        }

        return $this->pdf->download(
            'pdf.tabular',
            sprintf('%s.pdf', $basename),
            [
                'title' => $title,
                'subtitle' => $subtitle,
                'headings' => $headings,
                'rows' => $rows,
                'numericColumns' => $numericColumns,
            ],
        );
    }
}
