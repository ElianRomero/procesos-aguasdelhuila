@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto">
        <h1 class="text-xl font-semibold mb-4">Mis observaciones</h1>

        {{-- Resumen por estado --}}
        <div class="mb-4 flex flex-wrap gap-2 text-sm">
            @php
                $badges = [
                    'ENVIADA' => 'bg-blue-100 text-blue-700',
                    'ADMITIDA' => 'bg-green-100 text-green-700',
                    'RECHAZADA' => 'bg-red-100 text-red-700',
                    'RESUELTA' => 'bg-gray-100 text-gray-700',
                ];
            @endphp
            @foreach (['ENVIADA', 'ADMITIDA', 'RECHAZADA', 'RESUELTA'] as $est)
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded {{ $badges[$est] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $est }}: {{ $stats[$est] ?? 0 }}
                </span>
            @endforeach
        </div>

     

        <div class="bg-white border rounded overflow-x-auto">
            <table id="tablaMisObservaciones" class="min-w-full divide-y">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Proceso</th>
                        <th class="px-3 py-2">Asunto</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Archivos</th>
                        
                    </tr>
                </thead>
                <tbody class="divide-y text-sm">
                    @foreach ($observaciones as $o)
                        @php
                            $p = $o->proceso;
                            $badge = $badges[$o->estado] ?? 'bg-gray-100 text-gray-700';
                            $puedeEditar = method_exists($o, 'puedeEditarPor')
                                ? $o->puedeEditarPor(auth()->user())
                                : false;
                        @endphp

                        <tr x-data="{ openDesc: false, openFiles: false }">
                            {{-- Fecha --}}
                            <td class="px-3 py-2 align-top text-gray-600">
                                {{ $o->created_at->format('d/m/Y H:i') }}
                            </td>

                            {{-- Proceso --}}
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium">{{ $o->proceso_codigo }}</div>
                                @if ($p && $p->objeto)
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $p->objeto }}</div>
                                @endif
                            </td>

                            {{-- Asunto / Descripción --}}
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium">{{ $o->asunto }}</div>
                                @if ($o->descripcion)
                                    <button @click="openDesc=!openDesc" class="text-xs text-indigo-600 hover:underline">
                                        ver descripción
                                    </button>
                                    <div x-show="openDesc" x-cloak
                                        class="mt-1 text-xs text-gray-700 whitespace-pre-line border-l pl-2">
                                        {{ $o->descripcion }}
                                    </div>
                                @endif
                            </td>

                            {{-- Estado (+ Editar si aplica) --}}
                            <td class="px-3 py-2 align-top">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badge }}">
                                    {{ $o->estado }}
                                </span>

                                @if ($puedeEditar)
                                    <div class="mt-2">
                                        <a href="{{ route('observaciones.edit', $o) }}"
                                            class="text-xs text-indigo-600 hover:underline">
                                            Editar observación
                                        </a>
                                    </div>
                                @endif
                            </td>

                            {{-- Archivos (descarga) --}}
                            <td class="px-3 py-2 align-top">
                                @if ($o->archivos->count())
                                    <button @click="openFiles=!openFiles"
                                        class="text-xs px-2 py-1 rounded border hover:bg-gray-50">
                                        {{ $o->archivos->count() }} archivo(s)
                                    </button>
                                    <ul x-show="openFiles" x-cloak class="mt-2 space-y-1 text-xs">
                                        @foreach ($o->archivos as $f)
                                            <li>
                                                <a href="{{ route('observaciones.archivos.download', [$o->id, $f->id]) }}"
                                                    class="text-indigo-600 hover:underline">
                                                    {{ $f->original_name }}
                                                </a>
                                                <span class="text-gray-400">({{ number_format(($f->size ?? 0) / 1024, 0) }}
                                                    KB)</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </div>
@endsection

{{-- Importante: tu layout usa @yield("scripts"), así que aquí va @section --}}
@section('scripts')
    {{-- jQuery + DataTables (solo si no los cargaste ya en otra vista) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {
            $('#tablaMisObservaciones').DataTable({
                pageLength: 25,
                order: [
                    [0, 'desc']
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
@endsection
