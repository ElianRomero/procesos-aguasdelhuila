<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Procesos · Embed</title>

    <!-- DataTables + jQuery (CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- Tailwind (opcional, solo para filtros bonitos) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: #fff;
        }

        .dt-center {
            text-align: center;
        }

        .btn {
            padding: .5rem .75rem;
            border-radius: .5rem;
            border: 1px solid #e5e7eb;
        }

        .btn-primary {
            background: #111827;
            color: #fff;
        }
    </style>
</head>

<body class="bg-white">
    <div class="max-w-7xl mx-auto p-4">
        <h1 class="text-xl font-semibold mb-4">Procesos</h1>

        <!-- Filtros -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-3">
            <input id="q" type="text" placeholder="Buscar (código/objeto/tipo/estado)"
                class="border rounded px-3 py-2 md:col-span-2">

            <select id="tipo_proceso" class="border rounded px-3 py-2">
                <option value="">Tipo de proceso (todos)</option>
                @foreach ($tiposProceso as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>

            <select id="estado_contrato" class="border rounded px-3 py-2">
                <option value="">Estado contrato (todos)</option>
                @foreach ($estados as $e)
                    <option value="{{ $e }}">{{ $e }}</option>
                @endforeach
            </select>

            <select id="tipo_contrato" class="border rounded px-3 py-2">
                <option value="">Tipo de contrato (todos)</option>
                @foreach ($tiposContrato as $tc)
                    <option value="{{ $tc }}">{{ $tc }}</option>
                @endforeach
            </select>

            <select id="mes" class="border rounded px-3 py-2">
                <option value="">Mes (todos)</option>
                @foreach ($meses as $m)
                    <option value="{{ $m['v'] }}">{{ $m['t'] }}</option>
                @endforeach
            </select>

            <select id="anio" class="border rounded px-3 py-2">
                <option value="">Año (todos)</option>
                @foreach ($anios as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2 mb-4">
            <button id="btnFiltrar" class="btn btn-primary">Aplicar filtros</button>
            <button id="btnLimpiar" class="btn">Limpiar</button>
        </div>

        <!-- Tabla -->
        <table id="tabla" class="display" style="width:100%"></table>
    </div>

    <script>
        const tabla = $('#tabla').DataTable({
            serverSide: true,
            processing: true,
            pageLength: 10,
            order: [
                [1, 'desc']
            ], // fecha DESC por defecto
            ajax: {
                url: "{{ url('/api/public/procesos') }}",
                data: function(d) {
                    d.tipo_proceso = $('#tipo_proceso').val() || '';
                    d.estado_contrato = $('#estado_contrato').val() || '';
                    d.tipo_contrato = $('#tipo_contrato').val() || '';
                    d.mes = $('#mes').val() || '';
                    d.anio = $('#anio').val() || '';
                    d.search = {
                        value: $('#q').val() || ''
                    }; // sincroniza con búsqueda global
                }
            },
            columns: [{
                    data: 'codigo',
                    title: 'Código'
                },
                {
                    data: 'fecha',
                    title: 'Fecha'
                },
                {
                    data: 'objeto',
                    title: 'Objeto'
                },
                {
                    data: 'tipo_proceso',
                    title: 'Tipo proceso'
                },
                {
                    data: 'estado_contrato',
                    title: 'Estado'
                },
                {
                    data: 'tipo_contrato',
                    title: 'Tipo contrato'
                },
                {
                    data: 'valor',
                    title: 'Valor',
                    render: function(v) {
                        if (v == null || v === '') return '';
                        const n = Number(v);
                        return isNaN(n) ? v : n.toLocaleString('es-CO', {
                            style: 'currency',
                            currency: 'COP'
                        });
                    }
                },
                {
                    data: null,
                    title: 'Ver proceso',
                    orderable: false,
                    searchable: false,
                    className: 'dt-center',
                    render: function() {
                        return `<a class="btn btn-primary" href="https://procesos.aguasdelhuila.gov.co/login" target="_blank" rel="noopener">Ir</a>`;
                    }
                },
            ]
        });

        // Aplicar / Limpiar
        $('#btnFiltrar').on('click', () => tabla.ajax.reload());
        $('#btnLimpiar').on('click', () => {
            $('#q').val('');
            $('#tipo_proceso').val('');
            $('#estado_contrato').val('');
            $('#tipo_contrato').val('');
            $('#mes').val('');
            $('#anio').val('');
            tabla.search(''); // limpia búsqueda interna
            tabla.ajax.reload();
        });

        // Enter en el input de búsqueda
        $('#q').on('keyup', function(e) {
            if (e.key === 'Enter') $('#btnFiltrar').click();
        });

        // (Opcional) si lo vas a poner en iframe y quieres autoaltura:
        function postHeight() {
            const h = document.body.scrollHeight;
            parent.postMessage({
                type: 'aguas:height',
                px: h
            }, '*');
        }
        new ResizeObserver(postHeight).observe(document.body);
        document.addEventListener('DOMContentLoaded', postHeight);
    </script>
</body>

</html>
