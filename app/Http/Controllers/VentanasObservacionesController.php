<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use Carbon\Carbon;

class VentanasObservacionesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $procesos = Proceso::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('codigo', 'like', "%{$q}%")
                      ->orWhere('objeto', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('fecha')
            ->paginate(12)
            ->withQueryString();

        return view('observaciones.ventanas.index', compact('procesos', 'q'));
    }

    /**
     * Actualiza (o limpia) la ventana de observaciones de un proceso.
     */
    public function update(Request $request, Proceso $proceso)
    {
        // Si viene "limpiar", ponemos ambas fechas en null y salimos
        if ($request->boolean('limpiar')) {
            $proceso->update([
                'observaciones_abren_en' => null,
                'observaciones_cierran_en' => null,
            ]);
            return back()->with('ok', "Ventana de observaciones limpiada para {$proceso->codigo}.");
        }

        // Validación de fechas
        $data = $request->validate([
            'abren_en'  => 'required|date',
            'cierran_en'=> 'required|date',
        ]);

        $abren  = Carbon::parse($data['abren_en']);
        $cierran= Carbon::parse($data['cierran_en']);

        if ($abren->greaterThanOrEqualTo($cierran)) {
            return back()->withErrors([
                'cierran_en' => 'La fecha/hora de cierre debe ser mayor que la de apertura.'
            ])->withInput();
        }

        $proceso->update([
            'observaciones_abren_en'   => $abren,
            'observaciones_cierran_en' => $cierran,
        ]);

        return back()->with('ok', "Ventana actualizada para {$proceso->codigo}.");
    }
}
