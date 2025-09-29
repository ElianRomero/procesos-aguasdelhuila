@extends('layouts.app')

@section('content')
<div class="mt-10">
  <form method="POST" action="{{ route('proponentes.old.store') }}"
        class="max-w-4xl mx-auto mt-5 py-6 px-8 bg-white rounded-xl shadow-2xl border border-gray-200">
    @csrf

    <h1 class="text-xl font-semibold mb-6">Registrar Proponente </h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {{-- Razón social --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre o Razón Social</label>
        <input name="razon_social" required value="{{ old('razon_social') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800"/>
      </div>

      {{-- NIT --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
        <input name="nit" required value="{{ old('nit') }}"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800"/>
      </div>

      {{-- Representante --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Representante Legal</label>
        <input name="representante" required value="{{ old('representante') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800"/>
      </div>

      {{-- Tipo Identificación --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Identificación</label>
        <select name="tipo_identificacion_codigo" class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
          @foreach ($tiposIdentificacion as $t)
            <option value="{{ $t->codigo }}" @selected(old('tipo_identificacion_codigo')==$t->codigo)>{{ $t->nombre }}</option>
          @endforeach
        </select>
      </div>

             {{-- Departamento --}}
            <select
              id="departamento_id"
              name="departamento_id"
              data-initial-dep="{{ old('departamento_id') }}"
              class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800"
            >
              <option value="">Seleccione un departamento</option>
              @foreach ($departamentos as $dep)
                <option value="{{ $dep->id }}" @selected(old('departamento_id')==$dep->id)>{{ $dep->nombre }}</option>
              @endforeach
            </select>
            
            {{-- Ciudad --}}
            <select
              id="ciudad_id"
              name="ciudad_id"
              required
              data-initial-city="{{ old('ciudad_id') }}"
              class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800"
            >
              <option value="">Seleccione una ciudad</option>
            </select>
            



      {{-- Dirección --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
        <input name="direccion" required value="{{ old('direccion') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800"/>
      </div>

      {{-- CIIU --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Actividad Económica (CIIU)</label>
        <select name="ciiu_id" id="ciiu_id"
                class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
          @foreach ($ciius as $c)
            <option value="{{ $c->id }}" @selected(old('ciiu_id')==$c->id)>{{ ($c->codigo ?? $c->id) }} — {{ $c->nombre ?? $c->codigo ?? $c->id }}</option>
          @endforeach
        </select>
      </div>

      {{-- Teléfonos --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono 1</label>
        <input name="telefono1" required maxlength="15"
               oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,15)"
               value="{{ old('telefono1') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono 2</label>
        <input name="telefono2" maxlength="15"
               oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,15)"
               value="{{ old('telefono2') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm"/>
      </div>

      {{-- Correo --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
        <input name="correo" type="email" value="{{ old('correo') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm"/>
      </div>

      {{-- Sitio web --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sitio web</label>
        <input name="sitio_web" type="url" value="{{ old('sitio_web') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm"/>
      </div>

      {{-- Fecha inicio actividad --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio de actividad</label>
        <input name="actividad_inicio" type="date" required value="{{ old('actividad_inicio') }}"
               class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm"/>
      </div>

      {{-- Observación (col-span) --}}
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
        <textarea name="observacion" rows="3"
                  class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('observacion') }}</textarea>
      </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
      <x-primary-button>Guardar y generar certificado</x-primary-button>
      <a href="{{ route('proponentes.certificados.index') }}" class="text-sm underline">Volver a certificados</a>
    </div>
  </form>
</div>
@endsection


<script>
document.addEventListener('DOMContentLoaded', () => {
  // Este JSON viene del controlador, así evitamos el 500 en Blade
  const ciudadesPorDepto = @json($cityMap);

  const selDep  = document.getElementById('departamento_id');
  const selCiu  = document.getElementById('ciudad_id');

  function fillCiudades(depId, selectedId = '') {
    const list = ciudadesPorDepto[depId] || [];
    selCiu.innerHTML = '<option value="">Seleccione…</option>';
    list.forEach(function(c){
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = c.nombre;
      if (String(selectedId) === String(c.id)) opt.selected = true;
      selCiu.appendChild(opt);
    });
    selCiu.disabled = list.length === 0;
  }

  // Al cambiar departamento
  selDep?.addEventListener('change', function (e) {
    fillCiudades(e.target.value);
  });

  // Precarga si hay old() (por validación fallida)
  const depInit  = "{{ old('departamento_id', '') }}";
  const cityInit = "{{ old('ciudad_id', '') }}";
  if (depInit) {
    selDep.value = depInit;
    fillCiudades(depInit, cityInit);
  } else {
    selCiu.disabled = true; // hasta que elijan departamento
  }
});
</script>



