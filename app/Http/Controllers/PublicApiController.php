<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Proceso;

class PublicApiController extends Controller
{
     public function procesos(Request $r)
    {
        try {
            $columns = [
                'procesos.codigo',
                'procesos.fecha',
                'procesos.objeto',
                'procesos.valor',
                DB::raw('tp.nombre as tipo_proceso'),
                DB::raw('ec.nombre as estado_contrato'),
                DB::raw('tc.nombre as tipo_contrato'),
            ];

            $base = Proceso::query()
                ->leftJoin('tipo_procesos as tp', function ($join) {
                    $join->on(DB::raw('tp.codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('procesos.tipo_proceso_codigo COLLATE utf8mb4_unicode_ci'));
                })
                ->leftJoin('estado_contratos as ec', function ($join) {
                    $join->on(DB::raw('ec.codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('procesos.estado_contrato_codigo COLLATE utf8mb4_unicode_ci'));
                })
                ->leftJoin('tipo_contratos as tc', function ($join) {
                    $join->on(DB::raw('tc.codigo COLLATE utf8mb4_unicode_ci'), '=', DB::raw('procesos.tipo_contrato_codigo COLLATE utf8mb4_unicode_ci'));
                });

            $recordsTotal = Proceso::count('codigo');

            // Filtros
            if ($r->filled('tipo_proceso')) {
                $base->where('tp.nombre', $r->string('tipo_proceso'));
            }
            if ($r->filled('estado_contrato')) {
                $base->where('ec.nombre', $r->string('estado_contrato'));
            }
            if ($r->filled('tipo_contrato')) {
                $base->where('tc.nombre', $r->string('tipo_contrato'));
            }
            if ($r->filled('anio')) {
                $base->whereYear('procesos.fecha', (int) $r->input('anio'));
            }
            if ($r->filled('mes')) {
                $base->whereMonth('procesos.fecha', (int) $r->input('mes'));
            }

            // B��squeda (sin corchetes para evitar WAF)
            $search = $r->input('q') ?: $r->input('search.value'); // fallback
            if ($search) {
                $s = "%{$search}%";
                $base->where(function ($q) use ($s) {
                    $q->where('procesos.codigo', 'like', $s)
                      ->orWhere('procesos.objeto', 'like', $s)
                      ->orWhere('tp.nombre', 'like', $s)
                      ->orWhere('ec.nombre', 'like', $s)
                      ->orWhere('tc.nombre', 'like', $s);
                });
            }

            // Orden
            $map = [
                0 => 'procesos.codigo',
                1 => 'procesos.fecha',
                2 => 'procesos.objeto',
                3 => 'procesos.valor',
                4 => 'tp.nombre',
                5 => 'ec.nombre',
                6 => 'tc.nombre',
            ];
            $orderColIdx = (int) $r->input('order.0.column', 1);
            $orderDir    = strtolower((string) $r->input('order.0.dir', 'desc'));
            $orderCol    = $map[$orderColIdx] ?? 'procesos.fecha';
            $orderDir    = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

            $q = (clone $base)->select($columns)->orderBy($orderCol, $orderDir);

            // Paginaci��n
            $start  = max(0, (int) $r->input('start', 0));
            $length = max(1, min(100, (int) $r->input('length', 10)));

            // Conteo filtrado
            $recordsFiltered = (clone $base)->distinct()->count('procesos.codigo');

            // Datos
            $rows = $q->skip($start)->take($length)->get();

            return response()->json([
                'draw'            => (int) $r->input('draw', 0),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('API /api/procesos error', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error'   => 'server_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

