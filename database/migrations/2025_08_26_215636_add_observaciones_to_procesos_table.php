<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        Schema::table('procesos', function (Blueprint $table) {
            $table->dateTime('observaciones_abren_en')->nullable()->after('requisitos');
            $table->dateTime('observaciones_cierran_en')->nullable()->after('observaciones_abren_en');
        });
    }

    public function down(): void
    {
        Schema::table('procesos', function (Blueprint $table) {
            $table->dropColumn(['observaciones_abren_en', 'observaciones_cierran_en']);
        });
    }
};
