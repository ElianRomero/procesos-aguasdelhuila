<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Models\Proponente;
use App\Models\Proceso;
use App\Models\Postulacion;
use App\Models\PostulacionArchivo;
class AdminExpedientesController extends Controller
{
    
    public function index()
    {
        return view('backoffice.expedientes.grid');
    }

    public function data(Request $request)
    {
        // --- DEBUG SWITCHES (opcional, los puedes dejar) ---
        if ($request->boolean('counts')) {
            $counts = [
                'total_archivos' => DB::table('postulacion_archivos')->count(),
                'con_codigo'     => DB::table('postulacion_archivos')
                    ->whereNotNull('proceso_codigo')->whereRaw("TRIM(proceso_codigo) <> ''")->count(),
                'sin_codigo'     => DB::table('postulacion_archivos')
                    ->whereNull('proceso_codigo')
                    ->orWhereRaw("TRIM(COALESCE(proceso_codigo,'')) = ''")->count(),
            ];
            if ($request->boolean('dd')) dd($counts);
            return response()->json($counts);
        }

        if ($request->boolean('sample')) {
            $sample = DB::table('postulacion_archivos')
                ->select('id','proponente_id','proceso_codigo','path','created_at')
                ->orderByDesc('id')->limit(10)->get();
            if ($request->boolean('dd')) dd($sample);
            return response()->json($sample);
        }
        // ----------------------------------------------------

        $pairsQuery = DB::table('postulacion_archivos')
            ->select(
                'proponente_id',
                DB::raw("TRIM(COALESCE(proceso_codigo,'')) AS proceso_codigo"),
                DB::raw('COUNT(*) AS docs_count'),
                DB::raw('MAX(COALESCE(created_at, updated_at)) AS last_ts')
            )
            ->whereNotNull('proceso_codigo')
            ->whereRaw("TRIM(proceso_codigo) <> ''");

        $pairs = $pairsQuery
            ->groupBy('proponente_id', DB::raw("TRIM(COALESCE(proceso_codigo,''))"))
            ->orderByDesc('last_ts')
            ->get();

        if ($pairs->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $proponenteIds = $pairs->pluck('proponente_id')->unique()->values();
        $procCodes     = $pairs->pluck('proceso_codigo')->filter()->unique()->values();

        $props = Proponente::select('id','razon_social','correo','telefono1','telefono2')
            ->whereIn('id', $proponenteIds)->get()->keyBy('id');

        $postu = Postulacion::select('proponente_id','proceso_codigo','estado')
            ->whereIn('proponente_id', $proponenteIds)
            ->when($procCodes->isNotEmpty(), fn($q) => $q->whereIn('proceso_codigo', $procCodes))
            ->get()
            ->keyBy(fn($r) => $r->proponente_id . '|' . trim((string) $r->proceso_codigo));

        $data = $pairs->map(function ($r) use ($props, $postu) {
            $pro = $props[$r->proponente_id] ?? null;
            $cod = (string) $r->proceso_codigo;

            $contacto = $pro?->correo ?: ($pro?->telefono1 ?: ($pro?->telefono2 ?: '—'));

            $key    = $r->proponente_id . '|' . $cod;
            $estado = optional($postu->get($key))->estado ?? 'ENVIADA';

            $badgeClass = match ($estado) {
                'ACEPTADA'  => 'bg-green-100 text-green-700',
                'RECHAZADA' => 'bg-red-100 text-red-700',
                default     => 'bg-gray-100 text-gray-700',
            };

            // 🔹 Botón para abrir modal (usado por DataTables; el JS hará window.docsModalRef.load(...))
            $btn = sprintf(
                '<button class="btn-docs px-2.5 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700" '.
                'data-proponente="%d" data-proceso="%s" data-nombre="%s">Ver documentos (%d)</button>',
                $r->proponente_id,
                e($cod),
                e($pro->razon_social ?? 'Proponente'),
                (int) $r->docs_count
            );

            return [
                'proponente' => e($pro->razon_social ?? '—'),
                'contacto'   => e($contacto),
                'proceso'    => e($cod),
                'fecha'      => $r->last_ts ? date('Y-m-d H:i', strtotime($r->last_ts)) : '—',
                'estado'     => '<span class="inline-block px-2 py-1 rounded text-xs '.$badgeClass.'">'.e($estado).'</span>',
                'acciones'   => $btn,
            ];
        })->values();

        if ($request->boolean('data') && $request->boolean('dd')) {
            dd($data->take(50));
        }

        return response()->json(['data' => $data]);
    }

    public function docs(Proponente $proponente, Request $request)
    {
        $codigo = trim((string) $request->query('proceso', ''));

        if ($codigo === '') {
            if ($request->boolean('dd')) dd(['items' => [], 'msg' => 'proceso vacío']);
            return response()->json(['items' => []]);
        }

        $files = PostulacionArchivo::query()
            ->where('proponente_id', $proponente->id)
            ->whereRaw('TRIM(proceso_codigo) = ?', [$codigo])
            ->latest()
            ->get();

        $items = $files->map(function (PostulacionArchivo $a) use ($proponente) {
            $ruta = $a->path ? str_replace('\\','/',$a->path) : null;
            return [
                'id'    => $a->id,
                'req'   => $a->requisito_key ?? '',
                'name'  => $a->original_name ?? ($ruta ? basename($ruta) : 'Documento'),
                'size'  => $a->size_bytes ? number_format($a->size_bytes / 1024, 1) . ' KB' : null,
                'url'   => $ruta
                    ? URL::temporarySignedRoute(
                        'bo.expedientes.stream',
                        now()->addMinutes(30),
                        ['proponente' => $proponente->id, 'path' => $ruta]
                    )
                    : null,
                'fecha' => optional($a->created_at)->format('Y-m-d H:i'),
            ];
        })->values();

        if ($request->boolean('dd')) dd(['proceso' => $codigo, 'items' => $items->take(20)]);

        return response()->json(['proceso_codigo' => $codigo, 'items' => $items]);
    }

    public function stream(Proponente $proponente, string $path)
    {
        $disk = Storage::disk('private');
        $path = str_replace('\\','/',$path);

        if (!Str::contains($path, "/proponentes/{$proponente->id}/")) abort(403);
        if (!$disk->exists($path)) abort(404);

        $fullPath = $disk->path($path);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $name = basename($path);

        return response()->file($fullPath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }
}
