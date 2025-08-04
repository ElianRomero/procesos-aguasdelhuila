<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proponentes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ciudad_id')->constrained('ciudades');
            $table->foreignId('ciiu_id')->constrained('ciiu');
            $table->string('tipo_identificacion_codigo', 3);
            $table->foreign('tipo_identificacion_codigo')->references('codigo')->on('tipo_identificaciones');

            $table->string('razon_social', 512);
            $table->string('nit', 20)->unique();
            $table->string('representante', 512)->nullable();
            $table->string('direccion', 512)->nullable();
            $table->string('telefono1', 20)->nullable();
            $table->string('telefono2', 20)->nullable();
            $table->string('correo', 512)->nullable();
            $table->string('sitio_web', 512)->nullable();
            $table->date('actividad_inicio')->nullable();
            $table->text('observacion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proponentes');
    }
};
