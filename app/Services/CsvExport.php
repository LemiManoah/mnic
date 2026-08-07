<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV download.
 *
 * Streamed rather than built in memory: an arrears ageing or audit export grows
 * with the club's whole history, and the point of an export is that it still
 * works in year five.
 *
 * A leading BOM is written so Excel opens UTF-8 correctly — without it, member
 * names with accents arrive mangled, which is the sort of thing that makes
 * people distrust the whole file.
 */
final readonly class CsvExport
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<int, list<string|int|null>>  $rows
     */
    public function stream(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return Response::streamDownload(
            function () use ($headings, $rows): void {
                $handle = fopen('php://output', 'wb');

                if ($handle === false) {
                    return;
                }

                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, $headings, escape: '\\');

                foreach ($rows as $row) {
                    fputcsv($handle, $row, escape: '\\');
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
