@extends('layouts.pay')

@section('content')
<div class="flex items-center justify-center min-h-[70vh]">
    <div class="w-full max-w-md bg-white/90 backdrop-blur rounded-2xl shadow-xl p-8 border border-slate-200 text-center">
        
        <!-- Ícono -->
        <div class="mb-4">
            <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-red-100 text-red-600">
                ❌
            </div>
        </div>

        <!-- Título -->
        <h2 class="text-xl font-bold text-slate-800 mb-2">
            No encontramos la factura
        </h2>

        <!-- Mensaje -->
        <p class="text-slate-600 mb-6">
            No se encontró ninguna factura con el código <br>
            <span class="font-semibold text-slate-800">REFPAGO: {{ $refpago }}</span>
        </p>

        <!-- Botón volver -->
        <a href="{{ route('pago.search.form') }}"
           class="inline-block bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-medium px-6 py-2.5 rounded-lg shadow-md transition duration-200">
             Volver a buscar
        </a>
    </div>
</div>
@endsection
