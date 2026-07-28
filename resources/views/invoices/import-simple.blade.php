@extends('layouts.app')

@section('title', 'Importar facturas simplificadas')

@section('content')
    <div class="max-w-3xl mx-auto mt-10 space-y-6 text-black">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-black">Importar facturas simplificadas</h1>
            <p class="text-sm text-black mt-1">Carga facturas nuevas mediante CODIGO, NOMBRE, REFERNCIA y VALOR.</p>
        </div>

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-black">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-black">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invoices.simple-import.preview') }}" enctype="multipart/form-data"
            class="bg-white border border-slate-200 shadow-sm rounded-lg p-6 space-y-6">
            @csrf

            <div>
                <label for="file" class="block text-sm font-semibold text-black mb-2">Archivo</label>
                <input id="file" name="file" type="file" required accept=".xlsx,.xls,.csv"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-black
                        file:mr-4 file:border-0 file:bg-sky-200 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-black
                        hover:file:bg-sky-300">
                <p class="mt-2 text-xs text-black">Formatos admitidos: XLSX, XLS y CSV. Maximo 20 MB.</p>
            </div>

            <div>
                <label for="fecha_limite" class="block text-sm font-semibold text-black mb-2">
                    Fecha limite de pago
                </label>
                <input id="fecha_limite" name="fecha_limite" type="date" required min="{{ now()->toDateString() }}"
                    value="{{ old('fecha_limite') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-black">
            </div>

            <div class="border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-black">Columnas esperadas</p>
                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-black">
                    <span>CODIGO</span>
                    <span>NOMBRE</span>
                    <span>REFERNCIA</span>
                    <span>VALOR</span>
                </div>
                <p class="mt-3 text-xs text-black">
                    Tambien se aceptan REFERENCIA y REFPAGO como encabezado de la referencia.
                </p>
            </div>

            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-black">
                La previsualizacion no guarda registros. Las referencias existentes se mostraran, pero no se modificaran.
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('invoices.index') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-black hover:bg-slate-50">
                    Volver al seguimiento
                </a>
                <button type="submit"
                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-sky-200 text-sm font-semibold text-black hover:bg-sky-300">
                    Previsualizar archivo
                </button>
            </div>
        </form>
    </div>
@endsection
