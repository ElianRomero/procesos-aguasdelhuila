{{-- resources/views/admin/postulaciones/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto mt-10 px-4" x-data="{ showDetalle: false, det: {}, openDetalle(p) { this.det = p;
            this.showDetalle = true } }">

        <h1 class="text-2xl font-bold mb-6"></h1>

        @if (session('success'))
            <div class="mb-4 text-green-700 bg-green-100 px-3 py-2 rounded">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-red-700 bg-red-100 px-3 py-2 rounded">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        {{-- Filtros rápidos --}}
        <div class="bg-white p-4 rounded-lg shadow mb-4 mt-5">
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <label class="text-sm text-gray-600">Buscar</label>
                    <input id="f-q" type="text" class="w-full border rounded px-3 py-2"
                        placeholder="Proponente, NIT, Proceso, Objeto...">
                </div>
                <div>
                    <label class="text-sm text-gray-600">Estado</label>
                    <select id="f-estado" class="w-full border rounded px-3 py-2">
                        <option value="">Todos</option>
                        <option value="ENVIADA">ENVIADA</option>
                        <option value="ACEPTADA">ACEPTADA</option>
                        <option value="RECHAZADA">RECHAZADA</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Desde</label>
                    <input id="f-desde" type="date" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm text-gray-600">Hasta</label>
                    <input id="f-hasta" type="date" class="w-full border rounded px-3 py-2">
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden p-4">
            <div class="overflow-x-auto">
                <table id="tablaPostulaciones" class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Proponente</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Contacto</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Proceso</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fecha</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Estado</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($postulaciones as $p)
                            @php
                                $prop = $p->proponente;
                                $proc = $p->proceso;
                            @endphp
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $prop->razon_social ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">NIT: {{ $prop->nit ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div>{{ $prop->telefono1 ?? '—' }} {{ $prop->telefono2 ? ' / ' . $prop->telefono2 : '' }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $prop->correo ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium">#{{ $proc->codigo }}</div>
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $proc->objeto }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ optional($p->fecha_postulacion)->format('Y-m-d') ?? $p->created_at->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $estado = strtoupper($p->estado ?? 'ENVIADA');
                                        $color =
                                            [
                                                'ENVIADA' => 'bg-blue-100 text-blue-700',
                                                'ACEPTADA' => 'bg-green-100 text-green-700',
                                                'RECHAZADA' => 'bg-red-100 text-red-700',
                                            ][$estado] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded {{ $color }}">{{ $estado }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('proponentes.show', $prop) }}"
                                        class="text-sm bg-gray-800 text-white px-3 py-2 rounded hover:bg-black inline-block">
                                        Ver detalle
                                    </a>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    No hay postulaciones registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        

    </div>
@endsection

@section('scripts')
    {{-- Alpine.js (si no lo tienes global) --}}
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- jQuery + DataTables --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>

    <script>
        $(function() {
            const tabla = new DataTable('#tablaPostulaciones', {
                pageLength: 10,
                order: [
                    [3, 'desc']
                ], // por fecha
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                },
                dom: '<"flex flex-col md:flex-row md:items-center md:justify-between gap-3"f>rt<"flex items-center justify-between mt-4"lp>',
            });

            // Filtro global
            $('#f-q').on('keyup change', function() {
                tabla.search(this.value).draw();
            });

            // Filtro por estado
            $('#f-estado').on('change', function() {
                const val = this.value;
                // Busca por estado en la 5ta columna (índice 4)
                tabla.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            // Filtro por rango de fechas (4ta columna índice 3, formato YYYY-MM-DD)
            function filtrarPorFecha() {
                const d = $('#f-desde').val();
                const h = $('#f-hasta').val();
                $.fn.dataTable.ext.search.push(function(settings, data) {
                    const fecha = data[3]; // columna fecha
                    if (!fecha) return true;
                    if (d && fecha < d) return false;
                    if (h && fecha > h) return false;
                    return true;
                });
                tabla.draw();
                $.fn.dataTable.ext.search.pop();
            }
            $('#f-desde, #f-hasta').on('change', filtrarPorFecha);
        });
    </script>
@endsection
