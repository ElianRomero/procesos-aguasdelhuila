<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
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
        'wompi_reference'
    ];

    protected $casts = [
        'fecha' => 'date',
        'expires_at' => 'datetime',
    ];

    public function isPaymentLinkActive(): bool
    {
        return $this->payment_link_url && $this->expires_at && now()->lt($this->expires_at) && $this->status === 'pendiente';
    }
}
