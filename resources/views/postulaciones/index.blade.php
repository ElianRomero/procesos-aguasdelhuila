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
        /* --- UI --- */
        showDetalle: false,
        showArchivosModal: false,
    
        /* --- DETALLE --- */
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
            ya: false,
            observaciones: '',
            estado: '',
            ventana_definida: false,
            ventana_abierta: false,
            ventana_abre_fmt: '',
            ventana_cierra_fmt: '',
            obs_create_url: '',
            postular_url: '',
            archivos_url: '',
        },
    
        /* --- ARCHIVOS (solo redirección) --- */
        arch: { codigo: '', archivos_url: '' },
    
        routeStore(codigo) {
            return `{{ route('postulaciones.store', ':codigo') }}`.replace(':codigo', encodeURIComponent(codigo));
        },
    
        secopUrl(idOrUrl) {
            if (!idOrUrl) return '';
            if (/^https?:\/\//i.test(idOrUrl)) {
                const m = idOrUrl.match(/numConstancia=([^&]+)/i);
                return m ?
                    `https://www.contratos.gov.co/consultas/detalleProceso.do?numConstancia=${encodeURIComponent(m[1])}` :
                    idOrUrl;
            }
            return `https://www.contratos.gov.co/consultas/detalleProceso.do?numConstancia=${encodeURIComponent(idOrUrl)}`;
        },
    
        openDetalle(p) {
            this.showArchivosModal = false; // cierra el otro modal
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
                observaciones: p.observaciones || '',
                estado: p.estado || '',
                ventana_definida: !!p.ventana_definida,
                ventana_abierta: !!p.ventana_abierta,
                ventana_abre_fmt: p.ventana_abre_fmt || '',
                ventana_cierra_fmt: p.ventana_cierra_fmt || '',
                obs_create_url: p.obs_create_url || '',
                postular_url: p.postular_url || '',
                archivos_url: p.archivos_url || '',
                secop_url: '',
            };
            this.det.secop_url = this.secopUrl(this.det.link);
            this.showDetalle = true;
        },
    
        openArchivosModal(payload) {
            this.showDetalle = false; // cierra el de detalle
            this.arch = {
                codigo: payload.codigo || '',
                archivos_url: payload.archivos_url || '',
            };
            this.showArchivosModal = true;
        },
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
                            $ya = (bool) $pivot; // ya existe postulación
                            $estadoPivot = strtoupper($pivot?->estado ?? 'POSTULADO');

                            // estado visual + badge
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
                            $archivosUrl = route('postulaciones.archivos.form', ['codigo' => $mp->codigo]);
                        @endphp

                        <div class="flex items-center gap-2 mb-2">
                            {{-- Chip: abre modal de detalle --}}
                            <button type="button" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-sm"
                                @click="openDetalle(@js([
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
]))" title="Ver detalle">
                                <span class="font-medium">{{ $mp->codigo }}</span>
                                <span
                                    class="ml-2 text-xs px-2 py-0.5 rounded {{ $badge }}">{{ $estadoVisual }}</span>
                            </button>

                            {{-- Botón: abre modal que solo redirige a la gestión de documentos --}}
                            <button type="button"
                                class="px-3 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                                @click="openArchivosModal(@js([
    'codigo' => $mp->codigo,
    'archivos_url' => $archivosUrl,
]))">
                                Gestionar documentos
                            </button>
                        </div>
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

                            $postularUrl = route('postulaciones.store', ['codigo' => $p->codigo]);

                            $postulanteKey = $miProponente->slug ?? ($miProponente->codigo ?? $miProponente->id);
                            $archivosUrl = route('postulaciones.archivos.form', ['codigo' => $postulanteKey]);
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
    'link' => $p->link_secop,
    'tipo' => $p->tipoProceso->nombre ?? '',
    'estado_contrato' => $p->estadoContrato->nombre ?? '',
    'tipo_contrato' => $p->tipoContrato->nombre ?? '',
    'modalidad' => $p->modalidad_codigo ?? '',
    'requisitos' => $p->requisitos ?? [],
    'ya' => $ya,
    'observaciones' => $p->observaciones ?? '',
    'estado' => $p->estado,

    'ventana_definida' => $ventanaDef,
    'ventana_abierta' => $ventanaOpen,
    'ventana_abre_fmt' => optional($p->observaciones_abren_en)?->format('d/m/Y H:i'),
    'ventana_cierra_fmt' => optional($p->observaciones_cierran_en)?->format('d/m/Y H:i'),
    'obs_create_url' => route('procesos.observaciones.create', $p),
    'postular_url' => $postularUrl,
    'archivos_url' => $archivosUrl,
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
                    <div x-show="det.observaciones && det.observaciones.trim() !== ''" x-cloak
                        class="mt-5 p-3 rounded-lg bg-gray-50 border text-[13px] leading-relaxed text-gray-700">
                        <h2 class="text-sm font-semibold">Observaciones</h2>
                        <p class="mt-2 whitespace-pre-line" x-text="det.observaciones"></p>
                    </div>
                    <!-- Botón para ir al formulario -->
                    <div class="mt-4">
                        <!-- Solo si la ventana existe y está abierta -->
                        <template x-if="det.ventana_definida && det.ventana_abierta">
                            <a :href="det.obs_create_url"
                                class="inline-flex items-center px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-sm">
                                Realizar Observación
                            </a>
                        </template>

                        <!-- Si no hay ventana definida -->
                        <template x-if="!det.ventana_definida">
                            <div class="text-sm text-gray-700 bg-gray-100 border border-gray-200 rounded px-3 py-2">
                                Aún no se ha habilitado un periodo de observaciones para este proceso.
                            </div>
                        </template>

                        <!-- Si hay ventana definida pero no está activa -->
                        <template x-if="det.ventana_definida && !det.ventana_abierta">
                            <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                                Ventana no activa.
                                <template x-if="det.ventana_abre_fmt && det.ventana_cierra_fmt">
                                    <span> Disponible del <strong x-text="det.ventana_abre_fmt"></strong> al
                                        <strong x-text="det.ventana_cierra_fmt"></strong>.</span>
                                </template>
                            </div>
                        </template>
                    </div>



                    <!-- Acciones -->
                    <!-- Acciones -->
                    <div class="mt-6 flex flex-wrap gap-3 items-center">
                        {{-- CREADO → puede postularse --}}
                        <template x-if="!det.ya && det.estado === 'CREADO'">
                            <form :action="det.postular_url" method="POST" class="inline"
                                @submit="$event.target.querySelector('button[type=submit]').disabled = true">
                                @csrf
                                <input type="hidden" name="redirect_to" :value="det.archivos_url">
                                <button type="submit" class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">
                                    Interesado
                                </button>
                            </form>
                        </template>



                        {{-- VIGENTE → sin postulaciones ni carga --}}
                        <template x-if="det.estado === 'VIGENTE'">
                            <span class="px-3 py-1 rounded bg-amber-100 text-amber-800 text-sm">
                                Etapa de selección — no se reciben postulaciones ni carga de documentos
                            </span>
                        </template>

                        {{-- CERRADO → cerrado --}}
                        <template x-if="det.estado === 'CERRADO'">
                            <span class="px-3 py-1 rounded bg-gray-200 text-gray-700 text-sm">
                                Proceso cerrado
                            </span>
                        </template>

                        <button @click="showDetalle=false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                            Cerrar
                        </button>
                    </div>

                </div>
            </div>

        </div>
        <!-- Modal Subir Documentos -->
        <div x-show="showArchivosModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-5" @click.outside="showArchivosModal=false">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Subir documentos</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="showArchivosModal=false">✕</button>
                </div>

                <p class="text-sm text-gray-600 mb-4">
                    Proceso <span class="font-medium" x-text="arch.codigo"></span>.
                    Adjunta los documentos requeridos para tu postulación.
                </p>

                <!-- Si YA hay postulación: ir directo al form de archivos -->
                <template x-if="arch.ya">
                    <div class="space-y-3">
                        <a :href="arch.archivos_url"
                            class="w-full inline-flex justify-center px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white">
                            Ir a mis archivos
                        </a>
                        <p class="text-xs text-gray-500">
                            Ya estás postulado. Allí podrás adjuntar o eliminar documentos.
                        </p>
                    </div>
                </template>

                <!-- Si NO hay postulación: crearla y redirigir al form -->
                <template x-if="!arch.ya">
                    <form :action="arch.postular_url" method="POST" class="space-y-3"
                        @submit="$event.target.querySelector('button[type=submit]').disabled = true">
                        @csrf
                        <input type="hidden" name="redirect_to" :value="arch.archivos_url">
                        <button type="submit"
                            class="w-full inline-flex justify-center px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">
                            Me interesa y subir documentos
                        </button>
                        <p class="text-xs text-gray-500">
                            Primero registraremos tu interés y luego te llevaremos al formulario para adjuntar documentos.
                        </p>
                    </form>
                </template>
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
