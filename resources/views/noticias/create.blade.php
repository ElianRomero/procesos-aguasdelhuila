@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-4">
  <div class="flex items-center mb-4">
    <h1 class="text-2xl font-semibold">Nueva noticia</h1>
    <a href="{{ route('admin.noticias.index') }}" class="ml-auto text-sm underline">Volver al listado</a>
  </div>

  <form method="POST" action="{{ route('admin.noticias.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    {{-- Grid 2 columnas (bonito y compacto) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

      {{-- Proceso (selector con búsqueda AJAX) --}}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Proceso</label>

        {{-- Estado seleccionado (cuando eliges un proceso) --}}
        <div id="proceso-seleccionado" class="hidden items-center justify-between gap-2 border rounded px-3 py-2 bg-white">
          <div>
            <div id="proc-chip-codigo" class="text-sm font-medium"></div>
            <div id="proc-chip-objeto" class="text-xs text-gray-600 line-clamp-2"></div>
          </div>
          <button type="button" id="btn-cambiar-proceso" class="text-xs px-2 py-1 rounded border">
            Cambiar
          </button>
        </div>

        {{-- Buscador (estado inicial) --}}
        <div id="proceso-buscador" class="relative">
          <input
            id="input-buscar-proceso"
            type="text"
            class="w-full border rounded px-3 py-2"
            placeholder="Busca por código, objeto, modalidad o tipo"
            autocomplete="off"
          />

          {{-- Panel de resultados --}}
          <div id="panel-resultados"
               class="hidden absolute left-0 right-0 mt-1 bg-white border rounded shadow-lg z-10 max-h-60 overflow-auto">
            <div id="estado-busqueda" class="p-2 text-xs text-gray-500">Escribe para buscar…</div>
            <ul id="lista-resultados" class="divide-y"></ul>
          </div>
        </div>

        {{-- Campo real que se envía --}}
        <input type="hidden" name="proceso_codigo" id="proceso_codigo" value="{{ old('proceso_codigo') }}">
        @error('proceso_codigo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>

      {{-- Título --}}
      <div>
        <label class="block text-sm font-medium mb-1">Título</label>
        <input name="titulo" class="w-full border rounded px-3 py-2" required maxlength="180" value="{{ old('titulo') }}">
        @error('titulo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>

      {{-- Tipo --}}
      <div>
        <label class="block text-sm font-medium mb-1">Tipo</label>
        <select name="tipo" class="w-full border rounded px-3 py-2">
          @foreach (['COMUNICADO','PRORROGA','ADENDA','ACLARACION','CITACION','OTRO'] as $t)
            <option value="{{ $t }}" @selected(old('tipo')===$t)>{{ $t }}</option>
          @endforeach
        </select>
        @error('tipo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>

      {{-- Contenido --}}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Contenido</label>
        <textarea name="cuerpo" rows="8" class="w-full border rounded px-3 py-2" required>{{ old('cuerpo') }}</textarea>
        @error('cuerpo')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>

      {{-- Adjuntos --}}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Adjuntos (opcional)</label>
        <input type="file" name="archivos[]" multiple>
        @error('archivos.*')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
      </div>

      {{-- Alcance oculto: TODO pública --}}
      <input type="hidden" name="publico" value="1">
    </div>

    <div class="flex gap-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Publicar</button>
      <a href="{{ route('admin.noticias.index') }}" class="px-4 py-2 border rounded">Cancelar</a>
    </div>
  </form>
</div>

{{-- JS del combo buscador (vanilla) --}}
<script>
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  const input = $('#input-buscar-proceso');
  const panel = $('#panel-resultados');
  const estado = $('#estado-busqueda');
  const lista = $('#lista-resultados');
  const hiddenCodigo = $('#proceso_codigo');

  const chipWrap = $('#proceso-seleccionado');
  const chipCodigo = $('#proc-chip-codigo');
  const chipObjeto = $('#proc-chip-objeto');
  const btnCambiar = $('#btn-cambiar-proceso');
  const buscadorWrap = $('#proceso-buscador');

  let timer = null;
  let focusIndex = -1;
  let items = [];

  function openPanel() {
    panel.classList.remove('hidden');
  }

  function closePanel() {
    panel.classList.add('hidden');
    focusIndex = -1;
  }

  function renderItems(data) {
    items = data;
    lista.innerHTML = '';
    if (!data.length) {
      estado.textContent = 'Sin resultados';
      estado.classList.remove('hidden');
      return;
    }
    estado.classList.add('hidden');

    data.forEach((it, idx) => {
      const li = document.createElement('li');
      li.className = 'px-3 py-2 hover:bg-gray-50 cursor-pointer';
      li.dataset.index = idx;
      li.innerHTML = `
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-medium">[${escapeHtml(it.codigo)}]</div>
            <div class="text-xs text-gray-600 line-clamp-2">${escapeHtml(it.objeto || '')}</div>
          </div>
          <div class="text-[11px] text-gray-500 ml-3 whitespace-nowrap">${escapeHtml(it.fecha || '')}</div>
        </div>
        ${it.badge ? `<div class="text-[11px] text-gray-500 mt-1">${escapeHtml(it.badge)}</div>` : '' }
      `;
      li.addEventListener('click', () => selectItem(idx));
      lista.appendChild(li);
    });
  }

  function highlight(idx) {
    const lis = Array.from(lista.children);
    lis.forEach((el, i) => {
      el.classList.toggle('bg-gray-100', i === idx);
    });
    focusIndex = idx;
  }

  function selectItem(idx) {
    const it = items[idx];
    if (!it) return;

    hiddenCodigo.value = it.codigo;
    chipCodigo.textContent = `[${it.codigo}] ${it.fecha || ''}`;
    chipObjeto.textContent = it.objeto || '';
    chipWrap.classList.remove('hidden');
    buscadorWrap.classList.add('hidden');

    closePanel();
  }

  function resetSeleccion() {
    hiddenCodigo.value = '';
    chipCodigo.textContent = '';
    chipObjeto.textContent = '';
    chipWrap.classList.add('hidden');
    buscadorWrap.classList.remove('hidden');
    input.value = '';
    input.focus();
  }

  function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }

  // Buscar (debounce)
  input.addEventListener('input', () => {
    const q = input.value.trim();
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
      if (!q) {
        estado.textContent = 'Escribe para buscar…';
        estado.classList.remove('hidden');
        lista.innerHTML = '';
        openPanel();
        return;
      }
      estado.textContent = 'Buscando…';
      estado.classList.remove('hidden');
      openPanel();

      fetch(`{{ route('admin.procesos.buscar') }}?q=` + encodeURIComponent(q))
        .then(r => r.json())
        .then(renderItems)
        .catch(() => {
          estado.textContent = 'Error al buscar';
          estado.classList.remove('hidden');
          lista.innerHTML = '';
        });
    }, 250);
  });

  // Teclado: flechas y enter
  input.addEventListener('keydown', (e) => {
    if (panel.classList.contains('hidden')) return;
    const count = items.length;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!count) return;
      highlight((focusIndex + 1) % count);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (!count) return;
      highlight((focusIndex - 1 + count) % count);
    } else if (e.key === 'Enter') {
      if (focusIndex >= 0) {
        e.preventDefault();
        selectItem(focusIndex);
      }
    } else if (e.key === 'Escape') {
      closePanel();
    }
  });

  // Click fuera para cerrar
  document.addEventListener('click', (e) => {
    if (!buscadorWrap.contains(e.target)) closePanel();
  });

  // Botón cambiar
  btnCambiar.addEventListener('click', resetSeleccion);

  // Si venías con old('proceso_codigo'), mostramos la chip:
  @if(old('proceso_codigo'))
    ;(function preload() {
      const cod = @json(old('proceso_codigo'));
      // Intento mínimo para mostrar algo. Si quieres, haz un fetch al detalle.
      hiddenCodigo.value = cod;
      chipCodigo.textContent = '['+cod+']';
      chipObjeto.textContent = '';
      chipWrap.classList.remove('hidden');
      buscadorWrap.classList.add('hidden');
    })();
  @endif
</script>
@endsection
