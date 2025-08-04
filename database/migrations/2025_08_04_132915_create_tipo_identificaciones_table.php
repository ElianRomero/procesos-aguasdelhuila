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
        Schema::create('tipo_identificaciones', function (Blueprint $table) {
            $table->id(); // autoincremental
            $table->string('codigo', 5)->unique(); // AS, CC, CE, etc.
            $table->string('nombre', 100);         // Cédula de Ciudadanía, etc.
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_identificaciones');
    }
};
