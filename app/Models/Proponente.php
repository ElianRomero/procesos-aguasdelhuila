<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proponente extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ciudad_id',
        'ciiu_id',
        'tipo_identificacion_codigo',
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
    ];

    // Relaciones
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function ciudad() {
        return $this->belongsTo(Ciudad::class);
    }

    public function ciiu() {
        return $this->belongsTo(Ciiu::class);
    }

    public function tipoIdentificacion() {
        return $this->belongsTo(TipoIdentificacion::class, 'tipo_identificacion_codigo', 'codigo');
    }

}
