<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
     public function up(): void
    {
        // 1) Agregar la columna si no existe; si existe, fijar default
        if (!Schema::hasColumn('procesos', 'estado')) {
            Schema::table('procesos', function (Blueprint $table) {
                $table->string('estado', 20)->default('CREADO')->index()->after('modalidad_codigo');
            });
        } else {
            // establecer default a 'CREADO' y asegurar longitud
            DB::statement("ALTER TABLE procesos 
                MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'CREADO'");
            // index por si no lo tuviera
            try {
                Schema::table('procesos', function (Blueprint $table) {
                    $table->index('estado');
                });
            } catch (\Throwable $e) {}
        }

        // 2) (Opcional) CHECK en MySQL 8+ para permitir solo estos 3 valores
        try {
            DB::statement("ALTER TABLE procesos
                ADD CONSTRAINT chk_procesos_estado
                CHECK (estado IN ('CREADO','VIGENTE','CERRADO'))");
        } catch (\Throwable $e) {
            // Si tu MySQL no soporta CHECK, lo ignoramos.
        }

        // 3) Poner 'CREADO' a los registros existentes que estén null o vacíos
        DB::table('procesos')->whereNull('estado')->orWhere('estado','')->update(['estado' => 'CREADO']);
    }

    public function down(): void
    {
        // Quitar el CHECK si existe (MySQL 8)
        try {
            DB::statement("ALTER TABLE procesos DROP CHECK chk_procesos_estado");
        } catch (\Throwable $e) {}

        // Si quieres quitar la columna al hacer rollback:
        if (Schema::hasColumn('procesos','estado')) {
            Schema::table('procesos', function (Blueprint $table) {
                $table->dropIndex(['estado']);
                $table->dropColumn('estado');
            });
        }
    }
};
