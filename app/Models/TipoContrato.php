<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoContrato extends Model
{
       protected $fillable = ['codigo', 'nombre'];
       protected $table = 'tipo_contratos';
}
