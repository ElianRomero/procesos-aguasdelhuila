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
        det: {
            codigo: '',
            fecha: '',
            objeto: '',
            valor: '',
            tipo: '',
            estado_contrato: '',
            tipo_contrato: '',
            link: '',
            secop_url: '',
            requisitos: [],
            ya: false
        },
        secopUrl(idOrUrl) {
            if (!idOrUrl) return '';
            // Si viene URL completa, intenta extraer numConstancia
            if (/^https?:\/\//i.test(idOrUrl)) {
                const m = idOrUrl.match(/numConstancia=([^&]+)/i);
                return m ?
                    `https://www.contratos.gov.co/consultas/detalleProceso.do?numConstancia=${encodeURIComponent(m[1])}` :
                    idOrUrl;
            }
            // Si viene solo el ID (22-4-13368797)
            return `https://www.contratos.gov.co/consultas/detalleProceso.do?numConstancia=${encodeURIComponent(idOrUrl)}`;
        },
        openDetalle(p) {
            this.det = {
                codigo: p.codigo || '',
                fecha: p.fecha || '',
                objeto: p.objeto || '',
                valor: p.valor || '',
                tipo: p.tipo || '',
                estado_contrato: p.estado_contrato || '',
                tipo_contrato: p.tipo_contrato || '',
                link: p.link || '',
                requisitos: Array.isArray(p.requisitos) ? p.requisitos : [],
                ya: !!p.ya,
            };
            this.det.secop_url = this.secopUrl(this.det.link);
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
                <h3 class="text-lg font-semibold">Mis Oportunidades Activas</h3>
                <span class="text-sm text-gray-500">{{ $misPostulaciones->count() }} en total</span>
            </div>

            @if ($misPostulaciones->isEmpty())
                <div class="text-gray-500">Aún no te has postulado a ningún proceso.</div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($misPostulaciones as $mp)
                        @php
                            // Estado original del pivote (postulación)
                            $pivot = $mp->proponentesPostulados->firstWhere('id', $miProponente->id)?->pivot ?? null;
                            $estadoPivot = $pivot?->estado ?? 'POSTULADO';

                            // Si el proceso ya está cerrado (estado global en la tabla procesos)
                            // Si el proceso ya está cerrado (estado global en la tabla procesos)
                            if (strtoupper($mp->estado) === 'CERRADO') {
                                $estadoVisual = 'CERRADO';
                            }
                            // Si me asignaron como proponente
                            elseif ($mp->proponente_id === $miProponente->id) {
                                $estadoVisual = 'ASIGNADO';
                            }
                            // Si no, dejo el estado normal del pivote
                            else {
                                $estadoVisual = $estadoPivot;
                            }

                            // 🔹 Sobrescribir visualmente POSTULADO por INTERESADO
                            if ($estadoVisual === 'POSTULADO') {
                                $estadoVisual = 'INTERESADO';
                            }

                            // Colores según estado visual
                            $badge = match ($estadoVisual) {
                                'ASIGNADO' => 'bg-green-100 text-green-700',
                                'CERRADO' => 'bg-gray-300 text-gray-700',
                                'ACEPTADA', 'ACEPTADO' => 'bg-green-100 text-green-700',
                                'RECHAZADA', 'RECHAZADO' => 'bg-red-100 text-red-700',
                                default => 'bg-blue-100 text-blue-700', // INTERESADO u otros
                            };

                        @endphp

                        <button type="button" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-sm"
                            @click="openDetalle(@js([
    'codigo' => $mp->codigo,
    'objeto' => $mp->objeto,
    'valor' => '$' . number_format($mp->valor, 0, ',', '.'),
    'fecha' => optional($mp->fecha)->format('d/m/Y'),
    'estado' => $mp->estado,
    'estadoPostulacion' => $estadoVisual, // 👈 este es el que mostramos
    'link' => $mp->link_secop,
    'tipo' => $mp->tipoProceso->nombre ?? '',
    'estado_contrato' => $mp->estadoContrato->nombre ?? '',
    'tipo_contrato' => $mp->tipoContrato->nombre ?? '',
]))" title="Ver detalle">
                            <span class="font-medium">{{ $mp->codigo }}</span>
                            <span class="ml-2 text-xs px-2 py-0.5 rounded {{ $badge }}">{{ $estadoVisual }}</span>
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
                            <td>
                                <button type="button" class="text-indigo-600 hover:underline"
                                    @click="openDetalle(@js([
    'codigo' => $p->codigo,
    'objeto' => $p->objeto,
    'valor' => '$' . number_format($p->valor, 0, ',', '.'),
    'fecha' => optional($p->fecha)->format('d/m/Y'),
    'estadoPostulacion' => $estadoPost,
    'link' => $p->link_secop, // puede ser código SECOP o URL completa
    'tipo' => $p->tipoProceso->nombre ?? '',
    'estado_contrato' => $p->estadoContrato->nombre ?? '',
    'tipo_contrato' => $p->tipoContrato->nombre ?? '',
    'modalidad' => $p->modalidad_codigo ?? '',
    'requisitos' => $p->requisitos ?? [], // 👈 AÑADIDO
    'ya' => $ya, // 👈 AÑADIDO
]))">
                                    {{ $p->codigo }}
                                </button>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($p->objeto, 120) }}</td>
                            <td>${{ number_format($p->valor, 0, ',', '.') }}</td>
                            <td>{{ $p->fecha?->format('d/m/Y') }}</td>

                            {{-- OJITO DETALLE --}}


                            {{-- ACCIÓN (postular/retirar) --}}
                            <td>
                                @if (!$ya)
                                    <form action="{{ route('postulaciones.store', $p->codigo) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        <button class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-800">
                                            Interesado
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('postulaciones.destroy', [$p->codigo, $miProponente->id]) }}"
                                        method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="ml-2 px-3 py-1 rounded bg-gray-600 text-white hover:bg-gray-800">
                                            Desinteresado
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

            <!-- 🔹 Modal con altura fija y scroll interno -->
            <div class="bg-white rounded-xl shadow-xl w-[96vw] max-w-5xl h-[80vh] p-8 md:p-10 overflow-y-auto">

                <!-- Encabezado -->
                <div class="flex items-center justify-between mb-4 sticky top-0  z-10">
                    <h3 class="text-lg font-semibold"></h3>
                    <button @click="showDetalle=false" class="text-gray-500 hover:text-gray-700">✕</button>
                </div>

                <!-- Contenido -->
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-blue-700">Código</dt>
                        <dd x-text="det.codigo"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-blue-700">Fecha</dt>
                        <dd x-text="det.fecha"></dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="font-medium text-blue-700">Objeto</dt>
                        <dd class="whitespace-pre-line" x-text="det.objeto"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-blue-700">Valor</dt>
                        <dd x-text="det.valor"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-blue-700">Tipo de Proceso</dt>
                        <dd x-text="det.tipo || '—'"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-blue-700">Estado Contrato</dt>
                        <dd x-text="det.estado_contrato || '—'"></dd>
                    </div>
                    <div>
                        <dt class="font-medium text-blue-700">Tipo de Contrato</dt>
                        <dd x-text="det.tipo_contrato || '—'"></dd>
                    </div>
                </dl>

                <!-- Texto legal -->
                <div class="mt-5 p-3 rounded-lg bg-gray-50 border text-[13px] leading-relaxed text-gray-700">
                    Estimado interesado, en cumplimiento de la Ley 2195 de 2022 Art. 53, mediante el cual se adiciona el
                    Art. 13 de la Ley 1150 de 2007, el presente contrato se encuentra publicado en el SECOP II y podrá
                    acceder a través del siguiente botón.
                </div>

                <!-- Botón SECOP -->
                <div class="mt-4" x-show="det.secop_url">
                    <a :href="det.secop_url" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium">
                        Ver en SECOP
                    </a>
                </div>

                <!-- Requisitos -->
                <!-- Requisitos -->
                <div class="mt-6">
                    <h4 class="font-semibold mb-2">Requisitos</h4>

                    <template x-if="!(det.requisitos && det.requisitos.length)">
                        <p class="text-sm text-gray-500">Este proceso no tiene requisitos configurados.</p>
                    </template>

                    <!-- 🔹 Caja con scroll vertical si hay muchos requisitos -->
                    <div class="max-h-48 overflow-y-auto border rounded-lg p-3 bg-gray-50"
                        x-show="det.requisitos && det.requisitos.length">
                        <ul class="list-disc pl-6 space-y-1">
                            <template x-for="r in det.requisitos" :key="r.key">
                                <li class="text-sm text-gray-700" x-text="r.name"></li>
                            </template>
                        </ul>
                    </div>

                    <p class="text-xs text-black font-bold mt-3">
                        Para poder estar interesado en este proceso, debes adjuntar los documentos requeridos.
                    </p>
                </div>


                <!-- Acciones -->
                <div class="mt-6 flex flex-wrap gap-3 items-center">
                    <a :href="`{{ url('/postulaciones') }}/${det.codigo}/archivos`"
                        class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">
                        <span x-text="det.ya ? 'Si, Continuar' : 'Continuar a subir documentos'"></span>
                    </a>
                    <button @click="showDetalle=false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                        Cerrar
                    </button>
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
                            targets: 4,
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
