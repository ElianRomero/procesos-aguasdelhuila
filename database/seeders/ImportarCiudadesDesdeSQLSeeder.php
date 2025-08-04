<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ciudad;
use App\Models\Departamento;

class ImportarCiudadesDesdeSQLSeeder extends Seeder
{
   public function run(): void
    {
        $ciudades = DB::table('ciudad_raw')->get();

        foreach ($ciudades as $row) {
            $departamento = Departamento::where('codigo', $row->departamento_codigo)->first();

            if ($departamento) {
                Ciudad::updateOrCreate(
                    ['codigo' => $row->ciudad_codigo],
                    [
                        'nombre' => $row->ciudad_nombre,
                        'departamento_id' => $departamento->id,
                    ]
                );
            }
        }
    }
}
