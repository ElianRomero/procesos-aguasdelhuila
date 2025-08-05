@extends('layouts.app')

@section('content')
    <div class="mt-10">
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800">Completar Información del Proponente</h2>
        </x-slot>

        <form method="POST" action="{{ route('proponente.store') }}" class="max-w-4xl mx-auto py-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Razón social -->
                <div>
                    <label for="razon_social" class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                    <input id="razon_social" name="razon_social" required
                        value="{{ old('razon_social', $proponente->razon_social ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>

                <!-- NIT -->
                <div>
                    <label for="nit" class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                    <input id="nit" name="nit" required value="{{ old('nit', $proponente->nit ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>

                <!-- Representante -->
                <div>
                    <label for="representante" class="block text-sm font-medium text-gray-700 mb-1">Representante
                        Legal</label>
                    <input id="representante" name="representante" required
                        value="{{ old('representante', $proponente->representante ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>
                <div>
                    <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input id="direccion" name="direccion" type="text"
                        value="{{ old('direccion', $proponente->direccion ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>
                <!-- Departamento -->
                <div>
                    <label for="departamento_id" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                    <select id="departamento_id" name="departamento_id"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring focus:border-teal-500">
                        <option value="">Seleccione un departamento</option>
                        @foreach ($departamentos as $dep)
                            <option value="{{ $dep->id }}"
                                {{ old('departamento_id', $proponente->ciudad->departamento_id ?? '') == $dep->id ? 'selected' : '' }}>
                                {{ $dep->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <!-- Ciudad -->
                <div>
                    <label for="ciudad_id" class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <select name="ciudad_id" id="ciudad_id" required
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring focus:border-teal-500">
                        <option value="">Seleccione una ciudad</option>
                        @foreach ($departamentos as $dep)
                            @foreach ($dep->ciudades as $ciudad)
                                <option value="{{ $ciudad->id }}"
                                    {{ old('ciudad_id', $proponente->ciudad_id ?? '') == $ciudad->id ? 'selected' : '' }}>
                                    {{ $ciudad->nombre }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>


                <!-- CIIU -->
                <div>
                    <label for="ciiu_id" class="block text-sm font-medium text-gray-700 mb-1">Actividad Económica
                        (CIIU)</label>
                    <select name="ciiu_id" id="ciiu_id"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring focus:border-teal-500">
                        @foreach ($ciius as $ciiu)
                            <option value="{{ $ciiu->id }}"
                                {{ old('ciiu_id', $proponente->ciiu_id ?? '') == $ciiu->id ? 'selected' : '' }}>
                                {{ $ciiu->codigo }} - {{ $ciiu->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <!-- Tipo de identificación -->
                <div>
                    <label for="tipo_identificacion_codigo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de
                        Identificación</label>
                    <select name="tipo_identificacion_codigo" id="tipo_identificacion_codigo"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring focus:border-teal-500">
                        @foreach ($tiposIdentificacion as $tipo)
                            <option value="{{ $tipo->codigo }}"
                                {{ old('tipo_identificacion_codigo', $proponente->tipo_identificacion_codigo ?? '') == $tipo->codigo ? 'selected' : '' }}>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <!-- Teléfono 1 -->
                <div>
                    <label for="telefono1" class="block text-sm font-medium text-gray-700 mb-1">Teléfono 1</label>
                    <input id="telefono1" name="telefono1" type="tel" maxlength="15"
                        value="{{ old('telefono1', $proponente->telefono1 ?? '') }}"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,15);"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>


                <!-- Teléfono 2 -->
                <div>
                    <label for="telefono2" class="block text-sm font-medium text-gray-700 mb-1">Teléfono 2</label>
                    <input id="telefono2" name="telefono2" maxlength="15" type="tel"
                        value="{{ old('telefono2', $proponente->telefono2 ?? '') }}"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,15);"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>

                <!-- Correo -->
                <div>
                    <label for="correo" class="block text-sm font-medium text-gray-700 mb-1">Correo Alternativo</label>
                    <input id="correo" name="correo" type="email"
                        value="{{ old('correo', $proponente->correo ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>

                <!-- Sitio Web -->
                <div>
                    <label for="sitio_web" class="block text-sm font-medium text-gray-700 mb-1">Sitio Web</label>
                    <input id="sitio_web" name="sitio_web" type="url"
                        value="{{ old('sitio_web', $proponente->sitio_web ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm hover:border-gray-400 focus:outline-none focus:ring focus:border-teal-500" />
                </div>

                <!-- Fecha inicio -->
                <div>
                    <label for="actividad_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio de
                        Actividad</label>
                    <input id="actividad_inicio" name="actividad_inicio" type="date"
                        value="{{ old('actividad_inicio', $proponente->actividad_inicio ?? '') }}"
                        class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring focus:border-teal-500" />
                </div>
            </div>

            <!-- Observaciones (una sola columna) -->
            <div class="mt-6">
                <label for="observacion" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
              <textarea id="observacion" name="observacion" rows="4"
    class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 shadow-sm focus:outline-none focus:ring focus:border-teal-500">{{ old('observacion', $proponente->observacion ?? '') }}</textarea>

            </div>

            <!-- Botón -->
            <div class="mt-6">
                <x-primary-button>Guardar información</x-primary-button>
            </div>
        </form>

    </div>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // JSON de ciudades por departamento
        const ciudadesPorDepartamento = @json(
            $departamentos->mapWithKeys(function ($d) {
                return [
                    $d->id => $d->ciudades->map(function ($c) {
                            return ['id' => $c->id, 'nombre' => $c->nombre];
                        })->values(),
                ];
            }));

        const selectDepartamento = document.getElementById('departamento_id');
        const selectCiudad = document.getElementById('ciudad_id');

        if (selectDepartamento && selectCiudad) {
            selectDepartamento.addEventListener('change', function() {
                const departamentoId = this.value;
                const ciudades = ciudadesPorDepartamento[departamentoId] || [];

                // Limpiar el select
                selectCiudad.innerHTML = '<option value="">Seleccione una ciudad</option>';

                ciudades.forEach(ciudad => {
                    const option = document.createElement('option');
                    option.value = ciudad.id;
                    option.textContent = ciudad.nombre;
                    selectCiudad.appendChild(option);
                });
            });
        } else {
            console.error('Selects no encontrados en el DOM');
        }
    });
</script>
