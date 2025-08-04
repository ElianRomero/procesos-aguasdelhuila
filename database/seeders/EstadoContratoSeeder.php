<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EstadoContrato;

class EstadoContratoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EstadoContrato::insert([
            ['codigo' => 'adjudicado', 'nombre' => 'Adjudicado'],
            ['codigo' => 'borrador', 'nombre' => 'Borrador'],
            ['codigo' => 'celebrado', 'nombre' => 'Celebrado'],
            ['codigo' => 'convocado', 'nombre' => 'Convocado'],
            ['codigo' => 'descartado', 'nombre' => 'Descartado'],
            ['codigo' => 'desierto', 'nombre' => 'Desierto'],
            ['codigo' => 'en-ejecucion', 'nombre' => 'En ejecución'],
            ['codigo' => 'expresion-interes', 'nombre' => 'Expresión de Interés'],
            ['codigo' => 'liquidado', 'nombre' => 'Liquidado'],
            ['codigo' => 'lista-corta', 'nombre' => 'Lista Corta'],
            ['codigo' => 'manifestado', 'nombre' => 'Manifestado'],
            ['codigo' => 'pliego-definitivo', 'nombre' => 'Pliego Definitivo'],
            ['codigo' => 'suspendido', 'nombre' => 'Suspendido'],
            ['codigo' => 'terminado-anormalmente', 'nombre' => 'Terminado anormalmente después de convocado'],
            ['codigo' => 'terminado-sin-liquidar', 'nombre' => 'Terminado Sin Liquidar'],
        ]);
    }
}
