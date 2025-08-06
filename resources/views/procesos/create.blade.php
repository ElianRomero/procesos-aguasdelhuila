@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto py-8">
        <h2 class="text-2xl font-semibold mb-6">Registrar Proceso</h2>

        @if (session('success'))
            <div class="mb-4 text-green-600">{{ session('success') }}</div>
        @endif

        <form action="{{ route('procesos.store') }}" method="POST"
            class="max-w-4xl mx-auto mt-5 py-6 px-8 bg-white rounded-xl shadow-2xl border border-gray-200">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="mb-4">
                    <label for="codigo" class="block font-medium">Código</label>
                    <input type="text" name="codigo" id="codigo" class="w-full border-gray-300 rounded" required>
                </div>
                <div class="mb-4">
                    <label for="tipo_proceso_codigo" class="block font-medium">Tipo de Proceso</label>
                    <select name="tipo_proceso_codigo" id="tipo_proceso_codigo" class="w-full border-gray-300 rounded"
                        required>
                        <option value="">Seleccione...</option>
                        @foreach ($tiposProceso as $tipo)
                            <option value="{{ $tipo->codigo }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label for="objeto" class="block font-medium">Objeto Proceso</label>
                    <textarea name="objeto" id="objeto" class="w-full border-gray-300 rounded" required></textarea>
                </div>

                <div class="mb-4">
                    <label for="link_secop" class="block font-medium">Link SECOP</label>
                    <input type="url" name="link_secop" id="link_secop" class="w-full border-gray-300 rounded">
                </div>
                <div class="mb-4">
                    <label for="valor" class="block font-medium">Valor Contrato</label>
                    <input type="text" name="valor" id="valor"
                        class="w-full border border-gray-300 rounded px-3 py-2" required placeholder="0">
                </div>

                <div class="mb-4">
                    <label for="fecha" class="block font-medium">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="w-full border-gray-300 rounded" required>
                </div>
                <div class="mb-4">
                    <label for="estado_contrato_codigo" class="block font-medium">Estado del Contrato</label>
                    <select name="estado_contrato_codigo" id="estado_contrato_codigo" class="w-full border-gray-300 rounded"
                        required>
                        <option value="">Seleccione...</option>
                        @foreach ($estadosContrato as $estado)
                            <option value="{{ $estado->codigo }}">{{ $estado->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="tipo_contrato_codigo" class="block font-medium">Tipo de Contrato</label>
                    <select name="tipo_contrato_codigo" id="tipo_contrato_codigo" class="w-full border-gray-300 rounded"
                        required>
                        <option value="">Seleccione...</option>
                        @foreach ($tiposContrato as $tipo)
                            <option value="{{ $tipo->codigo }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-6">
                <label for="modalidad_codigo" class="block font-medium">Modalidad (texto libre)</label>
                <input type="text" name="modalidad_codigo" id="modalidad_codigo" class="w-full border-gray-300 rounded">
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar</button>
        </form>
    </div>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('valor');

        input.addEventListener('input', function() {
            let valor = this.value.replace(/\./g, '').replace(/\D/g, '');
            this.value = valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        });
    });
</script>
