@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto">
        <h1 class="text-xl font-semibold text-white mb-4 mt-10">Observaciones</h1>

        @if (session('ok'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-800 px-4 py-2 text-sm">
                {{ session('ok') }}
            </div>
        @endif
        <div class="flex justify-end">
            <a href="{{ route('observaciones.ventanas.index') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Crear Observación
            </a>
        </div>
        <div class="bg-white border rounded overflow-x-auto mt-4">
            <table id="tablaObservaciones" class="min-w-full divide-y">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="px-3 py-2">Proceso</th>
                        <th class="px-3 py-2">Asunto</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-sm">
                    @foreach ($observaciones as $o)
                        @php
                            $p = $o->proceso;
                            $usr = $o->usuario;

                            $urlProceso = $p
                                ? route('procesos.observaciones.index', $p)
                                : route('procesos.observaciones.index', ['proceso' => $o->proceso_codigo]);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-600">
                                {{ $o->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-3 py-2 align-top">
                                @if ($usr)
                                    <div class="font-medium">{{ $usr->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $usr->email }}</div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top">
                                <div class="font-medium">{{ $o->proceso_codigo }}</div>
                                @if ($p && $p->objeto)
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $p->objeto }}</div>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top">
                                <div class="font-medium">{{ $o->asunto }}</div>
                                @if ($o->descripcion)
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs text-indigo-600 hover:underline">ver
                                            descripción</summary>
                                        <div class="mt-1 text-xs text-gray-700 whitespace-pre-line border-l pl-2">
                                            {{ $o->descripcion }}
                                        </div>
                                    </details>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top">
                                {{-- Selector para cambiar estado; al cambiar, se envía y al recargar ya no aparecerá --}}
                                <form method="POST" action="{{ route('observaciones.actualizarEstado', $o) }}"
                                    class="inline-flex items-center gap-2 auto-submit-on-change">
                                    @csrf @method('PATCH')
                                    <select name="estado" class="text-xs border rounded px-2 py-1">
                                        @foreach (['ENVIADA', 'ADMITIDA', 'RECHAZADA', 'RESUELTA'] as $e)
                                            <option value="{{ $e }}" @selected($o->estado === $e)>
                                                {{ $e }}</option>
                                        @endforeach
                                    </select>
                                    <button class="text-xs px-2 py-1 rounded bg-gray-800 text-white">Guardar</button>
                                </form>
                            </td>

                            <td class="px-3 py-2 align-top">
                                <a href="{{ $urlProceso }}" class="text-xs text-indigo-600 hover:underline">
                                    Ver observaciones del proceso
                                </a>
                               
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- jQuery (requerido por DataTables) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables (versión jQuery) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {
            // Inicializa DataTable
            $('#tablaObservaciones').DataTable({
                pageLength: 25,
                order: [
                    [0, 'desc']
                ], // Orden por fecha desc
                columnDefs: [{
                        orderable: false,
                        targets: [4, 5]
                    } // Estado y Acciones sin ordenar (ajusta índices si cambian)
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            // Auto-submit al cambiar estado (para que desaparezca al ya no ser ENVIADA)
            $('.auto-submit-on-change select[name="estado"]').on('change', function() {
                this.form.submit();
            });
        });
    </script>
@endsection
