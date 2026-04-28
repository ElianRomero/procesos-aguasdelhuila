@props([
    'titulo' => 'Listado',
    'items' => collect(),
    'entidad' => 'estado', // estado|tipo_contrato|tipo_proceso|ciiu
])

<div
    class="bg-white rounded-xl border shadow p-4"
    x-data="{
        q: '',
        match(codigo, nombre) {
            const s = (this.q || '').toLowerCase().trim();
            if (!s) return true;
            return String(codigo ?? '').toLowerCase().includes(s)
                || String(nombre ?? '').toLowerCase().includes(s);
        }
    }"
>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
        <h3 class="font-semibold">{{ $titulo }}</h3>

        {{-- ✅ Buscador --}}
        <div class="w-full sm:w-80">
            <input
                type="text"
                class="w-full border rounded px-3 py-2 text-sm"
                placeholder="Buscar por codigo o nombre..."
                x-model.debounce.250ms="q"
            >
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-700">
                    <th class="px-3 py-2 w-28">Codigo</th>
                    <th class="px-3 py-2">Nombre</th>
                    <th class="px-3 py-2 w-40">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($items as $it)
                    <tr
                        x-data="{ editing: false }"
                        class="hover:bg-gray-50"
                        x-show="match(@js($it->codigo), @js($it->nombre))"
                        x-cloak
                    >
                        {{-- Vista --}}
                        <td class="px-3 py-2" x-show="!editing">{{ $it->codigo }}</td>
                        <td class="px-3 py-2" x-show="!editing">{{ $it->nombre }}</td>
                        <td class="px-3 py-2" x-show="!editing">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="editing=true"
                                    class="px-3 py-1 rounded bg-gray-500 text-white hover:bg-blue-600"
                                >
                                    Editar
                                </button>

                                <form
                                    action="{{ route('parametros.destroy', [$entidad, $it->id]) }}"
                                    method="POST"
                                    onsubmit="confirmarEliminar(event, @js($it->nombre))"
                                >
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>

                        {{-- Edición inline --}}
                        <td class="px-3 py-2" x-show="editing" colspan="3">
                            <form
                                class="grid sm:grid-cols-3 gap-3 items-end"
                                action="{{ route('parametros.update', [$entidad, $it->id]) }}"
                                method="POST"
                            >
                                @csrf @method('PUT')

                                <div>
                                    <label class="block text-xs text-gray-600">Código</label>
                                    <input
                                        name="codigo"
                                        value="{{ $it->codigo }}"
                                        class="w-full border rounded px-3 py-2 bg-gray-100"
                                        readonly
                                    >
                                    <p class="text-[11px] text-gray-500 mt-1">El código no se puede modificar.</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-gray-600">Nombre</label>
                                    <input
                                        name="nombre"
                                        value="{{ $it->nombre }}"
                                        class="w-full border rounded px-3 py-2"
                                        required
                                    >
                                </div>

                                <div class="sm:col-span-3 flex gap-2">
                                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                        Guardar
                                    </button>
                                    <button
                                        type="button"
                                        @click="editing=false"
                                        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-3 py-6 text-center text-gray-500">Sin registros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tip opcional --}}
    <p class="mt-3 text-xs text-gray-500">
        Escribe parte del código o nombre para filtrar.
    </p>
</div>
