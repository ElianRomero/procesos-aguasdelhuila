<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proponentes', function (Blueprint $table) {
            $table->text('google_drive_url')->nullable()->after('sitio_web');
        });
    }

    public function down(): void
    {
        Schema::table('proponentes', function (Blueprint $table) {
            $table->dropColumn('google_drive_url');
        });
    }
};
