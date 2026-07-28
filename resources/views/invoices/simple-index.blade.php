@extends('layouts.app')

@section('title', 'Facturas simplificadas')

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.dataTables.min.css">
@endpush

@section('content')
    <div class="space-y-6 mt-10 text-black">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-black">Facturas simplificadas</h1>
                <p class="text-sm text-black mt-1">Cobros importados por codigo, separados de las facturas tradicionales.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('invoices.simple-import.form') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg bg-sky-200 text-sm font-semibold text-black hover:bg-sky-300">
                    Importar archivo
                </a>
                <a href="{{ route('pago.search.form') }}"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg border border-black bg-white text-sm font-semibold text-black hover:bg-slate-100">
                    Buscar pago publico
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Total</p>
                <p class="text-2xl font-extrabold text-black">{{ $invoices->count() }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Pendientes</p>
                <p class="text-2xl font-extrabold text-black">{{ $invoices->where('status', 'pendiente')->count() }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Pagadas</p>
                <p class="text-2xl font-extrabold text-black">{{ $invoices->where('status', 'pagada')->count() }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Canceladas</p>
                <p class="text-2xl font-extrabold text-black">{{ $invoices->where('status', 'cancelada')->count() }}</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="simpleInvoicesTable" class="display stripe hover w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Codigo</th>
                            <th>Nombre</th>
                            <th>Referencia</th>
                            <th>Valor</th>
                            <th>Fecha limite</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Creada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            @php
                                $vencida = $invoice->fecha
                                    ? now()->startOfDay()->gt($invoice->fecha->copy()->endOfDay())
                                    : false;
                            @endphp
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td class="font-semibold">{{ $invoice->codigo }}</td>
                                <td>{{ $invoice->nombre }}</td>
                                <td>{{ $invoice->refpago }}</td>
                                <td data-order="{{ $invoice->valfactura }}">
                                    ${{ number_format($invoice->valfactura, 0, ',', '.') }}
                                </td>
                                <td>{{ optional($invoice->fecha)->format('d/m/Y') }}</td>
                                <td>
                                    @if ($invoice->status === 'pagada')
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-black">PAGADA</span>
                                    @elseif ($invoice->status === 'cancelada')
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-black">CANCELADA</span>
                                    @elseif ($vencida)
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-black">VENCIDA</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-black">PENDIENTE</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('pago.simple.show', ['refpago' => $invoice->refpago]) }}"
                                        class="inline-flex px-3 py-1.5 rounded-lg bg-sky-100 text-xs font-semibold text-black hover:bg-sky-200">
                                        Ver pago
                                    </a>
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
        $(function() {
            $('#simpleInvoicesTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_ facturas',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ facturas',
                    infoEmpty: 'No hay facturas',
                    zeroRecords: 'No se encontraron resultados',
                    paginate: { next: 'Siguiente', previous: 'Anterior' }
                }
            });
        });
    </script>
@endsection
