<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class InvoiceImportController extends Controller
{
    public function form()
    {
        return view('invoices.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        // ---- 1) Detectar delimitador ----
        $sample = file_get_contents($path, false, null, 0, 4096) ?: '';
        $candidates = [
            "," => substr_count($sample, ","),
            ";" => substr_count($sample, ";"),
            "\t" => substr_count($sample, "\t"),
            "|" => substr_count($sample, "|")
        ];
        arsort($candidates);
        $delimiter = array_key_first($candidates) ?: ",";

        $csv = Reader::createFromPath($path, 'r');
        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(0);

        // ---- 2) Normalizar cabeceras ----
        // Mapeo de alias -> clave estándar
        $aliases = [
            'NUMERO' => ['NUMERO', 'NÚMERO', 'NO', 'NRO', 'NUM', 'NUMERO_FACTURA'],
            'CODIGO' => ['CODIGO', 'CÓDIGO', 'CODE'],
            'REFPAGO' => ['REFPAGO', 'REFERENCIA', 'REF_PAGO', 'REF', 'REFERENCIA_PAGO'],
            'VALFACTURA' => ['VALFACTURA', 'VALOR', 'VALOR_FACTURA', 'TOTAL', 'MONTO'],
            'FECHA' => ['FECHA', 'DATE', 'FEC'],
            'NOMBRE' => ['NOMBRE', 'CLIENTE', 'NOM_CLIENTE'],
            'DIRECCION' => ['DIRECCION', 'DIRECCIÓN', 'DIR', 'DIRECCION_CLIENTE'],
        ];

        // Construye un traductor header->estándar
        $headersRaw = $csv->getHeader();
        $map = [];
        foreach ($headersRaw as $h) {
            $k = strtoupper(trim(preg_replace('/\x{FEFF}/u', '', $h))); // quita BOM y espacios
            $std = null;
            foreach ($aliases as $target => $list) {
                if (in_array($k, $list, true)) {
                    $std = $target;
                    break;
                }
            }
            $map[$h] = $std ?: $k; // si no hay alias, usa tal cual en MAYUS
        }

        $records = $csv->getRecords();

        $guardadas = 0;
        $actualizadas = 0;
        $saltadas = 0;

        // Motivos de salto para diagnóstico
        $reasons = [
            'sin_refpago' => 0,
            'duplicado_mismo_ref_en_archivo' => 0,
            'error_parse_valor' => 0,
            'excepcion' => 0,
        ];

        $refsVistos = [];

        foreach ($records as $i => $rowRaw) {
            try {
                // ---- 3) Normaliza fila con el mapa de cabeceras ----
                $row = [];
                foreach ($rowRaw as $h => $val) {
                    $row[$map[$h] ?? $h] = is_string($val) ? trim($val) : $val;
                }

                $ref = (string) ($row['REFPAGO'] ?? '');
                $ref = trim($ref);

                // Si no hay REFPAGO, intenta fallback a NUMERO (opcional)
                if ($ref === '' && !empty($row['NUMERO'])) {
                    $ref = trim((string) $row['NUMERO']);
                }

                if ($ref === '') {
                    $saltadas++;
                    $reasons['sin_refpago']++;
                    Log::warning("Fila $i saltada: sin REFPAGO/NUMERO identificable", $row);
                    continue;
                }

                // Evita procesar dos veces el mismo REFPAGO dentro del mismo archivo
                if (isset($refsVistos[$ref])) {
                    $saltadas++;
                    $reasons['duplicado_mismo_ref_en_archivo']++;
                    Log::warning("Fila $i duplicada en archivo para REFPAGO=$ref, saltada.");
                    continue;
                }
                $refsVistos[$ref] = true;

                // ---- 4) Valor en centavos (acepta 1.234.567,89 o 1234567 o 123,456) ----
                $valorStr = (string) ($row['VALFACTURA'] ?? '');
                if ($valorStr === '') {
                    // Fuerza 0 si viene vacío para “importar todo”
                    $valorCentavos = 0;
                } else {
                    // Normaliza: quita separadores de miles y convierte coma decimal a punto
                    $tmp = str_replace(['.', ' '], '', $valorStr);
                    $tmp = str_replace([','], ['.'], $tmp);
                    if (!is_numeric($tmp)) {
                        $saltadas++;
                        $reasons['error_parse_valor']++;
                        Log::warning("Fila $i: VALFACTURA no numérico ('$valorStr'), saltada.", $row);
                        continue;
                    }
                    $valorCentavos = (int) round(((float) $tmp) * 100);
                }

                // ---- 5) Fecha (acepta dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd) ----
                $fecha = null;
                if (!empty($row['FECHA'])) {
                    $f = str_replace(['.', ' '], '', $row['FECHA']);
                    $f = str_replace(['-'], '/', $f);
                    $p = explode('/', $f);
                    if (count($p) === 3) {
                        // Detecta si viene yyyy/mm/dd o dd/mm/yyyy
                        if (strlen($p[0]) === 4) {
                            $fecha = sprintf('%04d-%02d-%02d', (int) $p[0], (int) $p[1], (int) $p[2]);
                        } else {
                            $fecha = sprintf('%04d-%02d-%02d', (int) $p[2], (int) $p[1], (int) $p[0]);
                        }
                    }
                }

                $fecha = $this->parseDateToYmd($row['FECHA'] ?? null);

                $payload = [
                    'numero' => $this->toUtf8($row['NUMERO'] ?? ''),
                    'codigo' => $this->toUtf8($row['CODIGO'] ?? ''),
                    'valfactura' => $valorCentavos,
                    'fecha' => $fecha, // YYYY-MM-DD
                    'nombre' => $this->toUtf8($row['NOMBRE'] ?? ''),
                    'direccion' => $this->toUtf8($row['DIRECCION'] ?? ''),
                ];


                $existing = Invoice::where('refpago', $ref)->first();
                if ($existing) {
                    $existing->fill($payload);
                    // No pisar status si ya está pagada
                    if ($existing->status !== 'pagada') {
                        $existing->save();
                        $actualizadas++;
                    } else {
                        // Aún así guardamos otros campos que no arriesguen contabilidad, si quieres quitarlo, omite este save
                        $existing->save();
                        $actualizadas++;
                    }
                } else {
                    Invoice::create(array_merge($payload, ['refpago' => $ref]));
                    $guardadas++;
                }

            } catch (\Throwable $e) {
                $saltadas++;
                $reasons['excepcion']++;
                Log::error("Error fila $i: " . $e->getMessage(), ['row' => $rowRaw]);
            }
        }

        $msg = "Importación terminada. Nuevas: $guardadas, Actualizadas: $actualizadas, Saltadas: $saltadas";
        $diag = " | Motivos: sin_refpago={$reasons['sin_refpago']}, duplicadas_en_archivo={$reasons['duplicado_mismo_ref_en_archivo']}, error_valor={$reasons['error_parse_valor']}, excepcion={$reasons['excepcion']}";
        return back()->with('ok', $msg . $diag . " | Delimitador: " . ($delimiter === ' ' ? 'TAB' : $delimiter));
    }
    private function toUtf8($s): string
    {
        if ($s === null)
            return '';
        $s = (string) $s;

        // Si no es UTF-8, intenta convertir desde Windows-1252 / ISO-8859-1
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
        }

        // Quitar caracteres de control invisibles
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);

        // Normalizar símbolo N° / Nº / °
        $s = preg_replace('/\bN[°º]\b/u', 'No.', $s); // "N°" -> "No."
        $s = str_replace("N�", "No.", $s); // corrige el rombo negro ya roto

        // Colapsar espacios
        $s = preg_replace('/\s{2,}/', ' ', trim($s));

        // Asegurar UTF-8 válido (ignora lo que no entre)
        $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        return $s ?? '';
    }

    private function parseDateToYmd(?string $raw): ?string
    {
        if (!$raw)
            return null;
        $raw = trim($raw);
        $raw = str_replace(['.', ' '], '', $raw);
        $raw = str_replace('-', '/', $raw);
        $p = explode('/', $raw);
        if (count($p) === 3) {
            if (strlen($p[0]) === 4) { // yyyy/mm/dd
                return sprintf('%04d-%02d-%02d', (int) $p[0], (int) $p[1], (int) $p[2]);
            }
            // dd/mm/yyyy
            return sprintf('%04d-%02d-%02d', (int) $p[2], (int) $p[1], (int) $p[0]);
        }
        return null;
    }


}
