<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use App\Models\Observacion;
use App\Models\ObservacionArchivo;
use Illuminate\Support\Facades\Storage;


class ObservacionController extends Controller
{  public function create(Proceso $proceso)
    {
        if (!$proceso->tieneVentanaObservaciones()) {
            return redirect()->back()->withErrors([
                'observaciones' => 'Este proceso no tiene ventana de observaciones configurada.'
            ]);
        }

        if (!$proceso->ventanaObservacionesAbiertaYDefinida()) {
            return redirect()->back()->withErrors([
                'observaciones' => 'La ventana de observaciones no está activa actualmente.'
            ]);
        }

        return view('observaciones.create', [
            'proceso' => $proceso,
            'ventanaAbierta' => true, // a estas alturas, ya pasó las validaciones
        ]);
    }
    public function store(Request $request, Proceso $proceso)
    {
        $user = auth()->user();
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        if (!$proceso->ventanaObservacionesAbierta() && !$isAdmin) {
            return back()->withErrors(['observaciones' => 'Fuera del periodo permitido para registrar observaciones.'])->withInput();
        }

        $data = $request->validate([
            'asunto'       => 'required|string|max:180',
            'descripcion'  => 'nullable|string',
            'archivos'     => 'required|array|min:1',
            'archivos.*'   => 'file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:20480',
            'proponente_id' => 'nullable|exists:proponentes,id',
        ]);

        $proponenteId = $data['proponente_id'] ?? optional($user?->proponente)->id;

        $obs = Observacion::create([
            'proceso_codigo' => $proceso->codigo,
            'proponente_id'  => $proponenteId,
            'user_id'        => $user?->id,
            'asunto'         => trim($data['asunto']),
            'descripcion'    => trim($data['descripcion'] ?? ''),
            'estado'         => 'ENVIADA',
        ]);

        // 👇 Usa el disk PRIVATE que ya tienes configurado
        $disk = 'private';

        foreach ($request->file('archivos', []) as $file) {
            $dir  = "observaciones/{$proceso->codigo}/{$obs->id}";
            $path = $file->store($dir, $disk);

            ObservacionArchivo::create([
                'observacion_id' => $obs->id,
                'disk'           => $disk,
                'path'           => $path,
                'original_name'  => $file->getClientOriginalName(),
                'mime'           => $file->getClientMimeType(),
                'size'           => $file->getSize(),
            ]);
        }

        return redirect()->route('procesos.observaciones.create', $proceso)
            ->with('ok', 'Observación registrada correctamente.');
    }

    public function adminIndex(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $estado = $request->get('estado', '');

        $observaciones = \App\Models\Observacion::with([
            'proceso:codigo,objeto',
            'usuario:id,name,email',
            'archivos:id,observacion_id,original_name,size'
        ])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('proceso_codigo', 'like', "%{$q}%")
                        ->orWhere('asunto', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%")
                        // 👇 búsqueda por usuario (nombre/correo)
                        ->orWhereHas('usuario', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->where('estado', 'ENVIADA')           // 👈 solo las nuevas
            ->orderByDesc('created_at')
            ->limit(1000)                          // opcional
            ->get();

        return view('observaciones.index', compact('observaciones', 'q', 'estado'));
    }
    public function index(\App\Models\Proceso $proceso)
    {
        $observaciones = \App\Models\Observacion::with(['usuario:id,name,email', 'archivos:id,observacion_id,original_name,size'])
            ->where('proceso_codigo', $proceso->codigo)
            ->orderByDesc('created_at')
            ->get();

        return view('observaciones.proceso', compact('proceso', 'observaciones'));
    }
    public function actualizarEstado(\Illuminate\Http\Request $request, \App\Models\Observacion $observacion)
    {
        $data = $request->validate([
            'estado' => 'required|in:ENVIADA,ADMITIDA,RECHAZADA,RESUELTA',
        ]);
        $observacion->update(['estado' => $data['estado']]);
        return back()->with('ok', 'Estado actualizado.');
    }
    public function myIndex(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $proponenteId = optional($user->proponente)->id;
        $q = trim((string) $request->get('q', ''));

        $observaciones = \App\Models\Observacion::with([
            // 👇 agrega las columnas de ventana para que pueda evaluar si está abierta
            'proceso:codigo,objeto,observaciones_abren_en,observaciones_cierran_en',
            'archivos:id,observacion_id,original_name,size'
        ])
            ->where(function ($w) use ($user, $proponenteId) {
                $w->where('user_id', $user->id);
                if ($proponenteId) $w->orWhere('proponente_id', $proponenteId);
            })
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('proceso_codigo', 'like', "%{$q}%")
                        ->orWhere('asunto', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $stats = $observaciones->groupBy('estado')->map->count();

        return view('observaciones.mias', compact('observaciones', 'stats', 'q'));
    }

    // app/Http/Controllers/ObservacionController.php

    public function edit(\App\Models\Observacion $observacion)
    {
        $user = auth()->user();

        if (!$observacion->puedeEditarPor($user)) {
            return back()->withErrors(['observacion' => 'No puedes editar esta observación (fuera de ventana o ya procesada).']);
        }

        $observacion->load(['proceso:codigo,objeto,observaciones_abren_en,observaciones_cierran_en', 'archivos']);
        return view('observaciones.editar', compact('observacion'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Observacion $observacion)
    {
        $user = auth()->user();
        if (!$observacion->puedeEditarPor($user)) {
            return back()->withErrors(['observacion' => 'No puedes editar esta observación.']);
        }

        $data = $request->validate([
            'asunto'      => 'required|string|max:180',
            'descripcion' => 'nullable|string',
            'archivos'    => 'nullable|array',
            'archivos.*'  => 'file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:20480', // 20MB c/u
        ]);

        $observacion->update([
            'asunto'      => trim($data['asunto']),
            'descripcion' => trim($data['descripcion'] ?? ''),
        ]);

        // Adjuntar nuevos archivos (disk privado)
        if ($request->hasFile('archivos')) {
            $disk = 'private';
            $dir  = "observaciones/{$observacion->proceso_codigo}/{$observacion->id}";

            foreach ($request->file('archivos', []) as $file) {
                $path = $file->store($dir, $disk);
                \App\Models\ObservacionArchivo::create([
                    'observacion_id' => $observacion->id,
                    'disk'           => $disk,
                    'path'           => $path,
                    'original_name'  => $file->getClientOriginalName(),
                    'mime'           => $file->getClientMimeType(),
                    'size'           => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('mis.observaciones.index')->with('ok', 'Observación actualizada.');
    }

    public function destroyArchivo(\App\Models\Observacion $observacion, \App\Models\ObservacionArchivo $archivo)
    {
        $user = auth()->user();
        if (!$observacion->puedeEditarPor($user)) {
            abort(403, 'No autorizado para eliminar archivos.');
        }
        if ($archivo->observacion_id !== $observacion->id) {
            abort(404);
        }

        // borrar del storage y DB
        Storage::disk($archivo->disk)->delete($archivo->path);
        $archivo->delete();

        return back()->with('ok', 'Archivo eliminado.');
    }

    public function downloadArchivo(\App\Models\Observacion $observacion, \App\Models\ObservacionArchivo $archivo)
    {
        if ($archivo->observacion_id !== $observacion->id) abort(404);

        $user = auth()->user();
        $isOwner = $observacion->esDueno($user);
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        abort_unless($isOwner || $isAdmin, 403, 'No autorizado.');

        return Storage::disk($archivo->disk)->download($archivo->path, $archivo->original_name);
    }
}
