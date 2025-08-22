<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use App\Models\TipoProceso;
use App\Models\EstadoContrato;
use App\Models\TipoContrato;
use Illuminate\Support\Facades\DB;
use App\Models\Proponente;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ProcesoController extends Controller
{
    public function create(Request $request)
{
    $tiposProceso     = TipoProceso::all();
    $estadosContrato  = EstadoContrato::all();
    $tiposContrato    = TipoContrato::all();

    // 👉 trae proponente para pintar la columna
    $procesos = Proceso::with('proponente')
        ->latest('created_at')
        ->get();

    // 👉 opciones de estado para el select de filtro
    $estados = $procesos->pluck('estado')->filter()->unique()->values();

    $proponentes = Proponente::select('id', 'razon_social', 'nit')
        ->orderBy('razon_social')->get();

    $procesoEditar = null;
    if ($request->has('editar')) {
        $procesoEditar = Proceso::where('codigo', $request->editar)->first();
    }

    return view('procesos.create', compact(
        'tiposProceso','estadosContrato','tiposContrato',
        'procesos','proponentes','procesoEditar','estados'
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
            'requisitos_json' => 'nullable|string',
        ]);

        // limpiar valor
        $valorLimpio = str_replace('.', '', $request->valor);
        if (!is_numeric($valorLimpio)) {
            return back()->withErrors(['valor' => 'El valor ingresado no es numérico válido.'])->withInput();
        }

        // normalizar SECOP
        $linkSecop = $request->link_secop;
        if (!empty($linkSecop) && preg_match('/numConstancia=([^&]+)/i', $linkSecop, $m)) {
            $linkSecop = $m[1];
        }

        // parsear requisitos con helper
        $requisitos = $this->parseRequisitos($request->input('requisitos_json'));

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
            'requisitos' => $requisitos,
        ]);

        return redirect()->route('procesos.create')->with('success', 'Proceso creado correctamente.');
    }
public function destroy(Proceso $proceso)
{
    try {
        $proceso->delete();
        return back()->with('success', 'Proceso eliminado.');
    } catch (\Throwable $e) {
        return back()->withErrors('No se puede eliminar: está relacionado con otros registros.');
    }
}


    public function edit(string $codigo)
    {
        $procesoEditar = Proceso::where('codigo', $codigo)->firstOrFail();

        // Carga catálogos
        $tiposProceso     = TipoProceso::all();
        $estadosContrato  = EstadoContrato::all();
        $tiposContrato    = TipoContrato::all();

        // 👇 ESTOS FALTABAN
        $procesos = Proceso::latest('created_at')->get();
        $proponentes = Proponente::select('id', 'razon_social', 'nit')
            ->orderBy('razon_social')
            ->get();

        $editando = true;

        return view('procesos.create', compact(
            'editando',
            'procesoEditar',
            'tiposProceso',
            'estadosContrato',
            'tiposContrato',
            'procesos',       // 👈
            'proponentes'     // 👈
        ));
    }

    public function update(Request $request, string $codigo)
    {
        // Carga el modelo por codigo (si no existe 404)
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
            'estado' => 'required|in:CREADO,VIGENTE,CERRADO',
            'requisitos_json' => 'nullable|string',
        ]);

        // Valor
        $valorLimpio = str_replace('.', '', $request->valor);
        if (!is_numeric($valorLimpio)) {
            return back()->withErrors(['valor' => 'El valor ingresado no es numérico válido.'])->withInput();
        }

        // Link SECOP → si viene URL larga, extrae numConstancia
        $linkSecop = $request->link_secop;
        if (!empty($linkSecop) && preg_match('/numConstancia=([^&]+)/i', $linkSecop, $m)) {
            $linkSecop = $m[1];
        }

        // Atributos base
        $proceso->objeto = $request->objeto;
        $proceso->link_secop = $linkSecop;
        $proceso->valor = (float) $valorLimpio;
        $proceso->fecha = $request->fecha;
        $proceso->tipo_proceso_codigo = $request->tipo_proceso_codigo;
        $proceso->estado_contrato_codigo = $request->estado_contrato_codigo;
        $proceso->tipo_contrato_codigo = $request->tipo_contrato_codigo;
        $proceso->modalidad_codigo = $request->modalidad_codigo;
        $proceso->estado = $request->estado;

        // Requisitos (si vienen) → el cast 'array' se encarga del JSON
        if ($request->filled('requisitos_json')) {
            $proceso->requisitos = $this->parseRequisitos($request->input('requisitos_json'));
        }

        $proceso->save();

        // Redirect correcto: pasa 'codigo' explícitamente
        return redirect()
            ->route('procesos.edit', ['codigo' => $proceso->codigo])
            ->with('success', 'Proceso actualizado correctamente.');
    }




    private function parseRequisitos(?string $json): array
    {
        if (!$json) return [];
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) return [];

        $out = [];
        foreach ($data as $r) {
            if (is_string($r)) {
                $name = trim($r);
                $key  = Str::slug($name);
            } else {
                $name = trim((string)($r['name'] ?? ''));
                $key  = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($r['key'] ?? '')));
            }
            if ($name === '' || $key === '') continue;

            $out[] = [
                'name' => $name,
                'key'  => $key,
                // implícito: solo PDF; validaremos en uploads: mimes:pdf|max:10240
            ];
        }
        return $out;
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
