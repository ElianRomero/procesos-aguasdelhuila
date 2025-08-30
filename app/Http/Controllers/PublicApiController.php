<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Proceso;

class PublicApiController extends Controller
{
      public function procesos(Request $r)
    {
        // columnas que devolvemos
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
            ->leftJoin('tipo_procesos as tp', 'tp.codigo', '=', 'procesos.tipo_proceso_codigo')
            ->leftJoin('estado_contratos as ec', 'ec.codigo', '=', 'procesos.estado_contrato_codigo')
            ->leftJoin('tipo_contratos as tc', 'tc.codigo', '=', 'procesos.tipo_contrato_codigo');

        $recordsTotal = (clone $base)->count('procesos.codigo');

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
            $base->whereYear('procesos.fecha', (int) $r->anio);
        }
        if ($r->filled('mes')) {
            $base->whereMonth('procesos.fecha', (int) $r->mes);
        }

        // Búsqueda global (DataTables: search[value])
        $search = $r->input('search.value');
        if ($search) {
            $s = "%{$search}%";
            $base->where(function($q) use ($s) {
                $q->where('procesos.codigo', 'like', $s)
                  ->orWhere('procesos.objeto', 'like', $s)
                  ->orWhere('tp.nombre', 'like', $s)
                  ->orWhere('ec.nombre', 'like', $s)
                  ->orWhere('tc.nombre', 'like', $s);
            });
        }

        // Orden (por defecto: fecha DESC)
        $orderColIdx = (int) $r->input('order.0.column', 1);
        $orderDir    = $r->input('order.0.dir', 'desc');
        $map = [
            0 => 'procesos.codigo',
            1 => 'procesos.fecha',
            2 => 'procesos.objeto',
            3 => 'procesos.valor',
            4 => 'tp.nombre',
            5 => 'ec.nombre',
            6 => 'tc.nombre',
        ];
        $orderCol = $map[$orderColIdx] ?? 'procesos.fecha';
        $orderDir = in_array(strtolower($orderDir), ['asc','desc'], true) ? $orderDir : 'desc';

        $q = (clone $base)->select($columns)->orderBy($orderCol, $orderDir);

        // Paginación DataTables
        $start  = (int) $r->input('start', 0);
        $length = (int) $r->input('length', 10);
        $recordsFiltered = (clone $base)->count('procesos.codigo');

        $rows = $q->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $r->input('draw', 0),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }
}
