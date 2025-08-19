<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulacion_archivos', function (Blueprint $t) {
            $t->id();

            // Identificadores de la postulación (sin depender del id de la pivot)
            $t->string('proceso_codigo');            // FK -> procesos.codigo
            $t->unsignedBigInteger('proponente_id'); // FK -> proponentes.id

            // Requisito (la "key" que definiste en el admin, ej: "rut")
            $t->string('requisito_key');

            // Archivo
            $t->string('original_name');
            $t->string('path');                      // ruta en storage
            $t->unsignedBigInteger('size_bytes')->nullable();

            $t->timestamps();

            // 1 archivo por (proceso, proponente, requisito)
            $t->unique(['proceso_codigo','proponente_id','requisito_key'], 'uniq_post_archivo');

            // Índices/FKs (si quieres FK real y tu motor lo permite)
            $t->index('proceso_codigo');
            $t->foreign('proceso_codigo')->references('codigo')->on('procesos')->cascadeOnDelete();

            $t->foreign('proponente_id')->references('id')->on('proponentes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulacion_archivos');
    }
};
