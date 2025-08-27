@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-6 mt-10">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Observaciones del proceso <span class="text-indigo-600">{{ $proceso->codigo }}</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">{{ $proceso->objeto }}</p>
            </div>
            <a href="{{ route('admin.observaciones.index') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 mt-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path fill-rule="evenodd"
                        d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5" />
                </svg>
                Volver
            </a>

        </div>

        <!-- Observaciones -->
        <div class="space-y-6">
            @forelse($observaciones as $o)
                <div class="relative border border-gray-200 bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition">

                    <!-- Fecha -->
                    <div class="absolute  left-10 bg-indigo-600 text-white text-xs px-3 py-1  rounded-full shadow">
                        {{ $o->created_at->format('d/m/Y H:i') }}
                    </div>

                    <!-- Asunto -->
                    <h2 class="text-lg font-semibold text-gray-800 mt-5">{{ $o->asunto }}</h2>

                    <!-- Usuario -->
                    <p class="text-sm text-gray-500 mb-3">
                        por <span class="font-medium text-gray-700">
                            {{ $o->usuario->name ?? ($o->usuario->email ?? '—') }}
                        </span>
                    </p>

                    <!-- Descripción -->
                    @if ($o->descripcion)
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                            {{ $o->descripcion }}
                        </p>
                    @endif

                    <!-- Archivos -->
                    @if ($o->archivos->count())
                        <div class="mt-4">
                            <h3 class="text-xs font-semibold text-gray-500 mb-1">Archivos adjuntos:</h3>
                            <ul class="space-y-1">
                                @foreach ($o->archivos as $f)
                                    <li>
                                        <a href="{{ route('observaciones.archivos.download', [$o->id, $f->id]) }}"
                                            class="inline-flex items-center text-indigo-600 text-sm hover:underline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                            </svg>
                                            {{ $f->original_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-gray-500 text-center py-10">
                    <p class="text-lg">No hay observaciones para este proceso.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
