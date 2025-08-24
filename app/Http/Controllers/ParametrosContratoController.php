<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use App\Models\EstadoContrato;
use App\Models\TipoContrato;
use App\Models\TipoProceso;

class ParametrosContratoController extends Controller
{
    private array $map = [
        'estado'        => [EstadoContrato::class, 'estado_contratos', 'Estados de contrato'],
        'tipo_contrato' => [TipoContrato::class,  'tipo_contratos',   'Tipos de contrato'],
        'tipo_proceso'  => [TipoProceso::class,   'tipo_procesos',    'Tipos de proceso'],
    ];

    public function index(Request $request)
    {
        $estados        = EstadoContrato::orderBy('nombre')->get();
        $tiposContrato  = TipoContrato::orderBy('nombre')->get();
        $tiposProceso   = TipoProceso::orderBy('nombre')->get();

        // pestaña activa por query (?tab=estado|tipo_contrato|tipo_proceso)
        $tab = $request->get('tab', 'estado');
        if (! array_key_exists($tab, $this->map)) {
            $tab = 'estado';
        }

        return view('admin.parametros-contratos.index', compact(
            'estados',
            'tiposContrato',
            'tiposProceso',
            'tab'
        ));
    }

    public function store(Request $request)
    {
        $entidad = $request->get('entidad');
        [$model, $table] = $this->resolve($entidad);

        $data = $request->validate([
            'entidad' => ['required', Rule::in(array_keys($this->map))],
            'codigo'  => ['required', 'string', 'max:50', Rule::unique($table, 'codigo')],
            'nombre'  => ['required', 'string', 'max:255'],
        ]);

        $model::create([
            'codigo' => strtoupper($data['codigo']),
            'nombre' => $data['nombre'],
        ]);

        return back()->with('ok', 'Registro creado correctamente.')
            ->with('tab', $entidad);
    }

    public function update(Request $request, string $entidad, int $id)
    {
        [$model, $table] = $this->resolve($entidad);
        $row = $model::findOrFail($id);

        // ✅ Solo validamos nombre
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        // ✅ Solo actualizamos nombre; el CÓDIGO no se toca
        $row->update([
            'nombre' => $data['nombre'],
        ]);

        return redirect()->route('parametros.index', ['tab' => $entidad])
            ->with('ok', 'Registro actualizado.');
    }
    public function destroy(Request $request, string $entidad, int $id)
    {
        [$model, $table] = $this->resolve($entidad);
        $row = $model::findOrFail($id);

        try {
            $row->delete();
            return back()->with('ok', 'Registro eliminado.')->with('tab', $entidad);
        } catch (QueryException $e) {
            // Si hay FK en procesos, aquí atrapamos el error y avisamos
            return back()->with('error', 'No se puede eliminar: está en uso en otros registros.')
                ->with('tab', $entidad);
        }
    }

    private function resolve(string $entidad): array
    {
        if (! array_key_exists($entidad, $this->map)) {
            abort(404, 'Entidad no válida');
        }
        return $this->map[$entidad]; // [ModelClass, table, label]
    }
}
