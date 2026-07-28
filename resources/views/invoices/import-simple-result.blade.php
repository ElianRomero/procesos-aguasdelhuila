@extends('layouts.app')

@section('title', 'Resultado de importacion')

@section('content')
    <div class="space-y-6 mt-10 text-black">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-black">Resultado de importacion</h1>
                <p class="text-sm text-black mt-1">El lote temporal fue procesado y eliminado.</p>
            </div>
            <a href="{{ route('invoices.index') }}"
                class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-sky-200 text-sm font-semibold text-black hover:bg-sky-300">
                Ir al seguimiento de facturas
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Facturas creadas</p>
                <p class="mt-1 text-2xl font-extrabold text-black">{{ $result['created'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Duplicadas omitidas</p>
                <p class="mt-1 text-2xl font-extrabold text-black">{{ $result['duplicates'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Pagadas protegidas</p>
                <p class="mt-1 text-2xl font-extrabold text-black">{{ $result['paid_protected'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Filas invalidas</p>
                <p class="mt-1 text-2xl font-extrabold text-black">{{ $result['invalid'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Errores de creacion</p>
                <p class="mt-1 text-2xl font-extrabold text-black">{{ $result['errors'] }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-black">Valor importado</p>
                <p class="mt-1 text-xl font-extrabold text-black">
                    ${{ number_format($result['total_value'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5 overflow-hidden">
            <h2 class="text-lg font-bold text-black mb-4">Registros creados</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 text-left text-black">
                        <tr>
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">Codigo</th>
                            <th class="px-3 py-2">Numero</th>
                            <th class="px-3 py-2">Nombre</th>
                            <th class="px-3 py-2">Referencia</th>
                            <th class="px-3 py-2">Valor</th>
                            <th class="px-3 py-2">Fecha limite</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($result['created_rows'] as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['id'] }}</td>
                                <td class="px-3 py-2">{{ $row['codigo'] }}</td>
                                <td class="px-3 py-2">{{ $row['numero'] }}</td>
                                <td class="px-3 py-2">{{ $row['nombre'] }}</td>
                                <td class="px-3 py-2 font-semibold">{{ $row['refpago'] }}</td>
                                <td class="px-3 py-2">${{ number_format($row['valor'], 0, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ $row['fecha_limite'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-black">
                                    No se crearon facturas nuevas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
