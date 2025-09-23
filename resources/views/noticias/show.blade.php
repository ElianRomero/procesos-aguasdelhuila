@extends('layouts.app')

@section('content')
@php
  $dt = $noticia->publicada_en ?? $noticia->created_at;
  $mes = [
    '01'=>'ene','02'=>'feb','03'=>'mar','04'=>'abr','05'=>'may','06'=>'jun',
    '07'=>'jul','08'=>'ago','09'=>'sep','10'=>'oct','11'=>'nov','12'=>'dic'
  ];
  $fechaBonita = $dt ? $dt->format('d').' '.$mes[$dt->format('m')].' '.$dt->format('Y') : '';
@endphp

<div class="max-w-4xl mx-auto p-4 md:p-6">
  {{-- migas --}}
  <nav class="text-xs text-white/80 mb-3 flex items-center gap-2">
    <a href="{{ route('proponente.noticias.index') }}" class="underline hover:text-white">← Mis noticias</a>
    <span class="opacity-60">/</span>
    <a href="{{ route('procesos.noticias.index', $proceso) }}" class="underline hover:text-white">
      Proceso {{ $proceso->codigo }}
    </a>
  </nav>

  {{-- tarjeta blanca --}}
  <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
    {{-- header --}}
    <div class="px-5 py-4 border-b bg-white/60">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h1 class="text-xl md:text-2xl font-semibold text-gray-900">
            {{ $noticia->titulo }}
          </h1>

          <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600">
            {{-- tipo --}}
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border border-green-200 bg-green-100 text-green-800 text-xs font-medium">
              {{ $noticia->tipo }}
            </span>

            <span class="text-gray-300">•</span>

            {{-- fecha --}}
            <span class="inline-flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-9ZM6 8h12V7H6v1Z"/>
              </svg>
              {{ $fechaBonita }}
            </span>

            <span class="text-gray-300">•</span>

            {{-- proceso --}}
            <span class="inline-flex items-center gap-1">
              <span class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-gray-100 border text-gray-700">
                {{ $noticia->proceso_codigo }}
              </span>
              @if (!empty($proceso->objeto))
                <span class="hidden md:inline text-xs text-gray-500 line-clamp-1">— {{ \Illuminate\Support\Str::limit($proceso->objeto, 90) }}</span>
              @endif
            </span>
          </div>
        </div>

        {{-- acciones rápidas --}}
        <div class="flex items-center gap-2">
          <a href="{{ route('proponente.noticias.index', $proceso) }}"
             class="text-xs px-3 py-1.5 border rounded-lg hover:bg-gray-50">Volver</a>
       
        </div>
      </div>
    </div>

    {{-- cuerpo --}}
    <div class="px-5 py-6">
      <div class="text-[15px] leading-relaxed text-gray-800 whitespace-pre-line">
        {{ $noticia->cuerpo }}
      </div>

      {{-- adjuntos --}}
      @if($noticia->archivos && $noticia->archivos->count())
        <div class="mt-8">
          <h2 class="text-sm font-semibold text-gray-900 mb-3">Adjuntos</h2>

          <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($noticia->archivos as $a)
              @php
                $url = \Illuminate\Support\Facades\Storage::disk($a->disk)->url($a->path);
                $peso = $a->size ? ($a->size >= 1048576
                      ? number_format($a->size/1048576, 2).' MB'
                      : number_format($a->size/1024, 0).' KB') : null;
              @endphp
              <li>
                <a href="{{ $url }}" target="_blank"
                   class="flex items-start gap-3 p-3 border rounded-lg hover:bg-gray-50 transition">
                  <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-md border bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 2.5L18.5 9H14Z"/>
                    </svg>
                  </span>
                  <span class="min-w-0">
                    <span class="block text-sm font-medium text-gray-900 truncate">{{ $a->original_name }}</span>
                    <span class="block text-xs text-gray-500">
                      {{ $a->mime ?? 'archivo' }} @if($peso) • {{ $peso }} @endif
                    </span>
                  </span>
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
