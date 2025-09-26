@extends('layouts.pay')

@section('content')
    <div class="flex items-center justify-center min-h-[70vh]">
        <div class="w-full max-w-2xl bg-white/90 backdrop-blur rounded-2xl shadow-xl p-8 border border-slate-200">

            <!-- Header -->
            <div class="text-center mb-6">

                <h1 class="text-2xl font-bold text-slate-500 mt-1">Detalles de la factura generada</h1>
            </div>

            <!-- Datos factura -->
            <div class="space-y-3 mb-6 mt-3">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-medium text-slate-600">Cliente:</span>
                    <span class="text-slate-800">{{ $invoice->nombre }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-medium text-slate-600">Dirección:</span>
                    <span class="text-slate-800">{{ $invoice->direccion }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-medium text-slate-600">Fecha:</span>
                    <span class="text-slate-800">{{ optional($invoice->fecha)->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-medium text-slate-600">Valor:</span>
                    <span class="text-green-600 font-semibold">$
                        {{ number_format($invoice->valfactura / 100, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-medium text-slate-600">Estado:</span>
                    <span
                        class="px-3 py-1 text-xs rounded-full 
                    {{ $invoice->status === 'pagada' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ strtoupper($invoice->status) }}
                    </span>
                </div>
                @if ($invoice->payment_link_url && $invoice->expires_at)
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-medium text-slate-600">Enlace expira:</span>
                        <span class="text-slate-800">{{ $invoice->expires_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>

            <!-- Mensaje estado -->
            <!-- Mensaje estado -->
            @if ($invoice->status === 'pagada')
                <div class="mb-6 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200 text-center">
                    ✅ Esta factura ya fue pagada. ¡Gracias por tu pago!
                </div>
            @else
                <form method="POST" action="{{ route('pago.link', $invoice->refpago) }}" class="mb-6">
                    @csrf
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 rounded-lg shadow-md transition duration-200">
                        Pagar ahora
                    </button>
                </form>
            @endif



            <!-- Volver -->
            <div class="text-center">
                <a href="{{ route('pago.search.form') }}" class="text-sky-600 hover:text-sky-700 text-sm font-medium">
                    ← Buscar otra factura
                </a>
            </div>
        </div>
    </div>
@endsection
