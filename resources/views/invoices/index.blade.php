@extends('layouts.app')

@section('title', 'Facturas')

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.dataTables.min.css">
    <style>
        table.dataTable thead th {
            color: #0f172a !important;
            font-weight: 700;
            font-size: 13px;
        }

        table.dataTable tbody td {
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 6px 10px;
            background: white;
            color: #0f172a;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 10px !important;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0ea5e9 !important;
            border: 1px solid #0ea5e9 !important;
            color: white !important;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 12px;
            font-size: 14px;
            color: #475569;
        }
    </style>
@endpush

@section('content')
    <div class="space-y-6 mt-10">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800">Facturas importadas</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Consulta, busca y revisa las facturas cargadas en el sistema.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('invoices.import.form') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-semibold shadow-sm transition">
                    Importar Excel
                </a>

                <a href="{{ route('pago.search.form') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold shadow-sm transition">
                    Buscar factura pública
                </a>
            </div>
        </div>

        @if (session('ok'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('ok') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Total facturas</p>
                <h3 class="text-2xl font-extrabold text-slate-800 mt-1">{{ $invoices->count() }}</h3>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Pendientes</p>
                <h3 class="text-2xl font-extrabold text-yellow-600 mt-1">
                    {{ $invoices->where('status', 'pendiente')->count() }}
                </h3>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Pagadas</p>
                <h3 class="text-2xl font-extrabold text-green-600 mt-1">
                    {{ $invoices->where('status', 'pagada')->count() }}
                </h3>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Canceladas</p>
                <h3 class="text-2xl font-extrabold text-red-600 mt-1">
                    {{ $invoices->where('status', 'cancelada')->count() }}
                </h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="tablaFacturas" class="display stripe hover w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Número</th>
                            <th>N° Factura</th>
                            <th>Identificación</th>
                            <th>Valor</th>
                            <th>Fecha límite</th>
                            <th>Estado</th>
                            <th>Link pago</th>
                            <th>Creada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            @php
                                $vencida = $invoice->fecha ? now()->startOfDay()->gt(\Carbon\Carbon::parse($invoice->fecha)->endOfDay()) : false;
                            @endphp
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td class="font-semibold text-slate-800">{{ $invoice->numero }}</td>
                                <td>{{ $invoice->refpago }}</td>
                                <td>{{ $invoice->codigo }}</td>
                                <td class="font-semibold text-emerald-600">
                                    $ {{ number_format($invoice->valfactura, 0, ',', '.') }}
                                </td>
                                <td>
                                    {{ optional($invoice->fecha)->format('d/m/Y') }}
                                </td>
                                <td>
                                    @if ($invoice->status === 'pagada')
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                            PAGADA
                                        </span>
                                    @elseif($invoice->status === 'cancelada')
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                            CANCELADA
                                        </span>
                                    @elseif($vencida)
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">
                                            VENCIDA
                                        </span>
                                    @else
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                            PENDIENTE
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($invoice->payment_link_url)
                                        <a href="{{ $invoice->payment_link_url }}" target="_blank"
                                            class="inline-flex px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-100 text-sky-700 hover:bg-sky-200 transition">
                                            Ver link
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Sin link</span>
                                    @endif
                                </td>
                                <td>{{ optional($invoice->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaFacturas').DataTable({
                pageLength: 10,
                responsive: true,
                order: [
                    [0, 'desc']
                ],
                language: {
                    decimal: "",
                    emptyTable: "No hay facturas disponibles",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ facturas",
                    infoEmpty: "Mostrando 0 a 0 de 0 facturas",
                    infoFiltered: "(filtrado de _MAX_ facturas totales)",
                    infoPostFix: "",
                    thousands: ",",
                    lengthMenu: "Mostrar _MENU_ facturas",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron resultados",
                    paginate: {
                        first: "Primera",
                        last: "Última",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    aria: {
                        sortAscending: ": activar para ordenar la columna ascendente",
                        sortDescending: ": activar para ordenar la columna descendente"
                    }
                }
            });
        });
    </script>
@endsection