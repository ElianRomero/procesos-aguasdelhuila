@extends('layouts.app')

@section('styles')
  {{-- DataTables CSS (opcional, por el paginado/buscador) --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@endsection

@section('content')
<div class="max-w-6xl mx-auto p-4">
  <h1 class="text-2xl font-semibold">Noticias — Global (ADMIN)</h1>

  {{-- Filtros rápidos (opcional) --}}
  <form method="GET" class="flex flex-wrap items-center gap-2 mt-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar título, cuerpo o código"
           class="border rounded px-3 py-2 w-full md:w-1/2" />
    <select name="tipo" class="border rounded px-3 py-2">
      <option value="">Todos los tipos</option>
      @foreach (['COMUNICADO','PRORROGA','ADENDA','ACLARACION','CITACION','OTRO'] as $t)
        <option value="{{ $t }}" @selected(request('tipo')===$t)>{{ $t }}</option>
      @endforeach
    </select>
    <button class="px-4 py-2 bg-gray-800 text-white rounded">Buscar</button>
  </form>

  <div class="bg-white border rounded overflow-x-auto mt-4">
    <table id="tablaNoticias" class="min-w-full divide-y">
      <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
        <tr>
          <th class="px-3 py-2">Fecha</th>
          <th class="px-3 py-2">Usuario</th>
          <th class="px-3 py-2">Proceso</th>
          <th class="px-3 py-2">Título</th>
          <th class="px-3 py-2">Alcance</th>
          <th class="px-3 py-2">Acciones</th>
        </tr>
      </thead>

      <tbody class="divide-y text-sm">
        @foreach ($noticias as $n)
          @php
            $p = $n->proceso;
            $autor = $n->autor;

            $urlProceso = $p
              ? route('procesos.noticias.index', $p)
              : route('procesos.noticias.index', ['proceso' => $n->proceso_codigo]);

            $urlShow = route('procesos.noticias.show', ['proceso' => $n->proceso_codigo, 'noticia' => $n->id]);
          @endphp

          <tr>
            {{-- Fecha --}}
            <td class="px-3 py-2 align-top text-gray-600 whitespace-nowrap">
              {{ optional($n->publicada_en)->format('d/m/Y H:i') ?? $n->created_at->format('d/m/Y H:i') }}
            </td>

            {{-- Usuario / Autor --}}
            <td class="px-3 py-2 align-top">
              @if ($autor)
                <div class="font-medium">{{ $autor->name ?? '—' }}</div>
                <div class="text-xs text-gray-500">{{ $autor->email }}</div>
              @else
                <span class="text-xs text-gray-400">—</span>
              @endif
            </td>

            {{-- Proceso --}}
            <td class="px-3 py-2 align-top">
              <div class="font-medium">{{ $n->proceso_codigo }}</div>
              @if ($p && $p->objeto)
                <div class="text-xs text-gray-500 line-clamp-2">{{ $p->objeto }}</div>
              @endif
            </td>

            {{-- Título / Cuerpo (detalle colapsable) --}}
            <td class="px-3 py-2 align-top">
              <div class="font-medium">{{ $n->titulo }}</div>
              @if ($n->cuerpo)
                <details class="mt-1">
                  <summary class="cursor-pointer text-xs text-indigo-600 hover:underline">ver contenido</summary>
                  <div class="mt-1 text-xs text-gray-700 whitespace-pre-line border-l pl-2">
                    {{ $n->cuerpo }}
                  </div>
                </details>
              @endif

              {{-- Adjuntos (si hay) --}}
              @if ($n->archivos->count())
                <div class="mt-2 text-xs">
                  <strong>Adjuntos:</strong>
                  <ul class="list-disc ml-4">
                    @foreach ($n->archivos as $a)
                      <li><a class="text-indigo-600 hover:underline" href="{{ $a->url }}" target="_blank">{{ $a->original_name }}</a></li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </td>

            {{-- Alcance / Tipo --}}
            <td class="px-3 py-2 align-top">
              <div class="flex flex-wrap items-center gap-1">
                <span class="inline-block text-[11px] px-2 py-0.5 rounded-full border">{{ $n->tipo }}</span>
                @if ($n->publico)
                  <span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-green-50 text-green-700">Pública</span>
                @else
                  <span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Privada</span>
                  @if ($n->destinatarioProponente)
                    <span class="block text-[11px] text-gray-600">→ {{ $n->destinatarioProponente->razon_social }}</span>
                  @endif
                @endif>
              </div>
            </td>

            {{-- Acciones --}}
            <td class="px-3 py-2 align-top whitespace-nowrap">
              <div class="flex flex-col gap-1">
                <a href="{{ $urlShow }}" class="text-xs text-indigo-600 hover:underline">Ver</a>
                <a href="{{ $urlProceso }}" class="text-xs text-indigo-600 hover:underline">Ver noticias del proceso</a>

                @can('delete', $n)
                  <form method="POST" action="{{ route('procesos.noticias.destroy', [$n->proceso_codigo, $n->id]) }}"
                        onsubmit="return confirm('¿Eliminar noticia?')" class="mt-1">
                    @csrf @method('DELETE')
                    <button class="text-xs px-2 py-1 rounded bg-red-600 text-white">Eliminar</button>
                  </form>
                @endcan
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Paginado del backend (por si prefieres usarlo en lugar de DataTables) --}}
  <div class="mt-4">{{ $noticias->withQueryString()->links() }}</div>
</div>
@endsection

@section('scripts')
  {{-- jQuery (requerido por DataTables) --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  {{-- DataTables (versión jQuery) --}}
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script>
    $(function () {
      // Inicializa DataTables sobre TU diseño (no cambia el markup Tailwind)
      $('#tablaNoticias').DataTable({
        // Si usas el paginado de Laravel, puedes desactivar el de DataTables:
        paging: false,
        info: false,
        // O si prefieres que DataTables controle todo, quita el paginado de Laravel y activa paging=true

        order: [[0, 'desc']], // ordenar por Fecha
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        columnDefs: [
          { orderable: false, targets: [3, 4, 5] } // Título, Alcance y Acciones sin orden
        ]
      });
    });
  </script>
@endsection
