<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoContrato extends Model
{
   protected $fillable = ['codigo', 'nombre'];
       protected $table = 'estado_contratos';

}
