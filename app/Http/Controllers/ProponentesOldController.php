<?php
// app/Http/Controllers/ProponentesOldController.php

namespace App\Http\Controllers;

use App\Models\ProponenteOld;
use App\Models\Departamento;
use App\Models\TipoIdentificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ProponentesOldController extends Controller
{
   public function create()
    {
        $departamentos       = Departamento::with('ciudades:id,departamento_id,nombre')
                                ->orderBy('nombre')->get();
        $tiposIdentificacion = TipoIdentificacion::orderBy('nombre')->get();
    
        [$ciiuTable, $ciiuCodeCol, $ciiuNameCol] = $this->ciiuMeta();
        $ciius = collect();
        if ($ciiuTable) {
            $cols = ['id'];
            if ($ciiuCodeCol) $cols[] = $ciiuCodeCol.' as codigo';
            if ($ciiuNameCol) $cols[] = $ciiuNameCol.' as nombre';
            $ciius = DB::table($ciiuTable)->select($cols)->orderBy($ciiuNameCol ?? 'id')->get();
        }
    
        // Mapa {dep_id: [{id, nombre}, ...]}
        $cityMap = $departamentos->mapWithKeys(function ($d) {
            return [
                $d->id => $d->ciudades->map(function ($c) {
                    return ['id' => (string)$c->id, 'nombre' => $c->nombre];
                })->values()
            ];
        })->toArray();
    
return view('proponentes.old.create', compact('departamentos','tiposIdentificacion','ciius','cityMap'));
    }


    public function store(Request $request)
    {
        try {
            // 1) Validar
            $data = $request->validate([
                'razon_social'               => ['required','string','max:512'],
                'nit'                        => ['required','string','max:20'],
                'representante'              => ['required','string','max:512'],
                'tipo_identificacion_codigo' => ['required','string','max:3'],
                'ciiu_id'                    => ['required','integer'],
                'departamento_id'            => ['nullable','integer'],
                'ciudad_id'                  => ['required','integer'],
                'direccion'                  => ['required','string','max:512'],
                'telefono1'                  => ['required','string','max:15'],
                'telefono2'                  => ['nullable','string','max:15'],
                'correo'                     => ['nullable','email','max:512'],
                // Muchos usuarios ponen "miweb.com" sin http → no rompas por eso
                'sitio_web'                  => ['nullable','string','max:512'],
                'actividad_inicio'           => ['required','date'],
                'observacion'                => ['nullable','string','max:1024'],
            ]);
    
            // 2) Saneo
            $nit  = preg_replace('/\D+/', '', $data['nit']);
            $tel1 = preg_replace('/\D+/', '', $data['telefono1']);
            $tel2 = preg_replace('/\D+/', '', (string)($data['telefono2'] ?? ''));
    
            $sitio = $data['sitio_web'] ?? null;
            if ($sitio && !preg_match('#^https?://#i', $sitio)) {
                $sitio = 'https://'.$sitio; // evita fallo por formato URL
            }
    
            $actividadInicio = Carbon::parse($data['actividad_inicio'])->format('Y-m-d');
    
            // 3) CIIU → actividad_codigo real
            [$ciiuTable, $ciiuCodeCol] = $this->ciiuCodeMeta();
            if (!$ciiuTable || !$ciiuCodeCol) {
                return back()->withInput()->with('error','No se detectó tabla/columna de CIIU en la BD.');
            }
            // Aquí asumimos que el <select> nos envía el id real del registro en la tabla de CIIU
            $actividadCodigo = DB::table($ciiuTable)->where('id', (int)$data['ciiu_id'])->value($ciiuCodeCol);
            if (is_null($actividadCodigo) || $actividadCodigo==='') {
                return back()->withInput()->with('error','CIIU seleccionado no válido.');
            }
    
            // 4) Armar payload
            $payload = [
                'estado_codigo'               => 1,
                'tipo_identificacion_codigo'  => $data['tipo_identificacion_codigo'],
                'proponente_razonsocial'      => $data['razon_social'],
                'proponente_nit'              => $nit,
                'proponente_representante'    => $data['representante'],
                'actividad_codigo'            => $actividadCodigo,
                'municipio_id'                => (int)$data['ciudad_id'],
                'proponente_direccion'        => $data['direccion'],
                'proponente_telefono1'        => $tel1,
                'proponente_telefono2'        => $tel2 ?: null,
                'proponente_correo'           => $data['correo'] ?? null,
                'proponente_actividadinicio'  => $actividadInicio,
                'proponente_sitioweb'         => $sitio,
                'proponente_observacion'      => $data['observacion'] ?? null,
                'usuario_id'                  => Auth::id(),
                'proponente_fechamodificacion'=> now(),
            ];
    
            // 5) Si existe por NIT → update; si no, crear con id manual (por si NO es auto-increment)
            $old = \App\Models\ProponenteOld::where('proponente_nit', $nit)->first();
    
            if ($old) {
                $old->fill($payload)->save();
                $id = $old->proponente_id;
            } else {
                // Generar id manual por si la PK no es auto_increment
                $nextId = ((int) DB::table('proponentes_old')->max('proponente_id')) + 1;
                $payload['proponente_id']         = $nextId;
                $payload['proponente_fechacreacion'] = now();
    
                DB::table('proponentes_old')->insert($payload);
                $id = $nextId;
            }
    
            // 6) Ir al certificado (preview inline)
            return redirect()->to(
                route('proponentes.certificados.download', $id).'?source=old&disposition=inline'
            );
    
        } catch (\Throwable $e) {
            Log::error('proponentes_old.store failed', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
            // Devuelve al form con los datos y el error visible
            return back()->withInput()->with('error', 'No se pudo guardar: '.$e->getMessage());
        }
    }

    /** Detecta tabla/columnas de CIIU para listar y guardar */
    private function ciiuMeta(): array
    {
        $table = Schema::hasTable('ciiu') ? 'ciiu' : (Schema::hasTable('ciius') ? 'ciius' : null);
        if (!$table) return [null, null, null];

        $codeCol = Schema::hasColumn($table, 'ciiu_codigo') ? 'ciiu_codigo'
                 : (Schema::hasColumn($table, 'codigo') ? 'codigo'
                 : (Schema::hasColumn($table, 'id') ? 'id' : null));

        $nameCol = Schema::hasColumn($table, 'ciiu_nombre') ? 'ciiu_nombre'
                 : (Schema::hasColumn($table, 'nombre') ? 'nombre' : null);

        return [$table, $codeCol, $nameCol];
    }

    /** Igual que arriba, pero sólo lo necesario para guardar */
    private function ciiuCodeMeta(): array
    {
        $table = Schema::hasTable('ciiu') ? 'ciiu' : (Schema::hasTable('ciius') ? 'ciius' : null);
        if (!$table) return [null, null];

        $codeCol = Schema::hasColumn($table, 'ciiu_codigo') ? 'ciiu_codigo'
                 : (Schema::hasColumn($table, 'codigo') ? 'codigo'
                 : (Schema::hasColumn($table, 'id') ? 'id' : null));

        return [$table, $codeCol];
    }
}
