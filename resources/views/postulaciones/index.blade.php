@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Procesos Vigentes</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 mt-10">
        @if (session('success'))
            <div class="mb-4 text-green-700 bg-green-100 px-3 py-2 rounded">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-red-700 bg-red-100 px-3 py-2 rounded">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden p-4">
            <table id="tabla-procesos" class="min-w-full display">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Objeto</th>
                        <th>Valor</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($procesos as $p)
                        @php
                            $ya = $p->proponentesPostulados->isNotEmpty();
                            $estadoPost = $ya ? $p->proponentesPostulados->first()->pivot->estado : null;
                        @endphp
                        <tr>
                            <td>{{ $p->codigo }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($p->objeto, 120) }}</td>
                            <td>${{ number_format($p->valor, 0, ',', '.') }}</td>
                            <td>{{ $p->fecha?->format('d/m/Y') }}</td>
                            <td><span class="px-2 py-1 rounded bg-gray-100 text-xs">{{ $p->estado }}</span></td>
                            <td>
                                @if (!$ya)
                                    <form action="{{ route('postulaciones.store', $p->codigo) }}" method="POST"
                                        class="inline">
                                        @csrf
                                        <button class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-800">
                                            Postularme
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs px-2 py-1 rounded bg-blue-100">{{ $estadoPost }}</span>
                                    <form action="{{ route('postulaciones.destroy', [$p->codigo, $miProponente->id]) }}"
                                        method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="ml-2 px-3 py-1 rounded bg-red-600 text-white hover:bg-red-800">
                                            Retirar
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@section('scripts')

    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
          
            const start = () => {
                if (!window.jQuery || !window.DataTable) return setTimeout(start, 50);
                new DataTable('#tabla-procesos', {
                    responsive: true,
                    order: [
                        [3, 'desc']
                    ], 
                    pageLength: 10,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'
                    },
                    columnDefs: [{
                            targets: 2,
                            searchable: false
                        }, 
                        {
                            targets: 5,
                            orderable: false
                        } 
                    ]
                });
            };
            start();
        });
    </script>
@endsection

@endsection
