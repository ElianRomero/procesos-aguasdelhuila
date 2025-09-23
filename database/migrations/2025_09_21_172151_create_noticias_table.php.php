<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->string('proceso_codigo');
            $table->foreign('proceso_codigo')->references('codigo')->on('procesos')->cascadeOnDelete();

            $table->foreignId('autor_user_id')->constrained('users')->cascadeOnDelete();

            // Si es pública: destinatario_proponente_id = null
            // Si es privada: destinatario_proponente_id = ID del proponente
            $table->foreignId('destinatario_proponente_id')->nullable()->constrained('proponentes')->nullOnDelete();

            $table->string('titulo', 180);
            $table->text('cuerpo');

            // Tipos útiles para filtros/etiquetas
            $table->enum('tipo', ['COMUNICADO', 'PRORROGA', 'ADENDA', 'ACLARACION', 'CITACION', 'OTRO'])->default('COMUNICADO');

            $table->boolean('publico')->default(true);
            $table->enum('estado', ['BORRADOR', 'PUBLICADA'])->default('PUBLICADA');
            $table->timestamp('publicada_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['proceso_codigo', 'publico', 'estado']);
            $table->index(['destinatario_proponente_id']);
            $table->index(['publicada_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
