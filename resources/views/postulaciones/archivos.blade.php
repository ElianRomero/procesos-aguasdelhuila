@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-2">Subir documentos</h1>
        <div class="bg-white shadow rounded-lg p-6 mt-10">
            <p class="text-gray-600 mb-6">
                Proceso <span class="font-semibold">{{ $proceso->codigo }}</span> — {{ $proceso->objeto }}
            </p>

            @if (session('success'))
                <div class="mb-4 rounded bg-green-50 text-green-800 px-3 py-2">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded bg-red-50 text-red-700 px-3 py-2">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (empty($requisitos))
                <div class="rounded border p-4 bg-gray-50 text-gray-700 mb-6">
                    Este proceso no tiene requisitos configurados.
                </div>
            @endif

            <form action="{{ route('postulaciones.archivos.store', $proceso->codigo) }}" method="POST"
                enctype="multipart/form-data" class="space-y-4">
                @csrf

                @foreach ($requisitos ?? [] as $r)
                    @php
                        $k = $r['key'];
                        $ya = $subidos[$k] ?? null;
                    @endphp

                    <div class="border rounded-lg p-4">
                        <label class="block font-medium mb-1">
                            {{ $r['name'] }} <span class="text-xs text-gray-500">(PDF)</span>
                        </label>

                        <input type="file" name="files[{{ $k }}]" accept="application/pdf"
                            class="block w-full border rounded px-3 py-2">

                        @error("files.$k")
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror

                        @if ($ya)
                            <div class="text-sm text-gray-600 mt-2">
                                Ya subido: {{ $ya->original_name }}
                                ({{ number_format(($ya->size_bytes ?? 0) / 1024, 0) }} KB)
                                — <a class="underline text-blue-600"
                                    href="{{ route('postulaciones.archivos.show', [$proceso->codigo, $k]) }}">
                                    ver
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach

               <button type="button"
        onclick="if (history.length > 1) history.back(); else window.location='{{ route('postulaciones.index') }}';"
        class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
  Volver
</button>


                    <button class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">
                        Guardar documentos
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection
