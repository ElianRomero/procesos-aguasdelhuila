<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use App\Models\Proponente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostulacionController extends Controller

{
    public function index()
    {
        $miProponente = \App\Models\Proponente::where('user_id', auth()->id())->first();

        if (!$miProponente) {
            return redirect()->route('proponente.create')
                ->withErrors('Debes completar tu perfil de Proponente antes de ver los procesos.');
        }

        // Carga procesos vigentes + sólo la postulación del proponente actual (si existe)
        $procesos = \App\Models\Proceso::with(['proponentesPostulados' => function ($q) use ($miProponente) {
            $q->where('proponente_id', $miProponente->id);
        }])
            ->where('estado', 'VIGENTE')
            ->orderByDesc('fecha')
            ->get();

        return view('postulaciones.index', compact('procesos', 'miProponente'));
    }


    public function store(Request $request, $codigo)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();

        // proponente del usuario actual
        $proponente = Proponente::where('user_id', Auth::id())->first();
        if (!$proponente) {
            return back()->withErrors('Debes completar tu perfil de Proponente antes de postularte.');
        }

        $request->validate([
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        // Evita duplicados (también hay unique en BD)
        if ($proceso->proponentesPostulados()->where('proponente_id', $proponente->id)->exists()) {
            return back()->withErrors('Ya estás postulado a este proceso.');
        }

        $proceso->proponentesPostulados()->attach($proponente->id, [
            'estado' => 'POSTULADO',
            'observacion' => $request->observacion,
        ]);

        return back()->with('success', 'Postulación enviada.');
    }

    // (Opcional) Cambiar estado de una postulación (admin)
    public function cambiarEstado(Request $request, $codigo, Proponente $proponente)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();

        $request->validate([
            'estado' => ['required', Rule::in(['POSTULADO', 'ACEPTADO', 'RECHAZADO'])],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $proceso->proponentesPostulados()->updateExistingPivot($proponente->id, [
            'estado' => $request->estado,
            'observacion' => $request->observacion,
        ]);

        return back()->with('success', 'Estado actualizado.');
    }

    // Retirar postulación
    public function destroy($codigo, Proponente $proponente)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();
        $proceso->proponentesPostulados()->detach($proponente->id);

        return back()->with('success', 'Postulación retirada.');
    }
}
