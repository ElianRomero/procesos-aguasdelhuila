<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use App\Models\TipoProceso;
use App\Models\EstadoContrato;
use App\Models\TipoContrato;
use Illuminate\Support\Facades\DB;
use App\Models\Proponente;

class ProcesoController extends Controller
{
    public function create(Request $request)
    {
        $tiposProceso = TipoProceso::all();
        $estadosContrato = EstadoContrato::all();
        $tiposContrato = TipoContrato::all();
        $procesos = Proceso::latest('created_at')->get();

        // 🔹 Cargar proponentes
        $proponentes = Proponente::select('id', 'razon_social', 'nit')
            ->orderBy('razon_social')
            ->get();

        $procesoEditar = null;
        if ($request->has('editar')) {
            $procesoEditar = Proceso::where('codigo', $request->editar)->first();
        }

        return view('procesos.create', compact(
            'tiposProceso',
            'estadosContrato',
            'tiposContrato',
            'procesos',
            'proponentes',
            'procesoEditar'
        ));
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

        if (!is_numeric($valorLimpio)) {
            return back()->withErrors(['valor' => 'El valor ingresado no es numérico válido.'])->withInput();
        }

        // 🔹 Normalizar el link SECOP: si es URL, extraer solo el numConstancia
        $linkSecop = $request->link_secop;
        if (!empty($linkSecop) && preg_match('/numConstancia=([^&]+)/i', $linkSecop, $m)) {
            $linkSecop = $m[1]; // Solo el código
        }

        Proceso::create([
            'codigo' => $request->codigo,
            'objeto' => $request->objeto,
            'link_secop' => $linkSecop,
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

    public function edit($codigo)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();
        $tiposProceso = TipoProceso::all();
        $estadosContrato = EstadoContrato::all();
        $tiposContrato = TipoContrato::all();

        return view('procesos.edit', compact('proceso', 'tiposProceso', 'estadosContrato', 'tiposContrato'));
    }

    public function update(Request $request, $codigo)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();

        $request->validate([
            'objeto' => 'required|string',
            'link_secop' => 'nullable|url',
            'valor' => 'required',
            'fecha' => 'required|date',
            'tipo_proceso_codigo' => 'required|exists:tipo_procesos,codigo',
            'estado_contrato_codigo' => 'required|exists:estado_contratos,codigo',
            'tipo_contrato_codigo' => 'required|exists:tipo_contratos,codigo',
            'modalidad_codigo' => 'nullable|string|max:100',
            'estado' => 'required|in:CREADO,VIGENTE,CERRADO', // 👈 solo en update
        ]);

        $valorLimpio = (int) preg_replace('/\D/', '', $request->valor);

        $proceso->update([
            'objeto' => $request->objeto,
            'link_secop' => $request->link_secop,
            'valor' => $valorLimpio,
            'fecha' => $request->fecha,
            'tipo_proceso_codigo' => $request->tipo_proceso_codigo,
            'estado_contrato_codigo' => $request->estado_contrato_codigo,
            'tipo_contrato_codigo' => $request->tipo_contrato_codigo,
            'modalidad_codigo' => $request->modalidad_codigo,
            'estado' => $request->estado, // 👈 guardar estado
        ]);

        return redirect()->route('procesos.create')->with('success', 'Proceso actualizado correctamente.');
    }
    public function asignarProponente(Request $request, $codigo)
    {
        $request->validate([
            'proponente_id' => 'nullable|exists:proponentes,id',
        ]);

        $proceso = Proceso::findOrFail($codigo);

        // 🔒 Bloquea si NO está VIGENTE
        if (strtoupper($proceso->estado) !== 'VIGENTE') {
            return back()->withErrors('Este proceso no está VIGENTE; no es posible asignar proponente.');
        }

        $proceso->update([
            'proponente_id' => $request->proponente_id, // null = quitar
        ]);

        return redirect()->route('procesos.create')->with('success', 'Proponente asignado correctamente.');
    }
}
