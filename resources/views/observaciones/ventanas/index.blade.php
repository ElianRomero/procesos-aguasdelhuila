@extends('layouts.app') {{-- o el layout que uses --}}

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-xl font-semibold mb-4">Ventanas de observaciones</h1>

    {{-- Flash de OK --}}
    @if (session('ok'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-800 px-4 py-2 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Errores generales --}}
    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-800 px-4 py-2 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Buscador --}}
    <form method="GET" action="{{ route('observaciones.ventanas.index') }}" class="mb-5 flex gap-2">
        <input type="text" name="q" value="{{ $q }}"
               placeholder="Buscar proceso por código u objeto..."
               class="w-full border rounded px-3 py-2">
        <button class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Buscar</button>
        @if($q !== '')
            <a href="{{ route('observaciones.ventanas.index') }}" class="px-4 py-2 rounded border">Limpiar</a>
        @endif
    </form>

    {{-- Tabla --}}
    <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full divide-y">
            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
                <tr>
                    <th class="px-4 py-2">Código</th>
                    <th class="px-4 py-2">Objeto</th>
                    <th class="px-4 py-2">Ventana actual</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2">Configurar</th>
                </tr>
            </thead>
            <tbody class="divide-y text-sm">
                @forelse ($procesos as $p)
                    @php
                        $abre = $p->observaciones_abren_en ? $p->observaciones_abren_en->format('Y-m-d\TH:i') : '';
                        $cierra = $p->observaciones_cierran_en ? $p->observaciones_cierran_en->format('Y-m-d\TH:i') : '';
                        $nowOpen = $p->ventanaObservacionesAbierta();
                    @endphp
                    <tr>
                        <td class="px-4 py-2 align-top">
                            <div class="font-medium">{{ $p->codigo }}</div>
                            <div class="text-xs text-gray-500">{{ optional($p->fecha)->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-4 py-2 align-top">
                            <div class="line-clamp-3 max-w-[28rem]">{{ $p->objeto }}</div>
                        </td>
                        <td class="px-4 py-2 align-top">
                            @if($p->observaciones_abren_en && $p->observaciones_cierran_en)
                                <div>
                                    <div><span class="text-gray-500">Abre:</span> {{ $p->observaciones_abren_en->format('d/m/Y H:i') }}</div>
                                    <div><span class="text-gray-500">Cierra:</span> {{ $p->observaciones_cierran_en->format('d/m/Y H:i') }}</div>
                                </div>
                            @else
                                <span class="text-gray-400">Sin configurar</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 align-top">
                            @if($p->observaciones_abren_en && $p->observaciones_cierran_en)
                                @if($nowOpen)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Abierta</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Cerrada</span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No definida</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <form method="POST"
                                  action="{{ route('observaciones.ventanas.update', $p) }}"
                                  x-data="{ abre: '{{ $abre }}', cierra: '{{ $cierra }}' }"
                                  class="space-y-2">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-gray-600">Abre</label>
                                        <input type="datetime-local" name="abren_en"
                                               x-model="abre"
                                               class="w-full border rounded px-2 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-600">Cierra</label>
                                        <input type="datetime-local" name="cierran_en"
                                               x-model="cierra"
                                               :min="abre"
                                               class="w-full border rounded px-2 py-1.5 text-sm">
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button class="px-3 py-1.5 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">
                                        Guardar
                                    </button>

                                    <button name="limpiar" value="1" class="px-3 py-1.5 rounded border text-sm"
                                            onclick="return confirm('¿Quitar ventana para {{ $p->codigo }}?')">
                                        Quitar ventana
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-gray-500" colspan="5">
                            No hay procesos para mostrar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $procesos->links() }}
    </div>
</div>
@endsection
