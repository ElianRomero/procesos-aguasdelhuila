<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use App\Models\NoticiaArchivo;
use App\Models\Proceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\NoticiaLectura;
use App\Models\NoticiaComentario;
use App\Models\NoticiaComentarioArchivo;

class NoticiaController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request, Proceso $proceso)
    {
        $user = $request->user();
        $tipo = $request->get('tipo');     // filtro opcional
        $scope = $request->get('scope');   // all|public|private_to_me

        $q = Noticia::delProceso($proceso->codigo)->visiblesParaUsuario($user);

        if ($tipo) {
            $q->where('tipo', $tipo);
        }

        if ($scope === 'public') {
            $q->where('publico', true);
        } elseif ($scope === 'private_to_me') {
            $pid = optional(optional($user)->proponente)->id;
            $q->where('publico', false)->where('destinatario_proponente_id', $pid);
        }

        $noticias = $q->orderByDesc('publicada_en')->orderByDesc('id')->paginate(15);

        return view('noticias.index', compact('proceso', 'noticias'));
    }

    // Form de creación (solo admin)
    public function create(Proceso $proceso)
    {

        $proponentes = $proceso->proponentesPostulados()->get(); // para privados
        return view('noticias.create', compact('proceso', 'proponentes'));
    }

    // Guardar
    public function store(Request $request, Proceso $proceso)
    {


        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'cuerpo' => ['required', 'string'],
            'tipo' => ['required', Rule::in(['COMUNICADO', 'PRORROGA', 'ADENDA', 'ACLARACION', 'CITACION', 'OTRO'])],
            'publico' => ['required', 'boolean'],
            'destinatario_proponente_id' => ['nullable', 'integer', 'exists:proponentes,id'],
            'archivos.*' => ['nullable', 'file', 'max:10240'], // 10MB c/u
        ]);

        // Regla de consistencia: si es público, NO debe venir destinatario; si es privado, SÍ debe venir
        if ($data['publico'] === true) {
            $data['destinatario_proponente_id'] = null;
        } else {
            if (empty($data['destinatario_proponente_id'])) {
                return back()->withErrors(['destinatario_proponente_id' => 'Requerido para noticia privada.'])->withInput();
            }
        }

        $noticia = Noticia::create([
            'proceso_codigo' => $proceso->codigo,
            'autor_user_id' => $request->user()->id,
            'destinatario_proponente_id' => $data['destinatario_proponente_id'] ?? null,
            'titulo' => $data['titulo'],
            'cuerpo' => $data['cuerpo'],
            'tipo' => $data['tipo'],
            'publico' => (bool) $data['publico'],
            'estado' => 'PUBLICADA',
            'publicada_en' => now(),
        ]);

        // Adjuntos
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $file) {
                $disk = $noticia->publico ? 'public' : 'private';
                $path = $file->store("noticias/{$proceso->codigo}/{$noticia->id}", $disk);

                NoticiaArchivo::create([
                    'noticia_id' => $noticia->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        // (Opcional) aquí puedes disparar eventos/notifications a destinatarios

        return redirect()
            ->route('noticias.index', $proceso)
            ->with('ok', 'Noticia publicada correctamente.');
    }

    public function show(Request $request, Proceso $proceso, Noticia $noticia)
    {
        $this->authorize('view', $noticia);

        $noticia->load([
            'archivos',
            'comentarios' => function ($q) {
                $q->whereNull('parent_id')
                    ->with([
                        'autor',
                        'proponente',
                        'archivos', // 👈 añade archivos del padre
                        'children' => function ($c) {
                            $c->with(['autor', 'proponente', 'archivos']) // 👈 añade archivos de hijos
                                ->orderBy('created_at');
                        }
                    ])
                    ->orderBy('created_at');
            },
        ]);

        return view('noticias.show', compact('proceso', 'noticia'));
    }
    public function verArchivoNoticia(Proceso $proceso, Noticia $noticia, NoticiaArchivo $archivo)
    {
        // 1) Integridad de la ruta
        if ($noticia->proceso_codigo !== $proceso->codigo || $archivo->noticia_id !== $noticia->id) {
            abort(404);
        }

        // 2) Permisos (misma policy que para ver la noticia)
        $this->authorize('view', $noticia);

        // 3) Servir el archivo (inline si es PDF/imagen)
        $disk = Storage::disk($archivo->disk);
        if (!$disk->exists($archivo->path))
            abort(404);

        $headers = [
            'Content-Type' => $archivo->mime ?? 'application/octet-stream',
            // inline para que el PDF/imagen se abra en el navegador
            'Content-Disposition' => 'inline; filename="' . addslashes($archivo->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // Si tu versión de Laravel soporta ->response():
        if (method_exists($disk, 'response')) {
            return $disk->response($archivo->path, $archivo->original_name, $headers);
        }

        // Fallback (descarga directa):
        return response($disk->get($archivo->path), 200, $headers);
    }

    public function verArchivoComentario(
        Proceso $proceso,
        Noticia $noticia,
        NoticiaComentario $comentario,
        NoticiaComentarioArchivo $archivo
    ) {
        // 1) Integridad de la ruta
        if (
            $noticia->proceso_codigo !== $proceso->codigo ||
            $comentario->noticia_id !== $noticia->id ||
            $archivo->comentario_id !== $comentario->id
        ) {
            abort(404);
        }

        // 2) Permisos
        $this->authorize('view', $noticia);

        // 3) Servir el archivo
        $disk = Storage::disk($archivo->disk);
        if (!$disk->exists($archivo->path))
            abort(404);

        $headers = [
            'Content-Type' => $archivo->mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($archivo->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (method_exists($disk, 'response')) {
            return $disk->response($archivo->path, $archivo->original_name, $headers);
        }

        return response($disk->get($archivo->path), 200, $headers);
    }


    public function comentariosStore(Request $request, Proceso $proceso, Noticia $noticia)
    {
        $this->authorize('view', $noticia);

        $data = $request->validate([
            'cuerpo' => ['required', 'string', 'min:2', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:noticia_comentarios,id'],
            // 50 MB por archivo (Laravel usa KB): 50*1024 = 51200
            'archivos.*' => [
                'nullable',
                'file',
                'max:51200',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/svg+xml'
            ],
        ]);

        if (!empty($data['parent_id'])) {
            $ok = NoticiaComentario::where('id', $data['parent_id'])
                ->where('noticia_id', $noticia->id)->exists();
            if (!$ok) {
                return back()->withErrors(['parent_id' => 'La respuesta debe pertenecer a esta noticia.'])
                    ->withInput();
            }
        }

        // Límite recomendado de cantidad de archivos
        $maxFiles = 5;
        $files = $request->file('archivos', []);
        if (is_array($files) && count($files) > $maxFiles) {
            return back()->withErrors(['archivos' => "Máximo {$maxFiles} archivos por comentario."])
                ->withInput();
        }

        $proponenteId = optional(optional($request->user())->proponente)->id;

        $coment = NoticiaComentario::create([
            'noticia_id' => $noticia->id,
            'user_id' => $request->user()->id,
            'proponente_id' => $proponenteId,
            'parent_id' => $data['parent_id'] ?? null,
            'cuerpo' => $data['cuerpo'],
        ]);

        // Guardar adjuntos
        if ($files) {
            $disk = $noticia->publico ? 'public' : 'private';
            foreach ($files as $file) {
                if (!$file)
                    continue;
                $path = $file->store("noticias/{$proceso->codigo}/comentarios/{$coment->id}", $disk);
                NoticiaComentarioArchivo::create([
                    'comentario_id' => $coment->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        // (opcional) marcar noticia como leída al comentar
        if ($proponenteId) {
            NoticiaLectura::updateOrCreate(
                ['noticia_id' => $noticia->id, 'proponente_id' => $proponenteId],
                ['read_at' => now()]
            );
        }

        return redirect()
            ->route('procesos.noticias.show', [$proceso, $noticia])
            ->with('ok', 'Comentario publicado.')
            ->withFragment('c' . $coment->id);
    }


    public function comentariosDestroy(Request $request, Proceso $proceso, Noticia $noticia, NoticiaComentario $comentario)
    {
        $this->authorize('view', $noticia);

        $user = $request->user();
        $isOwner = $comentario->user_id === $user->id;
        $isAdmin = $user->can('isAdmin');

        if (!$isOwner && !$isAdmin)
            abort(403);
        if ($comentario->noticia_id !== $noticia->id)
            abort(404);

        // borrar archivos
        foreach ($comentario->archivos as $a) {
            Storage::disk($a->disk)->delete($a->path);
        }

        $comentario->delete();

        return back()->with('ok', 'Comentario eliminado.');
    }

    public function destroy(Request $request, Proceso $proceso, Noticia $noticia)
    {
        $this->authorize('delete', $noticia);
        // elimina archivos físicos
        foreach ($noticia->archivos as $a) {
            Storage::disk($a->disk)->delete($a->path);
        }
        $noticia->delete();

        return back()->with('ok', 'Noticia eliminada.');
    }
    // 1) PROCESOS (no pasa $noticias)
    public function adminProcesosIndex(Request $request)
    {
        return view('noticias.procesos-index'); // ← esta vista NO debe usar $noticias
    }


    /* =================== ADMIN: INDEX (vista) =================== */
    public function adminNoticiasIndex(Request $request)
    {
        // Solo retorna la vista; DataTables pedirá a adminNoticiasData
        return view('noticias.index');
    }

    /* =================== ADMIN: DATA (JSON para DataTables) =================== */
    public function adminNoticiasData(Request $request)
    {
        $q = trim($request->get('q', ''));
        $tipo = $request->get('tipo');

        $noticias = Noticia::with(['proceso', 'autor', 'destinatarioProponente'])
            ->when($q, function ($qq) use ($q) {
                $qq->where('titulo', 'like', "%{$q}%")
                    ->orWhere('cuerpo', 'like', "%{$q}%")
                    ->orWhereHas('proceso', fn($pq) => $pq->where('codigo', 'like', "%{$q}%"));
            })
            ->when($tipo, fn($qq) => $qq->where('tipo', $tipo))
            ->orderByDesc('publicada_en')->orderByDesc('id')
            ->get();

        $rows = $noticias->map(function ($n) {
            $fecha = optional($n->publicada_en)->format('d/m/Y H:i') ?? $n->created_at->format('d/m/Y H:i');
            $autorName = e(optional($n->autor)->name ?? '—');
            $autorEmail = e(optional($n->autor)->email ?? '');
            $procCod = e($n->proceso_codigo);
            $procObj = e(optional($n->proceso)->objeto ?? '');
            $titulo = e($n->titulo);
            $cuerpo = e($n->cuerpo);

            $alcance = '<div class="flex flex-wrap items-center gap-1">'
                . '<span class="inline-block text-[11px] px-2 py-0.5 rounded-full border">' . e($n->tipo) . '</span>'
                . ($n->publico
                    ? '<span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-green-50 text-green-700">Pública</span>'
                    : '<span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Privada</span>'
                );

            if (!$n->publico && $n->destinatarioProponente) {
                $alcance .= '<span class="block text-[11px] text-gray-600">→ '
                    . e($n->destinatarioProponente->razon_social) . '</span>';
            }
            $alcance .= '</div>';

            $urlProceso = route('procesos.noticias.index', ['proceso' => $n->proceso_codigo]);
            $urlShow = route('procesos.noticias.show', ['proceso' => $n->proceso_codigo, 'noticia' => $n->id]);

            // Eliminar vía fetch (AJAX) con meta CSRF en el layout
            $acciones = '
                <div class="flex flex-col gap-1">
                  <a href="' . e($urlShow) . '" class="text-xs text-indigo-600 hover:underline">Ver</a>
                  <a href="' . e($urlProceso) . '" class="text-xs text-indigo-600 hover:underline">Ver noticias del proceso</a>
                  <button type="button" class="text-xs px-2 py-1 rounded bg-red-600 text-white btn-eliminar"
                          data-proceso="' . e($n->proceso_codigo) . '"
                          data-id="' . e($n->id) . '">Eliminar</button>
                </div>';

            return [
                'fecha' => $fecha,
                'usuario' => '<div class="font-medium">' . $autorName . '</div><div class="text-xs text-gray-500">' . $autorEmail . '</div>',
                'proceso' => '<div class="font-medium">' . $procCod . '</div>'
                    . ($procObj ? '<div class="text-xs text-gray-500 line-clamp-2">' . $procObj . '</div>' : ''),
                'titulo' => '<div class="font-medium">' . $titulo . '</div>'
                    . ($cuerpo ? '<details class="mt-1"><summary class="cursor-pointer text-xs text-indigo-600 hover:underline">ver contenido</summary><div class="mt-1 text-xs text-gray-700 whitespace-pre-line border-l pl-2">' . $cuerpo . '</div></details>' : ''),
                'alcance' => $alcance,
                'acciones' => $acciones,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    /* =================== ADMIN: CREATE GLOBAL =================== */
    public function adminCreate()
    {


        // Puedes paginar/cargar más si son demasiados
        $procesos = Proceso::orderByDesc('fecha')
            ->select('codigo', 'objeto', 'fecha')
            ->limit(200)->get();

        return view('noticias.create', compact('procesos'));
    }

    public function adminStore(Request $request)
    {


        $data = $request->validate([
            'proceso_codigo' => ['required', 'string', 'exists:procesos,codigo'],
            'titulo' => ['required', 'string', 'max:180'],
            'cuerpo' => ['required', 'string'],
            'tipo' => ['required', Rule::in(['COMUNICADO', 'PRORROGA', 'ADENDA', 'ACLARACION', 'CITACION', 'OTRO'])],
            'publico' => ['required', 'boolean'],
            'destinatario_proponente_id' => ['nullable', 'integer', 'exists:proponentes,id'],
            'archivos.*' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($data['publico']) {
            $data['destinatario_proponente_id'] = null;
        } else {
            if (empty($data['destinatario_proponente_id'])) {
                return back()->withErrors(['destinatario_proponente_id' => 'Requerido para noticia privada.'])->withInput();
            }
            // (opcional) valida que el proponente pertenezca al proceso
            $pertenece = Proceso::where('codigo', $data['proceso_codigo'])
                ->whereHas('proponentesPostulados', fn($q) => $q->where('proponentes.id', $data['destinatario_proponente_id']))
                ->exists();
            if (!$pertenece) {
                return back()->withErrors(['destinatario_proponente_id' => 'El proponente no está postulado a ese proceso.'])->withInput();
            }
        }

        $noticia = Noticia::create([
            'proceso_codigo' => $data['proceso_codigo'],
            'autor_user_id' => $request->user()->id,
            'destinatario_proponente_id' => $data['destinatario_proponente_id'] ?? null,
            'titulo' => $data['titulo'],
            'cuerpo' => $data['cuerpo'],
            'tipo' => $data['tipo'],
            'publico' => (bool) $data['publico'],
            'estado' => 'PUBLICADA',
            'publicada_en' => now(),
        ]);

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $file) {
                $disk = $noticia->publico ? 'public' : 'private';
                $path = $file->store("noticias/{$data['proceso_codigo']}/{$noticia->id}", $disk);
                NoticiaArchivo::create([
                    'noticia_id' => $noticia->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('admin.noticias.index')->with('ok', 'Noticia publicada correctamente.');

    }

    /* =================== ADMIN: Proponentes por proceso (JSON) =================== */
    public function adminProponentesByProceso(Proceso $proceso)
    {
        $list = $proceso->proponentesPostulados()
            ->select('proponentes.id', 'proponentes.razon_social', 'proponentes.nit')
            ->orderBy('proponentes.razon_social')->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'text' => $p->razon_social . ' (NIT: ' . $p->nit . ')',
            ]);

        return response()->json($list);
    }
    public function adminProcesosSearch(\Illuminate\Http\Request $request)
    {
        $q = trim($request->get('q', ''));

        $procesos = \App\Models\Proceso::query()
            ->when($q, function ($qq) use ($q) {
                $qq->where('codigo', 'like', "%{$q}%")
                    ->orWhere('objeto', 'like', "%{$q}%")
                    ->orWhere('modalidad_codigo', 'like', "%{$q}%")
                    ->orWhere('tipo_proceso_codigo', 'like', "%{$q}%");
            })
            ->orderByDesc('fecha')
            ->limit(20)
            ->get(['codigo', 'objeto', 'fecha', 'modalidad_codigo', 'tipo_proceso_codigo']);

        return response()->json(
            $procesos->map(fn($p) => [
                'codigo' => $p->codigo,
                'objeto' => $p->objeto,
                'fecha' => optional($p->fecha)->format('Y-m-d'),
                'badge' => trim(($p->tipo_proceso_codigo ?? '') . ' • ' . ($p->modalidad_codigo ?? ''), ' •'),
            ])
        );
    }
    public function misNoticias(Request $request)
    {
        $user = $request->user();
        $proponente = optional($user)->proponente;
        if (!$proponente) {
            abort(403); // no es proponente
        }

        // Códigos de procesos a los que está postulado (interesado)
        $procesosCodigos = $proponente->procesosPostulados()->pluck('procesos.codigo');

        // Noticias relevantes:
        // - Públicas de sus procesos
        // - Privadas dirigidas a él (sin importar el proceso)
        $noticias = Noticia::with(['proceso', 'autor'])
            ->where(function ($q) use ($procesosCodigos, $proponente) {
                $q->where(function ($qq) use ($procesosCodigos) {
                    $qq->where('publico', true)
                        ->whereIn('proceso_codigo', $procesosCodigos);
                })->orWhere(function ($qq) use ($proponente) {
                    $qq->where('publico', false)
                        ->where('destinatario_proponente_id', $proponente->id);
                });
            })
            ->orderByDesc('publicada_en')->orderByDesc('id')
            ->paginate(20);

        return view('proponentes.mis-noticias', compact('noticias'));
    }
    public function marcarLeida(Request $request, Proceso $proceso, Noticia $noticia)
    {
        // Respetar tu policy de vista
        $this->authorize('view', $noticia);

        // Solo proponentes “marcan” lectura
        $proponenteId = optional(optional($request->user())->proponente)->id;
        if (!$proponenteId) {
            // No rompe el flujo: simplemente no hace nada
            return response()->json(['ok' => false, 'msg' => 'Solo proponentes marcan lectura'], 200);
        }

        NoticiaLectura::updateOrCreate(
            ['noticia_id' => $noticia->id, 'proponente_id' => $proponenteId],
            ['read_at' => now()]
        );

        return response()->json(['ok' => true]);
    }


}
