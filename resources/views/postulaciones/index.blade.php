@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Procesos Vigentes</h2>
    </x-slot>

    <style>
            [x-cloak] {
                display: none !important
            }
        </style>

        <div x-data="{
            showDetalle: false,
            det: {},
            openDetalle(p) {
                this.det = p;
                this.showDetalle = true;
            }
        }">

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

        {{-- MIS POSTULACIONES --}}
        <div class="bg-white shadow rounded-lg overflow-hidden p-4 mt-16 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Mis postulaciones</h3>
                <span class="text-sm text-gray-500">{{ $misPostulaciones->count() }} en total</span>
            </div>

            @if ($misPostulaciones->isEmpty())
                <div class="text-gray-500">Aún no te has postulado a ningún proceso.</div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($misPostulaciones as $mp)
                        @php
                            // Buscar mi estado en el pivote rápidamente
                            $pivot = $mp->proponentesPostulados->firstWhere('id', $miProponente->id)?->pivot ?? null;
                            $estadoPivot = $pivot?->estado ?? 'POSTULADO';
                        @endphp
                        <button type="button" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-sm"
                            @click="openDetalle(@js([
                                        'codigo' => $mp->codigo,
                                        'objeto' => $mp->objeto,
                                        'valor' => '$' . number_format($mp->valor, 0, ',', '.'),
                                        'fecha' => optional($mp->fecha)->format('d/m/Y'),
                                        'estado' => $mp->estado,
                                        'estadoPostulacion' => $estadoPivot,
                                        'link' => $mp->link_secop,
                                        'tipo' => $mp->tipoProceso->nombre ?? '',
                                        'estado_contrato' => $mp->estadoContrato->nombre ?? '',
                                        'tipo_contrato' => $mp->tipoContrato->nombre ?? '',
                                    ]))" title="Ver detalle">
                            <span class="font-medium">{{ $mp->codigo }}</span>
                            <span class="ml-2 text-xs px-2 py-0.5 rounded bg-blue-100">{{ $estadoPivot }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Necesario para ocultar el modal hasta que Alpine cargue --}}
      
            {{-- TABLA DE PROCESOS VIGENTES --}}
            <div class="bg-white shadow rounded-lg overflow-hidden p-4">
                <table id="tabla-procesos" class="min-w-full display">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Objeto</th>
                            <th>Valor</th>
                            <th>Fecha</th>
                            <th>Ver</th> {{-- ✅ quitamos “Estado” --}}
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($procesos as $p)
                            @php
                                $ya = $p->proponentesPostulados->isNotEmpty();
                                $estadoPost = $ya ? $p->proponentesPostulados->first()->pivot->estado : null;
                            @endphp
                            <tr>
                                <td>{{ $p->codigo }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($p->objeto, 120) }}</td>
                                <td>${{ number_format($p->valor, 0, ',', '.') }}</td>
                                <td>{{ $p->fecha?->format('d/m/Y') }}</td>

                                {{-- OJITO DETALLE --}}
                                <td>
                                    <button type="button" class="text-indigo-600 hover:underline"
                                        @click="openDetalle(@js([
                                                'codigo' => $p->codigo,
                                                'objeto' => $p->objeto,
                                                'valor' => '$' . number_format($p->valor, 0, ',', '.'),
                                                'fecha' => optional($p->fecha)->format('d/m/Y'),
                                                'estadoPostulacion' => $estadoPost,
                                                'link' => $p->link_secop,
                                                'tipo' => $p->tipoProceso->nombre ?? '',
                                                'estado_contrato' => $p->estadoContrato->nombre ?? '',
                                                'tipo_contrato' => $p->tipoContrato->nombre ?? '',
                                                'modalidad' => $p->modalidad_codigo ?? '',
                                            ]))">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="inline w-5 h-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.639 0 8.577 3.01 9.964 7.178.07.214.07.43 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.639 0-8.577-3.01-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Ver
                                    </button>
                                </td>

                                {{-- ACCIÓN (postular/retirar) --}}
                                <td>
                                    @if (!$ya)
                                        <form action="{{ route('postulaciones.store', $p->codigo) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <button class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-800">
                                                Postularme
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded bg-blue-100">{{ $estadoPost }}</span>
                                        <form
                                            action="{{ route('postulaciones.destroy', [$p->codigo, $miProponente->id]) }}"
                                            method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="ml-2 px-3 py-1 rounded bg-red-600 text-white hover:bg-red-800">
                                                Retirar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MODAL DETALLE --}}
            <div x-show="showDetalle" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Detalle del proceso</h3>
                        <button @click="showDetalle=false" class="text-gray-500 hover:text-gray-700">✕</button>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-600">Código</dt>
                            <dd x-text="det.codigo"></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Fecha</dt>
                            <dd x-text="det.fecha"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-gray-600">Objeto</dt>
                            <dd class="whitespace-pre-line" x-text="det.objeto"></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Valor</dt>
                            <dd x-text="det.valor"></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Tipo de Proceso</dt>
                            <dd x-text="det.tipo || '—' "></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Estado Contrato</dt>
                            <dd x-text="det.estado_contrato || '—' "></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Tipo de Contrato</dt>
                            <dd x-text="det.tipo_contrato || '—' "></dd>
                        </div>

                    </dl>

                    <div class="mt-4">
                        <a :href="det.link" target="_blank" class="text-blue-600 hover:underline"
                            x-show="det.link">Ver SECOP</a>
                    </div>

                    <div class="mt-6 text-right">
                        <button class="px-4 py-2 rounded bg-gray-200" @click="showDetalle=false">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script src="https://unpkg.com/alpinejs" defer></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const start = () => {
                if (!window.jQuery || !window.DataTable) return setTimeout(start, 50);
                new DataTable('#tabla-procesos', {
                    responsive: true,
                    order: [
                        [3, 'desc']
                    ], // Fecha ahora es la columna índice 3
                    pageLength: 10,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                    },
                    columnDefs: [{
                            targets: 2,
                            searchable: false
                        }, // Valor
                        {
                            targets: 4,
                            orderable: false
                        }, // Ver   (índice actualizado)
                        {
                            targets: 5,
                            orderable: false
                        }, // Acción
                    ],
                });
            };
            start();
        });
    </script>
@endsection
