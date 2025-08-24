@props([
    'titulo' => 'Listado',
    'items' => collect(),
    'entidad' => 'estado', // estado|tipo_contrato|tipo_proceso
])

<div class="bg-white rounded-xl border shadow p-4">
    <h3 class="font-semibold mb-3">{{ $titulo }}</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full border divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-700">
                    <th class="px-3 py-2 w-28">Código</th>
                    <th class="px-3 py-2">Nombre</th>
                    <th class="px-3 py-2 w-40">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $it)
                    <tr x-data="{ editing: false }" class="hover:bg-gray-50">
                        {{-- Vista --}}
                        <td class="px-3 py-2" x-show="!editing">{{ $it->codigo }}</td>
                        <td class="px-3 py-2" x-show="!editing">{{ $it->nombre }}</td>
                        <td class="px-3 py-2" x-show="!editing">
                            <div class="flex items-center gap-2">
                                <button @click="editing=true"
                                    class="px-3 py-1 rounded bg-gray-500 text-white hover:bg-blue-600">
                                    Editar
                                </button>

                                <form action="{{ route('parametros.destroy', [$entidad, $it->id]) }}" method="POST"
                                    onsubmit="confirmarEliminar(event, '{{ $it->nombre }}')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>


                        {{-- Edición inline --}}
                        <td class="px-3 py-2" x-show="editing" colspan="3">
                            <form class="grid sm:grid-cols-3 gap-3 items-end"
                                action="{{ route('parametros.update', [$entidad, $it->id]) }}" method="POST">
                                @csrf @method('PUT')

                                <div>
                                    <label class="block text-xs text-gray-600">Código</label>
                                    <input name="codigo" value="{{ $it->codigo }}"
                                        class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                                    {{-- 🔒 Bloqueado SIEMPRE --}}
                                    <p class="text-[11px] text-gray-500 mt-1">El código no se puede modificar.</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-gray-600">Nombre</label>
                                    <input name="nombre" value="{{ $it->nombre }}"
                                        class="w-full border rounded px-3 py-2" required>
                                </div>

                                <div class="sm:col-span-3 flex gap-2">
                                    <button
                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar</button>
                                    <button type="button" @click="editing=false"
                                        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</button>
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
</div>
