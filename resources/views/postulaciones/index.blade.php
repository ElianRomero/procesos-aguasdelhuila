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
            <h3 class="text-lg font-semibold">Mis Oportunidades Activas</h3>
            <span class="text-sm text-gray-500">{{ $misPostulaciones->count() }} en total</span>
        </div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-gray-500">
                Recuerda adjuntar los documentos necesarios para tu proceso de interés.
            </p>
        </div>

        @if ($misPostulaciones->isEmpty())
            <div class="text-gray-500">Aún no te has postulado a ningún proceso.</div>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($misPostulaciones as $mp)
                    @php
                        $pivot = $mp->proponentesPostulados->firstWhere('id', $miProponente->id)?->pivot ?? null;
                        $ya = (bool) $pivot; 
                        $estadoPivot = strtoupper($pivot?->estado ?? 'POSTULADO');

                        $estadoProceso = strtoupper($mp->estado ?? '');
                        if ($estadoProceso === 'CERRADO') {
                            $estadoVisual = 'CERRADO';
                        } elseif ($mp->proponente_id === $miProponente->id) {
                            $estadoVisual = 'ASIGNADO';
                        } else {
                            $estadoVisual = $estadoPivot;
                        }
                        if ($estadoVisual === 'POSTULADO') {
                            $estadoVisual = 'INTERESADO';
                        }

                        $badge = match ($estadoVisual) {
                            'ASIGNADO' => 'bg-green-100 text-green-700',
                            'CERRADO' => 'bg-gray-300 text-gray-700',
                            'ACEPTADA', 'ACEPTADO' => 'bg-green-100 text-green-700',
                            'RECHAZADA', 'RECHAZADO' => 'bg-red-100 text-red-700',
                            default => 'bg-blue-100 text-blue-700',
                        };

                        // ✅ URL de gestión por CÓDIGO
                        $archivosUrl = route('postulaciones.archivos.form', ['codigo' => $mp->codigo]); // ✅ por CÓDIGO
                    @endphp

                    <div class="flex items-center gap-2 mb-2">
                        {{-- Chip: abre modal de detalle --}}
                      <button type="button" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-sm"
  onclick="window.openModalMis(@js([
    '__click' => true,          // 👈 bandera anti auto-open
    'from' => 'mis',
    'codigo' => $mp->codigo,
    'objeto' => $mp->objeto,
    'valor' => '$' . number_format($mp->valor, 0, ',', '.'),
    'fecha' => optional($mp->fecha)->format('d/m/Y'),
    'estado' => $mp->estado,
    'estadoPostulacion' => $estadoVisual,
    'link' => $mp->link_secop,
    'tipo' => $mp->tipoProceso->nombre ?? '',
    'estado_contrato' => $mp->estadoContrato->nombre ?? '',
    'tipo_contrato' => $mp->tipoContrato->nombre ?? '',
    'ya' => (bool) $pivot,
    'archivos_url' => route('postulaciones.archivos.form', ['codigo' => $mp->codigo]),
    'requisitos' => $mp->requisitos ?? [],
    'observaciones' => $mp->observaciones ?? '',
    'ventana_definida' => $mp->tieneVentanaObservaciones(),
    'ventana_abierta' => $mp->ventanaObservacionesAbiertaYDefinida(),
    'ventana_abre_fmt' => optional($mp->observaciones_abren_en)?->format('d/m/Y H:i'),
    'ventana_cierra_fmt' => optional($mp->observaciones_cierran_en)?->format('d/m/Y H:i'),
    'obs_create_url' => route('procesos.observaciones.create', $mp),
    'postular_url' => route('postulaciones.store', ['codigo' => $mp->codigo]),
  ]))"
  title="Ver detalle">
  <span class="font-medium">{{ $mp->codigo }}</span>
  <span class="ml-2 text-xs px-2 py-0.5 rounded {{ $badge }}">{{ $estadoVisual }}</span>
</button>


                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden p-4">
        <table id="tabla-procesos" class="min-w-full display">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Objeto</th>
                    <th>Valor</th>
                    <th>Fecha</th>

                </tr>
            </thead>
            <tbody>
                @foreach ($procesos as $p)
                    @php
                        $pivotActual = $p->proponentesPostulados->firstWhere('id', $miProponente->id);
                        $ya = (bool) $pivotActual;
                        $estadoPost = $pivotActual?->pivot?->estado;
                        $ventanaDef = $p->tieneVentanaObservaciones();
                        $ventanaOpen = $p->ventanaObservacionesAbiertaYDefinida();
                        $postulanteKey = $miProponente->slug ?? ($miProponente->codigo ?? $miProponente->id);

                        $postularUrl = route('postulaciones.store', ['codigo' => $p->codigo]);
                        $archivosUrl = route('postulaciones.archivos.form', ['codigo' => $p->codigo]); // ✅ CORRECTO

                    @endphp
                    <tr>
                        <td>
                         <button type="button" class="text-indigo-600 hover:underline"
  onclick="window.openModalTabla(@js([
    '__click' => true,          // 👈 bandera anti auto-open
    'from' => 'tabla',
    'codigo' => $p->codigo,
    'objeto' => $p->objeto,
    'valor' => '$' . number_format($p->valor, 0, ',', '.'),
    'fecha' => optional($p->fecha)->format('d/m/Y'),
    'estado' => $p->estado,
    'estadoPostulacion' => $estadoPost,
    'link' => $p->link_secop,
    'tipo' => $p->tipoProceso->nombre ?? '',
    'estado_contrato' => $p->estadoContrato->nombre ?? '',
    'tipo_contrato' => $p->tipoContrato->nombre ?? '',
    'requisitos' => $p->requisitos ?? [],
    'ya' => (bool) $pivotActual,
    'observaciones' => $p->observaciones ?? '',
    'ventana_definida' => $ventanaDef,
    'ventana_abierta' => $ventanaOpen,
    'ventana_abre_fmt' => optional($p->observaciones_abren_en)?->format('d/m/Y H:i'),
    'ventana_cierra_fmt' => optional($p->observaciones_cierran_en)?->format('d/m/Y H:i'),
    'obs_create_url' => route('procesos.observaciones.create', $p),
    'postular_url' => route('postulaciones.store', ['codigo' => $p->codigo]),
    'archivos_url' => route('postulaciones.archivos.form', ['codigo' => $p->codigo]),
  ]))">
  {{ $p->codigo }}
</button>

                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($p->objeto, 120) }}</td>
                        <td>${{ number_format($p->valor, 0, ',', '.') }}</td>
                        <td>{{ $p->fecha?->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-proceso-modal-tabla />
    <x-proceso-modal-mis />

@endsection
@section('scripts')
   


<script src="https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js" defer></script>

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
                    ], // Fecha (col 3)
                    pageLength: 10,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                    },
                    columnDefs: [{
                            targets: 2,
                            searchable: false
                        }, // Valor
                        {
                            targets: 3,
                            orderable: false,
                            searchable: false
                        }, // Acción
                    ],
                });
            };
            start();
        });
    </script>
@endsection
