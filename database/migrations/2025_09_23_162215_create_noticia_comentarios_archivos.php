<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('noticia_comentario_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comentario_id');
            $table->string('disk', 20)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('comentario_id')
                ->references('id')->on('noticia_comentarios')
                ->onDelete('cascade');

            $table->index(['comentario_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('noticia_comentario_archivos');
    }
};
