<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class InvoiceSimpleImportService
{
    public const STATUS_NEW = 'new';

    public const STATUS_DUPLICATE_FILE = 'duplicate_file';

    public const STATUS_EXISTING = 'existing';

    public const STATUS_PAID = 'paid';

    public const STATUS_INVALID = 'invalid';

    private const REQUIRED_HEADERS = ['codigo', 'nombre', 'refpago', 'valor'];

    public function preview(UploadedFile $file, string $fechaLimite): array
    {
        $parsedRows = $this->readRows($file, $fechaLimite);
        $existingByReference = $this->existingInvoices($parsedRows);
        $seenReferences = [];
        $rows = [];

        foreach ($parsedRows as $row) {
            $errors = $row['errors'];
            $reference = $row['refpago'];
            $firstRow = $reference !== '' ? ($seenReferences[$reference] ?? null) : null;

            if ($reference !== '' && $firstRow === null) {
                $seenReferences[$reference] = $row['row_number'];
            }

            if ($firstRow !== null) {
                $status = self::STATUS_DUPLICATE_FILE;
                $observation = "La referencia aparecio primero en la fila {$firstRow}.";
            } elseif ($errors !== []) {
                $status = self::STATUS_INVALID;
                $observation = implode(' ', $errors);
            } elseif (isset($existingByReference[$reference])) {
                $invoice = $existingByReference[$reference];
                $isPaid = $invoice->status === 'pagada'
                    || strtoupper((string) $invoice->wompi_status) === 'APPROVED';
                $status = $isPaid ? self::STATUS_PAID : self::STATUS_EXISTING;
                $observation = $isPaid
                    ? 'La factura ya esta pagada y no sera modificada.'
                    : 'La referencia ya existe y sera omitida.';
            } else {
                $status = self::STATUS_NEW;
                $observation = 'Lista para importar.';
            }

            $rows[] = array_merge($row, [
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'observation' => $observation,
            ]);
        }

        return [
            'rows' => $rows,
            'summary' => $this->buildSummary($rows),
            'valid_rows' => array_values(array_filter(
                $rows,
                static fn (array $row): bool => $row['status'] === self::STATUS_NEW
            )),
        ];
    }

    public function confirm(array $batch): array
    {
        $result = [
            'created' => 0,
            'duplicates' => (int) data_get($batch, 'summary.existing', 0)
                + (int) data_get($batch, 'summary.duplicates', 0),
            'paid_protected' => (int) data_get($batch, 'summary.paid', 0),
            'invalid' => (int) data_get($batch, 'summary.invalid', 0),
            'errors' => 0,
            'total_value' => 0,
            'created_rows' => [],
        ];

        foreach ((array) ($batch['valid_rows'] ?? []) as $row) {
            $existing = Invoice::where('refpago', $row['refpago'])->first();

            if ($existing) {
                if ($existing->status === 'pagada'
                    || strtoupper((string) $existing->wompi_status) === 'APPROVED') {
                    $result['paid_protected']++;
                } else {
                    $result['duplicates']++;
                }

                continue;
            }

            try {
                $invoice = Invoice::create([
                    'numero' => $row['numero'],
                    'codigo' => $row['codigo'],
                    'refpago' => $row['refpago'],
                    'valfactura' => $row['valor'],
                    'fecha' => $row['fecha_limite'],
                    'nombre' => $row['nombre'],
                    'direccion' => null,
                    'status' => 'pendiente',
                    'payment_link_url' => null,
                    'expires_at' => null,
                    'wompi_reference' => null,
                    'wompi_link_id' => null,
                    'wompi_transaction_id' => null,
                    'wompi_status' => null,
                    'wompi_amount_in_cents' => null,
                    'paid_at' => null,
                ]);

                $result['created']++;
                $result['total_value'] += (int) $invoice->valfactura;
                $result['created_rows'][] = [
                    'id' => $invoice->id,
                    'numero' => $invoice->numero,
                    'codigo' => $invoice->codigo,
                    'nombre' => $invoice->nombre,
                    'refpago' => $invoice->refpago,
                    'valor' => (int) $invoice->valfactura,
                    'fecha_limite' => optional($invoice->fecha)->format('Y-m-d'),
                ];
            } catch (QueryException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    $result['duplicates']++;
                } else {
                    $result['errors']++;
                }
            } catch (\Throwable) {
                $result['errors']++;
            }
        }

        return $result;
    }

    private function readRows(UploadedFile $file, string $fechaLimite): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $previousValueBinder = null;

        if ($reader instanceof Csv) {
            $reader->setInputEncoding(Csv::GUESS_ENCODING);
            $reader->setDelimiter(null);
            $previousValueBinder = Cell::getValueBinder();
            Cell::setValueBinder(new StringValueBinder);
        }

        try {
            $spreadsheet = $reader->load($file->getRealPath());

            try {
                $sheet = $spreadsheet->getActiveSheet();
                $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
                $headerMap = $this->buildHeaderMap($sheet, $highestColumnIndex);
                $rows = [];

                for ($rowNumber = 2; $rowNumber <= $sheet->getHighestRow(); $rowNumber++) {
                    $raw = [];

                    foreach ($headerMap as $column => $field) {
                        $cell = $sheet->getCell([$column, $rowNumber]);
                        $value = $cell->getValue();
                        $raw[$field] = $cell->isFormula()
                            || (is_string($value) && str_starts_with(trim($value), '='))
                                ? null
                                : $value;
                    }

                    if ($this->isEmptyRow($raw)) {
                        continue;
                    }

                    $codigo = $this->normalizeIdentifier($raw['codigo'] ?? null);
                    $nombre = $this->normalizeName($raw['nombre'] ?? null);
                    $refpago = $this->normalizeIdentifier($raw['refpago'] ?? null);
                    $valor = $this->normalizeMoney($raw['valor'] ?? null);
                    $errors = [];

                    if ($codigo === '') {
                        $errors[] = "Fila {$rowNumber}: el codigo es obligatorio.";
                    }
                    if ($nombre === '') {
                        $errors[] = "Fila {$rowNumber}: el nombre es obligatorio.";
                    }
                    if ($refpago === '') {
                        $errors[] = "Fila {$rowNumber}: la referencia de pago es obligatoria.";
                    }
                    if ($valor === null || $valor <= 0) {
                        $errors[] = "Fila {$rowNumber}: el valor de la factura no es valido.";
                    }

                    $rows[] = [
                        'row_number' => $rowNumber,
                        'codigo' => $codigo,
                        'numero' => $codigo,
                        'nombre' => $nombre,
                        'refpago' => $refpago,
                        'valor' => $valor,
                        'fecha_limite' => $fechaLimite,
                        'errors' => $errors,
                    ];
                }

                return $rows;
            } finally {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        } finally {
            if ($previousValueBinder !== null) {
                Cell::setValueBinder($previousValueBinder);
            }
        }
    }

    private function buildHeaderMap($sheet, int $highestColumnIndex): array
    {
        $aliases = [
            'CODIGO' => 'codigo',
            'NOMBRE' => 'nombre',
            'REFERNCIA' => 'refpago',
            'REFERENCIA' => 'refpago',
            'REFPAGO' => 'refpago',
            'VALOR' => 'valor',
        ];
        $headerMap = [];

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $cell = $sheet->getCell([$column, 1]);
            $value = $cell->getValue();
            $normalized = $cell->isFormula()
                || (is_string($value) && str_starts_with(trim($value), '='))
                    ? ''
                    : $this->normalizeHeader($value);

            if (isset($aliases[$normalized])) {
                $headerMap[$column] = $aliases[$normalized];
            }
        }

        $duplicatedFields = array_keys(array_filter(
            array_count_values($headerMap),
            static fn (int $count): bool => $count > 1
        ));

        if ($duplicatedFields !== []) {
            throw new InvalidArgumentException(
                'Hay columnas equivalentes repetidas: '.implode(', ', $duplicatedFields).'.'
            );
        }

        $found = array_values(array_unique($headerMap));
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $found));

        if ($missing !== []) {
            throw new InvalidArgumentException(
                'Faltan columnas obligatorias: '.implode(', ', $missing).'.'
            );
        }

        return $headerMap;
    }

    private function existingInvoices(array $rows): array
    {
        $references = array_values(array_unique(array_filter(array_column($rows, 'refpago'))));
        $existing = [];

        foreach (array_chunk($references, 1000) as $chunk) {
            Invoice::query()
                ->whereIn('refpago', $chunk)
                ->get(['id', 'refpago', 'status', 'wompi_status'])
                ->each(function (Invoice $invoice) use (&$existing): void {
                    $existing[$invoice->refpago] = $invoice;
                });
        }

        return $existing;
    }

    private function buildSummary(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'new' => 0,
            'existing' => 0,
            'paid' => 0,
            'duplicates' => 0,
            'invalid' => 0,
            'total_value' => 0,
        ];

        foreach ($rows as $row) {
            $summary[$row['status']] = ($summary[$row['status']] ?? 0) + 1;

            if ($row['status'] === self::STATUS_NEW) {
                $summary['total_value'] += (int) $row['valor'];
            }
        }

        $summary['existing'] = $summary[self::STATUS_EXISTING] ?? 0;
        $summary['paid'] = $summary[self::STATUS_PAID] ?? 0;
        $summary['duplicates'] = $summary[self::STATUS_DUPLICATE_FILE] ?? 0;
        $summary['invalid'] = $summary[self::STATUS_INVALID] ?? 0;

        return $summary;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'Nueva y valida',
            self::STATUS_DUPLICATE_FILE => 'Duplicada en el archivo',
            self::STATUS_EXISTING => 'Ya existe en la base de datos',
            self::STATUS_PAID => 'Factura pagada protegida',
            default => 'Fila invalida',
        };
    }

    private function normalizeHeader(mixed $value): string
    {
        $value = Str::ascii(trim((string) ($value ?? '')));

        return preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?? '';
    }

    private function normalizeIdentifier(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return is_finite($value) ? number_format($value, 0, '.', '') : '';
        }

        $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $value) ?? '');

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[+-]?\d+(?:[.,]\d+)?[Ee][+-]?\d+$/', $value)) {
            $numeric = (float) str_replace(',', '.', $value);

            return is_finite($numeric) ? number_format($numeric, 0, '.', '') : '';
        }

        if (preg_match('/^(\d+)[.,]0+$/', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^\d{1,3}(?:[.,\s]\d{3})+$/', $value)) {
            return preg_replace('/[.,\s]/', '', $value) ?? '';
        }

        return $value;
    }

    private function normalizeName(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        $value = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');

        return mb_strimwidth($value, 0, 255, '', 'UTF-8');
    }

    private function normalizeMoney(mixed $value): ?int
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_float($value)) {
            return is_finite($value) && $value > 0 ? (int) round($value) : null;
        }

        $value = trim((string) $value);

        if ($value === '' || str_contains($value, '-')) {
            return null;
        }

        $value = preg_replace('/[^\d,.]/', '', $value) ?? '';

        if ($value === '') {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $value = str_replace($thousandsSeparator, '', $value);
            $value = str_replace($decimalSeparator, '.', $value);
        } elseif ($lastComma !== false || $lastDot !== false) {
            $separator = $lastComma !== false ? ',' : '.';
            $parts = explode($separator, $value);
            $lastPart = end($parts);

            if (count($parts) > 2 || strlen((string) $lastPart) === 3) {
                $value = str_replace($separator, '', $value);
            } else {
                $value = str_replace($separator, '.', $value);
            }
        }

        if (! is_numeric($value)) {
            return null;
        }

        $amount = (int) round((float) $value);

        return $amount > 0 ? $amount : null;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
