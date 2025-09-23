<?php

namespace App\Http\Controllers;

use App\Models\Proceso;
use App\Models\TipoProceso;
use App\Models\EstadoContrato;
use App\Models\TipoContrato;

class EmbedController extends Controller
{
     public function index()
    {
        $tiposProceso  = TipoProceso::orderBy('nombre')->pluck('nombre')->all();
        $estados       = EstadoContrato::orderBy('nombre')->pluck('nombre')->all();
        $tiposContrato = TipoContrato::orderBy('nombre')->pluck('nombre')->all();

        $anios = Proceso::whereNotNull('fecha')
            ->selectRaw('DISTINCT YEAR(fecha) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn($v) => (int) $v)
            ->all();

        $meses = [
            ['v' => 1,  't' => 'Enero'],
            ['v' => 2,  't' => 'Febrero'],
            ['v' => 3,  't' => 'Marzo'],
            ['v' => 4,  't' => 'Abril'],
            ['v' => 5,  't' => 'Mayo'],
            ['v' => 6,  't' => 'Junio'],
            ['v' => 7,  't' => 'Julio'],
            ['v' => 8,  't' => 'Agosto'],
            ['v' => 9,  't' => 'Septiembre'],
            ['v' => 10, 't' => 'Octubre'],
            ['v' => 11, 't' => 'Noviembre'],
            ['v' => 12, 't' => 'Diciembre'],
        ];

        $view = view('embed.procesos', compact('tiposProceso','estados','tiposContrato','anios','meses'));

        return response($view)
            ->header('Content-Security-Policy', "frame-ancestors 'self' https://aguasdelhuila.gov.co https://www.aguasdelhuila.gov.co https://*.aguasdelhuila.gov.co");
    }
}
