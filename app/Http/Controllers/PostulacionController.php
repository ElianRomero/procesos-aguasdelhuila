<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;
use App\Models\Proponente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\PostulacionArchivo;
use Illuminate\Support\Facades\Storage;

class PostulacionController extends Controller

{
    public function index()
    {
        $miProponente = Proponente::where('user_id', auth()->id())->first();

        if (!$miProponente) {
            return redirect()->route('proponente.create')
                ->withErrors('Debes completar tu perfil de Proponente antes de ver los procesos.');
        }

        // Procesos vigentes (para postularse)
        $procesos = Proceso::with([
            // para el botón Postularme/Retirar
            'proponentesPostulados' => fn($q) => $q->where('proponente_id', $miProponente->id),
            // para el modal de detalle
            'tipoProceso',
            'estadoContrato',
            'tipoContrato'
        ])
            ->where('estado', 'CREADO')
            ->orderByDesc('fecha')
            ->get();

        // Mis postulaciones (cualquier estado), con relaciones para mostrar detalle
        $misPostulaciones = \App\Models\Proceso::with(['tipoProceso', 'estadoContrato', 'tipoContrato'])
            ->whereHas('proponentesPostulados', fn($q) => $q->where('proponente_id', $miProponente->id))
            ->orderByDesc('fecha')
            ->get();

        return view('postulaciones.index', compact('procesos', 'miProponente', 'misPostulaciones'));
    }



    public function store(Request $request, $codigo)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();

        $proponente = Proponente::where('user_id', Auth::id())->first();
        if (!$proponente) {
            return back()->withErrors('Debes completar tu perfil de Proponente antes de postularte.');
        }

        $request->validate([
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        // ✅ Si YA existe, manda directo a archivos (idempotente)
        if ($proceso->proponentesPostulados()
            ->where('proponente_id', $proponente->id)
            ->exists()
        ) {
            return redirect("/postulaciones/{$proceso->codigo}/archivos");
        }

        // Crear postulación
        $proceso->proponentesPostulados()->attach($proponente->id, [
            'estado' => 'POSTULADO',
            'observacion' => $request->observacion,
        ]);

        // ✅ Mismo destino que tu link
        return redirect("/postulaciones/{$proceso->codigo}/archivos")
            ->with('success', 'Postulación enviada. Ahora sube los documentos.');
    }
    public function archivosForm(string $codigo)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();
        $proponente = Proponente::where('user_id', Auth::id())->first();
        if (!$proponente) {
            return redirect()->route('proponente.create')
                ->withErrors('Debes completar tu perfil de Proponente antes de continuar.');
        }

        $requisitos = $proceso->requisitos ?? [];
        $subidos = PostulacionArchivo::where('proceso_codigo', $codigo)
            ->where('proponente_id', $proponente->id)
            ->get()
            ->keyBy('requisito_key');

        // ⬇️ cambia el nombre de la vista a "postulaciones.archivos"
        return view('postulaciones.archivos', compact('proceso', 'proponente', 'requisitos', 'subidos'));
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
    // Mostrar form de subida de archivos por requisito

    // Guardar/actualizar PDFs por requisito
    public function archivosStore(Request $request, string $codigo)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();

        $proponente = Proponente::where('user_id', Auth::id())->first();
        if (!$proponente) {
            return back()->withErrors('Debes completar tu perfil de Proponente antes de postularte.');
        }

        $requisitos = $proceso->requisitos ?? [];
        if (empty($requisitos)) {
            return back()->withErrors('Este proceso no tiene requisitos configurados.');
        }

        // Validación dinámica (PDF máx 10MB). Cambia a 'required' si quieres forzar todos.
        $rules = [];
        foreach ($requisitos as $r) {
            $k = $r['key'];
            $rules["files.$k"] = ['nullable', 'file', 'mimes:pdf', 'max:10240']; // 10MB
        }
        $request->validate($rules);

        foreach ($requisitos as $r) {
            $k = $r['key'];
            if (!$request->hasFile("files.$k")) {
                continue; // si no envió ese archivo, no tocamos su registro
            }

            $file = $request->file("files.$k");

            // Ruta consistente por proponente y requisito (se reemplaza si sube de nuevo)
            $dir = "procesos/{$proceso->codigo}/proponentes/{$proponente->id}";
            $filename = "$k.pdf";

            // Guarda en disco privado (config('filesystems.disks.private'))
            $path = $file->storeAs($dir, $filename, ['disk' => 'private']);

            // Upsert por (proceso, proponente, requisito)
            PostulacionArchivo::updateOrCreate(
                [
                    'proceso_codigo' => $proceso->codigo,
                    'proponente_id'  => $proponente->id,
                    'requisito_key'  => $k,
                ],
                [
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'size_bytes'    => $file->getSize(),
                ]
            );
        }

        // (Opcional) si quieres que subir archivos automáticamente cree la postulación:
        // if (! $proceso->proponentesPostulados()->where('proponente_id', $proponente->id)->exists()) {
        //     $proceso->proponentesPostulados()->attach($proponente->id, [
        //         'estado' => 'POSTULADO',
        //         'observacion' => null,
        //     ]);
        // }

        return back()->with('success', 'Archivos guardados correctamente.');
    }

    // Ver/descargar un archivo subido (protegido)
    public function archivoShow(string $codigo, string $key)
    {
        $proceso = Proceso::where('codigo', $codigo)->firstOrFail();

        $proponente = Proponente::where('user_id', Auth::id())->first();
        if (!$proponente) abort(403);

        $archivo = PostulacionArchivo::where('proceso_codigo', $codigo)
            ->where('proponente_id', $proponente->id) // solo el dueño
            ->where('requisito_key', $key)
            ->firstOrFail();

        // (Si quieres permitir admins, añade una condición con tu gate/policy)
        // if (! (Auth::user()->can('isAdmin') || $archivo->proponente_id === $proponente->id)) abort(403);

        $abs = Storage::disk('private')->path($archivo->path);

        return response()->file($abs, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $archivo->original_name . '"',
        ]);
    }
}
