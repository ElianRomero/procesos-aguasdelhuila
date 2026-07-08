<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $proceso->codigo }} · Proceso</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
            --primary: #0f766e;
            --primary-dark: #115e59;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            background: linear-gradient(180deg, #f8fafc 0%, #eef6f5 100%);
            color: var(--text);
            font: 15px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }
        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
            overflow: hidden;
        }
        .hero {
            padding: 24px;
            background: linear-gradient(135deg, #0f766e 0%, #134e4a 100%);
            color: #fff;
        }
        .hero h1 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.15;
        }
        .hero p {
            margin: 0;
            opacity: .92;
        }
        .content {
            padding: 24px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 18px;
        }
        .field {
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fcfdfd;
        }
        .field.full { grid-column: 1 / -1; }
        .label {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .value {
            white-space: pre-line;
            word-break: break-word;
        }
        .requisitos {
            margin: 0;
            padding-left: 18px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text);
            background: #fff;
            font-weight: 600;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .btn-primary:hover { background: var(--primary-dark); }
        .notice {
            margin-top: 24px;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid #cfe7e3;
            background: #f0fdfa;
            color: #134e4a;
        }
        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <section class="hero">
                <p>Detalle público del proceso</p>
                <h1>{{ $proceso->codigo }}</h1>
                <p>{{ $proceso->objeto }}</p>
            </section>

            <section class="content">
                <div class="grid">
                    <div class="field">
                        <span class="label">Fecha</span>
                        <div class="value">{{ optional($proceso->fecha)->format('Y-m-d') ?: 'Sin fecha' }}</div>
                    </div>
                    <div class="field">
                        <span class="label">Valor</span>
                        <div class="value">
                            {{ $proceso->valor !== null ? '$ ' . number_format((float) $proceso->valor, 0, ',', '.') : 'No registrado' }}
                        </div>
                    </div>
                    <div class="field">
                        <span class="label">Tipo de proceso</span>
                        <div class="value">{{ $proceso->tipoProceso?->nombre ?: 'No registrado' }}</div>
                    </div>
                    <div class="field">
                        <span class="label">Estado del contrato</span>
                        <div class="value">{{ $proceso->estadoContrato?->nombre ?: 'No registrado' }}</div>
                    </div>
                    <div class="field">
                        <span class="label">Tipo de contrato</span>
                        <div class="value">{{ $proceso->tipoContrato?->nombre ?: 'No registrado' }}</div>
                    </div>
                    <div class="field">
                        <span class="label">Estado interno</span>
                        <div class="value">{{ $proceso->estado ?: 'No registrado' }}</div>
                    </div>
                    <div class="field full">
                        <span class="label">Objeto</span>
                        <div class="value">{{ $proceso->objeto ?: 'No registrado' }}</div>
                    </div>
                    <div class="field full">
                        <span class="label">Observaciones</span>
                        <div class="value">{{ $proceso->observaciones ?: 'Sin observaciones registradas.' }}</div>
                    </div>
                    <div class="field full">
                        <span class="label">Requisitos</span>
                        @php($requisitos = is_array($proceso->requisitos) ? $proceso->requisitos : [])
                        @if (count($requisitos))
                            <ul class="requisitos">
                                @foreach ($requisitos as $requisito)
                                    <li>{{ $requisito['name'] ?? $requisito['label'] ?? $requisito['key'] ?? 'Requisito' }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="value">Este proceso no tiene requisitos configurados.</div>
                        @endif
                    </div>
                </div>

                @if ($proceso->link_secop)
                    <div class="notice">
                        Este proceso también se encuentra publicado en SECOP II.
                    </div>
                @endif

                <div class="actions">
                    <a href="{{ route('embed.procesos') }}" class="btn">Volver al listado</a>
                    <a href="{{ url('/login') }}" class="btn btn-primary">Postularme</a>
                    @if ($proceso->link_secop)
                        <a href="{{ $proceso->link_secop }}" target="_blank" rel="noopener noreferrer" class="btn">
                            Ver en SECOP
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</body>
</html>
