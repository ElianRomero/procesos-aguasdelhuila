<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Proceso;
use App\Models\TipoProceso;
use App\Models\EstadoContrato;
use App\Models\TipoContrato;
use Carbon\Carbon;

class ProcesosMigrationSeeder extends Seeder
{
     public function run(): void
    {
        $registros = DB::table('proceso_old')->get();
        $total = 0;
        $fallidos = [];

        foreach ($registros as $registro) {
            // Buscar relaciones
            $tipoProceso = TipoProceso::where('codigo', $registro->tipo_proceso_codigo)->first();
            $estadoContrato = EstadoContrato::where('codigo', $registro->estado_contrato_codigo)->first();
            $tipoContrato = TipoContrato::where('codigo', $registro->tipo_contrato_codigo)->first();

            // Si alguna relación no existe, registrar fallo
            if (!$tipoProceso || !$estadoContrato || !$tipoContrato) {
                $fallidos[] = $registro->proceso_codigo ?? '[SIN CÓDIGO]';
                continue;
            }

            // Insertar proceso
            Proceso::updateOrCreate(
                ['codigo' => $registro->proceso_codigo],
                [
                    'objeto' => $registro->proceso_objeto,
                    'link_secop' => $registro->proceso_link_secop,
                    'valor' => $registro->proceso_valor,
                    'fecha' => $registro->proceso_fecha,
                    'tipo_proceso_codigo' => $registro->tipo_proceso_codigo,
                    'estado_contrato_codigo' => $registro->estado_contrato_codigo,
                    'tipo_contrato_codigo' => $registro->tipo_contrato_codigo,
                    'modalidad_codigo' => $registro->modalidad_codigo,
                    'estado' => $registro->proceso_estado ?? 'CREADO',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $total++;
        }

        // Mostrar resumen por consola
        echo "✅ Procesos migrados correctamente: $total\n";

        if (!empty($fallidos)) {
            echo "⚠️ Procesos con errores de relación:\n";
            foreach ($fallidos as $codigo) {
                echo "   - $codigo\n";
            }

            // Guardar en archivo log
            $ruta = storage_path('logs/procesos_fallidos.log');
            file_put_contents($ruta, implode(PHP_EOL, $fallidos));
            echo "\n📁 Procesos fallidos guardados en: $ruta\n";
        }
    }
}
