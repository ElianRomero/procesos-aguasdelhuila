<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use App\Models\TipoProceso;
use App\Models\EstadoContrato;
use App\Models\TipoContrato;
use Illuminate\Support\Facades\DB;

class ProcesoController extends Controller
{
    public function create()
    {
        $tiposProceso = TipoProceso::all();
        $estadosContrato = EstadoContrato::all();
        $tiposContrato = TipoContrato::all();

        return view('procesos.create', compact('tiposProceso', 'estadosContrato', 'tiposContrato'));
    }

   public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|unique:procesos,codigo',
            'objeto' => 'required|string',
            'link_secop' => 'nullable|url',
            'valor' => 'required',
            'fecha' => 'required|date',
            'tipo_proceso_codigo' => 'required|exists:tipo_procesos,codigo',
            'estado_contrato_codigo' => 'required|exists:estado_contratos,codigo',
            'tipo_contrato_codigo' => 'required|exists:tipo_contratos,codigo',
            'modalidad_codigo' => 'nullable|string|max:100',
        ]);

        // Limpiar y convertir el valor a número (quita puntos de miles)
        $valorLimpio = str_replace('.', '', $request->valor);

        // Verifica que el valor sea numérico
        if (!is_numeric($valorLimpio)) {
            return back()->withErrors(['valor' => 'El valor ingresado no es numérico válido.'])->withInput();
        }

        // Guardar el proceso
        Proceso::create([
            'codigo' => $request->codigo,
            'objeto' => $request->objeto,
            'link_secop' => $request->link_secop,
            'valor' => (float) $valorLimpio,
            'fecha' => $request->fecha,
            'tipo_proceso_codigo' => $request->tipo_proceso_codigo,
            'estado_contrato_codigo' => $request->estado_contrato_codigo,
            'tipo_contrato_codigo' => $request->tipo_contrato_codigo,
            'modalidad_codigo' => $request->modalidad_codigo,
            'estado' => 'CREADO',
        ]);

        return redirect()->route('procesos.create')->with('success', 'Proceso creado correctamente.');
    }

}
