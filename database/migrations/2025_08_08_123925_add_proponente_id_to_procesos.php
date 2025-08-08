<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void {
        Schema::table('procesos', function (Blueprint $table) {
            $table->foreignId('proponente_id')
                  ->nullable()
                  ->constrained('proponentes')
                  ->nullOnDelete()
                  ->after('estado');
        });
    }
    public function down(): void {
        Schema::table('procesos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proponente_id');
        });
    }
};
