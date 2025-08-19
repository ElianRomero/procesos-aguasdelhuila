<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\Proponente;
use Illuminate\Support\Facades\DB;
use App\Models\PostulacionArchivo;
use Illuminate\Support\Facades\Storage;

class AdminPostulacionesController extends Controller
{
    public function index()
    {
        $postulaciones = Postulacion::with([
            'proponente.ciudad',
            'proponente.ciiu',
            'proceso',
        ])
            ->get(); // 🔹 sin orden por fecha_postulacion

        return view('admin.postulaciones.index', compact('postulaciones'));
    }

    /**
     * Cambia el estado de una postulación específica (ENVIADA/ACEPTADA/RECHAZADA).
     */
    public function cambiarEstado(Request $request, string $codigo, Proponente $proponente)
    {
        $data = $request->validate([
            'estado' => 'required|in:ENVIADA,ACEPTADA,RECHAZADA',
            'observacion' => 'nullable|string|max:2000',
        ]);

        $proceso = Proceso::findOrFail($codigo);

        $postulacion = Postulacion::where('proponente_id', $proponente->id)
            ->where('proceso_codigo', $proceso->codigo)
            ->first();

        if (!$postulacion) {
            return back()->withErrors('No se encontró la postulación para ese proponente y proceso.');
        }

        $postulacion->update([
            'estado' => $data['estado'],
            'observacion' => $data['observacion'] ?? $postulacion->observacion,
        ]);

        return back()->with('success', 'Estado de la postulación actualizado correctamente.');
    }
    public function show(Proponente $proponente)
    {
        $proponente->load([
            'ciudad',
            'ciiu',
            'tipoIdentificacion',
            'procesosPostulados' => function ($q) {
                $q->with('tipoProceso')
                    ->withPivot(['estado', 'observacion', 'created_at'])
                    ->orderByPivot('created_at', 'desc');
            },
            'procesosAsignados',
        ]);

        $estadisticas = [
            'total'      => $proponente->procesosPostulados->count(),
            'enviadas'   => $proponente->procesosPostulados->where('pivot.estado', 'ENVIADA')->count(),
            'aceptadas'  => $proponente->procesosPostulados->where('pivot.estado', 'ACEPTADA')->count(),
            'rechazadas' => $proponente->procesosPostulados->where('pivot.estado', 'RECHAZADA')->count(),
        ];

        return view('admin.postulaciones.show', compact('proponente', 'estadisticas'));
    }

    // 🔹 Nuevo: lista documentos subidos por este proponente (JSON)
public function documentos(Proponente $proponente)
{
    $codigo = request('proceso'); // ← viene del query string

    $q = PostulacionArchivo::with(['proceso:codigo,objeto'])
        ->where('proponente_id', $proponente->id);

    if ($codigo) {
        $q->where('proceso_codigo', $codigo);
    }

    $files = $q->latest()->get();

    // Si viene proceso, NO agrupes: devuelve lista directa
    if ($codigo) {
        $items = $files->map(function ($a) {
            $ruta = $a->ruta ?? $a->path ?? null;
            return [
                'id'    => $a->id,
                'req'   => $a->requisito_key ?? '',
                'name'  => $a->nombre_original ?? $a->nombre ?? ($ruta ? basename($ruta) : 'Documento'),
                'url'   => $ruta ? Storage::url($ruta) : null,
                'mime'  => $a->mime ?? '',
                'fecha' => optional($a->created_at)->format('Y-m-d H:i'),
            ];
        })->values();

        return response()->json([
            'proceso_codigo' => $codigo,
            'items' => $items,
        ]);
    }

    // Si no viene proceso, devuélvelo agrupado (fallback)
    $groups = $files->groupBy('proceso_codigo')->map(function ($set, $codigo) {
        $proceso = optional($set->first()->proceso);
        return [
            'proceso_codigo' => $codigo,
            'proceso_objeto' => $proceso?->objeto,
            'items' => $set->map(function ($a) {
                $ruta = $a->ruta ?? $a->path ?? null;
                return [
                    'id'    => $a->id,
                    'req'   => $a->requisito_key ?? '',
                    'name'  => $a->nombre_original ?? $a->nombre ?? ($ruta ? basename($ruta) : 'Documento'),
                    'url'   => $ruta ? Storage::url($ruta) : null,
                    'mime'  => $a->mime ?? '',
                    'fecha' => optional($a->created_at)->format('Y-m-d H:i'),
                ];
            })->values(),
        ];
    })->values();

    return response()->json(['data' => $groups]);
}

}
