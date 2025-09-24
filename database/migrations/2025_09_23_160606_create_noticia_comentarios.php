<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('noticia_comentarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('noticia_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('proponente_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('cuerpo');
            $table->timestamps();

            $table->foreign('noticia_id')->references('id')->on('noticias')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('proponente_id')->references('id')->on('proponentes')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('noticia_comentarios')->onDelete('cascade');

            $table->index(['noticia_id','parent_id','created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('noticia_comentarios');
    }
};
