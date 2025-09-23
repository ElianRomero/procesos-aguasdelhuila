<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('noticia_lecturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('noticia_id');
            $table->unsignedBigInteger('proponente_id'); // marcamos por proponente
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('noticia_id')->references('id')->on('noticias')->onDelete('cascade');
            $table->foreign('proponente_id')->references('id')->on('proponentes')->onDelete('cascade');

            $table->unique(['noticia_id', 'proponente_id']); // una lectura por proponente/noticia
            $table->index(['proponente_id', 'read_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('noticia_lecturas');
    }
};
