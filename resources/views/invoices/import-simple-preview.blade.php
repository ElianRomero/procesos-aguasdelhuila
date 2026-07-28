@extends('layouts.app')

@section('title', 'Previsualizar facturas')

@push('head')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.dataTables.min.css">
@endpush

@section('content')
    @php
        $cards = [
            ['label' => 'Filas leidas', 'value' => $summary['total'], 'class' => 'text-black'],
            ['label' => 'Nuevas y validas', 'value' => $summary['new'], 'class' => 'text-black'],
            ['label' => 'Ya existentes', 'value' => $summary['existing'], 'class' => 'text-black'],
            ['label' => 'Pagadas protegidas', 'value' => $summary['paid'], 'class' => 'text-black'],
            ['label' => 'Duplicadas', 'value' => $summary['duplicates'], 'class' => 'text-black'],
            ['label' => 'Invalidas', 'value' => $summary['invalid'], 'class' => 'text-black'],
        ];
        $badgeClasses = [
            'new' => 'bg-green-100 text-black',
            'duplicate_file' => 'bg-yellow-100 text-black',
            'existing' => 'bg-yellow-100 text-black',
            'paid' => 'bg-blue-100 text-black',
            'invalid' => 'bg-red-100 text-black',
        ];
    @endphp

    <div class="space-y-6 mt-10 text-black">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-black">Previsualizacion del archivo</h1>
                <p class="text-sm text-black mt-1">
                    Revisa el lote antes de confirmar. Esta previsualizacion expira en {{ $expiresInMinutes }} minutos.
                </p>
            </div>
            <a href="{{ route('invoices.simple-import.form') }}"
                class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-300 text-sm font-semibold text-black hover:bg-slate-50">
                Cargar otro archivo
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach ($cards as $card)
                <div class="bg-white border border-slate-200 rounded-lg p-4">
                    <p class="text-xs text-black">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-extrabold {{ $card['class'] }}">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-sm text-black">Valor total a importar</p>
                <p class="text-2xl font-extrabold text-black">
                    ${{ number_format($summary['total_value'], 0, ',', '.') }}
                </p>
            </div>
            <div class="w-full md:w-72">
                <label for="statusFilter" class="block text-xs font-semibold text-black mb-1">Filtrar estado</label>
                <select id="statusFilter" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-black">
                    <option value="">Todas las filas</option>
                    <option value="new">Nuevas y validas</option>
                    <option value="duplicate_file">Duplicadas en el archivo</option>
                    <option value="existing">Ya existentes</option>
                    <option value="invalid">Invalidas</option>
                    <option value="paid">Pagadas protegidas</option>
                </select>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="previewTable" class="display stripe hover w-full">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Codigo</th>
                            <th>Numero</th>
                            <th>Nombre</th>
                            <th>Referencia de pago</th>
                            <th>Valor</th>
                            <th>Fecha limite</th>
                            <th>Estado</th>
                            <th>Observacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr data-status="{{ $row['status'] }}">
                                <td>{{ $row['row_number'] }}</td>
                                <td>{{ $row['codigo'] }}</td>
                                <td>{{ $row['numero'] }}</td>
                                <td>{{ $row['nombre'] }}</td>
                                <td class="font-semibold">{{ $row['refpago'] }}</td>
                                <td data-order="{{ $row['valor'] ?? 0 }}">
                                    {{ $row['valor'] ? '$'.number_format($row['valor'], 0, ',', '.') : '-' }}
                                </td>
                                <td>{{ $row['fecha_limite'] }}</td>
                                <td>
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeClasses[$row['status']] }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>
                                <td class="text-sm text-black">{{ $row['observation'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <p class="text-sm text-black">
                Se crearan <strong>{{ $summary['new'] }}</strong> facturas por un valor total de
                <strong>${{ number_format($summary['total_value'], 0, ',', '.') }}</strong>.
            </p>
            <form method="POST" action="{{ route('invoices.simple-import.confirm') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" @disabled($summary['new'] === 0)
                    class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-black
                        {{ $summary['new'] === 0 ? 'bg-slate-300 cursor-not-allowed' : 'bg-emerald-200 hover:bg-emerald-300' }}">
                    Confirmar importacion
                </button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        $(function() {
            const table = $('#previewTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_ filas',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ filas',
                    infoEmpty: 'No hay filas',
                    zeroRecords: 'No se encontraron resultados',
                    paginate: { next: 'Siguiente', previous: 'Anterior' }
                }
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'previewTable') {
                    return true;
                }

                const selected = $('#statusFilter').val();
                const status = $(table.row(dataIndex).node()).data('status');
                return selected === '' || selected === status;
            });

            $('#statusFilter').on('change', function() {
                table.draw();
            });
        });
    </script>
@endsection
