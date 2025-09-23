@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        {{-- Encabezado + botón Desinteresado --}}
        <div class="flex items-center justify-between mb-3 mt-16">
            <h1 class="text-2xl font-bold">Subir documentos</h1>
            @php
                $proponenteId = optional(auth()->user()->proponente)->id;
            @endphp
            @if ($proponenteId)
                <form id="form-desinteresado" action="{{ route('postulaciones.destroy', [$proceso->codigo, $proponenteId]) }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 rounded bg-gray-700 text-white hover:bg-gray-900">
                        Desinteresado
                    </button>
                </form>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <p class="text-gray-600 mb-6">
                Proceso <span class="font-semibold">{{ $proceso->codigo }}</span> — {{ $proceso->objeto }}
            </p>

            @if (session('success'))
                <div class="mb-4 rounded bg-green-50 text-green-800 px-3 py-2">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded bg-red-50 text-red-700 px-3 py-2">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                // ¿Todos los requisitos tienen archivo?
                $totalReq = count($requisitos ?? []);
                $faltantes = collect($requisitos ?? [])->reject(fn($r) => isset($subidos[$r['key'] ?? '']));
                $completo = $totalReq > 0 && $faltantes->isEmpty();
            @endphp

            @if (empty($requisitos))
                <div class="rounded border p-4 bg-gray-50 text-gray-700 mb-6">
                    Este proceso no tiene requisitos configurados.
                </div>
            @endif

            {{-- FORM de Guardar/Enviar documentos --}}
            <form action="{{ route('postulaciones.archivos.store', $proceso->codigo) }}" method="POST"
                enctype="multipart/form-data" class="space-y-4">
                @csrf

                @foreach ($requisitos ?? [] as $r)
                    @php
                        $k = $r['key'];
                        $ya = $subidos[$k] ?? null;
                    @endphp

                    <div class="border rounded-lg p-4">
                        <label class="block font-medium mb-1">
                            {{ $r['name'] }} <span class="text-xs text-gray-500">(PDF)</span>
                        </label>

                        @if (!$ya)
                            <input type="file" name="files[{{ $k }}]" accept="application/pdf"
                                id="file-{{ $k }}" class="block w-full border rounded px-3 py-2">
                            @error("files.$k")
                                <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        @else
                            <div class="text-sm text-gray-600">
                                Ya subido: {{ $ya->original_name }}
                                ({{ number_format(($ya->size_bytes ?? 0) / 1024, 0) }} KB)
                                —
                                <a class="underline text-blue-600"
                                    href="{{ route('postulaciones.archivos.show', [$proceso->codigo, $k]) }}"
                                    target="_blank">ver</a>
                                <button type="button" class="ml-2 text-blue-600 hover:underline btn-reemplazar"
                                    data-target="file-{{ $k }}">
                                    Reemplazar
                                </button>
                            </div>

                            <div id="wrap-file-{{ $k }}" class="hidden mt-3">
                                <input type="file" name="files[{{ $k }}]" accept="application/pdf"
                                    id="file-{{ $k }}" class="block w-full border rounded px-3 py-2" disabled>
                                @error("files.$k")
                                    <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                                @enderror

                                <div class="mt-2">
                                    <button type="button" class="text-gray-600 hover:underline btn-cancelar"
                                        data-target="file-{{ $k }}">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach



                <div class="flex items-center gap-2 pt-2">
                    <button type="button"
                        onclick="if (history.length > 1) history.back(); else window.location='{{ route('postulaciones.index') }}';"
                        class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                        Volver
                    </button>

                    <button id="btn-enviar" type="submit" data-completo="{{ $completo ? '1' : '0' }}"
                        @if ($completo) disabled @endif
                        class="px-4 py-2 rounded text-white {{ $completo ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }}">
                        Enviar Documentos
                    </button>

                </div>

                @if ($completo)
                    <div class="mt-2 text-sm text-green-700">
                        ✅ Ya cargaste todos los documentos requeridos para este proceso.
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (() => {
            // Confirmación para "Desinteresado"
            const form = document.getElementById('form-desinteresado');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Marcar como desinteresado?',
                        text: 'Se retirará tu postulación a este proceso.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, retirar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                    }).then((res) => {
                        if (res.isConfirmed) form.submit();
                    });
                });
            }

            // Toast de éxito al guardar (lee flash)
            @if (session('toast_success') || session('success'))
                Swal.fire({
                    toast: true,
                    icon: 'success',
                    title: @json(session('toast_success') ?? session('success')),
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true,
                });
            @endif

            // Evita doble submit y muestra "Enviando..."
            const subirForm = document.querySelector(
                'form[action="{{ route('postulaciones.archivos.store', $proceso->codigo) }}"]');
            if (subirForm) {
                subirForm.addEventListener('submit', () => {
                    const btn = document.getElementById('btn-enviar');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                        btn.classList.add('bg-gray-400', 'cursor-wait');
                        btn.textContent = 'Enviando...';
                    }
                });
            }
        })();
    </script>
    <script>
        (function() {
            // Mostrar el input oculto para reemplazar
            document.addEventListener('click', function(e) {
                const rep = e.target.closest('.btn-reemplazar');
                if (rep) {
                    const id = rep.dataset.target; // ej. file-LLAVE
                    const wrapper = document.getElementById('wrap-' + id); // wrap-file-LLAVE
                    const input = document.getElementById(id);

                    if (wrapper && input) {
                        wrapper.classList.remove('hidden');
                        input.disabled = false;
                        input.focus();
                    }
                }

                const cancel = e.target.closest('.btn-cancelar');
                if (cancel) {
                    const id = cancel.dataset.target; // ej. file-LLAVE
                    const wrapper = document.getElementById('wrap-' + id);
                    const input = document.getElementById(id);

                    if (wrapper && input) {
                        // Limpia y oculta
                        input.value = '';
                        input.disabled = true;
                        wrapper.classList.add('hidden');
                    }
                }
            });
        })();
    </script>
    <script>
        (function() {
            const submitBtn = document.getElementById('btn-enviar');
            if (!submitBtn) return;

            const initiallyComplete = submitBtn.dataset.completo === '1';

            function anyActiveSelection() {
                // Algún input file visible y habilitado con archivo seleccionado
                return Array.from(document.querySelectorAll('input[type="file"][name^="files["]'))
                    .some(inp => !inp.disabled && inp.value);
            }

            function anyReplacementOpen() {
                // Algún wrapper de reemplazo visible
                return Array.from(document.querySelectorAll('[id^="wrap-file-"]'))
                    .some(wrap => !wrap.classList.contains('hidden'));
            }

            function enableSubmit() {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                // El texto lo dejamos "Enviar Documentos"
            }

            function disableSubmit() {
                submitBtn.disabled = true;
                submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }

            function reevaluateButton() {
                // Si al inicio estaba completo, solo deshabilita si NO hay cambios pendientes
                if (initiallyComplete) {
                    if (anyActiveSelection() || anyReplacementOpen()) {
                        enableSubmit();
                    } else {
                        disableSubmit();
                    }
                } else {
                    // Si NO estaba completo inicialmente, lo dejamos habilitado por defecto
                    // (el propio submit-handler lo pondrá "Enviando..." y bloqueará)
                }
            }

            // Abrir reemplazo
            document.addEventListener('click', function(e) {
                const rep = e.target.closest('.btn-reemplazar');
                if (rep) {
                    const id = rep.dataset.target; // ej. file-KEY
                    const wrapper = document.getElementById('wrap-' + id); // wrap-file-KEY
                    const input = document.getElementById(id);
                    if (wrapper && input) {
                        wrapper.classList.remove('hidden');
                        input.disabled = false;
                        input.focus();
                        enableSubmit(); // <- habilita al instante
                    }
                }

                // Cancelar reemplazo
                const cancel = e.target.closest('.btn-cancelar');
                if (cancel) {
                    const id = cancel.dataset.target;
                    const wrapper = document.getElementById('wrap-' + id);
                    const input = document.getElementById(id);
                    if (wrapper && input) {
                        input.value = '';
                        input.disabled = true;
                        wrapper.classList.add('hidden');
                        reevaluateButton(); // <- quizá volver a deshabilitar si ya no hay cambios
                    }
                }
            });

            // Al seleccionar/quitar un archivo, re-evaluar
            document.addEventListener('change', function(e) {
                if (e.target.matches('input[type="file"][name^="files["]')) {
                    if (e.target.value) enableSubmit();
                    else reevaluateButton();
                }
            });

            // Si por algo el form recarga con errores, re-evalúa el estado inicial
            document.addEventListener('DOMContentLoaded', reevaluateButton);
        })();
    </script>
@endsection
