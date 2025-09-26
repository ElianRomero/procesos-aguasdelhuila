<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->index();          
            $table->string('codigo')->nullable();       
            $table->string('refpago')->unique();        
            $table->unsignedBigInteger('valfactura');   
            $table->date('fecha')->nullable();          
            $table->string('nombre')->nullable();       
            $table->string('direccion')->nullable();    
            $table->enum('status', ['pendiente', 'pagada', 'expirada', 'cancelada'])->default('pendiente');

            $table->string('payment_link_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('wompi_reference')->nullable()->index(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
