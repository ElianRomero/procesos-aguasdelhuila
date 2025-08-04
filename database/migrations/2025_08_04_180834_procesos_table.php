<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('procesos', function (Blueprint $table) {
            $table->string('codigo', 256)->primary(); // proceso_codigo
            $table->string('objeto', 2056);
            $table->string('link_secop', 1024);
            $table->unsignedBigInteger('valor');
            $table->date('fecha');

            // Relaciones
            $table->string('tipo_proceso_codigo', 32);
            $table->foreign('tipo_proceso_codigo')->references('codigo')->on('tipo_procesos');

            $table->string('estado_contrato_codigo', 32);
            $table->foreign('estado_contrato_codigo')->references('codigo')->on('estado_contratos');

            $table->string('tipo_contrato_codigo', 32);
            $table->foreign('tipo_contrato_codigo')->references('codigo')->on('tipo_contratos');

            $table->string('modalidad_codigo', 20); // sin relación

            $table->string('estado', 20)->default('CREADO');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procesos');
    }
};
