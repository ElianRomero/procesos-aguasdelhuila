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
        Schema::create('tipo_contratos', function (Blueprint $table) {
            $table->id(); // id autoincremental
            $table->string('codigo', 70)->unique(); // ejemplo: 'actividades-cientificas'
            $table->string('nombre', 256); // ejemplo: 'Actividades científicas...'
            $table->timestamps(); // opcional pero útil
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_contratos');
    }
};
