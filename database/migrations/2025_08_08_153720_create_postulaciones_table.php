<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void {
    Schema::create('postulaciones', function (Blueprint $t) {
      $t->id();
      $t->string('proceso_codigo');                   // FK al PK string de procesos
      $t->foreign('proceso_codigo')->references('codigo')->on('procesos')->cascadeOnDelete();
      $t->foreignId('proponente_id')->constrained('proponentes')->cascadeOnDelete();

      // Campos extra útiles
      $t->enum('estado', ['POSTULADO','ACEPTADO','RECHAZADO'])->default('POSTULADO');
      $t->text('observacion')->nullable();
      $t->timestamp('postulado_en')->useCurrent();

      $t->timestamps();

      // Evitar doble postulación del mismo proponente al mismo proceso
      $t->unique(['proceso_codigo','proponente_id']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('postulaciones');
  }
};
