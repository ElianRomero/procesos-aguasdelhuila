@extends('layouts.app')

@section('content')
    @php
        $editando = isset($procesoEditar);
    @endphp

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="{ mostrarFormulario: {{ $editando ? 'true' : 'false' }} }">
        <div class="max-full mx-auto py-10">
            <h2 class="text-2xl font-bold mb-6 mt-5">Gestión de Procesos</h2>


            <div class="flex items-center gap-4 mb-6">
                <button @click="mostrarFormulario = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-800">
                    {{ $editando ? 'Editar Proceso' : 'Crear Nuevo Proceso' }}
                </button>

                @if ($editando)
                    <a href="{{ route('procesos.create') }}"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Cancelar edición
                    </a>
                @else
                    <template x-if="mostrarFormulario">
                        <button @click="mostrarFormulario = false"
                            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-800">
                            Ocultar Formulario
                        </button>
                    </template>
                @endif
            </div>


            {{-- Formulario --}}
            <div x-show="mostrarFormulario || {{ $editando ? 'true' : 'false' }}" x-cloak>
                @if (session('success'))
                    <div class="mb-4 text-green-600 font-semibold">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ $editando ? route('procesos.update', $procesoEditar->codigo) : route('procesos.store') }}"
                    method="POST" class="bg-white border rounded-xl shadow-xl p-6 mb-8">

                    @csrf
                    @if ($editando)
                        @method('PUT')
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block font-medium">Código</label>
                            <input type="text" name="codigo"
                                value="{{ old('codigo', $editando ? $procesoEditar->codigo : '') }}"
                                class="w-full border-gray-300 rounded {{ $editando ? 'bg-gray-100 text-gray-500' : '' }}"
                                {{ $editando ? 'readonly' : 'required' }}>
                        </div>


                        <div>
                            <label class="block font-medium">Tipo de Proceso</label>
                            <select name="tipo_proceso_codigo" class="w-full border-gray-300 rounded" required>
                                <option value="">Seleccione...</option>
                                @foreach ($tiposProceso as $tipo)
                                    <option value="{{ $tipo->codigo }}" @selected(old('tipo_proceso_codigo', $editando ? $procesoEditar->tipo_proceso_codigo : '') == $tipo->codigo)>
                                        {{ $tipo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium">Objeto</label>
                            <textarea name="objeto" class="w-full border-gray-300 rounded" required>{{ old('objeto', $editando ? $procesoEditar->objeto : '') }}</textarea>
                        </div>

                        <div>
                            <label class="block font-medium">Link SECOP</label>
                            <input type="url" name="link_secop"
                                value="{{ old('link_secop', $editando ? $procesoEditar->link_secop : '') }}"
                                class="w-full border-gray-300 rounded">
                        </div>


                        <div>
                            <label class="block font-medium">Valor</label>
                            <input type="text" name="valor" id="valor" inputmode="numeric" autocomplete="off"
                                class="w-full border-gray-300 rounded"
                                value="{{ old('valor', $editando ? number_format($procesoEditar->valor, 0, '', '.') : '') }}"
                                required>
                        </div>

                        <div>
                            <label class="block font-medium">Fecha</label>
                            <input type="text" id="fecha" name="fecha" class="w-full border-gray-300 rounded"
                                required>
                        </div>


                        <div>
                            <label class="block font-medium">Estado del Contrato</label>
                            <select name="estado_contrato_codigo" class="w-full border-gray-300 rounded" required>
                                <option value="">Seleccione...</option>
                                @foreach ($estadosContrato as $estado)
                                    <option value="{{ $estado->codigo }}" @selected(old('estado_contrato_codigo', $editando ? $procesoEditar->estado_contrato_codigo : '') == $estado->codigo)>
                                        {{ $estado->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium">Tipo de Contrato</label>
                            <select name="tipo_contrato_codigo" class="w-full border-gray-300 rounded" required>
                                <option value="">Seleccione...</option>
                                @foreach ($tiposContrato as $tipo)
                                    <option value="{{ $tipo->codigo }}" @selected(old('tipo_contrato_codigo', $editando ? $procesoEditar->tipo_contrato_codigo : '') == $tipo->codigo)>
                                        {{ $tipo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium">Modalidad</label>
                            <input type="text" name="modalidad_codigo"
                                value="{{ old('modalidad_codigo', $editando ? $procesoEditar->modalidad_codigo : '') }}"
                                class="w-full border-gray-300 rounded">
                        </div>
                        @if ($editando)
                            <div>
                                <label class="block font-medium">Estado del Proceso</label>
                                @php
                                    $estadoSel = old('estado', $procesoEditar->estado ?? 'CREADO');
                                @endphp
                                <select name="estado" class="w-full border-gray-300 rounded" required>
                                    <option value="CREADO" @selected($estadoSel === 'CREADO')>CREADO</option>
                                    <option value="VIGENTE" @selected($estadoSel === 'VIGENTE')>VIGENTE</option>
                                    <option value="CERRADO" @selected($estadoSel === 'CERRADO')>CERRADO</option>
                                </select>
                            </div>
                        @endif

                    </div>

                    <div class="mt-6">
                        @php
                            $btn = $editando ? 'bg-yellow-600 hover:bg-yellow-800' : 'bg-blue-600 hover:bg-blue-800';
                        @endphp
                        <button type="submit" class="{{ $btn }} text-white px-4 py-2 rounded">
                            {{ $editando ? 'Actualizar Proceso' : 'Guardar Proceso' }}
                        </button>

                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr class="text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                        <th class="px-4 py-2">Código</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Objeto</th>
                        <th class="px-4 py-2">Valor</th>
                        <th class="px-4 py-2">Link</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($procesos as $proceso)
                        <tr class="hover:bg-gray-50 text-sm">
                            <td class="px-4 py-2">{{ $proceso->codigo }}</td>
                            <td class="px-4 py-2">{{ $proceso->estado }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($proceso->fecha)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ Str::limit($proceso->objeto, 60) }}</td>
                            <td class="px-4 py-2">${{ number_format($proceso->valor, 0, ',', '.') }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ $proceso->link_secop }}" class="text-blue-600 underline"
                                    target="_blank">Ver</a>
                            </td>
                            <td class="px-4 py-2">
                                <a href="{{ route('procesos.create', ['editar' => $proceso->codigo]) }}"
                                    class="text-indigo-600 hover:underline">Editar</a>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/alpinejs" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('valor');

            const onlyDigits = (s) => s.replace(/\D/g, '');
            const withDots = (d) => d.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            function formatWithCaret(el) {
                // cuántos dígitos hay a la izquierda del caret
                const start = el.selectionStart ?? el.value.length;
                const leftDigits = el.value.slice(0, start).replace(/\D/g, '').length;

                const digits = onlyDigits(el.value);
                el.value = withDots(digits);

                // recolocar caret al mismo índice de dígitos
                let pos = 0,
                    seen = 0;
                while (pos < el.value.length && seen < leftDigits) {
                    if (/\d/.test(el.value[pos])) seen++;
                    pos++;
                }
                el.setSelectionRange(pos, pos);
            }

            // bloquear todo excepto números y teclas de navegación
            input.addEventListener('keydown', (e) => {
                const allowed = e.ctrlKey || e.metaKey || ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight',
                    'Home', 'End', 'Tab'
                ].includes(e.key);
                if (!allowed && !/^\d$/.test(e.key)) e.preventDefault();
            });

            // formatear en vivo
            input.addEventListener('input', () => formatWithCaret(input));

            // si viene con valor (editar), formatea al cargar
            if (input.value.trim() !== '') formatWithCaret(input);
        });
    </script>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Idioma español -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#fecha", {
                dateFormat: "Y-m-d", // formato que Laravel entiende
                defaultDate: "{{ old('fecha', $editando ? $procesoEditar->fecha : '') }}",
                locale: flatpickr.l10ns.es // 🔥 Forzar idioma español
            });
        });
    </script>
@endsection
