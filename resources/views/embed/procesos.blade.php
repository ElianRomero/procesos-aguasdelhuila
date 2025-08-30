<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Procesos · Embed</title>

    <!-- DataTables + jQuery -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <style>
        :root {
            --border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --primary: #111827;
        }

        html,
        body {
            background: #fff;
            color: var(--text);
            font: 14px/1.45 system-ui, -apple-system, Segoe UI, Roboto, Arial;
        }

        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .5rem;
            margin-bottom: .75rem;
        }

        .input,
        .select,
        .btn {
            border: 1px solid var(--border);
            border-radius: .5rem;
            padding: .5rem .75rem;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .actions {
            display: flex;
            gap: .5rem;
            margin: .25rem 0 1rem;
        }

        @media (max-width: 900px) {
            .filters {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .filters {
                grid-template-columns: 1fr;
            }
        }

        .dt-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="wrap" id="embed-root">
        <h1 style="font-size:18px; font-weight:600; margin:0 0 12px">Procesos</h1>

        <!-- Filtros -->
        <div class="filters">
            <input id="q" class="input" type="text" placeholder="Buscar (código/objeto/tipo/estado)">
            <select id="tipo_proceso" class="select">
                <option value="">Tipo de proceso (todos)</option>
                @foreach ($tiposProceso as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
            <select id="estado_contrato" class="select">
                <option value="">Estado contrato (todos)</option>
                @foreach ($estados as $e)
                    <option value="{{ $e }}">{{ $e }}</option>
                @endforeach
            </select>
            <select id="tipo_contrato" class="select">
                <option value="">Tipo de contrato (todos)</option>
                @foreach ($tiposContrato as $tc)
                    <option value="{{ $tc }}">{{ $tc }}</option>
                @endforeach
            </select>
            <select id="mes" class="select">
                <option value="">Mes (todos)</option>
                @foreach ($meses as $m)
                    <option value="{{ $m['v'] }}">{{ $m['t'] }}</option>
                @endforeach
            </select>
            <select id="anio" class="select">
                <option value="">Año (todos)</option>
                @foreach ($anios as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="actions">
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
            autoWidth: false, // evita recálculos de ancho
            responsive: true, // hace la tabla fluida sin saltos
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
                    };
                }
            },
            columns: [{
                    data: 'codigo',
                    title: 'Código'
                },
                {
                    data: 'fecha',
                    title: 'Fecha',
                    render: function(v) {
                        if (!v) return '';
                        try {
                            return new Date(v).toISOString().slice(0, 10);
                        } // YYYY-MM-DD (UTC)
                        catch (e) {
                            return String(v).slice(0, 10);
                        }
                    }
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
                    title: 'Interesado',
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
            tabla.search('');
            tabla.ajax.reload();
        });

        // Enter en búsqueda
        $('#q').on('keyup', (e) => {
            if (e.key === 'Enter') $('#btnFiltrar').click();
        });

        // (Opcional) autoaltura para iframe
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
