<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simple_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->index();
            $table->string('codigo')->index();
            $table->string('refpago')->unique();
            $table->unsignedBigInteger('valfactura');
            $table->date('fecha');
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->enum('status', ['pendiente', 'pagada', 'expirada', 'cancelada'])
                ->default('pendiente');
            $table->text('payment_link_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('wompi_reference')->nullable()->index();
            $table->string('wompi_link_id')->nullable()->index();
            $table->string('wompi_transaction_id')->nullable()->index();
            $table->string('wompi_status')->nullable();
            $table->unsignedBigInteger('wompi_amount_in_cents')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simple_invoices');
    }
};
