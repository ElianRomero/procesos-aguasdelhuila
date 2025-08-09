<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\Proponente;
use Illuminate\Support\Facades\DB;

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
            // postulaciones con su proceso relacionado
            'procesosPostulados' => function ($q) {
                $q->with('tipoProceso') // por si agregas inversa en Proceso
                  ->withPivot(['estado', 'observacion', 'created_at'])
                  ->orderByPivot('created_at', 'desc');
            },
            'procesosAsignados', // ganador/asignado si lo usas
        ]);

        // Totales rápidos por estado de postulación
        $estadisticas = [
            'total'     => $proponente->procesosPostulados->count(),
            'enviadas'  => $proponente->procesosPostulados->where('pivot.estado', 'ENVIADA')->count(),
            'aceptadas' => $proponente->procesosPostulados->where('pivot.estado', 'ACEPTADA')->count(),
            'rechazadas'=> $proponente->procesosPostulados->where('pivot.estado', 'RECHAZADA')->count(),
        ];

        return view('admin.postulaciones.show', compact('proponente', 'estadisticas'));
    }
}
