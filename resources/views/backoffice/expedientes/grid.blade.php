@extends('layouts.app')

@section('content')
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>

    <div class="max-w-7xl mx-auto p-6" x-data>
        <div class="mb-6 mt-10">
            <h1 class="text-2xl font-bold text-white">Postulaciones Proponentes</h1>
        </div>

        <div class="bg-white border rounded-lg p-4">
            <table id="expedientesTable" class="min-w-full text-sm display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Proponente</th>
                        <th>Contacto</th>
                        <th>Proceso</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Ver</th>
                    </tr>
                </thead>
            </table>
        </div>

        {{-- Modal Alpine (global) --}}
        <div x-data="docsModal()" x-init="window.docsModalRef = this"
            x-on:open-docs.window="load($event.detail.url, $event.detail.title)" class="mt-6">
            <!-- Modal -->
            <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-xl w-[96vw] max-w-4xl max-h-[80vh] p-5 overflow-y-auto">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold" x-text="title || 'Documentos'"></h3>
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
                                            <td class="px-3 py-2 truncate max-w-[300px]" :title="f.name"
                                                x-text="f.name"></td>
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
                        <button @click="open=false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection




@section('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
    {{-- Alpine.js --}}
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function docsModal() {
            return {
                open: false,
                loading: false,
                error: null,
                items: [],
                title: '',
                async load(url, titulo = '') {
                    this.open = true;
                    this.loading = true;
                    this.error = null;
                    this.items = [];
                    this.title = titulo;
                    try {
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw new Error('Error ' + res.status);
                        const data = await res.json();
                        this.items = data.items || [];
                    } catch (e) {
                        this.error = e.message || 'No se pudo cargar.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        // Helper robusto para abrir el modal, sin depender de tiempos
        function openDocsModal(url, title) {
            // 1) Intenta via window event (no depende de la global)
            window.dispatchEvent(new CustomEvent('open-docs', {
                detail: {
                    url,
                    title
                }
            }));
            // 2) Fallback inmediato a la global si ya existe
            if (window.docsModalRef && typeof window.docsModalRef.load === 'function') {
                window.docsModalRef.load(url, title);
                return;
            }
            // 3) Retry ultra corto por si Alpine termina de inicializar
            setTimeout(() => {
                if (window.docsModalRef && typeof window.docsModalRef.load === 'function') {
                    window.docsModalRef.load(url, title);
                }
            }, 100);
        }

        $(function() {
            const table = $('#expedientesTable').DataTable({
                ajax: {
                    url: '{{ route('bo.expedientes.data') }}',
                    dataSrc: 'data',
                    error: function(xhr) {
                        console.error('DT AJAX error:', xhr.status, xhr.responseText);
                        alert('Error cargando la tabla (' + xhr.status + '). Revisa la consola.');
                    }
                },
                responsive: true,
                processing: true,
                deferRender: true,
                pageLength: 25,
                columns: [{
                        data: 'proponente'
                    },
                    {
                        data: 'contacto'
                    },
                    {
                        data: 'proceso',
                        width: 100
                    },
                    {
                        data: 'fecha',
                        width: 130
                    },
                    {
                        data: 'estado',
                        orderable: false,
                        searchable: false,
                        width: 100
                    },
                    {
                        data: 'acciones',
                        orderable: false,
                        searchable: false,
                        width: 150
                    },
                ],
                createdRow: function(row, data) {
                    $('td', row).eq(4).html(data.estado);
                    $('td', row).eq(5).html(data.acciones);
                },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.7/i18n/es-ES.json'
                }
            });

            // Click: dispara el modal
            $('#expedientesTable').on('click', '.btn-docs', function() {
                const proponenteId = this.dataset.proponente;
                const procesoCod = this.dataset.proceso;
                const nombre = this.dataset.nombre || 'Proponente';

                const url = `{{ route('bo.expedientes.docs', ['proponente' => 'PROPID']) }}`
                    .replace('PROPID', proponenteId) +
                    `?proceso=${encodeURIComponent(procesoCod)}`;

                const titulo = `Documentos — ${nombre} (Proceso ${procesoCod})`;

                openDocsModal(url, titulo);
            });
        });
    </script>
@endsection
