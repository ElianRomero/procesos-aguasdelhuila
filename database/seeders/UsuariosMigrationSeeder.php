<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UsuariosMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = DB::table('usuarios_old')->get();
        $total = 0;
        $fallidos = [];

        foreach ($usuarios as $usuario) {
            // Validar email real
            if (!filter_var($usuario->usuario_email, FILTER_VALIDATE_EMAIL)) {
                $fallidos[] = "{$usuario->usuario_id} | {$usuario->usuario_nombre} | {$usuario->usuario_email} => ❌ Email no válido";
                continue;
            }

            // Buscar proponente por correo
            $proponente = DB::table('proponentes_old')
                ->where('proponente_correo', $usuario->usuario_email)
                ->first();

            // Usar NIT si hay proponente, si no usar clave por defecto
            if (!$proponente) {
                $nit = '12345678';
                $fallidos[] = "{$usuario->usuario_id} | {$usuario->usuario_nombre} | {$usuario->usuario_email} => ⚠️ Migrado SIN proponente (contraseña: 12345678)";
            } else {
                $nit = (string) $proponente->proponente_nit;
            }

            // Asegurar que el perfil_codigo es válido (1 a 4)
            $rolValido = in_array($usuario->perfil_codigo, [1, 2, 3, 4]) ? $usuario->perfil_codigo : 3;

            // Insertar usuario
            DB::table('users')->insert([
                'id' => $usuario->usuario_id,
                'name' => $usuario->usuario_nombre,
                'email' => $usuario->usuario_email,
                'email_verified_at' => now(),
                'password' => Hash::make($nit),
                'remember_token' => Str::random(10),
                'created_at' => Carbon::parse($usuario->usuario_fecha_creacion),
                'updated_at' => now(),
                'role_id' => $rolValido,
            ]);

            $total++;
        }

        echo "✅ Usuarios migrados: $total\n";

        if (!empty($fallidos)) {
            $logPath = storage_path('logs/usuarios_fallidos.txt');
            file_put_contents($logPath, implode(PHP_EOL, $fallidos));
            echo "📄 Log generado en: storage/logs/usuarios_fallidos.txt\n";
        }
    }
}
