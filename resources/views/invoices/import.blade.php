@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white shadow-lg rounded-2xl p-8 mt-16">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">
             Importar Facturas CSV
        </h1>

        {{-- Mensaje de éxito --}}
        @if (session('ok'))
            <div class="mb-6 p-3 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm text-center">
                ✅ {{ session('ok') }}
            </div>
        @endif

        <form method="POST" action="{{ route('invoices.import') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Input archivo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Selecciona tu archivo CSV
                </label>
                <input type="file" 
                       name="file" 
                       required
                       class="block w-full text-sm text-gray-600
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-500 file:text-white
                              hover:file:bg-blue-600
                              cursor-pointer"/>
            </div>

            {{-- Botón --}}
            <div class="text-center">
                <button type="submit" 
                        class="px-6 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 text-white font-semibold shadow">
                     Subir Archivo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
