@extends('layouts.pay')

@section('content')
<div class="flex items-center justify-center min-h-[70vh]">
    <div class="w-full max-w-md bg-white/90 backdrop-blur rounded-2xl shadow-xl p-8 border border-slate-200 text-center">
        <div class="mb-4">
            <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-red-100 text-red-600">
                X
            </div>
        </div>

        <h2 class="text-xl font-bold text-slate-800 mb-2">
            No encontramos la factura
        </h2>

        <p class="text-slate-600 mb-6">
            No se encontro ninguna factura con el codigo
            <br>
            <span class="font-semibold text-slate-800">REFPAGO: {{ $refpago }}</span>
        </p>

        <a href="{{ route('pago.checkout.search.form') }}"
           class="inline-block bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium px-6 py-2.5 rounded-lg shadow-md transition duration-200">
             Volver a buscar en pruebas
        </a>
    </div>
</div>
@endsection
