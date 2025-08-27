@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

    <!-- Título -->
    <h1 class="text-2xl font-bold text-gray-800 mb-4 mt-2">
        Nueva observación – <span class="text-indigo-600">{{ $proceso->codigo }}</span>
    </h1>

    <!-- Mensajes -->
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

    <!-- Información del proceso -->
    <div class="mb-6 p-4 border border-gray-200 rounded-2xl bg-white shadow-sm">
        <div class="text-sm text-gray-700">
            <div><span class="text-gray-500">Objeto:</span> {{ $proceso->objeto }}</div>
            @if ($proceso->observaciones_abren_en && $proceso->observaciones_cierran_en)
                <div class="mt-1">
                    <span class="text-gray-500">Ventana:</span>
                    {{ $proceso->observaciones_abren_en->format('d/m/Y H:i') }} —
                    {{ $proceso->observaciones_cierran_en->format('d/m/Y H:i') }}

                    @if (!$ventanaAbierta)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            Cerrada
                        </span>
                    @else
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Abierta
                        </span>
                    @endif
                </div>
            @else
                <div class="mt-1 text-gray-500">
                    Ventana sin configurar (se permite por defecto a menos que cambies la lógica).
                </div>
            @endif
        </div>
    </div>

    <!-- Formulario -->
    <form method="POST" action="{{ route('procesos.observaciones.store', $proceso) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Asunto -->
        <div>
            <label class="block text-sm font-medium mb-1">Asunto</label>
            <input type="text" name="asunto" value="{{ old('asunto') }}"
                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2"
                   required maxlength="180">
        </div>

        <!-- Descripción -->
        <div>
            <label class="block text-sm font-medium mb-1">Descripción (opcional)</label>
            <textarea name="descripcion" rows="4"
                      class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-3 py-2"
                      placeholder="Describe brevemente...">{{ old('descripcion') }}</textarea>
        </div>

        <!-- Archivos -->
        <div>
            <label class="block text-sm font-medium mb-1">Archivos (múltiples)</label>
            <input id="inputArchivos" type="file" name="archivos[]" multiple
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                   class="w-full border rounded-xl shadow-sm px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">

            <p class="text-xs text-gray-500 mt-1">PDF, DOC(X), XLS(X), PNG, JPG. Máx 20MB por archivo.</p>

            <!-- Lista archivos -->
            <ul id="listaArchivos" class="mt-3 text-sm text-gray-700 space-y-2"></ul>
        </div>

        <!-- Script lista archivos (solo diseño visual, no lógica extra) -->
        <script>
            document.getElementById('inputArchivos').addEventListener('change', function(e) {
                const lista = document.getElementById('listaArchivos');
                lista.innerHTML = "";

                Array.from(e.target.files).forEach((file, index) => {
                    const li = document.createElement('li');
                    li.classList.add("flex", "items-center", "justify-between", "bg-white", "border", "rounded-lg", "px-3", "py-2", "shadow-sm");

                    li.innerHTML = `
                        <span class="truncate">${file.name}
                            <span class="ml-2 text-xs text-gray-400">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                        </span>
                        <button type="button" class="text-red-500 hover:text-red-700 text-sm" data-index="${index}">✕</button>
                    `;
                    lista.appendChild(li);
                });

                lista.querySelectorAll("button").forEach(btn => {
                    btn.addEventListener("click", function() {
                        this.parentElement.remove();
                    });
                });
            });
        </script>

        <!-- Botón submit -->
        @php $deshabilitar = (!$ventanaAbierta && !(auth()->user()->isAdmin() ?? false)); @endphp
        <div class="pt-4">
            <button type="submit"
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-white font-medium shadow
                           {{ $deshabilitar ? 'bg-gray-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300' }}"
                    {{ $deshabilitar ? 'disabled' : '' }}>
                Enviar observación
            </button>
        </div>
    </form>
</div>
@endsection
