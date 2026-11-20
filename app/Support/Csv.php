<?php

namespace App\Support;

use App\Http\Controllers\Admin\StudentController;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Excel-friendly CSV downloads (UTF-8 BOM for Arabic, formula injection neutralised). */
class Csv
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => StudentController::csvSafe(is_float($v) ? (string) round($v, 2) : (string) $v), $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
