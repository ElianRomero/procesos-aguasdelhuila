@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto mt-10 px-4">
        <div class="flex items-center justify-between mb-6 pt-2">
            <h1 class="text-2xl font-bold"></h1>
            <button type="button" onclick="history.back()" class="text-sm px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                ← Volver
            </button>

        </div>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <div class="text-gray-500 text-xs">Nombre o Razón Social</div>
                    <div class="text-lg font-semibold">{{ $proponente->razon_social ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">NIT</div>
                    <div class="text-lg">{{ $proponente->nit ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Tipo identificación</div>
                    <div class="text-lg">
                        {{ optional($proponente->tipoIdentificacion)->nombre ?? '—' }}
                        <span class="text-gray-400">({{ $proponente->tipo_identificacion_codigo ?? '—' }})</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos principales --}}
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white rounded-2xl shadow p-6">
                    <div class="text-sm text-gray-500 mb-2">Representante</div>
                    <div class="font-medium">{{ $proponente->representante ?? '—' }}</div>

                    <div class="grid md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <div class="text-sm text-gray-500">Teléfono(s)</div>
                            <div>{{ $proponente->telefono1 ?? '—' }}
                                {{ $proponente->telefono2 ? ' / ' . $proponente->telefono2 : '' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Correo</div>
                            <div>{{ $proponente->correo ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Dirección</div>
                            <div>{{ $proponente->direccion ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Sitio web</div>
                            @if ($proponente->sitio_web)
                                <a href="{{ $proponente->sitio_web }}" target="_blank"
                                    class="text-blue-600 hover:underline">
                                    Link
                                </a>
                            @else
                                <div>—</div>
                            @endif
                        </div>

                    </div>

                    <div class="grid md:grid-cols-3 gap-4 mt-6">
                        <div>
                            <div class="text-sm text-gray-500">Ciudad</div>
                            <div>{{ optional($proponente->ciudad)->nombre ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">CIIU</div>
                            <div>{{ optional($proponente->ciiu)->nombre ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Inicio de actividad</div>
                            <div>{{ $proponente->actividad_inicio ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4 mt-6">
                        @if ($proponente->observacion)
                            <div class="mt-6">
                                <div class="text-sm text-gray-500 mb-1">Observación</div>
                                <div class="whitespace-pre-line">{{ $proponente->observacion }}</div>
                            </div>
                        @endif
                        @php $procesoCodigo = request('proceso'); @endphp

                        <div x-data="docsModal()" class="mt-6">
                            <div class="text-sm text-gray-500">Documentos del proceso</div>

                            <button type="button"
                                @click="load('{{ route('proponentes.documentos', $proponente) }}?proceso={{ $procesoCodigo }}')"
                                class="text-blue-600 hover:underline">
                                Ver documentos
                            </button>

                         
                            <!-- Modal -->
                            <div x-show="open" x-cloak
                                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                                <div
                                    class="bg-white rounded-xl shadow-xl w-[96vw] max-w-4xl max-h-[80vh] p-5 overflow-y-auto">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-semibold">
                                            Documentos — Proceso {{ $procesoCodigo ?: 'N/A' }}
                                        </h3>
                                        <button @click="open=false" class="text-gray-500 hover:text-gray-700">✕</button>
                                    </div>

                                    <template x-if="loading">
                                        <div class="p-4 text-sm text-gray-500">Cargando...</div>
                                    </template>
                                    <template x-if="error">
                                        <div class="p-4 text-sm text-red-600" x-text="error"></div>
                                    </template>
                                    <template x-if="!loading && !error && items.length === 0">
                                        <div class="p-4 text-sm text-gray-500">No hay documentos para este proceso.</div>
                                    </template>

                                    <template x-if="!loading && !error && items.length">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead>
                                                    <tr class="bg-gray-50 text-left">
                                                        <th class="px-3 py-2">Requisito</th>
                                                        <th class="px-3 py-2">Archivo</th>
                                                        <th class="px-3 py-2">Fecha</th>
                                                        <th class="px-3 py-2">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="f in items" :key="f.id">
                                                        <tr class="border-t">
                                                            <td class="px-3 py-2" x-text="f.req || '—'"></td>
                                                            <td class="px-3 py-2 truncate max-w-[300px]"
                                                                :title="f.name" x-text="f.name"></td>
                                                            <td class="px-3 py-2" x-text="f.fecha"></td>
                                                            <td class="px-3 py-2">
                                                                <template x-if="f.url">
                                                                    <a :href="f.url" target="_blank"
                                                                        class="px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 text-xs">
                                                                        Ver
                                                                    </a>
                                                                </template>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>

                                    <div class="text-right mt-4">
                                        <button @click="open=false"
                                            class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            function docsModal() {
                                return {
                                    open: false,
                                    loading: false,
                                    error: null,
                                    items: [],
                                    async load(url) {
                                        this.open = true;
                                        this.loading = true;
                                        this.error = null;
                                        this.items = [];
                                        try {
                                            const res = await fetch(url, {
                                                headers: {
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            });
                                            if (!res.ok) throw new Error('Error ' + res.status);
                                            const data = await res.json();
                                            // cuando viene ?proceso=... el controlador devuelve { items: [...] }
                                            this.items = data.items || [];
                                        } catch (e) {
                                            this.error = e.message || 'No se pudo cargar.';
                                        } finally {
                                            this.loading = false;
                                        }
                                    }
                                }
                            }
                        </script>

                    </div>

                </div>



            </div>

            {{-- Sidebar: resumen / asignaciones --}}
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-3">Resumen</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex justify-between"><span>Total postulaciones</span><span
                                class="font-semibold">{{ $estadisticas['total'] }}</span></li>
                        <li class="flex justify-between"><span>Aceptadas</span><span
                                class="font-semibold">{{ $estadisticas['aceptadas'] }}</span></li>
                        <li class="flex justify-between"><span>Rechazadas</span><span
                                class="font-semibold">{{ $estadisticas['rechazadas'] }}</span></li>
                        <li class="flex justify-between"><span>Enviadas</span><span
                                class="font-semibold">{{ $estadisticas['enviadas'] }}</span></li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-3">Procesos asignados</h3>
                    @if ($proponente->procesosAsignados->isEmpty())
                        <div class="text-sm text-gray-500">Sin asignaciones.</div>
                    @else
                        <div class="space-y-3">
                            @foreach ($proponente->procesosAsignados as $pa)
                                <div class="border rounded-lg p-3">
                                    <div class="text-sm font-medium">#{{ $pa->codigo }}</div>
                                    <div class="text-xs text-gray-600 line-clamp-2">{{ $pa->objeto }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow p-6 mt-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Postulaciones</h2>
                <div class="text-sm text-gray-600">
                    Total: <span class="font-semibold">{{ $estadisticas['total'] }}</span> ·
                    Enviadas: <span class="font-semibold">{{ $estadisticas['enviadas'] }}</span> ·
                    Aceptadas: <span class="font-semibold">{{ $estadisticas['aceptadas'] }}</span> ·
                    Rechazadas: <span class="font-semibold">{{ $estadisticas['rechazadas'] }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Proceso</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Objeto</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proponente->procesosPostulados as $proc)
                            @php
                                $estado = strtoupper($proc->pivot->estado ?? 'ENVIADA');
                                $color =
                                    [
                                        'ENVIADA' => 'bg-blue-100 text-blue-700',
                                        'ACEPTADA' => 'bg-green-100 text-green-700',
                                        'RECHAZADA' => 'bg-red-100 text-red-700',
                                    ][$estado] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <tr class="border-t">
                                <td class="px-4 py-2 align-top">
                                    <div class="font-medium">#{{ $proc->codigo }}</div>
                                    <div class="text-xs text-gray-500">{{ $proc->tipo_proceso_codigo }}</div>
                                </td>
                                <td class="px-4 py-2 align-top">
                                    <div class="text-sm">{{ $proc->objeto }}</div>
                                </td>
                                <td class="px-4 py-2 align-top">
                                    <span
                                        class="px-2 py-1 text-xs rounded {{ $color }}">{{ $estado }}</span>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin postulaciones.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
