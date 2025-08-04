<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            ['nombre' => 'SuperAdmin'],
            ['nombre' => 'Funcionario Contratacion'],
            ['nombre' => 'Proveedor'],
            ['nombre' => 'Auditor'],
        ]);
    }
}
