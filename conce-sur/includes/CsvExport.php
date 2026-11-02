<?php
declare(strict_types=1);

/** Exportador CSV protegido frente a fórmulas ejecutables en hojas de cálculo. */
final class CsvExport
{
    public static function safeCell($value): string
    {
        $value = (string)($value ?? '');
        if ($value !== '' && preg_match('/^[\\s]*[=+@\\-\\t\\r]/u', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    /** @param array<int,string> $headers @param iterable<array<int,mixed>> $rows */
    public static function stream(string $filename, array $headers, iterable $rows): void
    {
        if (headers_sent()) {
            throw new RuntimeException('No se pudo iniciar la descarga porque ya se enviaron encabezados.');
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            throw new RuntimeException('No se pudo generar el archivo CSV.');
        }
        // BOM UTF-8 para que Excel abra correctamente los acentos.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_map([self::class, 'safeCell'], $headers), ';', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'safeCell'], $row), ';', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
