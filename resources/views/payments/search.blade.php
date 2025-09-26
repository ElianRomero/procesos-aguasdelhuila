@extends('layouts.pay')

@section('content')
<div class="flex items-center justify-center min-h-[50vh]">
    <div class="w-full max-w-lg bg-white/90 backdrop-blur rounded-2xl shadow-xl p-8 border border-slate-200">
        
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800"> Buscar factura</h1>
            <p class="text-sm text-slate-500 mt-1">Ingresa el código <span class="font-semibold">REFPAGO</span> para consultar tu factura</p>
        </div>

        @if (session('error'))
            <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-600 text-sm border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('pago.search') }}" class="space-y-4">
            <div>
                <label for="refpago" class="block text-sm font-medium text-slate-600 mb-1">REFPAGO</label>
                <input 
                    type="text" 
                    id="refpago" 
                    name="refpago" 
                    placeholder="Ej: 1234567890" 
                    required
                    class="w-full rounded-lg border border-slate-300 focus:ring-2 focus:ring-sky-400 focus:border-sky-400 px-4 py-2 text-slate-700 placeholder-slate-400"
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-medium py-2.5 rounded-lg shadow-md transition duration-200">
                Buscar factura
            </button>
        </form>
    </div>
</div>
@endsection
