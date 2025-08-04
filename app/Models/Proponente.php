<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proponente extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',
        'razon_social',
        'nit',
        'representante',
        'direccion',
        'telefono1',
        'telefono2',
        'correo',
        'sitio_web',
        'actividad_inicio',
        'observacion',
        'ciudad_id',
        'actividad_id',
        'estado_id',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

   
}
