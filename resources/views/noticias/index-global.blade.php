@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center gap-3 mb-4">
    <h1 class="text-2xl font-semibold">Noticias — Global (ADMIN)</h1>
    <a href="{{ route('admin.noticias.procesos.index') }}" class="ml-auto text-sm underline">Volver a procesos</a>
  </div>

  <form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por título, cuerpo o código de proceso"
           class="border rounded px-3 py-2 w-full md:w-1/2" />
    <select name="tipo" class="border rounded px-3 py-2">
      <option value="">Todos los tipos</option>
      @foreach (['COMUNICADO','PRORROGA','ADENDA','ACLARACION','CITACION','OTRO'] as $t)
        <option value="{{ $t }}" @selected(request('tipo')===$t)>{{ $t }}</option>
      @endforeach
    </select>
    <button class="px-4 py-2 bg-gray-800 text-white rounded">Buscar</button>
  </form>

  @forelse($noticias as $n)
    <div class="border rounded-lg p-3 mb-3">
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <span class="px-2 py-0.5 rounded-full border">{{ $n->tipo }}</span>
        @if($n->publico)
          <span class="px-2 py-0.5 rounded-full bg-green-50 text-green-700">Pública</span>
        @else
          <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Privada</span>
          @if($n->destinatarioProponente)
            <span>→ {{ $n->destinatarioProponente->razon_social }}</span>
          @endif
        @endif
        <span class="ml-auto">{{ optional($n->publicada_en)->format('Y-m-d H:i') }}</span>
      </div>

      <h2 class="text-lg font-semibold mt-2">
        [{{ optional($n->proceso)->codigo ?? '—' }}] {{ $n->titulo }}
      </h2>
      <div class="prose max-w-none text-sm">{!! nl2br(e($n->cuerpo)) !!}</div>

      <div class="mt-2 text-xs text-gray-500">
        Publicado por: {{ optional($n->autor)->name ?? 'Entidad' }}
      </div>

      <div class="mt-2 text-sm">
        <a class="underline text-blue-600"
           href="{{ route('procesos.noticias.index', optional($n->proceso)) }}">
          Ver noticias del proceso
        </a>
      </div>
    </div>
  @empty
    <p class="text-gray-500">No hay noticias.</p>
  @endforelse

  <div class="mt-4">{{ $noticias->links() }}</div>
</div>
@endsection
