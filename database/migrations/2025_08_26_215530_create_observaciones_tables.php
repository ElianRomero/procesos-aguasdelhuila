<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up(): void
    {
        Schema::create('observaciones', function (Blueprint $table) {
            $table->id();
            $table->string('proceso_codigo', 191); // <- ajusta a la longitud real de procesos.codigo
            $table->unsignedBigInteger('proponente_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('asunto', 180);
            $table->text('descripcion')->nullable();
            $table->string('estado', 20)->default('ENVIADA'); // ENVIADA | ADMITIDA | RECHAZADA | RESUELTA (si la usas)
            $table->timestamps();

            $table->foreign('proceso_codigo')
                ->references('codigo')->on('procesos')
                ->onDelete('cascade');

            $table->foreign('proponente_id')
                ->references('id')->on('proponentes')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index(['proceso_codigo']);
            $table->index(['proponente_id']);
        });

        Schema::create('observacion_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('observacion_id');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('observacion_id')
                ->references('id')->on('observaciones')
                ->onDelete('cascade');

            $table->index(['observacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observacion_archivos');
        Schema::dropIfExists('observaciones');
    }
};
