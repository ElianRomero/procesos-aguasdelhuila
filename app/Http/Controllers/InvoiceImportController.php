<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InvoiceImportController extends Controller
{
    
    public function form()
    {
        return view('invoices.import');
    }
    public function index()
    {
        $invoices = Invoice::orderByDesc('id')->get();
    
        return view('invoices.index', compact('invoices'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $file = $request->file('file');

        $guardadas = 0;
        $actualizadas = 0;
        $saltadas = 0;

        $reasons = [
            'sin_numero' => 0,
            'sin_numero_factura' => 0,
            'sin_numero_identificacion' => 0,
            'error_parse_valor' => 0,
            'excepcion' => 0,
        ];

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            if ($highestRow < 2) {
                return back()->with('error', 'El archivo no contiene datos para importar.');
            }

            $headersRaw = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $headersRaw[] = $this->cleanExcelValue(
                    $sheet->getCellByColumnAndRow($col, 1)->getValue()
                );
            }

            $aliases = [
                'NUMERO' => ['NUMERO', 'NÚMERO'],
                'NUMERO_FACTURA' => ['NUMERO DE FACTURA', 'NÚMERO DE FACTURA', 'NUMERO_FACTURA'],
                'NUMERO_IDENTIFICACION' => ['NUMERO DE IDENTIFICACION', 'NÚMERO DE IDENTIFICACIÓN', 'NUMERO_IDENTIFICACION'],
                'PRIMER_VALOR' => ['PRIMER VALOR', 'PRIMER_VALOR'],
                'FECHA' => ['FECHA', 'FECHA '],
            ];

            $headerMap = [];
            foreach ($headersRaw as $index => $header) {
                $normalized = $this->normalizeHeader($header);
                $std = null;

                foreach ($aliases as $target => $list) {
                    $normalizedList = array_map(fn($item) => $this->normalizeHeader($item), $list);
                    if (in_array($normalized, $normalizedList, true)) {
                        $std = $target;
                        break;
                    }
                }

                $headerMap[$index + 1] = $std ?: $normalized;
            }

            $refsVistos = [];

            for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                try {
                    $row = [];

                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $fieldName = $headerMap[$col] ?? null;
                        if (!$fieldName) {
                            continue;
                        }

                        $cell = $sheet->getCellByColumnAndRow($col, $rowNumber);
                        $rawValue = $this->getSafeCellValue($cell);

                        $row[$fieldName] = $this->cleanExcelValue($rawValue);
                    }

                    $numero = trim((string) ($row['NUMERO'] ?? ''));
                    $codigo = trim((string) ($row['NUMERO_FACTURA'] ?? ''));
                    $refpago = trim((string) ($row['NUMERO_IDENTIFICACION'] ?? ''));

                    if ($numero === '') {
                        $saltadas++;
                        $reasons['sin_numero']++;
                        Log::warning("Fila {$rowNumber} saltada: sin NUMERO", $row);
                        continue;
                    }

                    if ($codigo === '') {
                        $saltadas++;
                        $reasons['sin_numero_factura']++;
                        Log::warning("Fila {$rowNumber} saltada: sin NUMERO DE FACTURA", $row);
                        continue;
                    }

                    if ($refpago === '') {
                        $saltadas++;
                        $reasons['sin_numero_identificacion']++;
                        Log::warning("Fila {$rowNumber} saltada: sin NUMERO DE IDENTIFICACION", $row);
                        continue;
                    }

                    if (isset($refsVistos[$refpago])) {
                        $saltadas++;
                        Log::warning("Fila {$rowNumber} duplicada en archivo para REFPAGO={$refpago}, saltada.");
                        continue;
                    }
                    $refsVistos[$refpago] = true;

                    $valor = $this->parseMoneyToStoredValue($row['PRIMER_VALOR'] ?? null);
                    if ($valor === null) {
                        $saltadas++;
                        $reasons['error_parse_valor']++;
                        Log::warning("Fila {$rowNumber}: PRIMER VALOR inválido", $row);
                        continue;
                    }

                    $fecha = $this->parseDateToYmd($row['FECHA'] ?? null);

                    $payload = [
                        'numero' => $this->toUtf8($numero),
                        'codigo' => $this->toUtf8($codigo),
                        'refpago' => $this->toUtf8($refpago),
                        'valfactura' => $valor, // PESOS enteros
                        'fecha' => $fecha,
                        'nombre' => '',
                        'direccion' => '',
                    ];

                    $existing = Invoice::where('refpago', $refpago)->first();

                    if ($existing) {
                        $existing->fill($payload);

                        // limpiar todo lo relacionado al pago
                        $existing->status = 'pendiente';
                        $existing->payment_link_url = null;
                        $existing->expires_at = null;
                        $existing->wompi_reference = null;
                        $existing->wompi_link_id = null;
                        $existing->wompi_transaction_id = null;
                        $existing->wompi_status = null;
                        $existing->wompi_amount_in_cents = null;
                        $existing->paid_at = null;

                        $existing->save();
                        $actualizadas++;
                    } else {
                        Invoice::create(array_merge($payload, [
                            'status' => 'pendiente',
                            'payment_link_url' => null,
                            'expires_at' => null,
                            'wompi_reference' => null,
                            'wompi_link_id' => null,
                            'wompi_transaction_id' => null,
                            'wompi_status' => null,
                            'wompi_amount_in_cents' => null,
                            'paid_at' => null,
                        ]));
                        $guardadas++;
                    }
                } catch (\Throwable $e) {
                    $saltadas++;
                    $reasons['excepcion']++;
                    Log::error("Error fila {$rowNumber}: " . $e->getMessage());
                }
            }

            $msg = "Importación terminada. Nuevas: {$guardadas}, Actualizadas: {$actualizadas}, Saltadas: {$saltadas}";
            $diag = " | Motivos: sin_numero={$reasons['sin_numero']}, sin_numero_factura={$reasons['sin_numero_factura']}, sin_numero_identificacion={$reasons['sin_numero_identificacion']}, error_valor={$reasons['error_parse_valor']}, excepcion={$reasons['excepcion']}";

            return back()->with('ok', $msg . $diag . ' | Formato: XLSX');

        } catch (\Throwable $e) {
            Log::error('Error importando XLSX: ' . $e->getMessage());
            return back()->with('error', 'No se pudo leer el archivo XLSX. Verifica que sea un Excel válido.');
        }
    }

    private function getSafeCellValue($cell)
    {
        try {
            if ($cell->isFormula()) {
                $cached = $cell->getOldCalculatedValue();
                if ($cached !== null && $cached !== '') {
                    return $cached;
                }

                $raw = $cell->getValue();
                if (is_string($raw) && str_starts_with($raw, '=')) {
                    return null;
                }

                return $raw;
            }

            return $cell->getValue();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeHeader($header): string
    {
        $header = $header ?? '';
        $header = trim((string) $header);
        $header = preg_replace('/\x{FEFF}/u', '', $header);
        $header = mb_strtoupper($header, 'UTF-8');

        $replacements = [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ];

        $header = strtr($header, $replacements);
        $header = preg_replace('/\s+/', ' ', $header);

        return $header;
    }

    private function cleanExcelValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            if ((float) $value == (int) $value) {
                return (string) ((int) $value);
            }

            return rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.');
        }

        $value = trim((string) $value);

        if (preg_match('/^\[?\d+\]?[A-Za-z0-9_]+![A-Z]+\d+$/', $value)) {
            return null;
        }

        if (str_starts_with($value, '=')) {
            return null;
        }

        return $value;
    }

    private function parseMoneyToStoredValue($value): ?int
    {
        if ($value === null) {
            return 0;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value);

        if ($value === '') {
            return 0;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $parts = explode(',', $value);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } else {
            $value = str_replace(',', '', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }

    private function parseDateToYmd($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            try {
                $date = ExcelDate::excelToDateTimeObject($raw);
                return Carbon::instance($date)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        $raw = trim((string) $raw);

        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'Y/m/d',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'Y-m-d H:i:s',
            'Y/m/d H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toUtf8($s): string
    {
        if ($s === null) {
            return '';
        }

        $s = (string) $s;

        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
        }

        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
        $s = preg_replace('/\s{2,}/', ' ', trim($s));
        $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s);

        return $s ?? '';
    }
}