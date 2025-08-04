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
        Schema::create('tipo_procesos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique(); // Ej: 'concurso-meritos'
            $table->string('nombre', 256);          // Ej: 'Concurso de méritos con lista corta'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_procesos');
    }
};
