@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Parámetros de contratación</h1>

    @if (session('ok'))
        <div class="mb-3 rounded border border-green-200 bg-green-50 px-3 py-2 text-green-800">
            {{ session('ok') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-red-800">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ tab: '{{ $tab }}', setTab(t){ this.tab=t; const url=new URL(window.location); url.searchParams.set('tab', t); history.replaceState({},'',url); } }">
        {{-- Pestañas --}}
        <div class="flex gap-2 mb-4">
            <button @click="setTab('estado')"
                :class="tab==='estado' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                class="px-3 py-1.5 rounded border shadow">
                Estados de contrato ({{ $estados->count() }})
            </button>

            <button @click="setTab('tipo_contrato')"
                :class="tab==='tipo_contrato' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                class="px-3 py-1.5 rounded border shadow">
                Tipos de contrato ({{ $tiposContrato->count() }})
            </button>

            <button @click="setTab('tipo_proceso')"
                :class="tab==='tipo_proceso' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                class="px-3 py-1.5 rounded border shadow">
                Tipos de proceso ({{ $tiposProceso->count() }})
            </button>
        </div>

        {{-- Bloque Crear --}}
        <div class="bg-white rounded-xl border shadow p-4 mb-6">
            <h2 class="font-semibold mb-3">Agregar nuevo</h2>

            {{-- Form crear (un solo form, cambia "entidad") --}}
            <form method="POST" action="{{ route('parametros.store') }}" class="grid sm:grid-cols-3 gap-3">
                @csrf
                <input type="hidden" name="entidad" x-bind:value="tab">

                <div>
                    <label class="block text-sm text-gray-700">Código</label>
                    <input name="codigo" required class="w-full border rounded px-3 py-2"
                           placeholder="Ej: ACT, OBRA, SEL"
                           oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-700">Nombre</label>
                    <input name="nombre" required class="w-full border rounded px-3 py-2"
                           placeholder="Nombre descriptivo">
                </div>

                <div class="sm:col-span-3">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>

        {{-- Listados --}}
        {{-- Estados --}}
        <section x-show="tab==='estado'" x-cloak>
            <x-listado-parametro titulo="Estados de contrato" :items="$estados" entidad="estado" />
        </section>

        {{-- Tipos de contrato --}}
        <section x-show="tab==='tipo_contrato'" x-cloak>
            <x-listado-parametro titulo="Tipos de contrato" :items="$tiposContrato" entidad="tipo_contrato" />
        </section>

        {{-- Tipos de proceso --}}
        <section x-show="tab==='tipo_proceso'" x-cloak>
            <x-listado-parametro titulo="Tipos de proceso" :items="$tiposProceso" entidad="tipo_proceso" />
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Confirm simple para eliminar (sin SweetAlert)
    function confirmarEliminar(e, nombre) {
        if(!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)){
            e.preventDefault();
            e.stopPropagation();
        }
    }
</script>
@endpush
