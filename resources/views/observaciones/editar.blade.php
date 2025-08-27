@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8 ">

        <!-- Header -->
        <h1 class="text-2xl font-bold text-gray-800 mb-2 mt-5">
            Editar observación – <span class="text-indigo-600">{{ $observacion->proceso_codigo }}</span>
        </h1>

        <!-- Flash messages -->
        @if (session('ok'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-700 px-4 py-3 text-sm shadow-sm">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm shadow-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Proceso info -->
        <div class="mb-6 p-4 border border-gray-200 rounded-2xl bg-white shadow-sm">
            <div class="text-sm text-gray-700">
                <div><span class="text-gray-500">Proceso:</span> <strong>{{ $observacion->proceso_codigo }}</strong></div>
                @if ($observacion->proceso?->objeto)
                    <div class="mt-1 text-gray-600">{{ $observacion->proceso->objeto }}</div>
                @endif
                @if ($observacion->proceso?->observaciones_abren_en && $observacion->proceso?->observaciones_cierran_en)
                    <div class="mt-2 text-xs text-gray-500">
                        Ventana: {{ $observacion->proceso->observaciones_abren_en->format('d/m/Y H:i') }} —
                        {{ $observacion->proceso->observaciones_cierran_en->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST" action="{{ route('observaciones.update', $observacion) }}" enctype="multipart/form-data"
            class="space-y-6">
            @csrf @method('PATCH')

            <!-- Asunto -->
            <div>
                <label class="block text-sm font-medium mb-1">Asunto</label>
                <input type="text" name="asunto" value="{{ old('asunto', $observacion->asunto) }}"
                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2"
                    required maxlength="180">
            </div>

            <!-- Descripción -->
            <div>
                <label class="block text-sm font-medium mb-1">Descripción (opcional)</label>
                <textarea name="descripcion" rows="4"
                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2"
                    placeholder="Describe brevemente...">{{ old('descripcion', $observacion->descripcion) }}</textarea>
            </div>

            <!-- Archivos actuales -->
            <div class="border border-gray-200 rounded-2xl p-4 bg-gray-50 shadow-sm">
                <div class="text-sm font-medium text-gray-700 mb-3">Archivos actuales</div>
                @if ($observacion->archivos->count())
                    <ul class="space-y-2 text-sm">
                        @foreach ($observacion->archivos as $f)
                            <li class="flex items-center justify-between bg-white px-3 py-2 rounded-lg shadow-sm border">
                                <div class="truncate">
                                    <a class="text-indigo-600 hover:underline font-medium"
                                        href="{{ route('observaciones.archivos.download', [$observacion->id, $f->id]) }}">
                                        {{ $f->original_name }}
                                    </a>
                                    <span class="ml-2 text-gray-400 text-xs">
                                        ({{ number_format(($f->size ?? 0) / 1024, 0) }} KB)
                                    </span>
                                </div>

                                {{-- ⚠️ YA NO HAY <form> AQUÍ DENTRO --}}
                                <button type="button"
                                    class="text-xs px-3 py-1 rounded-lg border border-red-300 text-red-600 hover:bg-red-50"
                                    data-action="{{ route('observaciones.archivos.destroy', [$observacion->id, $f->id]) }}"
                                    onclick="eliminarArchivo(this)">
                                    Eliminar
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-xs text-gray-500">No hay archivos aún.</div>
                @endif
            </div>


            <!-- Agregar archivos -->
            <div>
                <label class="block text-sm font-medium mb-1">Agregar nuevos archivos</label>
                <input type="file" name="archivos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                    class="w-full border rounded-xl shadow-sm px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <p class="text-xs text-gray-500 mt-1">PDF, DOC(X), XLS(X), PNG, JPG. Máx 20MB por archivo.</p>
            </div>

            <!-- Submit -->
            <div class="pt-4">
                <button type="submit"
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium shadow hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 transition">
                    Guardar cambios
                </button>
            </div>
        </form>
        <form id="del-file-form" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

    </div>
@endsection
@section('scripts')
    <script>
        function eliminarArchivo(btn) {
            if (!confirm('¿Eliminar este archivo?')) return;
            const form = document.getElementById('del-file-form');
            form.action = btn.getAttribute('data-action');
            form.submit();
        }
    </script>
@endsection
