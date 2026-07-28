<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpleInvoice extends Model
{
    protected $fillable = [
        'numero',
        'codigo',
        'refpago',
        'valfactura',
        'fecha',
        'nombre',
        'direccion',
        'status',
        'payment_link_url',
        'expires_at',
        'wompi_reference',
        'wompi_link_id',
        'wompi_transaction_id',
        'wompi_status',
        'wompi_amount_in_cents',
        'paid_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
}
