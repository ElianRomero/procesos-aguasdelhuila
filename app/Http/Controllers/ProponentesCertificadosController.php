<?php

namespace App\Http\Controllers;

use App\Models\Proponente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ProponentesCertificadosController extends Controller
{
    public function index()
    {
        return view('proponentes.certificados.index');
    }

    /**
     * DataTables source (siempre devuelve JSON válido).
     * Une "proponentes" y "proponentes_old" sin duplicar por NIT.
     */
    public function data()
    {
        try {
            $rowsMap = [];

            // ===== 1) NUEVA (si existe la tabla) =====
            if (Schema::hasTable('proponentes')) {
                $new = Proponente::with(['ciiu:id,nombre', 'ciudad:id,nombre'])
                    ->select([
                        'id','razon_social','nit','telefono1','correo',
                        'ciiu_id','ciudad_id','tipo_identificacion_codigo','actividad_inicio',
                    ])
                    ->orderBy('razon_social')
                    ->get()
                    ->map(function (Proponente $p) {
                        return [
                            'id'              => $p->id,
                            'source'          => 'new',
                            'razon_social'    => (string) $p->razon_social,
                            'nit'             => (string) $p->nit,
                            'ciiu'            => optional($p->ciiu)->nombre,
                            'ciudad'          => optional($p->ciudad)->nombre,
                            'telefono1'       => (string) $p->telefono1,
                            'correo'          => (string) $p->correo,
                            'certificado_url' => route('proponentes.certificados.download', $p->id),
                        ];
                    });

                foreach ($new as $r) {
                    $key = $r['nit'] !== '' ? $r['nit'] : 'new-'.$r['id'];
                    $rowsMap[$key] = $r; // prioridad a registros "new"
                }
            }

            // ===== 2) LEGADA (si existe la tabla) =====
            if (Schema::hasTable('proponentes_old')) {
                [$ciiuTable, $ciiuCodeCol, $ciiuNameCol] = $this->ciiuMeta();

                $q = DB::table('proponentes_old as po');

                if ($ciiuTable && $ciiuCodeCol) {
                    $q->leftJoin("$ciiuTable as c", "po.actividad_codigo", "=", "c.$ciiuCodeCol");
                }

                $q->select([
                    DB::raw('po.proponente_id as id'),
                    DB::raw('po.proponente_razonsocial as razon_social'),
                    DB::raw('po.proponente_nit as nit'),
                    DB::raw('po.proponente_telefono1 as telefono1'),
                    DB::raw('po.proponente_correo as correo'),
                ]);

                // Nombre CIIU si existe
                if ($ciiuTable && $ciiuNameCol) {
                    $q->addSelect(DB::raw("c.$ciiuNameCol as ciiu"));
                } else {
                    $q->addSelect(DB::raw("NULL as ciiu"));
                }

                $old = $q->orderBy('po.proponente_razonsocial')->get()->map(function ($o) {
                    return [
                        'id'              => (int) $o->id,
                        'source'          => 'old',
                        'razon_social'    => (string) $o->razon_social,
                        'nit'             => (string) $o->nit,
                        'ciiu'            => $o->ciiu,
                        'ciudad'          => null,
                        'telefono1'       => (string) $o->telefono1,
                        'correo'          => (string) $o->correo,
                        'certificado_url' => route('proponentes.certificados.download', $o->id) . '?source=old',
                    ];
                });

                foreach ($old as $r) {
                    $key = $r['nit'] !== '' ? $r['nit'] : 'old-'.$r['id'];
                    if (!isset($rowsMap[$key])) {
                        $rowsMap[$key] = $r; // solo si no vino desde "new"
                    }
                }
            }

            $rows = array_values($rowsMap);

            return response()->json(['data' => $rows], 200, [
                'Content-Type' => 'application/json',
            ]);
        } catch (\Throwable $e) {
            Log::error('[certificados.data] error', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine()
            ]);

            return response()->json([
                'data'    => [],
                'error'   => true,
                'message' => 'data() exception: '.$e->getMessage(),
            ], 200, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * Genera el PDF (preview inline o descarga).
     * - ?disposition=inline  (o ?preview=1) => ver en navegador
     * - default => attachment (descargar)
     * - ?source=old fuerza tomar de proponentes_old
     */
    public function download($id)
    {
        $source = request('source'); // 'old' | null
        $data   = null;

        // 1) Forzar LEGADO
        if ($source === 'old' && Schema::hasTable('proponentes_old')) {
            $data = $this->findLegacyById($id);
        }

        // 2) Intentar NUEVO por id
        if (!$data && Schema::hasTable('proponentes')) {
            $p = Proponente::with('ciiu')->find($id);
            if ($p) {
                $data = [
                    'tipo'              => $p->tipo_identificacion_codigo,
                    'razon_social'      => $p->razon_social,
                    'nit'               => $p->nit,
                    'representante'     => $p->representante,
                    'direccion'         => $p->direccion,
                    'telefono1'         => $p->telefono1,
                    'telefono2'         => $p->telefono2,
                    'correo'            => $p->correo,
                    'ciiu_nombre'       => optional($p->ciiu)->nombre,
                    'actividad_inicio'  => $p->actividad_inicio,
                ];
            }

            // 3) Fallback LEGADO por NIT/ID si faltan campos clave
            if ((!$data) || !$data['razon_social'] || !$data['ciiu_nombre'] || !$data['actividad_inicio']) {
                if (Schema::hasTable('proponentes_old')) {
                    $legacy = $this->findLegacyByNitOrId($data['nit'] ?? null, $id);
                    if ($legacy) $data = $this->mergeLegacyOver($data, $legacy);
                }
            }
        }

        // 4) Si no hay nada, busca LEGADO por id
        if (!$data && Schema::hasTable('proponentes_old')) {
            $data = $this->findLegacyById($id);
        }

        if (!$data || (!$data['razon_social'] && !$data['nit'])) {
            abort(404, 'No se encontró el proponente.');
        }

        if (ob_get_length()) { @ob_end_clean(); }

        // ====== FPDF puro (setasign/fpdf) ======
        $pdf = new class extends \FPDF {
            function Header()
            {
                $candidates = [
                    public_path('image/logo-aguas-del-huila.png'),
                    public_path('images/logo-aguas-del-huila.png'),
                ];
                foreach ($candidates as $logo) {
                    if (is_file($logo)) { $this->Image($logo, 20, 6, 40); break; }
                }
                $this->SetFont('Arial','B',11);
                $this->Cell(80);
                $this->Cell(40,10,utf8_decode('AGUAS DEL HUILA S.A. E.S.P'),0,0,'C');
                $this->Ln(5);
                $this->Cell(202,10,utf8_decode('NIT. 800.100.553-2'),0,0,'C');
                $this->Ln(5);
                $this->Cell(206,10,utf8_decode('CERTIFICADO DE PROPONENTES Y PROVEEDORES'),0,0,'C');
                $this->Ln(30);
                $this->Rect(10, 5, 190, 33);
                $this->Line(68, 5, 68, 38);
            }
            function Footer()
            {
                $this->SetY(-15);
                $this->SetFont('Arial','I',11);
                $this->Rect(10, 265, 190, 25);
                $this->Cell(0, -20, utf8_decode('"Líderes en el sector de agua potable y saneamiento básico"'), 0, 0, 'C');
                $this->Ln(5);
                $this->Cell(0, -20, utf8_decode('Calle 21 No. 1C 17 - PBX: 8752321 FAX: 8751391 Neiva, Huila'), 0, 0, 'C');
                $this->Ln(5);
                $this->Cell(0, -20, utf8_decode('www.aguasdelhuila.gov.co'), 0, 0, 'C');
            }
        };

        $pdf->SetMargins(20, 8);
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',11);

        $tipopersona = ($data['tipo'] === 'NIT') ? 'PERSONA JURÍDICA' : 'PERSONA NATURAL';
        $pdf->Cell(170,10,utf8_decode("DATOS DEL REGISTRO - ".$tipopersona),0,0,'C');
        $pdf->Ln(18);

        $pdf->SetFont('Arial','',10);
        $put = function ($label, $value) use ($pdf) {
            $pdf->Cell(10,10,utf8_decode($label),0,0,'L');
            $pdf->Ln(0);
            $pdf->SetX(70);
            $pdf->Cell(10,10,utf8_decode((string)($value ?? '')),0,0,'L');
            $pdf->Ln(9);
        };

        $put('Razón Social:',        $data['razon_social']);
        $put('NIT: ',                $data['nit']);
        $put('DIRECCIÓN: ',          $data['direccion']);
        $put('TELÉFONO: ',           $data['telefono1']);
        $put('CELULAR: ',            $data['telefono2']);
        $put('EMAIL: ',              $data['correo']);
        $put('REPRESENTANTE LEGAL: ',$data['representante']);
        $put('ACTIVIDAD ECONÓMICA: ',$data['ciiu_nombre']);
        $put('EXPERIENCIA DESDE: ',  $data['actividad_inicio']);

        $filename = ($data['nit'] ?: 'certificado').'.pdf';
        $inline   = request('disposition') === 'inline' || request()->boolean('preview');
        $binary   = $pdf->Output('S');

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="'.$filename.'"',
        ]);
    }

    /**
     * Detecta tabla/columnas de CIIU: table, codeCol, nameCol.
     */
    private function ciiuMeta(): array
    {
        $table = Schema::hasTable('ciiu') ? 'ciiu' : (Schema::hasTable('ciius') ? 'ciius' : null);
        if (!$table) return [null, null, null];

        $codeCol = Schema::hasColumn($table, 'ciiu_codigo') ? 'ciiu_codigo'
                 : (Schema::hasColumn($table, 'codigo') ? 'codigo'
                 : (Schema::hasColumn($table, 'id') ? 'id' : null));

        $nameCol = Schema::hasColumn($table, 'ciiu_nombre') ? 'ciiu_nombre'
                 : (Schema::hasColumn($table, 'nombre') ? 'nombre' : null);

        return [$table, $codeCol, $nameCol];
    }

    /**
     * Busca un registro LEGADO por id y lo mapea a arreglo de datos para PDF.
     */
    private function findLegacyById($id): ?array
    {
        [$ciiuTable, $ciiuCodeCol, $ciiuNameCol] = $this->ciiuMeta();

        $q = DB::table('proponentes_old as po');
        if ($ciiuTable && $ciiuCodeCol) {
            $q->leftJoin("$ciiuTable as c", 'po.actividad_codigo', '=', "c.$ciiuCodeCol");
        }

        $q->where('po.proponente_id', $id);

        $selects = [
            DB::raw('po.tipo_identificacion_codigo as tipo'),
            DB::raw('po.proponente_razonsocial as razon_social'),
            DB::raw('po.proponente_nit as nit'),
            DB::raw('po.proponente_representante as representante'),
            DB::raw('po.proponente_direccion as direccion'),
            DB::raw('po.proponente_telefono1 as telefono1'),
            DB::raw('po.proponente_telefono2 as telefono2'),
            DB::raw('po.proponente_correo as correo'),
            DB::raw('po.proponente_actividadinicio as actividad_inicio'),
        ];
        if ($ciiuTable && $ciiuNameCol) {
            $selects[] = DB::raw("c.$ciiuNameCol as ciiu_nombre");
        } else {
            $selects[] = DB::raw("NULL as ciiu_nombre");
        }

        $row = $q->select($selects)->first();
        return $row ? (array) $row : null;
    }

    /**
     * Busca LEGADO por NIT (o por id si no hay NIT) y devuelve array.
     */
    private function findLegacyByNitOrId(?string $nit, $id): ?array
    {
        [$ciiuTable, $ciiuCodeCol, $ciiuNameCol] = $this->ciiuMeta();

        $q = DB::table('proponentes_old as po');
        if ($ciiuTable && $ciiuCodeCol) {
            $q->leftJoin("$ciiuTable as c", 'po.actividad_codigo', '=', "c.$ciiuCodeCol");
        }

        $q->when($nit, fn($qq) => $qq->where('po.proponente_nit', $nit))
          ->orWhere('po.proponente_id', $id);

        $selects = [
            DB::raw('po.tipo_identificacion_codigo as tipo'),
            DB::raw('po.proponente_razonsocial as razon_social'),
            DB::raw('po.proponente_nit as nit'),
            DB::raw('po.proponente_representante as representante'),
            DB::raw('po.proponente_direccion as direccion'),
            DB::raw('po.proponente_telefono1 as telefono1'),
            DB::raw('po.proponente_telefono2 as telefono2'),
            DB::raw('po.proponente_correo as correo'),
            DB::raw('po.proponente_actividadinicio as actividad_inicio'),
        ];
        if ($ciiuTable && $ciiuNameCol) {
            $selects[] = DB::raw("c.$ciiuNameCol as ciiu_nombre");
        } else {
            $selects[] = DB::raw("NULL as ciiu_nombre");
        }

        $row = $q->select($selects)->first();
        return $row ? (array) $row : null;
    }

    /**
     * Completa $base (nueva) con campos faltantes desde $legacy.
     */
    private function mergeLegacyOver(?array $base, array $legacy): array
    {
        $base = $base ?? [];
        foreach ($legacy as $k => $v) {
            if ((!isset($base[$k]) || $base[$k] === null || $base[$k] === '') && $v !== null && $v !== '') {
                $base[$k] = $v;
            }
        }
        return $base;
    }
}
