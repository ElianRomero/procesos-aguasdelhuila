<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Proponente;
use App\Models\Ciiu;
use App\Models\Ciudad;

class ProponentesMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $registros = DB::table('proponentes_old')->get();
        $total = 0;

        foreach ($registros as $registro) {
            // Validar y buscar relaciones
            $ciiu = Ciiu::where('codigo', $registro->actividad_codigo)->first();
            $ciudad = Ciudad::where('codigo', $registro->municipio_id)->first();

            // Saltar si no hay ciiu o ciudad
            if (!$ciiu || !$ciudad) {
                continue;
            }

            // Insertar nuevo proponente
            Proponente::updateOrCreate(
                ['nit' => $registro->proponente_nit], // evitar duplicados
                [
                    'user_id' => $registro->usuario_id,
                    'ciiu_id' => $ciiu->id,
                    'ciudad_id' => $ciudad->id,
                    'tipo_identificacion_codigo' => $registro->tipo_identificacion_codigo,
                    'razon_social' => $registro->proponente_razonsocial,
                    'nit' => $registro->proponente_nit,
                    'representante' => $registro->proponente_representante,
                    'direccion' => $registro->proponente_direccion,
                    'telefono1' => $registro->proponente_telefono1,
                    'telefono2' => $registro->proponente_telefono2,
                    'correo' => $registro->proponente_correo,
                    'sitio_web' => $registro->proponente_sitioweb,
                    'actividad_inicio' => $registro->proponente_actividadinicio,
                    'observacion' => $registro->proponente_observacion,
                    'created_at' => $registro->proponente_fechacreacion,
                    'updated_at' => $registro->proponente_fechamodificacion,
                ]
            );

            $total++;
        }

        echo "✅ Proponentes migrados correctamente: $total\n";
    }
}
